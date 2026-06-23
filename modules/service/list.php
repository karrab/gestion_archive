<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.view');

$pdo = db();

$sort = in_array($_GET['sort'] ?? '', ['id', 'nom']) ? $_GET['sort'] : 'nom';
$dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$filtreNom = trim($_GET['f_nom'] ?? '');
$perPage = $_GET['per_page'] ?? '50';

$where = [];
$params = [];
if ($filtreNom !== '') {
    $where[] = 'nom LIKE :nom';
    $params['nom'] = '%' . $filtreNom . '%';
}
$cursorVal = $_GET['cursor_val'] ?? null;
$cursorId = isset($_GET['cursor_id']) && $_GET['cursor_id'] !== '' ? (int) $_GET['cursor_id'] : null;

$selectFromSql = "SELECT id, nom, notes FROM service";

if ($perPage === 'all') {
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("{$selectFromSql} {$whereSql} ORDER BY {$sort} {$dir}, id {$dir}");
    $stmt->execute($params);
    $services = $stmt->fetchAll();
    $hasMore = false;
} else {
    $result = keyset_paginate($pdo, $selectFromSql, $where, $params, "{$sort}", $dir, 'id', $cursorVal, $cursorId, (int) $perPage);
    $services = $result['items'];
    $hasMore = $result['has_more'];
}

$pageTitle = 'الخدمات';
require __DIR__ . '/../../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4><i class="bi bi-diagram-3"></i> الخدمات</h4>
  <div>
    <?php if (has_permission('reference.print')): ?>
    <a href="<?= BASE_URL ?>/pdf/export.php?type=service" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
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
          <th data-col="id">#</th>
          <th data-col="nom">الاسم</th>
          <th>ملاحظات</th>
          <th>إجراءات</th>
        </tr>
        <tr class="column-search">
          <td></td>
          <td><input type="text" class="form-control form-control-sm" data-col="nom" value="<?= e($filtreNom) ?>" placeholder="بحث..."></td>
          <td></td>
          <td></td>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($services as $s): ?>
        <tr>
          <td><?= (int) $s['id'] ?></td>
          <td><?= e($s['nom']) ?></td>
          <td><?= e($s['notes']) ?></td>
          <td>
            <a href="view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php if (has_permission('reference.delete')): ?><form method="post" action="delete.php" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= $s['id'] ?>">
              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
            </form><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$services): ?>
        <tr><td colspan="4" class="text-center text-muted">لا توجد بيانات</td></tr>
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
    <?php if ($hasMore && $services): ?>
      <?php $last = end($services); ?>
      <a href="<?= e(pagination_url(['cursor_val' => $last[$sort], 'cursor_id' => $last['id']])) ?>" class="btn btn-sm btn-outline-primary">الصفحة التالية <i class="bi bi-chevron-left"></i></a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
