<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.add');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nom = trim($_POST['nom'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($nom === '') {
        $errors[] = 'الاسم مطلوب.';
    }

    if (!$errors) {
        db()->prepare('INSERT INTO service (nom, notes) VALUES (?, ?)')->execute([$nom, $notes]);
        log_historique('ajout', 'service', "Ajout du service: {$nom}");
        flash_set('success', 'تمت الإضافة بنجاح.');
        redirect('list.php');
    }
}

$pageTitle = 'إضافة خدمة';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-plus-lg"></i> إضافة خدمة</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">الاسم</label>
        <input type="text" name="nom" class="form-control" required value="<?= e($_POST['nom'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">ملاحظات</label>
        <textarea name="notes" class="form-control" rows="3"><?= e($_POST['notes'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="list.php" class="btn btn-secondary">إلغاء</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
