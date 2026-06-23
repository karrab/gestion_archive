<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('versement.view');

$pdo = db();
$dateDebut = trim($_GET['date_debut'] ?? '');
$dateFin = trim($_GET['date_fin'] ?? '');
$filtreRef = trim($_GET['f_ref_vers'] ?? '');
$filtreBordereau = trim($_GET['f_num_bordereau'] ?? '');
$perPage = $_GET['per_page'] ?? '50';

$sortable = ['id_vers', 'date_vers', 'ref_vers', 'num_bordereau'];
$sort = in_array($_GET['sort'] ?? '', $sortable) ? $_GET['sort'] : 'date_vers';
$dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$where = [];
$params = [];
if ($dateDebut !== '') { $where[] = 'v.date_vers >= :date_debut'; $params['date_debut'] = $dateDebut; }
if ($dateFin !== '') { $where[] = 'v.date_vers <= :date_fin'; $params['date_fin'] = $dateFin; }
if ($filtreRef !== '') { $where[] = 'v.ref_vers LIKE :ref_vers'; $params['ref_vers'] = '%' . $filtreRef . '%'; }
if ($filtreBordereau !== '') { $where[] = 'v.num_bordereau LIKE :num_bordereau'; $params['num_bordereau'] = '%' . $filtreBordereau . '%'; }
$cursorVal = $_GET['cursor_val'] ?? null;
$cursorId = isset($_GET['cursor_id']) && $_GET['cursor_id'] !== '' ? (int) $_GET['cursor_id'] : null;

$selectFromSql = "SELECT v.id_vers, v.date_vers, v.ref_vers, v.num_bordereau, v.nb_doc, v.nb_boite, v.metrage_lin,
               s.nom AS service_nom, e.nom AS employe_nom, e.renom AS employe_renom, i.description AS institution_nom
        FROM versement v
        INNER JOIN service s ON s.id = v.service
        INNER JOIN employe e ON e.id = v.employe
        LEFT JOIN institution i ON i.id_ins = v.institution";

if ($perPage === 'all') {
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("{$selectFromSql} {$whereSql} ORDER BY v.{$sort} {$dir}, v.id_vers {$dir}");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    $hasMore = false;
} else {
    $result = keyset_paginate($pdo, $selectFromSql, $where, $params, "v.{$sort}", $dir, 'v.id_vers', $cursorVal, $cursorId, (int) $perPage);
    $items = $result['items'];
    $hasMore = $result['has_more'];
}

$pageTitle = 'الإيداع';
require __DIR__ . '/../../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4><i class="bi bi-box-arrow-in-down"></i> الإيداع</h4>
  <div>
    <?php if (has_permission('versement.print')): ?>
    <a href="<?= BASE_URL ?>/pdf/export.php?type=versement" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <?php endif; ?>
    <?php if (has_permission('versement.add')): ?>
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
          <th data-col="id_vers">#</th>
          <th data-col="date_vers">التاريخ</th>
          <th data-col="ref_vers">المرجع</th>
          <th data-col="num_bordereau">رقم المذكرة</th>
          <th>عدد الوثائق</th>
          <th>عدد الصناديق</th>
          <th>المتر الخطي</th>
          <th>الخدمة</th>
          <th>الموظف</th>
          <th>المؤسسة</th>
          <th>إجراءات</th>
        </tr>
        <tr class="column-search">
          <td></td><td></td>
          <td><input type="text" class="form-control form-control-sm" data-col="ref_vers" value="<?= e($filtreRef) ?>"></td>
          <td><input type="text" class="form-control form-control-sm" data-col="num_bordereau" value="<?= e($filtreBordereau) ?>"></td>
          <td colspan="7"></td>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= (int) $it['id_vers'] ?></td>
          <td><?= e($it['date_vers']) ?></td>
          <td><?= e($it['ref_vers']) ?></td>
          <td><?= e($it['num_bordereau']) ?></td>
          <td><?= (int) $it['nb_doc'] ?></td>
          <td><?= (int) $it['nb_boite'] ?></td>
          <td><?= e($it['metrage_lin']) ?></td>
          <td><?= e($it['service_nom']) ?></td>
          <td><?= e($it['employe_nom']) ?> <?= e($it['employe_renom']) ?></td>
          <td><?= e($it['institution_nom']) ?></td>
          <td class="text-nowrap">
            <a href="view.php?id=<?= $it['id_vers'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <?php if (has_permission('versement.edit')): ?>
              <a href="edit.php?id=<?= $it['id_vers'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <?php if (has_permission('versement.print')): ?>
              <a href="<?= BASE_URL ?>/pdf/versement_view.php?id=<?= $it['id_vers'] ?>" class="btn btn-sm btn-outline-danger" target="_blank"><i class="bi bi-file-earmark-pdf"></i></a>
            <?php endif; ?>
            <?php if (has_permission('versement.delete')): ?>
              <form method="post" action="delete.php" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $it['id_vers'] ?>">
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?>
        <tr><td colspan="11" class="text-center text-muted">لا توجد بيانات</td></tr>
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
      <a href="<?= e(pagination_url(['cursor_val' => $last[$sort], 'cursor_id' => $last['id_vers']])) ?>" class="btn btn-sm btn-outline-primary">الصفحة التالية <i class="bi bi-chevron-left"></i></a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
