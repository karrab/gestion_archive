<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM service WHERE id = ?');
$stmt->execute([$id]);
$service = $stmt->fetch();
if (!$service) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$pageTitle = 'تفاصيل الخدمة';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-eye"></i> تفاصيل الخدمة</h4>
<div class="card">
  <div class="card-body">
    <dl class="row">
      <dt class="col-sm-3">#</dt><dd class="col-sm-9"><?= (int) $service['id'] ?></dd>
      <dt class="col-sm-3">الاسم</dt><dd class="col-sm-9"><?= e($service['nom']) ?></dd>
      <dt class="col-sm-3">ملاحظات</dt><dd class="col-sm-9"><?= nl2br(e($service['notes'])) ?></dd>
    </dl>
    <a href="list.php" class="btn btn-secondary">رجوع</a>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
