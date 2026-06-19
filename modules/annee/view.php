<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM annee WHERE id_annee = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$pageTitle = 'تفاصيل السنة';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-eye"></i> تفاصيل السنة</h4>
<div class="card">
  <div class="card-body">
    <dl class="row">
      <dt class="col-sm-3">#</dt><dd class="col-sm-9"><?= (int) $item['id_annee'] ?></dd>
      <dt class="col-sm-3">السنة</dt><dd class="col-sm-9"><?= (int) $item['annee'] ?></dd>
    </dl>
    <a href="list.php" class="btn btn-secondary">رجوع</a>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
