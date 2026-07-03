<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('versement.edit');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM versement WHERE id_vers = ?');
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
        'date_vers' => trim($_POST['date_vers'] ?? ''),
        'ref_vers' => trim($_POST['ref_vers'] ?? ''),
        'service' => (int) ($_POST['service'] ?? 0),
        'employe' => (int) ($_POST['employe'] ?? 0),
        'institution' => (int) ($_POST['institution'] ?? 0) ?: null,
        'responsable_reception' => trim($_POST['responsable_reception'] ?? ''),
    ];

    if ($data['date_vers'] === '' || $data['ref_vers'] === '' || $data['service'] <= 0 || $data['employe'] <= 0) {
        $errors[] = 'جميع الحقول الأساسية مطلوبة.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'UPDATE versement SET date_vers=:date_vers, ref_vers=:ref_vers, service=:service, employe=:employe,
                institution=:institution, responsable_reception=:responsable_reception
             WHERE id_vers=:id'
        );
        $stmt->execute($data + ['id' => $id]);
        log_historique('modification', 'versement', "Modification du versement #{$id}");
        flash_set('success', 'تم التعديل بنجاح.');
        redirect('list.php');
    }
    $item = array_merge($item, $_POST);
}

$pdo = db();
$services = $pdo->query('SELECT id, nom FROM service ORDER BY nom')->fetchAll();
$institutions = $pdo->query('SELECT id_ins, description FROM institution ORDER BY description')->fetchAll();
$employes = $pdo->prepare('SELECT id, nom, renom FROM employe WHERE service_id = ? ORDER BY nom');
$employes->execute([$item['service']]);
$employes = $employes->fetchAll();

$pageTitle = 'تعديل إيداع';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-pencil"></i> تعديل إيداع</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">التاريخ</label>
          <input type="date" name="date_vers" class="form-control" required value="<?= e($item['date_vers']) ?>">
        </div>
        <div class="col-md-5 mb-3">
          <label class="form-label">المرجع</label>
          <input type="text" name="ref_vers" class="form-control" required value="<?= e($item['ref_vers']) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">رقم المذكرة</label>
          <input type="text" class="form-control" value="<?= e($item['num_bordereau']) ?>" readonly>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">الخدمة</label>
          <select name="service" id="service" class="form-select select2" required>
            <?php foreach ($services as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $item['service'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">الموظف</label>
          <select name="employe" id="employe" class="form-select select2" required>
            <?php foreach ($employes as $emp): ?>
              <option value="<?= $emp['id'] ?>" <?= $item['employe'] == $emp['id'] ? 'selected' : '' ?>><?= e($emp['nom']) ?> <?= e($emp['renom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">المؤسسة</label>
          <select name="institution" class="form-select select2">
            <option value="">--</option>
            <?php foreach ($institutions as $i): ?>
              <option value="<?= $i['id_ins'] ?>" <?= $item['institution'] == $i['id_ins'] ? 'selected' : '' ?>><?= e($i['description']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">المسؤول عن الاستلام</label>
          <input type="text" name="responsable_reception" class="form-control" value="<?= e($item['responsable_reception']) ?>">
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
document.getElementById('service').addEventListener('change', function () {
  const employeSelect = document.getElementById('employe');
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
</script>
