<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('user.add');

$roles = db()->query('SELECT id, nom FROM role ORDER BY nom')->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $mail = trim($_POST['mail'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $roleId = (int) ($_POST['role_id'] ?? 0);
    $actif = isset($_POST['actif']) ? 1 : 0;

    if ($nom === '' || $login === '' || $mail === '' || $roleId <= 0) {
        $errors[] = 'جميع الحقول المطلوبة يجب ملؤها.';
    }
    if ($password === '' || strlen($password) < 6) {
        $errors[] = 'كلمة المرور مطلوبة (6 أحرف على الأقل).';
    }

    if (!$errors) {
        try {
            db()->prepare('INSERT INTO user (nom, prenom, mail, login, password, role_id, actif) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$nom, $prenom, $mail, $login, password_hash($password, PASSWORD_BCRYPT), $roleId, $actif]);
            log_historique('ajout', 'user', "Ajout: {$login}");
            flash_set('success', 'تمت الإضافة بنجاح.');
            redirect('list.php');
        } catch (PDOException $e) {
            $errors[] = 'اسم الدخول أو البريد الإلكتروني موجود مسبقًا.';
        }
    }
}

$pageTitle = 'إضافة مستخدم';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-person-plus"></i> إضافة مستخدم</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">الاسم</label>
          <input type="text" name="nom" class="form-control" required value="<?= e($_POST['nom'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">اللقب</label>
          <input type="text" name="prenom" class="form-control" value="<?= e($_POST['prenom'] ?? '') ?>">
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">البريد الإلكتروني</label>
          <input type="email" name="mail" class="form-control" required value="<?= e($_POST['mail'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">اسم الدخول</label>
          <input type="text" name="login" class="form-control" required value="<?= e($_POST['login'] ?? '') ?>">
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">كلمة المرور</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">الدور</label>
          <select name="role_id" class="form-select" required>
            <option value="">-- اختر --</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>"><?= e($r['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="mb-3 form-check">
        <input type="checkbox" name="actif" class="form-check-input" id="actif" checked>
        <label class="form-check-label" for="actif">نشط</label>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="list.php" class="btn btn-secondary">إلغاء</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
