<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('role.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    try {
        db()->prepare('DELETE FROM role WHERE id = ?')->execute([$id]);
        log_historique('suppression', 'role', "Suppression #{$id}");
        flash_set('success', 'تم الحذف بنجاح.');
    } catch (PDOException $e) {
        flash_set('danger', 'لا يمكن حذف هذا الدور لأنه مستخدم في بيانات أخرى.');
    }
}
redirect('list.php');
