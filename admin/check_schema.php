<?php
require __DIR__ . '/../includes/config.php';
$pdo = getPDO();
$stmt = $pdo->query("SHOW CREATE TABLE produits");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo $row['Create Table'];
