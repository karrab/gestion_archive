<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('elimination.delete');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT archive_id FROM ligne_elimination WHERE id_elm = ?');
        $stmt->execute([$id]);
        $archiveIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $pdo->prepare('DELETE FROM elimination WHERE id_elm = ?')->execute([$id]);

        if ($archiveIds) {
            $stmtMaj = $pdo->prepare("UPDATE archive SET etat_archive = 'Disponible' WHERE id = ?");
            foreach (array_filter($archiveIds) as $archiveId) {
                $stmtMaj->execute([$archiveId]);
            }
        }
        $pdo->commit();
        log_historique('suppression', 'elimination', "Suppression de l'élimination #{$id}");
        flash_set('success', 'تم الحذف بنجاح.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash_set('danger', 'حدث خطأ أثناء الحذف.');
    }
}
redirect('list.php');
