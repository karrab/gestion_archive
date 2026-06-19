<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('user.view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT u.*, r.nom AS role_nom FROM user u INNER JOIN role r ON r.id = u.role_id WHERE u.id = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$pageTitle = 'تفاصيل المستخدم';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-eye"></i> تفاصيل المستخدم</h4>
<div class="card">
  <div class="card-body">
    <dl class="row">
      <dt class="col-sm-3">#</dt><dd class="col-sm-9"><?= (int) $item['id'] ?></dd>
      <dt class="col-sm-3">الاسم</dt><dd class="col-sm-9"><?= e($item['nom']) ?></dd>
      <dt class="col-sm-3">اللقب</dt><dd class="col-sm-9"><?= e($item['prenom']) ?></dd>
      <dt class="col-sm-3">البريد الإلكتروني</dt><dd class="col-sm-9"><?= e($item['mail']) ?></dd>
      <dt class="col-sm-3">اسم الدخول</dt><dd class="col-sm-9"><?= e($item['login']) ?></dd>
      <dt class="col-sm-3">الدور</dt><dd class="col-sm-9"><?= e($item['role_nom']) ?></dd>
      <dt class="col-sm-3">نشط</dt><dd class="col-sm-9"><?= ((int) $item['actif'] === 1) ? 'نعم' : 'لا' ?></dd>
    </dl>
    <a href="list.php" class="btn btn-secondary">رجوع</a>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
