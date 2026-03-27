<?php

require_once '../../config/Database.php';
require_once '../../controllers/FinishOrderController.php';

$db = Database::getConnection();
$controller = new FinishOrderController($db);

try {
  $controller->finish();
  header("Location: ../../history.php?success=1");
  exit;
} catch (Exception $e) {
  error_log($e->getMessage());

  $message = urlencode($e->getMessage());
  header("Location: ../../index.php?error=$message");
  exit;
}
