<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('role.manage');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM role WHERE id = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nom = trim($_POST['nom'] ?? '');
    if ($nom === '') {
        $errors[] = 'الاسم مطلوب.';
    }
    if (!$errors) {
        try {
            db()->prepare('UPDATE role SET nom = ? WHERE id = ?')->execute([$nom, $id]);
            log_historique('modification', 'role', "Modification #{$id}");
            flash_set('success', 'تم التعديل بنجاح.');
            redirect('list.php');
        } catch (PDOException $e) {
            $errors[] = 'هذا الاسم موجود مسبقًا.';
        }
    }
    $item = array_merge($item, $_POST);
}

$pageTitle = 'تعديل دور';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-pencil"></i> تعديل دور</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <div class="mb-3">
        <label class="form-label">الاسم</label>
        <input type="text" name="nom" class="form-control" required value="<?= e($item['nom']) ?>">
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="list.php" class="btn btn-secondary">إلغاء</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
