<?php

require_once '../../config/Database.php';
require_once '../../controllers/OrderController.php';

$db = Database::getConnection();
$controller = new OrderController($db);

$id = $_GET['code'];

try {
  $controller->delete($id);
  header("Location: ../../index.php");
} catch (Exception $e) {
  error_log("DB Error " . $e->getMessage());

  $message = urlencode($e->getMessage());
  header("Location: ../../index.php?error=$message");
}