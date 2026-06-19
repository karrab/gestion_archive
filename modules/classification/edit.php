<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.edit');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM classification WHERE id_cls = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

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
            db()->prepare('UPDATE classification SET ref_classification = ?, titre_classfication = ?, notes = ? WHERE id_cls = ?')
                ->execute([$ref, $titre, $notes, $id]);
            log_historique('modification', 'classification', "Modification #{$id}");
            flash_set('success', 'تم التعديل بنجاح.');
            redirect('list.php');
        } catch (PDOException $e) {
            $errors[] = 'هذا المرجع موجود مسبقًا.';
        }
    }
    $item = array_merge($item, $_POST);
}

$pageTitle = 'تعديل تصنيف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-pencil"></i> تعديل تصنيف</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <div class="mb-3">
        <label class="form-label">المرجع</label>
        <input type="text" name="ref_classification" class="form-control" required value="<?= e($item['ref_classification']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">العنوان</label>
        <input type="text" name="titre_classfication" class="form-control" required value="<?= e($item['titre_classfication']) ?>">
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
