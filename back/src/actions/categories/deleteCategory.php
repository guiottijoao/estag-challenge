<?php

require_once '../../config/Database.php';
require_once '../../controllers/CategoryController.php';

$db = Database::getConnection();
$controller = new CategoryController($db);

$id = $_GET['code'];

try {
  $controller->delete($id);
  header("Location: ../../categories.php");
  exit;
} catch (Exception $e) {
  $message = urlencode($e->getMessage());
  if ($e->getCode() == 23503) {
    header("Location: ../../categories.php?error=fk");
  } else {
    echo "Internal server error: ", $e->getMessage();
    header("Location: ../../categories.php?error=$message");
  }
  exit;
}
