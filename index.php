<?php
declare(strict_types=1);
require __DIR__ . '/config/config.php';
auth_check();

$pdo = db();
$stats = cache_remember('dashboard_stats', 120, function () use ($pdo) {
    return [
        'total_archives' => (int) $pdo->query('SELECT COUNT(*) FROM archive')->fetchColumn(),
        'disponibles' => (int) $pdo->query("SELECT COUNT(*) FROM archive WHERE etat_archive = 'Disponible'")->fetchColumn(),
        'transferes' => (int) $pdo->query('SELECT COUNT(*) FROM transfert')->fetchColumn(),
        'verses' => (int) $pdo->query('SELECT COUNT(*) FROM versement')->fetchColumn(),
        'elimines' => (int) $pdo->query('SELECT COUNT(*) FROM elimination')->fetchColumn(),
        'depots' => (int) $pdo->query('SELECT COUNT(*) FROM depot')->fetchColumn(),
    ];
});

$parRepartition = $pdo->query(
    "SELECT etat_archive AS etat, COUNT(*) AS total FROM archive GROUP BY etat_archive"
)->fetchAll();

$appVersion = $pdo->query('SELECT num_ver FROM version_app ORDER BY id_ver DESC LIMIT 1')->fetchColumn() ?: '1.0';
$pageTitle = 'لوحة القيادة';
require __DIR__ . '/includes/layout_header.php';
?>
<h4 class="mb-4"><i class="bi bi-speedometer2"></i> لوحة القيادة</h4>

<div class="row g-3 mb-4">
  <div class="col-md-2">
    <div class="card card-stat bg-primary text-white p-3">
      <div class="display-6"><?= $stats['total_archives'] ?></div>
      <div>إجمالي الأرشيف</div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card card-stat bg-success text-white p-3">
      <div class="display-6"><?= $stats['disponibles'] ?></div>
      <div>متوفر</div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card card-stat bg-info text-white p-3">
      <div class="display-6"><?= $stats['transferes'] ?></div>
      <div>عمليات النقل</div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card card-stat bg-warning text-dark p-3">
      <div class="display-6"><?= $stats['verses'] ?></div>
      <div>عمليات الإيداع</div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card card-stat bg-danger text-white p-3">
      <div class="display-6"><?= $stats['elimines'] ?></div>
      <div>عمليات الإتلاف</div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card card-stat bg-secondary text-white p-3">
      <div class="display-6"><?= $stats['depots'] ?></div>
      <div>المستودعات</div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">توزيع الأرشيف حسب الحالة</div>
  <div class="card-body">
    <table class="table table-sm">
      <thead><tr><th>الحالة</th><th>العدد</th></tr></thead>
      <tbody>
      <?php foreach ($parRepartition as $row): ?>
        <tr><td><?= e($row['etat']) ?></td><td><?= (int) $row['total'] ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/includes/layout_footer.php'; ?>
