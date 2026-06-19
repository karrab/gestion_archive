<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.add');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $ref = trim($_POST['ref_classification'] ?? '');
    $titre = trim($_POST['titre_classfication'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($ref === '' || $titre === '') {
        $errors[] = 'المرجع والعنوان مطلوبان.';
    }

    if (!$errors) {
        try {
            db()->prepare('INSERT INTO classification (ref_classification, titre_classfication, notes) VALUES (?, ?, ?)')
                ->execute([$ref, $titre, $notes]);
            log_historique('ajout', 'classification', "Ajout: {$ref}");
            flash_set('success', 'تمت الإضافة بنجاح.');
            redirect('list.php');
        } catch (PDOException $e) {
            $errors[] = 'هذا المرجع موجود مسبقًا.';
        }
    }
}

$pageTitle = 'إضافة تصنيف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-plus-lg"></i> إضافة تصنيف</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">المرجع</label>
        <input type="text" name="ref_classification" class="form-control" required value="<?= e($_POST['ref_classification'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">العنوان</label>
        <input type="text" name="titre_classfication" class="form-control" required value="<?= e($_POST['titre_classfication'] ?? '') ?>">
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
