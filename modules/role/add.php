<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('role.manage');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nom = trim($_POST['nom'] ?? '');
    if ($nom === '') {
        $errors[] = 'الاسم مطلوب.';
    }
    if (!$errors) {
        try {
            db()->prepare('INSERT INTO role (nom) VALUES (?)')->execute([$nom]);
            log_historique('ajout', 'role', "Ajout: {$nom}");
            flash_set('success', 'تمت الإضافة بنجاح.');
            redirect('list.php');
        } catch (PDOException $e) {
            $errors[] = 'هذا الاسم موجود مسبقًا.';
        }
    }
}

$pageTitle = 'إضافة دور';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-plus-lg"></i> إضافة دور</h4>
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
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="list.php" class="btn btn-secondary">إلغاء</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
