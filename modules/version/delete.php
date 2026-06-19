<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('version.delete');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    try {
        db()->prepare('DELETE FROM version_app WHERE id_ver = ?')->execute([$id]);
        log_historique('suppression', 'version', "Suppression #{$id}");
        flash_set('success', 'تم الحذف بنجاح.');
    } catch (PDOException $e) {
        flash_set('danger', 'تعذر حذف هذا العنصر.');
    }
}
redirect('list.php');
