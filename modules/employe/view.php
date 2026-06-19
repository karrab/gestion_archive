<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT e.*, s.nom AS service_nom FROM employe e INNER JOIN service s ON s.id = e.service_id WHERE e.id = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$pageTitle = 'تفاصيل الموظف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-eye"></i> تفاصيل الموظف</h4>
<div class="card">
  <div class="card-body">
    <dl class="row">
      <dt class="col-sm-3">#</dt><dd class="col-sm-9"><?= (int) $item['id'] ?></dd>
      <dt class="col-sm-3">الرقم الوظيفي</dt><dd class="col-sm-9"><?= e($item['matricule']) ?></dd>
      <dt class="col-sm-3">الاسم</dt><dd class="col-sm-9"><?= e($item['nom']) ?></dd>
      <dt class="col-sm-3">اللقب</dt><dd class="col-sm-9"><?= e($item['renom']) ?></dd>
      <dt class="col-sm-3">الخدمة</dt><dd class="col-sm-9"><?= e($item['service_nom']) ?></dd>
      <dt class="col-sm-3">البريد الإلكتروني</dt><dd class="col-sm-9"><?= e($item['mail']) ?></dd>
      <dt class="col-sm-3">الهاتف 1</dt><dd class="col-sm-9"><?= e($item['tel1']) ?></dd>
      <dt class="col-sm-3">الهاتف 2</dt><dd class="col-sm-9"><?= e($item['tel2']) ?></dd>
      <dt class="col-sm-3">ملاحظات</dt><dd class="col-sm-9"><?= nl2br(e($item['notes'])) ?></dd>
    </dl>
    <a href="list.php" class="btn btn-secondary">رجوع</a>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
