<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
auth_check();

header('Content-Type: application/json; charset=utf-8');
$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = db()->prepare(
    "SELECT id, titre_dossier, num_boite FROM archive
     WHERE MATCH(titre_dossier, num_boite, emplacement, titre_classfication, notes) AGAINST (:q IN BOOLEAN MODE)
     LIMIT 20"
);
$stmt->execute(['q' => $q . '*']);
$items = $stmt->fetchAll();

if (!$items) {
    $stmt = db()->prepare("SELECT id, titre_dossier, num_boite FROM archive WHERE titre_dossier LIKE :q OR num_boite LIKE :q LIMIT 20");
    $stmt->execute(['q' => '%' . $q . '%']);
    $items = $stmt->fetchAll();
}

echo json_encode($items, JSON_UNESCAPED_UNICODE);
