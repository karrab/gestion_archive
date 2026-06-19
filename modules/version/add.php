<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('version.add');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $numVer = trim($_POST['num_ver'] ?? '');
    $dev = trim($_POST['developper_par'] ?? '');
    $direction = trim($_POST['direction'] ?? '');
    $nouveaute = trim($_POST['nouveaute'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($numVer === '') {
        $errors[] = 'رقم الإصدار مطلوب.';
    }

    if (!$errors) {
        db()->prepare('INSERT INTO version_app (num_ver, developper_par, direction, nouveaute, note) VALUES (?, ?, ?, ?, ?)')
            ->execute([$numVer, $dev, $direction, $nouveaute, $note]);
        log_historique('ajout', 'version', "Ajout: {$numVer}");
        flash_set('success', 'تمت الإضافة بنجاح.');
        redirect('list.php');
    }
}

$pageTitle = 'إضافة إصدار';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-plus-lg"></i> إضافة إصدار</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">رقم الإصدار</label>
        <input type="text" name="num_ver" class="form-control" required value="<?= e($_POST['num_ver'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">تطوير بواسطة</label>
        <input type="text" name="developper_par" class="form-control" value="<?= e($_POST['developper_par'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">الإدارة</label>
        <input type="text" name="direction" class="form-control" value="<?= e($_POST['direction'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">الجديد</label>
        <textarea name="nouveaute" class="form-control" rows="3"><?= e($_POST['nouveaute'] ?? '') ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">ملاحظات</label>
        <textarea name="note" class="form-control" rows="3"><?= e($_POST['note'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="list.php" class="btn btn-secondary">إلغاء</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
