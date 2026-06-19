<?php
declare(strict_types=1);
require __DIR__ . '/config/config.php';

if (!empty($_SESSION['user_id'])) {
    redirect(BASE_URL . '/index.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    if (auth_attempt($login, $password)) {
        redirect(BASE_URL . '/index.php');
    }
    $error = 'بيانات الدخول غير صحيحة.';
}

$parametres = get_parametres();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول - <?= e($parametres['nom_etablissement'] ?? 'Gestion Archive') ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap-icons/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body>
<div class="login-wrapper">
  <div class="card login-card shadow">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <img src="<?= BASE_URL ?>/images/<?= e($parametres['logo'] ?? 'logo.png') ?>" height="64" alt="logo">
        <h5 class="mt-2"><?= e($parametres['nom_etablissement'] ?? 'Gestion Archive') ?></h5>
        <?php if (!empty($parametres['adresse'])): ?>
          <p class="text-muted small mb-0"><?= e($parametres['adresse']) ?></p>
        <?php endif; ?>
      </div>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">اسم المستخدم</label>
          <input type="text" name="login" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">كلمة المرور</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> دخول</button>
      </form>
    </div>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
