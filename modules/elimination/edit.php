<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('elimination.edit');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM elimination WHERE id_elm = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $dateElm = trim($_POST['date_elm'] ?? '');
    $numeroVisa = trim($_POST['numero_visa'] ?? '');
    $dateVisa = trim($_POST['date_visa'] ?? '') ?: null;
    $serviceId = (int) ($_POST['service'] ?? 0);
    $employeId = (int) ($_POST['employe'] ?? 0);
    $institutionId = (int) ($_POST['institution'] ?? 0) ?: null;
    $responsable = trim($_POST['responsable_reception'] ?? '');
    $president = trim($_POST['president_commission'] ?? '');
    $membres = trim($_POST['membres_commission'] ?? '');

    if ($dateElm === '' || $serviceId <= 0 || $employeId <= 0) {
        $errors[] = 'التاريخ والخدمة والموظف مطلوبون.';
    }

    if (!$errors) {
        db()->prepare(
            'UPDATE elimination SET date_elm=?, numero_visa=?, date_visa=?, service=?, employe=?, institution=?,
                responsable_reception=?, president_commission=?, membres_commission=? WHERE id_elm=?'
        )->execute([$dateElm, $numeroVisa, $dateVisa, $serviceId, $employeId, $institutionId, $responsable, $president, $membres, $id]);
        log_historique('modification', 'elimination', "Modification de l'élimination #{$id}");
        flash_set('success', 'تم التعديل بنجاح.');
        redirect('view.php?id=' . $id);
    }
    $item = array_merge($item, $_POST);
}

$pdo = db();
$services = $pdo->query('SELECT id, nom FROM service ORDER BY nom')->fetchAll();
$employes = $pdo->prepare('SELECT id, nom, renom FROM employe WHERE service_id = ? ORDER BY nom');
$employes->execute([$item['service']]);
$employes = $employes->fetchAll();
$institutions = $pdo->query('SELECT id_ins, description FROM institution ORDER BY description')->fetchAll();

$pageTitle = 'تعديل إتلاف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-pencil"></i> تعديل إتلاف</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <div class="row">
        <div class="col-md-2 mb-3">
          <label class="form-label">التاريخ</label>
          <input type="date" name="date_elm" class="form-control" required value="<?= e($item['date_elm']) ?>">
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">رقم التأشيرة</label>
          <input type="text" name="numero_visa" class="form-control" value="<?= e($item['numero_visa']) ?>">
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">تاريخ التأشيرة</label>
          <input type="date" name="date_visa" class="form-control" value="<?= e($item['date_visa']) ?>">
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">الخدمة</label>
          <select name="service" id="service" class="form-select select2" required>
            <?php foreach ($services as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $item['service'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">الموظف</label>
          <select name="employe" id="employe" class="form-select select2" required>
            <?php foreach ($employes as $emp): ?>
              <option value="<?= $emp['id'] ?>" <?= $item['employe'] == $emp['id'] ? 'selected' : '' ?>><?= e($emp['nom']) ?> <?= e($emp['renom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">المؤسسة</label>
          <select name="institution" class="form-select select2">
            <option value="">--</option>
            <?php foreach ($institutions as $it): ?>
              <option value="<?= $it['id_ins'] ?>" <?= $item['institution'] == $it['id_ins'] ? 'selected' : '' ?>><?= e($it['description']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">المسؤول عن الاستلام</label>
          <input type="text" name="responsable_reception" class="form-control" value="<?= e($item['responsable_reception']) ?>">
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">رئيس اللجنة</label>
          <input type="text" name="president_commission" class="form-control" value="<?= e($item['president_commission']) ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">أعضاء اللجنة</label>
          <textarea name="membres_commission" class="form-control" rows="2"><?= e($item['membres_commission']) ?></textarea>
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
      <a href="lignes.php?id=<?= $id ?>" class="btn btn-outline-primary"><i class="bi bi-list"></i> الوثائق</a>
      <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">إلغاء</a>
    </form>
  </div>
</div>
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
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
