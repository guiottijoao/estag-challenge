<?php

class ProductController
{

  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function store(array $data)
  {

    try {
      $this->validate($data);

      $stmt = $this->db->prepare("INSERT INTO products (name, amount, price, category_code, business_code) VALUES (:name, :amount, :price, :category_code, :business_code)");
      $stmt->bindValue(':name', $this->sanitize($data['name']), PDO::PARAM_STR);
      $stmt->bindValue(':amount', (int)$data['amount']);
      $stmt->bindValue(':price', $data['price']);
      $stmt->bindValue(':category_code', $data['category-code']);
      $stmt->bindValue(':business_code', $this->generateBusinessCode());

      return $stmt->execute();
    } catch (Exception $e) {
      throw $e;
    }
  }

  public function delete($productId)
  {
    $associated_registers_stmt = $this->db->query(
      "SELECT * FROM order_item oi
      INNER JOIN orders o
      ON oi.order_code = o.code
      WHERE oi.product_code = '$productId'
      AND o.status = 'open'"
    );
    if ($associated_registers_stmt->fetch()) {
      throw new Exception("Can't delete, this item has associated registers.", 23503);
    }
    $stmt = $this->db->prepare("UPDATE products SET status = 'inactive' WHERE code = :code");
    $stmt->execute(["code" => $productId]);
  }

  private function generateBusinessCode()
  {
    $stmt = $this->db->query("SELECT COALESCE(MAX(business_code) + 1, 1) FROM products WHERE status = 'active'");
    return $stmt->fetchColumn();
  }

  private function validate(array $data)
  {
    $name = trim($data['name']);
    $amount = $data['amount'];
    $price = $data['price'];
    $categoryCode = $data['category-code'];

    if (empty($name)) {
      throw new Exception("Name is required.");
    }

    if (mb_strlen($name) > 20) {
      throw new Exception("Name cannot exceed 20 characters.");
    }

    if (!preg_match('/^[\p{L}\p{N}\s]+$/u', $name)) {
      throw new Exception("Name contains invalid characters.");
    }

    if ($this->nameExists($name)) {
      throw new Exception("Product with this name already exists.");
    }

    if ($amount < 1 || $amount > 10000) {
      throw new Exception("Amount must be a number between 1 and 10000 (ten thousand).");
    }

    if ($price < 0.1 || $price > 1000000000) {
      throw new Exception("Price must be a number between 0.1 and 1000000000 (one billion)");
    }

    if (empty($categoryCode)) {
      throw new Exception("Category is required");
    }
  }

  private function nameExists(string $name)
  {
    $trimmedName = trim($name);
    $normalizedName = str_replace(' ', '', $trimmedName);

    $stmt = $this->db->prepare("SELECT COUNT(*) FROM products p WHERE p.status = 'active' AND LOWER(REPLACE(p.name, ' ', '')) = LOWER(:normalizedName)");
    $stmt->bindValue("normalizedName", $normalizedName);
    $stmt->execute();

    return $stmt->fetchColumn() > 0;
  }

  private function sanitize(string $string)
  {
    return htmlspecialchars(preg_replace('/\s+/', ' ', trim($string)));
  }
}
