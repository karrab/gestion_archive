<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('user.edit');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM user WHERE id = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

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
    if ($password !== '' && strlen($password) < 6) {
        $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';
    }

    if (!$errors) {
        try {
            if ($password !== '') {
                db()->prepare('UPDATE user SET nom = ?, prenom = ?, mail = ?, login = ?, password = ?, role_id = ?, actif = ? WHERE id = ?')
                    ->execute([$nom, $prenom, $mail, $login, password_hash($password, PASSWORD_BCRYPT), $roleId, $actif, $id]);
            } else {
                db()->prepare('UPDATE user SET nom = ?, prenom = ?, mail = ?, login = ?, role_id = ?, actif = ? WHERE id = ?')
                    ->execute([$nom, $prenom, $mail, $login, $roleId, $actif, $id]);
            }
            log_historique('modification', 'user', "Modification #{$id}");
            flash_set('success', 'تم التعديل بنجاح.');
            redirect('list.php');
        } catch (PDOException $e) {
            $errors[] = 'اسم الدخول أو البريد الإلكتروني موجود مسبقًا.';
        }
    }
    $item = array_merge($item, $_POST);
}

$pageTitle = 'تعديل مستخدم';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-pencil"></i> تعديل مستخدم</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">الاسم</label>
          <input type="text" name="nom" class="form-control" required value="<?= e($item['nom']) ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">اللقب</label>
          <input type="text" name="prenom" class="form-control" value="<?= e($item['prenom']) ?>">
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">البريد الإلكتروني</label>
          <input type="email" name="mail" class="form-control" required value="<?= e($item['mail']) ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">اسم الدخول</label>
          <input type="text" name="login" class="form-control" required value="<?= e($item['login']) ?>">
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">كلمة المرور الجديدة (اختياري)</label>
          <input type="password" name="password" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">الدور</label>
          <select name="role_id" class="form-select" required>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>" <?= (int) $item['role_id'] === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="mb-3 form-check">
        <input type="checkbox" name="actif" class="form-check-input" id="actif" <?= ((int) $item['actif'] === 1) ? 'checked' : '' ?>>
        <label class="form-check-label" for="actif">نشط</label>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="list.php" class="btn btn-secondary">إلغاء</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
