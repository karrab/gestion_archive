<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.view');

$pdo = db();
$sort = in_array($_GET['sort'] ?? '', ['id_annee', 'annee']) ? $_GET['sort'] : 'annee';
$dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$filtre = trim($_GET['f_annee'] ?? '');
$perPage = $_GET['per_page'] ?? '50';

$where = [];
$params = [];
if ($filtre !== '') {
    $where[] = 'annee LIKE :annee';
    $params['annee'] = '%' . $filtre . '%';
}
$cursorVal = $_GET['cursor_val'] ?? null;
$cursorId = isset($_GET['cursor_id']) && $_GET['cursor_id'] !== '' ? (int) $_GET['cursor_id'] : null;

$selectFromSql = "SELECT id_annee, annee FROM annee";

if ($perPage === 'all') {
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("{$selectFromSql} {$whereSql} ORDER BY {$sort} {$dir}, id_annee {$dir}");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    $hasMore = false;
} else {
    $result = keyset_paginate($pdo, $selectFromSql, $where, $params, "{$sort}", $dir, 'id_annee', $cursorVal, $cursorId, (int) $perPage);
    $items = $result['items'];
    $hasMore = $result['has_more'];
}

$pageTitle = 'السنوات';
require __DIR__ . '/../../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4><i class="bi bi-calendar3"></i> السنوات</h4>
  <div>
    <?php if (has_permission('reference.print')): ?>
    <a href="<?= BASE_URL ?>/pdf/export.php?type=annee" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <?php endif; ?>
    <?php if (has_permission('reference.add')): ?>
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
    <table class="table table-hover table-bordered" data-grid data-sort-col="<?= e($sort) ?>" data-sort-dir="<?= e(strtolower($dir)) ?>">
      <thead>
        <tr>
          <th data-col="id_annee">#</th>
          <th data-col="annee">السنة</th>
          <th>إجراءات</th>
        </tr>
        <tr class="column-search">
          <td></td>
          <td><input type="text" class="form-control form-control-sm" data-col="annee" value="<?= e($filtre) ?>" placeholder="بحث..."></td>
          <td></td>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= (int) $it['id_annee'] ?></td>
          <td><?= (int) $it['annee'] ?></td>
          <td>
            <a href="view.php?id=<?= $it['id_annee'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <?php if (has_permission('reference.edit')): ?><a href="edit.php?id=<?= $it['id_annee'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a><?php endif; ?>
            <?php if (has_permission('reference.delete')): ?><form method="post" action="delete.php" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= $it['id_annee'] ?>">
              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
            </form><?php endif; ?>
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
<?php if ($perPage !== 'all'): ?>
<div class="d-flex justify-content-between align-items-center mt-3">
  <div>
    <?php if ($cursorVal !== null): ?>
      <a href="<?= e(pagination_url(['cursor_val' => null, 'cursor_id' => null])) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-skip-start"></i> الصفحة الأولى</a>
    <?php endif; ?>
  </div>
  <div>
    <?php if ($hasMore && $items): ?>
      <?php $last = end($items); ?>
      <a href="<?= e(pagination_url(['cursor_val' => $last[$sort], 'cursor_id' => $last['id_annee']])) ?>" class="btn btn-sm btn-outline-primary">الصفحة التالية <i class="bi bi-chevron-left"></i></a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
