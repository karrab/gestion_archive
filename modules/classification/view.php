<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.manage');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM classification WHERE id_cls = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$pageTitle = 'تفاصيل التصنيف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-eye"></i> تفاصيل التصنيف</h4>
<div class="card">
  <div class="card-body">
    <dl class="row">
      <dt class="col-sm-3">#</dt><dd class="col-sm-9"><?= (int) $item['id_cls'] ?></dd>
      <dt class="col-sm-3">المرجع</dt><dd class="col-sm-9"><?= e($item['ref_classification']) ?></dd>
      <dt class="col-sm-3">العنوان</dt><dd class="col-sm-9"><?= e($item['titre_classfication']) ?></dd>
      <dt class="col-sm-3">ملاحظات</dt><dd class="col-sm-9"><?= nl2br(e($item['notes'])) ?></dd>
    </dl>
    <a href="list.php" class="btn btn-secondary">رجوع</a>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
