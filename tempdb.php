<?php
$pdo = new PDO('sqlite:data/minirank.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sql = file_get_contents('db/schema.sql');
$pdo->exec($sql);
echo "Database schema initialized.\n";