<?php

require_once '../../config/Database.php';
require_once '../../controllers/CategoryController.php';

$db = Database::getConnection();
$controller = new CategoryController($db);

$name = $_POST['name'];
$tax = $_POST['tax'];

try {
  $controller->store($_POST);
  header("Location: ../../categories.php?success=1");
  exit;
} catch (Exception $e) {
  error_log("DB Error: " . $e->getMessage());

  $message = urlencode($e->getMessage());
  header("Location: ../../categories.php?error=$message");
  exit;
}
