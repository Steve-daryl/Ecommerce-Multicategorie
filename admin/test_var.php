<?php
require 'c:/xampp/htdocs/ecommerceMbeppa/version2/includes/config.php';
$pdo = getPDO();
$stmt = $pdo->query('SHOW CREATE TABLE produit_variantes');
$row = $stmt->fetch();
echo $row[1];
