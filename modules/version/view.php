<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('version.view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM version_app WHERE id_ver = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$pageTitle = 'تفاصيل الإصدار';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-eye"></i> تفاصيل الإصدار</h4>
<div class="card">
  <div class="card-body">
    <dl class="row">
      <dt class="col-sm-3">#</dt><dd class="col-sm-9"><?= (int) $item['id_ver'] ?></dd>
      <dt class="col-sm-3">رقم الإصدار</dt><dd class="col-sm-9"><?= e($item['num_ver']) ?></dd>
      <dt class="col-sm-3">تطوير بواسطة</dt><dd class="col-sm-9"><?= e($item['developper_par']) ?></dd>
      <dt class="col-sm-3">الإدارة</dt><dd class="col-sm-9"><?= e($item['direction']) ?></dd>
      <dt class="col-sm-3">الجديد</dt><dd class="col-sm-9"><?= nl2br(e($item['nouveaute'])) ?></dd>
      <dt class="col-sm-3">ملاحظات</dt><dd class="col-sm-9"><?= nl2br(e($item['note'])) ?></dd>
      <dt class="col-sm-3">التاريخ</dt><dd class="col-sm-9"><?= e($item['created_at']) ?></dd>
    </dl>
    <a href="list.php" class="btn btn-secondary">رجوع</a>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
