<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.manage');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM annee WHERE id_annee = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $annee = (int) ($_POST['annee'] ?? 0);

    if ($annee < 1900 || $annee > 2200) {
        $errors[] = 'السنة غير صحيحة.';
    }

    if (!$errors) {
        try {
            db()->prepare('UPDATE annee SET annee = ? WHERE id_annee = ?')->execute([$annee, $id]);
            log_historique('modification', 'annee', "Modification #{$id}");
            flash_set('success', 'تم التعديل بنجاح.');
            redirect('list.php');
        } catch (PDOException $e) {
            $errors[] = 'هذه السنة موجودة مسبقًا.';
        }
    }
    $item = array_merge($item, $_POST);
}

$pageTitle = 'تعديل سنة';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-pencil"></i> تعديل سنة</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <div class="mb-3">
        <label class="form-label">السنة</label>
        <input type="number" name="annee" class="form-control" required min="1900" max="2200" value="<?= e((string) $item['annee']) ?>">
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="list.php" class="btn btn-secondary">إلغاء</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
