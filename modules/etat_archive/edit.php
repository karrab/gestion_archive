<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.edit');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM etat_archive WHERE id_etat = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $etat = trim($_POST['etat'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($etat === '') {
        $errors[] = 'الحالة مطلوب.';
    }

    if (!$errors) {
        db()->prepare('UPDATE etat_archive SET etat = ?, notes = ? WHERE id_etat = ?')->execute([$etat, $notes, $id]);
        log_historique('modification', 'etat_archive', "Modification #{$id}");
        flash_set('success', 'تم التعديل بنجاح.');
        redirect('list.php');
    }
    $item = array_merge($item, $_POST);
}

$pageTitle = 'تعديل حالة الأرشيف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-pencil"></i> تعديل حالة الأرشيف</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <div class="mb-3">
        <label class="form-label">الحالة</label>
        <input type="text" name="etat" class="form-control" required value="<?= e($item['etat']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">ملاحظات</label>
        <textarea name="notes" class="form-control" rows="3"><?= e($item['notes']) ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="list.php" class="btn btn-secondary">إلغاء</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
