<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('role.manage');

$items = db()->query('SELECT id, nom FROM role ORDER BY nom')->fetchAll();

$pageTitle = 'الأدوار والصلاحيات';
require __DIR__ . '/../../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4><i class="bi bi-shield-lock"></i> الأدوار والصلاحيات</h4>
  <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> إضافة</a>
</div>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-hover table-bordered">
      <thead>
        <tr>
          <th>#</th>
          <th>الاسم</th>
          <th>إجراءات</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= (int) $it['id'] ?></td>
          <td><?= e($it['nom']) ?></td>
          <td>
            <a href="permissions.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-key"></i> الصلاحيات</a>
            <a href="edit.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form method="post" action="delete.php" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= $it['id'] ?>">
              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?>
        <tr><td colspan="3" class="text-center text-muted">لا توجد بيانات</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
