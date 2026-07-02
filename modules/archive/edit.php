<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('archive.edit');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM archive WHERE id = ?');
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
        'date_archive' => trim($_POST['date_archive'] ?? ''),
        'service_id' => (int) ($_POST['service_id'] ?? 0),
        'employe_id' => (int) ($_POST['employe_id'] ?? 0),
        'service_origin' => (int) ($_POST['service_origin'] ?? 0) ?: null,
        'employe_origin' => (int) ($_POST['employe_origin'] ?? 0) ?: null,
        'titre_dossier' => trim($_POST['titre_dossier'] ?? ''),
        'num_boite' => trim($_POST['num_boite'] ?? ''),
        'num_depot' => (int) ($_POST['num_depot'] ?? 0),
        'num_etagere' => trim($_POST['num_etagere'] ?? ''),
        'num_plaque' => trim($_POST['num_plaque'] ?? ''),
        'emplacement' => trim($_POST['emplacement'] ?? ''),
        'annee_min' => (int) ($_POST['annee_min'] ?? 0) ?: null,
        'annee_max' => (int) ($_POST['annee_max'] ?? 0) ?: null,
        'ref_classification' => trim($_POST['ref_classification'] ?? ''),
        'titre_classfication' => trim($_POST['titre_classfication'] ?? ''),
        'carac_ideologique_id' => (int) ($_POST['carac_ideologique_id'] ?? 0) ?: null,
        'carac_temporelle_id' => (int) ($_POST['carac_temporelle_id'] ?? 0) ?: null,
        'carac_geographique_id' => (int) ($_POST['carac_geographique_id'] ?? 0) ?: null,
        'type_doc_id' => (int) ($_POST['type_doc_id'] ?? 0) ?: null,
        'duree_conser' => trim($_POST['duree_conser'] ?? ''),
        'sort_fin_doc_id' => (int) ($_POST['sort_fin_doc_id'] ?? 0) ?: null,
        'etat_archive' => trim($_POST['etat_archive'] ?? 'Disponible'),
        'notes' => trim($_POST['notes'] ?? ''),
    ];

    if ($data['date_archive'] === '' || $data['service_id'] <= 0 || $data['employe_id'] <= 0
        || $data['titre_dossier'] === '' || $data['num_depot'] <= 0 || $data['ref_classification'] === '') {
        $errors[] = 'الحقول الأساسية مطلوبة.';
    }

    $fichier = $item['fichier'];
    if (!$errors && !empty($_FILES['fichier']['name'])) {
        try {
            $fichier = upload_fichier($_FILES['fichier']);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'UPDATE archive SET date_archive=:date_archive, service_id=:service_id, employe_id=:employe_id,
                service_origin=:service_origin, employe_origin=:employe_origin, titre_dossier=:titre_dossier, num_boite=:num_boite, num_depot=:num_depot, num_etagere=:num_etagere,
                num_plaque=:num_plaque, emplacement=:emplacement, annee_min=:annee_min, annee_max=:annee_max,
                ref_classification=:ref_classification, titre_classfication=:titre_classfication,
                carac_ideologique_id=:carac_ideologique_id, carac_temporelle_id=:carac_temporelle_id,
                carac_geographique_id=:carac_geographique_id, type_doc_id=:type_doc_id, duree_conser=:duree_conser,
                sort_fin_doc_id=:sort_fin_doc_id, etat_archive=:etat_archive, fichier=:fichier, notes=:notes
             WHERE id=:id'
        );
        $stmt->execute($data + ['fichier' => $fichier, 'id' => $id]);
        log_historique('modification', 'archive', "Modification de l'archive #{$id}");
        flash_set('success', 'تم التعديل بنجاح.');
        redirect('list.php');
    }
    $item = array_merge($item, $_POST);
}

$pdo = db();
$services = $pdo->query('SELECT id, nom FROM service ORDER BY nom')->fetchAll();
$employes = $pdo->prepare('SELECT id, nom, renom FROM employe WHERE service_id = ? ORDER BY nom');
$employes->execute([$item['service_id']]);
$employes = $employes->fetchAll();
$employesOrigin = [];
if (!empty($item['service_origin'])) {
    $stmtEO = $pdo->prepare('SELECT id, nom, renom FROM employe WHERE service_id = ? ORDER BY nom');
    $stmtEO->execute([$item['service_origin']]);
    $employesOrigin = $stmtEO->fetchAll();
}
$depots = $pdo->query('SELECT id_dept, numero FROM depot ORDER BY numero')->fetchAll();
$ideologiques = $pdo->query('SELECT id_ideo, description FROM carac_ideologique ORDER BY description')->fetchAll();
$temporelles = $pdo->query('SELECT id_tmp, description FROM carac_temporelle ORDER BY description')->fetchAll();
$geographiques = $pdo->query('SELECT id_geo, description FROM carac_geographique ORDER BY description')->fetchAll();
$typesDoc = $pdo->query('SELECT id_typ, description FROM type_doc ORDER BY description')->fetchAll();
$sorts = $pdo->query('SELECT id_sort, description FROM sort_fin_doc ORDER BY description')->fetchAll();
$etats = $pdo->query('SELECT etat FROM etat_archive ORDER BY etat')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'تعديل أرشيف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-pencil"></i> تعديل أرشيف</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">التاريخ</label>
          <input type="date" name="date_archive" class="form-control" required value="<?= e($item['date_archive']) ?>">
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">الخدمة</label>
          <select name="service_id" id="service_id" class="form-select select2" required>
            <?php foreach ($services as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $item['service_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">الموظف</label>
          <select name="employe_id" id="employe_id" class="form-select select2" required>
            <?php foreach ($employes as $emp): ?>
              <option value="<?= $emp['id'] ?>" <?= $item['employe_id'] == $emp['id'] ? 'selected' : '' ?>><?= e($emp['nom']) ?> <?= e($emp['renom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">خدمة المصدر</label>
          <select name="service_origin" id="service_origin" class="form-select select2">
            <option value="">-- اختر --</option>
            <?php foreach ($services as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $item['service_origin'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">موظف المصدر</label>
          <select name="employe_origin" id="employe_origin" class="form-select select2">
            <option value="">-- اختر --</option>
            <?php foreach ($employesOrigin as $emp): ?>
              <option value="<?= $emp['id'] ?>" <?= $item['employe_origin'] == $emp['id'] ? 'selected' : '' ?>><?= e($emp['nom']) ?> <?= e($emp['renom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">عنوان الملف</label>
          <input type="text" name="titre_dossier" class="form-control" required value="<?= e($item['titre_dossier']) ?>">
        </div>
      </div>
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">رقم الصندوق</label>
          <input type="text" name="num_boite" class="form-control" value="<?= e($item['num_boite']) ?>">
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">المستودع</label>
          <select name="num_depot" class="form-select select2" required>
            <?php foreach ($depots as $d): ?>
              <option value="<?= $d['id_dept'] ?>" <?= $item['num_depot'] == $d['id_dept'] ? 'selected' : '' ?>><?= e($d['numero']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">الرف</label>
          <input type="text" name="num_etagere" class="form-control" value="<?= e($item['num_etagere']) ?>">
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">اللوحة</label>
          <input type="text" name="num_plaque" class="form-control" value="<?= e($item['num_plaque']) ?>">
        </div>
      </div>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">الموقع</label>
          <input type="text" name="emplacement" class="form-control" value="<?= e($item['emplacement']) ?>">
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">من سنة</label>
          <input type="number" name="annee_min" class="form-control" value="<?= e((string) $item['annee_min']) ?>">
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">إلى سنة</label>
          <input type="number" name="annee_max" class="form-control" value="<?= e((string) $item['annee_max']) ?>">
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">مرجع التصنيف</label>
          <input type="text" name="ref_classification" id="ref_classification" class="form-control" required value="<?= e($item['ref_classification']) ?>">
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">عنوان التصنيف</label>
          <input type="text" name="titre_classfication" id="titre_classfication" class="form-control" readonly value="<?= e($item['titre_classfication']) ?>">
        </div>
      </div>
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">الخاصية الإيديولوجية</label>
          <select name="carac_ideologique_id" class="form-select select2">
            <option value="">--</option>
            <?php foreach ($ideologiques as $it): ?>
              <option value="<?= $it['id_ideo'] ?>" <?= $item['carac_ideologique_id'] == $it['id_ideo'] ? 'selected' : '' ?>><?= e($it['description']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">الخاصية الزمنية</label>
          <select name="carac_temporelle_id" class="form-select select2">
            <option value="">--</option>
            <?php foreach ($temporelles as $it): ?>
              <option value="<?= $it['id_tmp'] ?>" <?= $item['carac_temporelle_id'] == $it['id_tmp'] ? 'selected' : '' ?>><?= e($it['description']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">الخاصية الجغرافية</label>
          <select name="carac_geographique_id" class="form-select select2">
            <option value="">--</option>
            <?php foreach ($geographiques as $it): ?>
              <option value="<?= $it['id_geo'] ?>" <?= $item['carac_geographique_id'] == $it['id_geo'] ? 'selected' : '' ?>><?= e($it['description']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">نوع الوثيقة</label>
          <select name="type_doc_id" class="form-select select2">
            <option value="">--</option>
            <?php foreach ($typesDoc as $it): ?>
              <option value="<?= $it['id_typ'] ?>" <?= $item['type_doc_id'] == $it['id_typ'] ? 'selected' : '' ?>><?= e($it['description']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">مدة الحفظ</label>
          <input type="text" name="duree_conser" class="form-control" value="<?= e($item['duree_conser']) ?>">
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">مصير الوثيقة</label>
          <select name="sort_fin_doc_id" class="form-select select2">
            <option value="">--</option>
            <?php foreach ($sorts as $it): ?>
              <option value="<?= $it['id_sort'] ?>" <?= $item['sort_fin_doc_id'] == $it['id_sort'] ? 'selected' : '' ?>><?= e($it['description']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">الحالة</label>
          <select name="etat_archive" class="form-select select2">
            <?php foreach ($etats as $it): ?>
              <option value="<?= e($it) ?>" <?= $item['etat_archive'] === $it ? 'selected' : '' ?>><?= e($it) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">الملف الحالي</label>
          <div>
            <?php if ($item['fichier']): ?>
              <a href="download.php?id=<?= $id ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i> الملف الحالي</a>
            <?php else: ?>
              <span class="text-muted">لا يوجد ملف</span>
            <?php endif; ?>
          </div>
          <input type="file" name="fichier" class="form-control mt-2">
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
<script>
document.getElementById('service_id').addEventListener('change', function () {
  const employeSelect = document.getElementById('employe_id');
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

document.getElementById('service_origin').addEventListener('change', function () {
  const sel = document.getElementById('employe_origin');
  sel.innerHTML = '<option value="">جاري التحميل...</option>';
  fetch(window.BASE_URL + '/modules/employe/by_service_ajax.php?service_id=' + this.value)
    .then(r => r.json())
    .then(data => {
      sel.innerHTML = '<option value="">-- اختر --</option>';
      data.forEach(it => {
        const opt = document.createElement('option');
        opt.value = it.id;
        opt.textContent = it.text;
        sel.appendChild(opt);
      });
    });
});

document.getElementById('ref_classification').addEventListener('change', function () {
  const ref = this.value.trim();
  if (!ref) return;
  fetch(window.BASE_URL + '/modules/classification/lookup_ajax.php?ref=' + encodeURIComponent(ref))
    .then(r => r.json())
    .then(data => {
      document.getElementById('titre_classfication').value = data.titre || '';
    });
});
</script>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
