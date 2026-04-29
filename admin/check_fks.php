<?php
require __DIR__ . '/../includes/config.php';
$pdo = getPDO();
$stmt = $pdo->query("
    SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME, DELETE_RULE 
    FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = 'shopmax_db'
");
$fks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Foreign Keys with Rules:\n";
print_r($fks);
