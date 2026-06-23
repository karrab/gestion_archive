<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.view');

$pdo = db();
$sort = in_array($_GET['sort'] ?? '', ['id_cls', 'ref_classification', 'titre_classfication']) ? $_GET['sort'] : 'ref_classification';
$dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$filtreRef = trim($_GET['f_ref_classification'] ?? '');
$filtreTitre = trim($_GET['f_titre_classfication'] ?? '');
$perPage = $_GET['per_page'] ?? '50';

$where = [];
$params = [];
if ($filtreRef !== '') {
    $where[] = 'ref_classification LIKE :ref';
    $params['ref'] = '%' . $filtreRef . '%';
}
if ($filtreTitre !== '') {
    $where[] = 'titre_classfication LIKE :titre';
    $params['titre'] = '%' . $filtreTitre . '%';
}
$cursorVal = $_GET['cursor_val'] ?? null;
$cursorId = isset($_GET['cursor_id']) && $_GET['cursor_id'] !== '' ? (int) $_GET['cursor_id'] : null;

$selectFromSql = "SELECT id_cls, ref_classification, titre_classfication, notes FROM classification";

if ($perPage === 'all') {
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("{$selectFromSql} {$whereSql} ORDER BY {$sort} {$dir}, id_cls {$dir}");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    $hasMore = false;
} else {
    $result = keyset_paginate($pdo, $selectFromSql, $where, $params, "{$sort}", $dir, 'id_cls', $cursorVal, $cursorId, (int) $perPage);
    $items = $result['items'];
    $hasMore = $result['has_more'];
}

$pageTitle = 'التصنيف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4><i class="bi bi-tags"></i> التصنيف</h4>
  <div>
    <?php if (has_permission('reference.print')): ?>
    <a href="<?= BASE_URL ?>/pdf/export.php?type=classification" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
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
          <th data-col="id_cls">#</th>
          <th data-col="ref_classification">المرجع</th>
          <th data-col="titre_classfication">العنوان</th>
          <th>ملاحظات</th>
          <th>إجراءات</th>
        </tr>
        <tr class="column-search">
          <td></td>
          <td><input type="text" class="form-control form-control-sm" data-col="ref_classification" value="<?= e($filtreRef) ?>" placeholder="بحث..."></td>
          <td><input type="text" class="form-control form-control-sm" data-col="titre_classfication" value="<?= e($filtreTitre) ?>" placeholder="بحث..."></td>
          <td></td><td></td>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= (int) $it['id_cls'] ?></td>
          <td><?= e($it['ref_classification']) ?></td>
          <td><?= e($it['titre_classfication']) ?></td>
          <td><?= e($it['notes']) ?></td>
          <td>
            <a href="view.php?id=<?= $it['id_cls'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <?php if (has_permission('reference.edit')): ?><a href="edit.php?id=<?= $it['id_cls'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a><?php endif; ?>
            <?php if (has_permission('reference.delete')): ?><form method="post" action="delete.php" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= $it['id_cls'] ?>">
              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
            </form><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?>
        <tr><td colspan="5" class="text-center text-muted">لا توجد بيانات</td></tr>
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
      <a href="<?= e(pagination_url(['cursor_val' => $last[$sort], 'cursor_id' => $last['id_cls']])) ?>" class="btn btn-sm btn-outline-primary">الصفحة التالية <i class="bi bi-chevron-left"></i></a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
