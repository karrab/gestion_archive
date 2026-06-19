<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
auth_check();

header('Content-Type: application/json; charset=utf-8');
$ref = trim($_GET['ref'] ?? '');

$stmt = db()->prepare('SELECT titre_classfication AS titre FROM classification WHERE ref_classification = ? LIMIT 1');
$stmt->execute([$ref]);
$row = $stmt->fetch();

echo json_encode($row ?: ['titre' => ''], JSON_UNESCAPED_UNICODE);
