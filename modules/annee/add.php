<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.add');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $annee = (int) ($_POST['annee'] ?? 0);

    if ($annee < 1900 || $annee > 2200) {
        $errors[] = 'السنة غير صحيحة.';
    }

    if (!$errors) {
        try {
            db()->prepare('INSERT INTO annee (annee) VALUES (?)')->execute([$annee]);
            log_historique('ajout', 'annee', "Ajout de l'année: {$annee}");
            flash_set('success', 'تمت الإضافة بنجاح.');
            redirect('list.php');
        } catch (PDOException $e) {
            $errors[] = 'هذه السنة موجودة مسبقًا.';
        }
    }
}

$pageTitle = 'إضافة سنة';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-plus-lg"></i> إضافة سنة</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">السنة</label>
        <input type="number" name="annee" class="form-control" required min="1900" max="2200" value="<?= e($_POST['annee'] ?? '') ?>">
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="list.php" class="btn btn-secondary">إلغاء</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
