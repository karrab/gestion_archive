<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
auth_check();

$id = current_user_id();
$stmt = db()->prepare('SELECT * FROM user WHERE id = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $item['password'])) {
        $errors[] = 'كلمة المرور الحالية غير صحيحة.';
    }
    if (strlen($new) < 6) {
        $errors[] = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.';
    }
    if ($new !== $confirm) {
        $errors[] = 'كلمتا المرور غير متطابقتين.';
    }

    if (!$errors) {
        db()->prepare('UPDATE user SET password = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_BCRYPT), $id]);
        log_historique('modification', 'profile', 'Changement du mot de passe');
        flash_set('success', 'تم تغيير كلمة المرور بنجاح.');
        redirect('edit.php');
    }
}

$pageTitle = 'تغيير كلمة المرور';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-shield-lock"></i> تغيير كلمة المرور</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">كلمة المرور الحالية</label>
        <input type="password" name="current_password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">كلمة المرور الجديدة</label>
        <input type="password" name="new_password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">تأكيد كلمة المرور</label>
        <input type="password" name="confirm_password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="edit.php" class="btn btn-secondary">رجوع</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
