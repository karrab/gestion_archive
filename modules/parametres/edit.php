<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('parametres.manage');

$item = db()->query('SELECT * FROM parametres ORDER BY id LIMIT 1')->fetch();
if (!$item) {
    db()->exec("INSERT INTO parametres (nom_etablissement) VALUES ('')");
    $item = db()->query('SELECT * FROM parametres ORDER BY id LIMIT 1')->fetch();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nom = trim($_POST['nom_etablissement'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $telFixe = trim($_POST['tel_fixe'] ?? '');
    $telMobile = trim($_POST['tel_mobile'] ?? '');
    $fax = trim($_POST['fax'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($nom === '') {
        $errors[] = 'اسم المؤسسة مطلوب.';
    }

    $logo = $item['logo'];
    if (!$errors && !empty($_FILES['logo']['name'])) {
        try {
            $nomFichier = $_FILES['logo']['name'];
            $ext = strtolower(pathinfo($nomFichier, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg'], true)) {
                $errors[] = 'صيغة الشعار غير مدعومة.';
            } else {
                $destDir = BASE_PATH . '/images';
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                $newName = 'logo_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (!move_uploaded_file($_FILES['logo']['tmp_name'], $destDir . '/' . $newName)) {
                    $errors[] = 'تعذر حفظ الشعار.';
                } else {
                    $logo = $newName;
                }
            }
        } catch (Throwable $e) {
            $errors[] = 'حدث خطأ أثناء رفع الشعار.';
        }
    }

    if (!$errors) {
        db()->prepare('UPDATE parametres SET nom_etablissement = ?, logo = ?, adresse = ?, tel_fixe = ?, tel_mobile = ?, fax = ?, email = ? WHERE id = ?')
            ->execute([$nom, $logo, $adresse, $telFixe, $telMobile, $fax, $email, $item['id']]);
        cache_delete('parametres');
        log_historique('modification', 'parametres', 'Mise à jour des paramètres');
        flash_set('success', 'تم التعديل بنجاح.');
        redirect('edit.php');
    }
    $item = array_merge($item, $_POST, ['logo' => $logo]);
}

$pageTitle = 'المعلمات';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-gear"></i> معلمات المؤسسة</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">اسم المؤسسة</label>
        <input type="text" name="nom_etablissement" class="form-control" required value="<?= e($item['nom_etablissement']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">الشعار</label><br>
        <?php if (!empty($item['logo'])): ?>
          <img src="<?= BASE_URL ?>/images/<?= e($item['logo']) ?>" alt="logo" height="48" class="mb-2"><br>
        <?php endif; ?>
        <input type="file" name="logo" class="form-control" accept="image/*">
      </div>
      <div class="mb-3">
        <label class="form-label">العنوان</label>
        <input type="text" name="adresse" class="form-control" value="<?= e($item['adresse']) ?>">
      </div>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">الهاتف الثابت</label>
          <input type="text" name="tel_fixe" class="form-control" value="<?= e($item['tel_fixe']) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">الهاتف المحمول</label>
          <input type="text" name="tel_mobile" class="form-control" value="<?= e($item['tel_mobile']) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">الفاكس</label>
          <input type="text" name="fax" class="form-control" value="<?= e($item['fax']) ?>">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">البريد الإلكتروني</label>
        <input type="email" name="email" class="form-control" value="<?= e($item['email']) ?>">
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
