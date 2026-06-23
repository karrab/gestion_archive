<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('transfert.view');

$pdo = db();
$dateDebut = trim($_GET['date_debut'] ?? '');
$dateFin = trim($_GET['date_fin'] ?? '');
$filtreRef = trim($_GET['f_ref_trans'] ?? '');
$filtreBordereau = trim($_GET['f_num_bordereau'] ?? '');
$perPage = $_GET['per_page'] ?? '50';

$sortable = ['id_trans', 'date_trans', 'ref_trans', 'num_bordereau'];
$sort = in_array($_GET['sort'] ?? '', $sortable) ? $_GET['sort'] : 'date_trans';
$dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$where = [];
$params = [];
if ($dateDebut !== '') { $where[] = 't.date_trans >= :date_debut'; $params['date_debut'] = $dateDebut; }
if ($dateFin !== '') { $where[] = 't.date_trans <= :date_fin'; $params['date_fin'] = $dateFin; }
if ($filtreRef !== '') { $where[] = 't.ref_trans LIKE :ref_trans'; $params['ref_trans'] = '%' . $filtreRef . '%'; }
if ($filtreBordereau !== '') { $where[] = 't.num_bordereau LIKE :num_bordereau'; $params['num_bordereau'] = '%' . $filtreBordereau . '%'; }
$cursorVal = $_GET['cursor_val'] ?? null;
$cursorId = isset($_GET['cursor_id']) && $_GET['cursor_id'] !== '' ? (int) $_GET['cursor_id'] : null;

$selectFromSql = "SELECT t.id_trans, t.date_trans, t.ref_trans, t.num_bordereau, t.nb_doc, t.nb_boite, t.metrage_lin,
               sd.nom AS service_dest_nom, ed.nom AS employe_dest_nom, ed.renom AS employe_dest_renom,
               so.nom AS service_origin_nom, eo.nom AS employe_origin_nom, eo.renom AS employe_origin_renom
        FROM transfert t
        INNER JOIN service sd ON sd.id = t.service_dest
        INNER JOIN employe ed ON ed.id = t.employe_dest
        INNER JOIN service so ON so.id = t.service_origin
        INNER JOIN employe eo ON eo.id = t.employe_origin";

if ($perPage === 'all') {
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("{$selectFromSql} {$whereSql} ORDER BY t.{$sort} {$dir}, t.id_trans {$dir}");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    $hasMore = false;
} else {
    $result = keyset_paginate($pdo, $selectFromSql, $where, $params, "t.{$sort}", $dir, 't.id_trans', $cursorVal, $cursorId, (int) $perPage);
    $items = $result['items'];
    $hasMore = $result['has_more'];
}

$pageTitle = 'النقل';
require __DIR__ . '/../../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4><i class="bi bi-arrow-left-right"></i> النقل</h4>
  <div>
    <?php if (has_permission('transfert.print')): ?>
    <a href="<?= BASE_URL ?>/pdf/export.php?type=transfert" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <?php endif; ?>
    <?php if (has_permission('transfert.add')): ?>
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
          <th data-col="id_trans">#</th>
          <th data-col="date_trans">التاريخ</th>
          <th data-col="ref_trans">المرجع</th>
          <th data-col="num_bordereau">رقم المذكرة</th>
          <th>عدد الوثائق</th>
          <th>عدد الصناديق</th>
          <th>المتر الخطي</th>
          <th>الخدمة المستقبلة</th>
          <th>الموظف المستقبل</th>
          <th>الخدمة المصدر</th>
          <th>الموظف المصدر</th>
          <th>إجراءات</th>
        </tr>
        <tr class="column-search">
          <td></td><td></td>
          <td><input type="text" class="form-control form-control-sm" data-col="ref_trans" value="<?= e($filtreRef) ?>"></td>
          <td><input type="text" class="form-control form-control-sm" data-col="num_bordereau" value="<?= e($filtreBordereau) ?>"></td>
          <td colspan="8"></td>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= (int) $it['id_trans'] ?></td>
          <td><?= e($it['date_trans']) ?></td>
          <td><?= e($it['ref_trans']) ?></td>
          <td><?= e($it['num_bordereau']) ?></td>
          <td><?= (int) $it['nb_doc'] ?></td>
          <td><?= (int) $it['nb_boite'] ?></td>
          <td><?= e($it['metrage_lin']) ?></td>
          <td><?= e($it['service_dest_nom']) ?></td>
          <td><?= e($it['employe_dest_nom']) ?> <?= e($it['employe_dest_renom']) ?></td>
          <td><?= e($it['service_origin_nom']) ?></td>
          <td><?= e($it['employe_origin_nom']) ?> <?= e($it['employe_origin_renom']) ?></td>
          <td class="text-nowrap">
            <a href="view.php?id=<?= $it['id_trans'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <?php if (has_permission('transfert.edit')): ?>
              <a href="edit.php?id=<?= $it['id_trans'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <?php if (has_permission('transfert.print')): ?>
              <a href="<?= BASE_URL ?>/pdf/transfert_view.php?id=<?= $it['id_trans'] ?>" class="btn btn-sm btn-outline-danger" target="_blank"><i class="bi bi-file-earmark-pdf"></i></a>
            <?php endif; ?>
            <?php if (has_permission('transfert.delete')): ?>
              <form method="post" action="delete.php" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $it['id_trans'] ?>">
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
      <a href="<?= e(pagination_url(['cursor_val' => $last[$sort], 'cursor_id' => $last['id_trans']])) ?>" class="btn btn-sm btn-outline-primary">الصفحة التالية <i class="bi bi-chevron-left"></i></a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
