<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('transfert.edit');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM transfert WHERE id_trans = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data = [
        'date_trans' => trim($_POST['date_trans'] ?? ''),
        'ref_trans' => trim($_POST['ref_trans'] ?? ''),
        'service_dest' => (int) ($_POST['service_dest'] ?? 0),
        'employe_dest' => (int) ($_POST['employe_dest'] ?? 0),
        'service_origin' => (int) ($_POST['service_origin'] ?? 0),
        'employe_origin' => (int) ($_POST['employe_origin'] ?? 0),
    ];

    if ($data['date_trans'] === '' || $data['ref_trans'] === '' || $data['service_dest'] <= 0
        || $data['employe_dest'] <= 0 || $data['service_origin'] <= 0 || $data['employe_origin'] <= 0) {
        $errors[] = 'جميع الحقول الأساسية مطلوبة.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'UPDATE transfert SET date_trans=:date_trans, ref_trans=:ref_trans, service_dest=:service_dest,
                employe_dest=:employe_dest, service_origin=:service_origin, employe_origin=:employe_origin
             WHERE id_trans=:id'
        );
        $stmt->execute($data + ['id' => $id]);
        log_historique('modification', 'transfert', "Modification du transfert #{$id}");
        flash_set('success', 'تم التعديل بنجاح.');
        redirect('list.php');
    }
    $item = array_merge($item, $_POST);
}

$pdo = db();
$services = $pdo->query('SELECT id, nom FROM service ORDER BY nom')->fetchAll();
$employesOrigin = $pdo->prepare('SELECT id, nom, renom FROM employe WHERE service_id = ? ORDER BY nom');
$employesOrigin->execute([$item['service_origin']]);
$employesOrigin = $employesOrigin->fetchAll();
$employesDest = $pdo->prepare('SELECT id, nom, renom FROM employe WHERE service_id = ? ORDER BY nom');
$employesDest->execute([$item['service_dest']]);
$employesDest = $employesDest->fetchAll();

$pageTitle = 'تعديل نقل';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-pencil"></i> تعديل نقل</h4>
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
          <label class="form-label">التاريخ</label>
          <input type="date" name="date_trans" class="form-control" required value="<?= e($item['date_trans']) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">المرجع</label>
          <input type="text" name="ref_trans" class="form-control" required value="<?= e($item['ref_trans']) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">رقم المذكرة</label>
          <input type="text" class="form-control" value="<?= e($item['num_bordereau']) ?>" readonly>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">الخدمة المصدر</label>
          <select name="service_origin" id="service_origin" class="form-select select2" required>
            <?php foreach ($services as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $item['service_origin'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">الموظف المصدر</label>
          <select name="employe_origin" id="employe_origin" class="form-select select2" required>
            <?php foreach ($employesOrigin as $emp): ?>
              <option value="<?= $emp['id'] ?>" <?= $item['employe_origin'] == $emp['id'] ? 'selected' : '' ?>><?= e($emp['nom']) ?> <?= e($emp['renom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">الخدمة المستقبلة</label>
          <select name="service_dest" id="service_dest" class="form-select select2" required>
            <?php foreach ($services as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $item['service_dest'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">الموظف المستقبل</label>
          <select name="employe_dest" id="employe_dest" class="form-select select2" required>
            <?php foreach ($employesDest as $emp): ?>
              <option value="<?= $emp['id'] ?>" <?= $item['employe_dest'] == $emp['id'] ? 'selected' : '' ?>><?= e($emp['nom']) ?> <?= e($emp['renom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="lignes.php?id=<?= $id ?>" class="btn btn-outline-primary"><i class="bi bi-list"></i> الوثائق</a>
      <a href="list.php" class="btn btn-secondary">إلغاء</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
<script>
function bindCascade(serviceSelectId, employeSelectId) {
  document.getElementById(serviceSelectId).addEventListener('change', function () {
    const employeSelect = document.getElementById(employeSelectId);
    employeSelect.innerHTML = '<option value="">جاري التحميل...</option>';
    fetch(window.BASE_URL + '/modules/employe/by_service_ajax.php?service_id=' + this.value)
      .then(r => r.json())
      .then(data => {
        employeSelect.innerHTML = '<option value="">-- اختر --</option>';
        data.forEach(it => {
          const opt = document.createElement('option');
          opt.value = it.id;
          opt.textContent = it.text;
          employeSelect.appendChild(opt);
        });
      });
  });
}
bindCascade('service_origin', 'employe_origin');
bindCascade('service_dest', 'employe_dest');
</script>
