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
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $mail = trim($_POST['mail'] ?? '');

    if ($nom === '' || $mail === '') {
        $errors[] = 'الاسم والبريد الإلكتروني مطلوبان.';
    }

    if (!$errors) {
        try {
            db()->prepare('UPDATE user SET nom = ?, prenom = ?, mail = ? WHERE id = ?')
                ->execute([$nom, $prenom, $mail, $id]);
            log_historique('modification', 'profile', 'Mise à jour du profil');
            flash_set('success', 'تم التعديل بنجاح.');
            redirect('edit.php');
        } catch (PDOException $e) {
            $errors[] = 'هذا البريد الإلكتروني موجود مسبقًا.';
        }
    }
    $item = array_merge($item, $_POST);
}

$pageTitle = 'الملف الشخصي';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-person-circle"></i> الملف الشخصي</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">الاسم</label>
        <input type="text" name="nom" class="form-control" required value="<?= e($item['nom']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">اللقب</label>
        <input type="text" name="prenom" class="form-control" value="<?= e($item['prenom']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">البريد الإلكتروني</label>
        <input type="email" name="mail" class="form-control" required value="<?= e($item['mail']) ?>">
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="password.php" class="btn btn-outline-secondary">تغيير كلمة المرور</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
