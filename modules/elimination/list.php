<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('elimination.view');

$pdo = db();
$dateDebut = trim($_GET['date_debut'] ?? '');
$dateFin = trim($_GET['date_fin'] ?? '');
$filtreRef = trim($_GET['f_ref_elm'] ?? '');
$filtrePv = trim($_GET['f_numero_pv'] ?? '');
$perPage = $_GET['per_page'] ?? '50';

$sortable = ['id_elm', 'date_elm', 'ref_elm', 'numero_pv'];
$sort = in_array($_GET['sort'] ?? '', $sortable) ? $_GET['sort'] : 'date_elm';
$dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$where = [];
$params = [];
if ($dateDebut !== '') { $where[] = 'el.date_elm >= :date_debut'; $params['date_debut'] = $dateDebut; }
if ($dateFin !== '') { $where[] = 'el.date_elm <= :date_fin'; $params['date_fin'] = $dateFin; }
if ($filtreRef !== '') { $where[] = 'el.ref_elm LIKE :ref_elm'; $params['ref_elm'] = '%' . $filtreRef . '%'; }
if ($filtrePv !== '') { $where[] = 'el.numero_pv LIKE :numero_pv'; $params['numero_pv'] = '%' . $filtrePv . '%'; }
$cursorVal = $_GET['cursor_val'] ?? null;
$cursorId = isset($_GET['cursor_id']) && $_GET['cursor_id'] !== '' ? (int) $_GET['cursor_id'] : null;

$selectFromSql = "SELECT el.id_elm, el.date_elm, el.ref_elm, el.numero_pv, el.numero_visa, el.date_visa, el.num_bordereau,
               el.nb_doc, el.nb_boite, el.metrage_lin, s.nom AS service_nom, e.nom AS employe_nom, e.renom AS employe_renom
        FROM elimination el
        INNER JOIN service s ON s.id = el.service
        INNER JOIN employe e ON e.id = el.employe";

if ($perPage === 'all') {
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("{$selectFromSql} {$whereSql} ORDER BY el.{$sort} {$dir}, el.id_elm {$dir}");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    $hasMore = false;
} else {
    $result = keyset_paginate($pdo, $selectFromSql, $where, $params, "el.{$sort}", $dir, 'el.id_elm', $cursorVal, $cursorId, (int) $perPage);
    $items = $result['items'];
    $hasMore = $result['has_more'];
}

$pageTitle = 'الإتلاف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4><i class="bi bi-trash3"></i> الإتلاف</h4>
  <div>
    <?php if (has_permission('elimination.print')): ?>
    <a href="<?= BASE_URL ?>/pdf/export.php?type=elimination" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <?php endif; ?>
    <?php if (has_permission('elimination.add')): ?>
    <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> إضافة</a>
    <?php endif; ?>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2">
      <div class="col-md-2">
        <label class="form-label small">من تاريخ</label>
        <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= e($dateDebut) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small">إلى تاريخ</label>
        <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= e($dateFin) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small">عدد العناصر</label>
        <select name="per_page" class="form-select form-select-sm">
          <?php foreach (['10','50','100','200','500','1000','all'] as $opt): ?>
            <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt === 'all' ? 'الكل' : $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> بحث</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-hover table-bordered" data-grid data-sort-col="<?= e($sort) ?>" data-sort-dir="<?= e(strtolower($dir)) ?>">
      <thead>
        <tr>
          <th data-col="id_elm">#</th>
          <th data-col="date_elm">التاريخ</th>
          <th data-col="ref_elm">المرجع</th>
          <th data-col="numero_pv">رقم المحضر</th>
          <th>رقم التأشيرة</th>
          <th>تاريخ التأشيرة</th>
          <th>رقم المذكرة</th>
          <th>عدد الوثائق</th>
          <th>عدد الصناديق</th>
          <th>الخدمة</th>
          <th>الموظف</th>
          <th>إجراءات</th>
        </tr>
        <tr class="column-search">
          <td></td><td></td>
          <td><input type="text" class="form-control form-control-sm" data-col="ref_elm" value="<?= e($filtreRef) ?>"></td>
          <td><input type="text" class="form-control form-control-sm" data-col="numero_pv" value="<?= e($filtrePv) ?>"></td>
          <td colspan="8"></td>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= (int) $it['id_elm'] ?></td>
          <td><?= e($it['date_elm']) ?></td>
          <td><?= e($it['ref_elm']) ?></td>
          <td><?= e($it['numero_pv']) ?></td>
          <td><?= e($it['numero_visa']) ?></td>
          <td><?= e($it['date_visa']) ?></td>
          <td><?= e($it['num_bordereau']) ?></td>
          <td><?= (int) $it['nb_doc'] ?></td>
          <td><?= (int) $it['nb_boite'] ?></td>
          <td><?= e($it['service_nom']) ?></td>
          <td><?= e($it['employe_nom']) ?> <?= e($it['employe_renom']) ?></td>
          <td class="text-nowrap">
            <a href="view.php?id=<?= $it['id_elm'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <?php if (has_permission('elimination.edit')): ?>
              <a href="edit.php?id=<?= $it['id_elm'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <?php if (has_permission('elimination.print')): ?>
              <a href="<?= BASE_URL ?>/pdf/elimination_view.php?id=<?= $it['id_elm'] ?>" class="btn btn-sm btn-outline-danger" target="_blank"><i class="bi bi-file-earmark-pdf"></i></a>
            <?php endif; ?>
            <?php if (has_permission('elimination.delete')): ?>
              <form method="post" action="delete.php" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $it['id_elm'] ?>">
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?>
        <tr><td colspan="12" class="text-center text-muted">لا توجد بيانات</td></tr>
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
      <a href="<?= e(pagination_url(['cursor_val' => $last[$sort], 'cursor_id' => $last['id_elm']])) ?>" class="btn btn-sm btn-outline-primary">الصفحة التالية <i class="bi bi-chevron-left"></i></a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
