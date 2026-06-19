<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    try {
        db()->prepare('DELETE FROM depot WHERE id_dept = ?')->execute([$id]);
        log_historique('suppression', 'depot', "Suppression #{$id}");
        flash_set('success', 'تم الحذف بنجاح.');
    } catch (PDOException $e) {
        flash_set('danger', 'لا يمكن حذف هذا العنصر لأنه مستخدم في بيانات أخرى.');
    }
}
redirect('list.php');
