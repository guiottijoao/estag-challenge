<?php

class OrderController
{

  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  private function calcOrderItemTotalTax(float $taxPercent, float $unitPrice, int $amount)
  {
    return ($taxPercent / 100) * $unitPrice * $amount;
  }

  private function verifyStockAvailability(array $product, array $orderItem)
  {
    $existing_item_amount_stmt = $this->db->prepare("SELECT amount FROM order_item o WHERE o.product_code = :product_code");
    $existing_item_amount_stmt->execute([":product_code" => $orderItem['product-code']]);
    $existingItemAmount = $existing_item_amount_stmt->fetchColumn();
    if ($product['amount'] < $orderItem['amount'] + $existingItemAmount) {
      throw new Exception("This product has only " . (int)$product['amount'] . " itens in stock.");
    }
    return true;
  }

  private function isOrderItemRepeated($productId, $orderId)
  {
    $stmt = $this->db->prepare("SELECT * FROM order_item o WHERE o.product_code = :product_code AND o.order_code = :order_code");
    $stmt->execute([":product_code" => $productId, ":order_code" => $orderId]);
    if ($stmt->rowCount() > 0) return true;
    return false;
  }

  public function store(array $data)
  {
    try {
      $this->validate($data);

      $activeOrder = [];
      $order_select_stmt = $this->db->query("SELECT * FROM orders WHERE status = 'open'");
      $productId = $data['product-code'];

      $search_category_id = $this->db->prepare(
        "SELECT c.tax
      FROM categories c
      INNER JOIN products p
      ON c.code = p.category_code
      WHERE p.code = :product_code"
      );
      $search_category_id->execute([":product_code" => $productId]);

      $categoryTax = $search_category_id->fetch(PDO::FETCH_ASSOC)['tax'];

      $search_product_price = $this->db->prepare(
        "SELECT p.price
        FROM products p
        WHERE p.code = :product_code"
      );
      $search_product_price->execute([":product_code" => $productId]);

      $productAmount = $data['amount'];
      $productPrice = $search_product_price->fetch(PDO::FETCH_ASSOC)['price'];
      $orderItemTotalTax = $this->calcOrderItemTotalTax($categoryTax, $productPrice, $productAmount);

      $orderItemTotalPrice = $orderItemTotalTax + ($productPrice * $productAmount);

      //criação
      $order_items_stmt = $this->db->query("SELECT * FROM order_item");
      $orderItems = $order_items_stmt->fetch();

      $order_insert_stmt = $this->db->prepare("INSERT INTO orders (total, tax) VALUES (:total, :tax)");
      $order_update_stmt = $this->db->prepare("UPDATE orders o
      SET total = :total, tax = :tax
      WHERE status = 'open'");

      $activeOrder = $order_select_stmt->fetch(PDO::FETCH_ASSOC);

      $insert_item_stmt = $this->db->prepare(
        "INSERT INTO order_item (order_code, product_code, amount, price, tax)
        VALUES (:order_code, :product_code, :amount, :price, :tax)
        RETURNING *"
      );

      // Sem order -> cria order -> insere item
      if (!$activeOrder) {
        $order_insert_stmt->execute([":total" => $orderItemTotalPrice,  ":tax" => $orderItemTotalTax]);
        $order_select_stmt = $this->db->query("SELECT * FROM orders o WHERE o.status = 'open'");
        $activeOrder = $order_select_stmt->fetch(PDO::FETCH_ASSOC);

        return $insert_item_stmt->execute([":order_code" => $activeOrder['code'], ":product_code" => $productId, "amount" => $productAmount, ":price" => $productPrice, ":tax" => $orderItemTotalTax]);

        // Com order
      } else {
        $orderTotalPrice = $activeOrder['total'] + $orderItemTotalPrice;
        $orderTotalTax = $activeOrder['tax'] + $orderItemTotalTax;
        $order_update_stmt->execute([":total" => $orderTotalPrice, ":tax" => $orderTotalTax]);

        // Com order, com items, produto repetido
        if ($orderItems && $this->isOrderItemRepeated($productId, $activeOrder['code'])) {
          $stmt = $this->db->prepare("SELECT * FROM order_item o
            WHERE o.product_code = :product_code AND o.order_code = :order_code");
          $stmt->execute([":product_code" => $data['product-code'], ":order_code" => $activeOrder['code']]);
          $existingOrderItem = $stmt->fetch(PDO::FETCH_ASSOC);
          $amountsAdded = $data['amount'] + $existingOrderItem['amount'];
          $newTotalTax = $this->calcOrderItemTotalTax($categoryTax, $productPrice, $data['amount']) + $existingOrderItem['tax'];

          $existing_item_stmt = $this->db->prepare(
            "UPDATE order_item o
            SET amount = :new_amount, tax = :new_total_tax
            WHERE product_code = :product_code"
          );

          return $existing_item_stmt->execute([":new_amount" => $amountsAdded, ":new_total_tax" => $newTotalTax, ":product_code" => $productId]);
        }
        // Com order, sem items ou produto novo -> insere item
        return $insert_item_stmt->execute([":order_code" => $activeOrder['code'], ":product_code" => $productId, "amount" => $productAmount, ":price" => $productPrice, ":tax" => $orderItemTotalTax]);
      }
    } catch (Exception $e) {
      throw $e;
    }
  }

  private function validate(array $data)
  {
    $productCode = $data['product-code'] ?? null;
    $amount = $data['amount'];
    $product_stmt = $this->db->prepare("SELECT * FROM products p WHERE p.code = :product_code");
    $product_stmt->execute([":product_code" => $productCode]);
    $orderItemProduct = $product_stmt->fetch(PDO::FETCH_ASSOC);


    if (empty($productCode)) {
      throw new Exception("Select a product.");
    }

    if ($amount < 1 || $amount > !is_int($amount)) {
      throw new Exception("Amount must be positive integer number.");
    }

    $this->verifyStockAvailability($orderItemProduct, $data);
  }

  private function calculateOrderWhenItemDeleted(int $deletedItemId)
  {

    $item_stmt = $this->db->prepare("SELECT * FROM order_item o WHERE o.code = :code");
    $item_stmt->execute([":code" => $deletedItemId]);
    $itemToDelete = $item_stmt->fetch(PDO::FETCH_ASSOC);

    $itemTotalPrice = $itemToDelete['tax'] + ($itemToDelete['amount'] * $itemToDelete['price']);

    $order_stmt = $this->db->query("SELECT * FROM orders o WHERE o.status = 'open'");
    $activeOrder = $order_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$activeOrder) {
      throw new Exception("Error: No orders open.");
    }

    $orderTotalPrice = $activeOrder['total'] - $itemTotalPrice;
    $orderTotalTax = $activeOrder['tax'] - $itemToDelete['tax'];

    $order_update_stmt = $this->db->prepare("UPDATE orders o SET total = :total, tax = :tax WHERE o.code = :order_code");
    $order_update_stmt->execute([":total" => $orderTotalPrice, ":tax" => $orderTotalTax, ":order_code" => $itemToDelete['order_code']]);

    if ($order_update_stmt->rowCount() === 0) {
      throw new Exception("Error during total calculation, no rows affected.");
    }
  }

  public function delete(int $id)
  {
    $this->calculateOrderWhenItemDeleted($id);

    $stmt = $this->db->prepare("DELETE FROM order_item WHERE code = :code");
    $stmt->execute([":code" => $id]);

    if ($stmt->rowCount() === 0) {
      throw new Exception("No register found to delete.");
    }
  }
}
