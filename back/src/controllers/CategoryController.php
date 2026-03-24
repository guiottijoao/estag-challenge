<?php

class CategoryController
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

      $stmt = $this->db->prepare("INSERT INTO categories (name, tax) VALUES (:name, :tax)");
      $stmt->bindValue(':name', $this->sanitize($data['name']), PDO::PARAM_STR);
      $stmt->bindValue(':tax', (float)$data['tax']);

      return $stmt->execute();
    } catch (Exception $e) {
      throw $e;
    }
  }

  private function validate(array $data)
  {
    $name = trim($data['name']);

    if (empty($name)) {
      throw new Exception("Category name is required.");
    }

    if (mb_strlen($name) > 20) {
      throw new Exception("Category name cannot exceed 20 characters.");
    }

    // verificar se contém apenas letras, números e espeços
    if (!preg_match('/^[\p{L}\p{N}\s]+$/u', $name)) {
      throw new Exception("Name contains invalid characters.");
    }

    if ($this->nameExists($data['name'])) {
      throw new Exception("A category with this name already exists.");
    }

    if ($data['tax'] < 0 || $data['tax'] > 100) {
      throw new Exception("Tax must be a number between 0 and 100");
    }
  }

  private function nameExists(string $name)
  {
    $trimmedName = trim($name);
    $normalizedName = str_replace(' ', '', $trimmedName);

    $query = "SELECT COUNT(*) FROM categories WHERE LOWER(REPLACE(name, ' ', '')) = LOWER(:normalizedName)";
    $stmt = $this->db->prepare($query);
    $stmt->bindValue(':normalizedName', $normalizedName);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
  }

  private function sanitize(string $string)
  {
    $string = trim($string);

    $string = strip_tags($string);

    $string = preg_replace('/\s+/', ' ', $string);

    return htmlspecialchars(preg_replace('/\s+/', ' ', strip_tags(trim($string))));
  }
}

//testar