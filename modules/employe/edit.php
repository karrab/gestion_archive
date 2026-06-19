<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('reference.manage');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM employe WHERE id = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $matricule = trim($_POST['matricule'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $renom = trim($_POST['renom'] ?? '');
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $mail = trim($_POST['mail'] ?? '');
    $tel1 = trim($_POST['tel1'] ?? '');
    $tel2 = trim($_POST['tel2'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($matricule === '' || $nom === '' || $renom === '' || $serviceId <= 0) {
        $errors[] = 'الرقم الوظيفي والاسم واللقب والخدمة مطلوبون.';
    }

    if (!$errors) {
        try {
            db()->prepare('UPDATE employe SET matricule=?, nom=?, renom=?, service_id=?, mail=?, tel1=?, tel2=?, notes=? WHERE id=?')
                ->execute([$matricule, $nom, $renom, $serviceId, $mail, $tel1, $tel2, $notes, $id]);
            log_historique('modification', 'employe', "Modification #{$id}");
            flash_set('success', 'تم التعديل بنجاح.');
            redirect('list.php');
        } catch (PDOException $e) {
            $errors[] = 'هذا الرقم الوظيفي موجود مسبقًا.';
        }
    }
    $item = array_merge($item, $_POST);
}

$services = db()->query('SELECT id, nom FROM service ORDER BY nom')->fetchAll();

$pageTitle = 'تعديل موظف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-pencil"></i> تعديل موظف</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">الرقم الوظيفي</label>
          <input type="text" name="matricule" class="form-control" required value="<?= e($item['matricule']) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">الاسم</label>
          <input type="text" name="nom" class="form-control" required value="<?= e($item['nom']) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">اللقب</label>
          <input type="text" name="renom" class="form-control" required value="<?= e($item['renom']) ?>">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">الخدمة</label>
        <select name="service_id" class="form-select select2" required>
          <option value="">-- اختر --</option>
          <?php foreach ($services as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $item['service_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">البريد الإلكتروني</label>
          <input type="email" name="mail" class="form-control" value="<?= e($item['mail']) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">الهاتف 1</label>
          <input type="text" name="tel1" class="form-control" value="<?= e($item['tel1']) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">الهاتف 2</label>
          <input type="text" name="tel2" class="form-control" value="<?= e($item['tel2']) ?>">
        </div>
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
