<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.manage');

$pdo = db();
$sort = in_array($_GET['sort'] ?? '', ['id_sort', 'description']) ? $_GET['sort'] : 'description';
$dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$filtre = trim($_GET['f_description'] ?? '');
$perPage = $_GET['per_page'] ?? '50';

$where = [];
$params = [];
if ($filtre !== '') {
    $where[] = 'description LIKE :description';
    $params['description'] = '%' . $filtre . '%';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT id_sort, description, notes FROM sort_fin_doc {$whereSql} ORDER BY {$sort} {$dir}";
if ($perPage !== 'all') {
    $sql .= ' LIMIT ' . (int) $perPage;
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$pageTitle = 'مصير الوثيقة';
require __DIR__ . '/../../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4><i class="bi bi-signpost-split"></i> مصير الوثيقة</h4>
  <div>
    <a href="<?= BASE_URL ?>/pdf/export.php?type=sort_fin_doc" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> إضافة</a>
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
    <table class="table table-hover table-bordered" data-grid data-sort-col="<?= e($sort) ?>" data-sort-dir="<?= e(strtolower($dir)) ?>">
      <thead>
        <tr>
          <th data-col="id_sort">#</th>
          <th data-col="description">الوصف</th>
          <th>ملاحظات</th>
          <th>إجراءات</th>
        </tr>
        <tr class="column-search">
          <td></td>
          <td><input type="text" class="form-control form-control-sm" data-col="description" value="<?= e($filtre) ?>" placeholder="بحث..."></td>
          <td></td>
          <td></td>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= (int) $it['id_sort'] ?></td>
          <td><?= e($it['description']) ?></td>
          <td><?= e($it['notes']) ?></td>
          <td>
            <a href="view.php?id=<?= $it['id_sort'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <a href="edit.php?id=<?= $it['id_sort'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form method="post" action="delete.php" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= $it['id_sort'] ?>">
              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?>
        <tr><td colspan="4" class="text-center text-muted">لا توجد بيانات</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
