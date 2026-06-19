<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.manage');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM depot WHERE id_dept = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

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
            db()->prepare('UPDATE depot SET numero = ?, description = ?, notes = ? WHERE id_dept = ?')->execute([$numero, $description, $notes, $id]);
            log_historique('modification', 'depot', "Modification #{$id}");
            flash_set('success', 'تم التعديل بنجاح.');
            redirect('list.php');
        } catch (PDOException $e) {
            $errors[] = 'هذا الرقم موجود مسبقًا.';
        }
    }
    $item = array_merge($item, $_POST);
}

$pageTitle = 'تعديل مستودع';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-pencil"></i> تعديل مستودع</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <div class="mb-3">
        <label class="form-label">الرقم</label>
        <input type="text" name="numero" class="form-control" required value="<?= e($item['numero']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">الوصف</label>
        <input type="text" name="description" class="form-control" value="<?= e($item['description']) ?>">
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
