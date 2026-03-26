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

  public function store(array $data)
  {
    try {
      $this->validate($data);
      $orders = [];

      $order_select_stmt = $this->db->query("SELECT * FROM orders");

      if ($order_select_stmt->rowCount() === 0) {
        $order_insert_stmt = $this->db->prepare("INSERT INTO orders (total, tax) VALUES (:total, :tax)");
        $order_insert_stmt->bindValue(':total', 0);
        $order_insert_stmt->bindValue(':tax', 0);
        $order_insert_stmt->execute();
        $order_select_stmt = $this->db->query("SELECT * FROM orders");
      }
      $orders = $order_select_stmt->fetch(PDO::FETCH_ASSOC);
      $activeOrderId = $orders['code'];
      $productId = $data['product-code'];

      $search_category_id = $this->db->prepare(
        "SELECT c.tax
      FROM categories c
      INNER JOIN products p
      ON c.code = p.category_code
      WHERE p.code = :product_code"
      );
      $search_category_id->bindValue(":product_code", $productId);
      $search_category_id->execute();
      $categoryTax = $search_category_id->fetch(PDO::FETCH_ASSOC)['tax'];

      $search_product_price = $this->db->prepare(
        "SELECT p.price
        FROM products p
        WHERE p.code = :product_code"
      );
      $search_product_price->bindValue(":product_code", $productId);
      $search_product_price->execute();
      $productPrice = $search_product_price->fetch(PDO::FETCH_ASSOC)['price'];

      $productAmount = $data['amount'];
      $orderItemTotalTax = $this->calcOrderItemTotalTax($categoryTax, $productPrice, $productAmount);
      $orderItemTotalPrice = $orderItemTotalTax + ($productPrice * $productAmount);

      //order
      $order_items_stmt = $this->db->query("SELECT * FROM order_item");
      $orderItems = $order_items_stmt->fetch();

      if (!$orderItems) {
        $stmt = $this->db->prepare("UPDATE orders o
        SET total = :total, tax = :tax
        WHERE o.code = :order_id");

        $stmt->bindValue(":total", $orderItemTotalPrice);
        $stmt->bindValue(":tax", $orderItemTotalTax);
        $stmt->bindValue(":order_id", $activeOrderId);
        $stmt->execute();
      } else {
        $order_select_stmt = $this->db->query("SELECT * FROM orders");
        $activeOrder = $order_select_stmt->fetch(PDO::FETCH_ASSOC);
        $orderTotalPrice = $activeOrder['total'] + $orderItemTotalPrice;
        $orderTotalTax = $activeOrder['tax'] + $orderItemTotalTax;

        $stmt = $this->db->prepare("UPDATE orders o
        SET total = :total, tax = :tax
        WHERE o.code = :order_id");
        $stmt->bindValue(":total", $orderTotalPrice);
        $stmt->bindValue(":tax", $orderTotalTax);
        $stmt->bindValue(":order_id", $activeOrderId);
        $stmt->execute();
      }

      // order_item
      $stmt = $this->db->prepare(
        "INSERT INTO order_item (order_code, product_code, amount, price, tax)
      VALUES (:order_code, :product_code, :amount, :price, :tax)"
      );
      $stmt->bindValue(':order_code', $activeOrderId);
      $stmt->bindValue(':product_code', $productId);
      $stmt->bindValue(':amount', $productAmount);
      $stmt->bindValue(':price', $productPrice);
      $stmt->bindValue(':tax', $orderItemTotalTax);

      return $stmt->execute();
    } catch (Exception $e) {
      throw $e;
    }
  }

  private function validate(array $data)
  {
    $product = $data['product-code'] ?? null;
    $amount = $data['amount'];

    if (empty($product)) {
      throw new Exception("Select a product.");
    }

    if ($amount < 1 || $amount > !is_int($amount)) {
      throw new Exception("Amount must be positive integer number.");
    }
  }

  private function calculateOrderWhenItemDeleted(int $deletedItemId)
  {
    $item_stmt = $this->db->prepare("SELECT * FROM order_item o WHERE o.code = :code");
    $item_stmt->bindValue(":code", $deletedItemId);
    $item_stmt->execute();
    $itemToDelete = $item_stmt->fetch(PDO::FETCH_ASSOC);

    $itemTotalPrice = $itemToDelete['tax'] + ($itemToDelete['amount'] * $itemToDelete['price']);
    error_log(print_r($itemTotalPrice, true));

    $order_stmt = $this->db->query("SELECT * FROM orders");
    $activeOrder = $order_stmt->fetch(PDO::FETCH_ASSOC);

    $orderTotalPrice = $activeOrder['total'] - $itemTotalPrice;
    $orderTotalTax = $activeOrder['tax'] - $itemToDelete['tax'];

    $order_insert_stmt = $this->db->prepare("UPDATE orders o SET total = :total, tax = :tax");
    $order_insert_stmt->execute([":total" => $orderTotalPrice, ":tax" => $orderTotalTax]);

    if ($order_insert_stmt->rowCount() === 0) {
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
