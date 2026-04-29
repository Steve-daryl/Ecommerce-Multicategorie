<?php
require __DIR__ . '/../includes/config.php';
$pdo = getPDO();
$stmt = $pdo->query('SHOW COLUMNS FROM produit_variantes');
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($r);
}
