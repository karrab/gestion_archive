<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('user.delete');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id === current_user_id()) {
        flash_set('danger', 'لا يمكنك حذف حسابك الخاص.');
        redirect('list.php');
    }
    try {
        db()->prepare('DELETE FROM user WHERE id = ?')->execute([$id]);
        log_historique('suppression', 'user', "Suppression #{$id}");
        flash_set('success', 'تم الحذف بنجاح.');
    } catch (PDOException $e) {
        flash_set('danger', 'لا يمكن حذف هذا المستخدم لأنه مستخدم في بيانات أخرى.');
    }
}
redirect('list.php');
