<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('role.manage');

$id = (int) ($_GET['id'] ?? $_POST['role_id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM role WHERE id = ?');
$stmt->execute([$id]);
$role = $stmt->fetch();
if (!$role) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $checked = array_map('intval', $_POST['permissions'] ?? []);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM role_permission WHERE role_id = ?')->execute([$id]);
        $insert = $pdo->prepare('INSERT INTO role_permission (role_id, permission_id) VALUES (?, ?)');
        foreach ($checked as $permId) {
            $insert->execute([$id, $permId]);
        }
        $pdo->commit();
        log_historique('modification', 'role', "Mise à jour des permissions du rôle #{$id}");
        flash_set('success', 'تم تحديث الصلاحيات بنجاح.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash_set('danger', 'حدث خطأ أثناء التحديث.');
    }
    redirect('permissions.php?id=' . $id);
}

$allPermissions = db()->query('SELECT id, code, libelle, module FROM permission ORDER BY module, code')->fetchAll();
$assignedStmt = db()->prepare('SELECT permission_id FROM role_permission WHERE role_id = ?');
$assignedStmt->execute([$id]);
$assigned = array_column($assignedStmt->fetchAll(), 'permission_id');
$assigned = array_map('intval', $assigned);

$grouped = [];
foreach ($allPermissions as $p) {
    $grouped[$p['module']][] = $p;
}

$pageTitle = 'صلاحيات الدور';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-key"></i> صلاحيات الدور: <?= e($role['nom']) ?></h4>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="role_id" value="<?= (int) $id ?>">
      <?php foreach ($grouped as $module => $perms): ?>
        <div class="mb-3">
          <h6 class="text-primary"><?= e($module) ?></h6>
          <div class="row">
            <?php foreach ($perms as $p): ?>
              <div class="col-md-4 form-check">
                <input type="checkbox" class="form-check-input" name="permissions[]" value="<?= $p['id'] ?>" id="perm<?= $p['id'] ?>" <?= in_array((int) $p['id'], $assigned, true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="perm<?= $p['id'] ?>"><?= e($p['libelle']) ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="list.php" class="btn btn-secondary">رجوع</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
