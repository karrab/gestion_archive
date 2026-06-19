<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('version.edit');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM version_app WHERE id_ver = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

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
        db()->prepare('UPDATE version_app SET num_ver = ?, developper_par = ?, direction = ?, nouveaute = ?, note = ? WHERE id_ver = ?')
            ->execute([$numVer, $dev, $direction, $nouveaute, $note, $id]);
        log_historique('modification', 'version', "Modification #{$id}");
        flash_set('success', 'تم التعديل بنجاح.');
        redirect('list.php');
    }
    $item = array_merge($item, $_POST);
}

$pageTitle = 'تعديل إصدار';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-pencil"></i> تعديل إصدار</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <div class="mb-3">
        <label class="form-label">رقم الإصدار</label>
        <input type="text" name="num_ver" class="form-control" required value="<?= e($item['num_ver']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">تطوير بواسطة</label>
        <input type="text" name="developper_par" class="form-control" value="<?= e($item['developper_par']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">الإدارة</label>
        <input type="text" name="direction" class="form-control" value="<?= e($item['direction']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">الجديد</label>
        <textarea name="nouveaute" class="form-control" rows="3"><?= e($item['nouveaute']) ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">ملاحظات</label>
        <textarea name="note" class="form-control" rows="3"><?= e($item['note']) ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="list.php" class="btn btn-secondary">إلغاء</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
