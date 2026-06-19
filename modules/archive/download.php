<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('archive.view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT fichier, titre_dossier FROM archive WHERE id = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item || !$item['fichier']) {
    http_response_code(404);
    die('الملف غير موجود.');
}

$path = UPLOAD_DIR . '/' . $item['fichier'];
if (!is_file($path)) {
    http_response_code(404);
    die('الملف غير موجود.');
}

log_historique('telechargement', 'archive', "Téléchargement du fichier de l'archive #{$id}");

$ext = pathinfo($path, PATHINFO_EXTENSION);
$safeName = preg_replace('/[^\p{L}\p{N}_\-]+/u', '_', $item['titre_dossier']);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $safeName . '.' . $ext . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
