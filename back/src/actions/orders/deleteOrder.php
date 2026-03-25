<?php

require_once '../../config/Database.php';

$db = Database::getConnection();

$id = $_GET['code'];

$stmt = $db->prepare("DELETE FROM order_item WHERE code = :code");
$stmt->execute([":code" => $id]);

header("Location: ../../index.php");