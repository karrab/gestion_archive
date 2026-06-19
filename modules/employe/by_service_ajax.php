<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
auth_check();

header('Content-Type: application/json; charset=utf-8');
$serviceId = (int) ($_GET['service_id'] ?? 0);

$stmt = db()->prepare('SELECT id, nom, renom FROM employe WHERE service_id = ? ORDER BY nom');
$stmt->execute([$serviceId]);
$items = array_map(static function (array $row): array {
    return ['id' => $row['id'], 'text' => $row['nom'] . ' ' . $row['renom']];
}, $stmt->fetchAll());

echo json_encode($items, JSON_UNESCAPED_UNICODE);
