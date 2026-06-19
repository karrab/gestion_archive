<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('archive.delete');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = db()->prepare('SELECT fichier FROM archive WHERE id = ?');
    $stmt->execute([$id]);
    $fichier = $stmt->fetchColumn();

    try {
        db()->prepare('DELETE FROM archive WHERE id = ?')->execute([$id]);
        if ($fichier && is_file(UPLOAD_DIR . '/' . $fichier)) {
            unlink(UPLOAD_DIR . '/' . $fichier);
        }
        log_historique('suppression', 'archive', "Suppression de l'archive #{$id}");
        flash_set('success', 'تم الحذف بنجاح.');
    } catch (PDOException $e) {
        flash_set('danger', 'لا يمكن حذف هذا العنصر لأنه مستخدم في بيانات أخرى (نقل/إيداع/إتلاف).');
    }
}
redirect('list.php');
