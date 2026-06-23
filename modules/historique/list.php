<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('historique.view');

$pdo = db();
$filtreModule = trim($_GET['f_module'] ?? '');
$filtreAction = trim($_GET['f_action'] ?? '');
$dateDebut = trim($_GET['date_debut'] ?? '');
$dateFin = trim($_GET['date_fin'] ?? '');
$perPage = $_GET['per_page'] ?? '50';

$where = [];
$params = [];
if ($filtreModule !== '') {
    $where[] = 'h.module LIKE :module';
    $params['module'] = '%' . $filtreModule . '%';
}
if ($filtreAction !== '') {
    $where[] = 'h.action LIKE :action';
    $params['action'] = '%' . $filtreAction . '%';
}
if ($dateDebut !== '') {
    $where[] = 'h.created_at >= :date_debut';
    $params['date_debut'] = $dateDebut . ' 00:00:00';
}
if ($dateFin !== '') {
    $where[] = 'h.created_at <= :date_fin';
    $params['date_fin'] = $dateFin . ' 23:59:59';
}
$cursorVal = $_GET['cursor_val'] ?? null;
$cursorId = isset($_GET['cursor_id']) && $_GET['cursor_id'] !== '' ? (int) $_GET['cursor_id'] : null;

$selectFromSql = "SELECT h.id, u.login, h.action, h.module, h.description, h.ip_address, h.created_at
        FROM historique h LEFT JOIN user u ON u.id = h.user_id";

if ($perPage === 'all') {
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("{$selectFromSql} {$whereSql} ORDER BY h.created_at DESC, h.id DESC");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    $hasMore = false;
} else {
    $result = keyset_paginate($pdo, $selectFromSql, $where, $params, 'h.created_at', 'DESC', 'h.id', $cursorVal, $cursorId, (int) $perPage);
    $items = $result['items'];
    $hasMore = $result['has_more'];
}

$pageTitle = 'سجل العمليات';
require __DIR__ . '/../../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4><i class="bi bi-clock-history"></i> سجل العمليات</h4>
  <div>
    <?php if (has_permission('historique.print')): ?>
      <a href="<?= BASE_URL ?>/pdf/export.php?type=historique" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <?php endif; ?>
  </div>
</div>

<form method="get" class="row g-2 mb-3">
  <div class="col-auto">
    <input type="text" name="f_module" class="form-control form-control-sm" placeholder="الوحدة" value="<?= e($filtreModule) ?>">
  </div>
  <div class="col-auto">
    <input type="text" name="f_action" class="form-control form-control-sm" placeholder="العملية" value="<?= e($filtreAction) ?>">
  </div>
  <div class="col-auto">
    <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= e($dateDebut) ?>">
  </div>
  <div class="col-auto">
    <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= e($dateFin) ?>">
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-sm btn-outline-primary">بحث</button>
  </div>
  <div class="col-auto">
    <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
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
          <th>#</th><th>المستخدم</th><th>العملية</th><th>الوحدة</th><th>الوصف</th><th>عنوان IP</th><th>التاريخ</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= (int) $it['id'] ?></td>
          <td><?= e($it['login'] ?? '-') ?></td>
          <td><?= e($it['action']) ?></td>
          <td><?= e($it['module']) ?></td>
          <td><?= e($it['description']) ?></td>
          <td><?= e($it['ip_address']) ?></td>
          <td><?= e($it['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?>
        <tr><td colspan="7" class="text-center text-muted">لا توجد بيانات</td></tr>
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
      <a href="<?= e(pagination_url(['cursor_val' => $last['created_at'], 'cursor_id' => $last['id']])) ?>" class="btn btn-sm btn-outline-primary">الصفحة التالية <i class="bi bi-chevron-left"></i></a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
