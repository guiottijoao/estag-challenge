<?php

class CategoryController {

  private $db;

  public function __construct(PDO $db) {
    $this->db = $db;
  }

  public function store(array $data) {
    try {
      $this->validate($data);

      $stmt = $this->db->prepare("INSERT INTO categories (name, tax) VALUES (:name, :tax)");
      $stmt->bindValue(':name', $this->sanitize($data['name']), PDO::PARAM_STR);
      $stmt->bindValue(':tax', (float)$data['tax']);

      return $stmt->execute();
    } catch (Exception $e) {
      throw $e;
    }
  }

  private function validate(array $data) {
    if (empty(trim($data['name']))) {
      throw new Exception("Category name is required.");
    }

    if ($this->nameExists($data['name'])) {
      throw new Exception("A category with this name already exists.");
    }

    if ($data['tax'] < 0 || $data['tax'] > 100) {
      throw new Exception("Tax must be a number between 0 and 100");
    }
  }

  private function nameExists(string $name) {
    $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE name = :name");
    $stmt->bindValue(':name', $name);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
  }

  private function sanitize(string $string) {
    return htmlspecialchars(strip_tags(trim($string)));
  }
}