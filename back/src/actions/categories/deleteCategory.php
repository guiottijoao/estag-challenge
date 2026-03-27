<?php

require_once '../../config/Database.php';

$db = Database::getConnection();

$id = $_GET['code'];

try {
  $stmt = $db->prepare('DELETE FROM categories WHERE code = :code');
  $stmt->execute(["code" => $id]);
  header("Location: ../../categories.php");
  exit;
} catch (Exception $e) {
  if ($e->getCode() == 23503) {
    header("Location: ../../categories.php?error=fk");
  } else {
    header("Location: ../../categories.php?error=$message");
  }
  exit;
}