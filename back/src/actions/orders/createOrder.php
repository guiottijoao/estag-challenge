<?php

require_once '../../config/Database.php';
require_once '../../controllers/OrderController.php';

$db = Database::getConnection();
$controller = new OrderController($db);

try {
  $controller->store($_POST);
  header("Location: ../../index.php?success=1");
  exit;
} catch (Exception $e) {
  error_log("DB Error " . $e->getMessage());
  $message = urlencode($e->getMessage());
  header("Location: ../../index.php?error=$message");
  exit;
}
