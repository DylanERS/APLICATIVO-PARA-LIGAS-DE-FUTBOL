<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT * FROM ligas");
print_r($stmt->fetchAll());
$stmt = $conn->query("SELECT * FROM temporadas");
print_r($stmt->fetchAll());
?>
