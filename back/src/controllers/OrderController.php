<?php

class OrderController
{

  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  private function calcOrderItemTotalTax(float $tax, float $unitPrice, int $amount)
  {
    return ($tax / 100) * $unitPrice * $amount;
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

      //order
      $order_items_stmt = $this->db->query("SELECT * FROM order_item");
      $orderItems = $order_items_stmt->fetch();
      if (!$orderItems) {
        error_log("Join if");
        $orderTotalPrice = $orderItemTotalTax + ($productPrice * $productAmount);
        $stmt = $this->db->prepare("UPDATE orders O
        SET total = :total, tax = :tax
        WHERE o.code = :order_id");
      
        $stmt->bindValue(":total", $orderTotalPrice);
        $stmt->bindValue(":tax", $orderItemTotalTax);
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
    $product = $data['product-code'];
    $amount = $data['amount'];

    if (empty($product)) {
      throw new Exception("Select a product.");
    }

    if ($amount < 1 || $amount > !is_int($amount)) {
      throw new Exception("Amount must be positive integer number.");
    }
  }
}
