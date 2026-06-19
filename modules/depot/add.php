<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.add');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $numero = trim($_POST['numero'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($numero === '') {
        $errors[] = 'الرقم مطلوب.';
    }

    if (!$errors) {
        try {
            db()->prepare('INSERT INTO depot (numero, description, notes) VALUES (?, ?, ?)')->execute([$numero, $description, $notes]);
            log_historique('ajout', 'depot', "Ajout du dépôt: {$numero}");
            flash_set('success', 'تمت الإضافة بنجاح.');
            redirect('list.php');
        } catch (PDOException $e) {
            $errors[] = 'هذا الرقم موجود مسبقًا.';
        }
    }
}

$pageTitle = 'إضافة مستودع';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-plus-lg"></i> إضافة مستودع</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">الرقم</label>
        <input type="text" name="numero" class="form-control" required value="<?= e($_POST['numero'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">الوصف</label>
        <input type="text" name="description" class="form-control" value="<?= e($_POST['description'] ?? '') ?>">
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
