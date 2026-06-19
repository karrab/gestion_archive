<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('version.view');

$perPage = $_GET['per_page'] ?? '50';
$sql = 'SELECT id_ver, num_ver, developper_par, direction, nouveaute, created_at FROM version_app ORDER BY id_ver DESC';
if ($perPage !== 'all') {
    $sql .= ' LIMIT ' . (int) $perPage;
}
$items = db()->query($sql)->fetchAll();

$pageTitle = 'الإصدارات';
require __DIR__ . '/../../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4><i class="bi bi-tag"></i> الإصدارات</h4>
  <div>
    <?php if (has_permission('version.print')): ?>
      <a href="<?= BASE_URL ?>/pdf/export.php?type=version" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <?php endif; ?>
    <?php if (has_permission('version.add')): ?>
      <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> إضافة</a>
    <?php endif; ?>
  </div>
</div>

<form method="get" class="row g-2 mb-3">
  <div class="col-auto">
    <select name="per_page" class="form-select" onchange="this.form.submit()">
      <?php foreach (['10','50','100','200','500','1000','all'] as $opt): ?>
        <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt === 'all' ? 'الكل' : $opt ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-hover table-bordered">
      <thead>
        <tr>
          <th>#</th><th>الإصدار</th><th>تطوير</th><th>الإدارة</th><th>التاريخ</th><th>إجراءات</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= (int) $it['id_ver'] ?></td>
          <td><?= e($it['num_ver']) ?></td>
          <td><?= e($it['developper_par']) ?></td>
          <td><?= e($it['direction']) ?></td>
          <td><?= e($it['created_at']) ?></td>
          <td>
            <a href="view.php?id=<?= $it['id_ver'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <?php if (has_permission('version.edit')): ?>
              <a href="edit.php?id=<?= $it['id_ver'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <?php if (has_permission('version.delete')): ?>
              <form method="post" action="delete.php" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $it['id_ver'] ?>">
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?>
        <tr><td colspan="6" class="text-center text-muted">لا توجد بيانات</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
