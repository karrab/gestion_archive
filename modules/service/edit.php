<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.edit');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM service WHERE id = ?');
$stmt->execute([$id]);
$service = $stmt->fetch();
if (!$service) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nom = trim($_POST['nom'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($nom === '') {
        $errors[] = 'الاسم مطلوب.';
    }

    if (!$errors) {
        db()->prepare('UPDATE service SET nom = ?, notes = ? WHERE id = ?')->execute([$nom, $notes, $id]);
        log_historique('modification', 'service', "Modification du service #{$id}");
        flash_set('success', 'تم التعديل بنجاح.');
        redirect('list.php');
    }
    $service = array_merge($service, $_POST);
}

$pageTitle = 'تعديل خدمة';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-pencil"></i> تعديل خدمة</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <div class="mb-3">
        <label class="form-label">الاسم</label>
        <input type="text" name="nom" class="form-control" required value="<?= e($service['nom']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">ملاحظات</label>
        <textarea name="notes" class="form-control" rows="3"><?= e($service['notes']) ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="list.php" class="btn btn-secondary">إلغاء</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
