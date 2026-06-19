<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('transfert.add');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $dateTrans = trim($_POST['date_trans'] ?? '');
    $serviceDest = (int) ($_POST['service_dest'] ?? 0);
    $employeDest = (int) ($_POST['employe_dest'] ?? 0);
    $serviceOrigin = (int) ($_POST['service_origin'] ?? 0);
    $employeOrigin = (int) ($_POST['employe_origin'] ?? 0);
    $lignes = json_decode($_POST['lignes_json'] ?? '[]', true) ?: [];

    if ($dateTrans === '' || $serviceDest <= 0 || $employeDest <= 0 || $serviceOrigin <= 0 || $employeOrigin <= 0) {
        $errors[] = 'التاريخ والخدمة/الموظف المستقبل والمصدر مطلوبون.';
    }
    if (!$lignes) {
        $errors[] = 'يجب إضافة سطر واحد على الأقل.';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $refTrans = generate_numero('TR');
            $numBordereau = generate_numero('BR');
            $nbDoc = count($lignes);
            $nbBoite = count(array_unique(array_filter(array_column($lignes, 'num_boite'))));
            $metrage = array_sum(array_column($lignes, 'metrage_lin'));

            $pdo->prepare(
                'INSERT INTO transfert (date_trans, ref_trans, num_bordereau, nb_doc, nb_boite, metrage_lin,
                    service_dest, employe_dest, service_origin, employe_origin)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$dateTrans, $refTrans, $numBordereau, $nbDoc, $nbBoite, $metrage, $serviceDest, $employeDest, $serviceOrigin, $employeOrigin]);
            $idTrans = (int) $pdo->lastInsertId();

            $stmtLigne = $pdo->prepare(
                'INSERT INTO ligne_transfert (id_trans, archive_id, titre_dossier, num_boite, num_depot, num_etagere,
                    num_plaque, emplacement, annee_min, annee_max, ref_classification, titre_classfication,
                    carac_ideologique_id, carac_temporelle_id, carac_geographique_id, type_doc_id, duree_conser,
                    sort_fin_doc_id, etat_archive)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmtMaj = $pdo->prepare("UPDATE archive SET etat_archive = 'Transféré' WHERE id = ?");

            foreach ($lignes as $l) {
                $archiveId = !empty($l['archive_id']) ? (int) $l['archive_id'] : null;
                $stmtLigne->execute([
                    $idTrans, $archiveId,
                    $l['titre_dossier'] ?? '', $l['num_boite'] ?? null, $l['num_depot'] ?: null, $l['num_etagere'] ?? null,
                    $l['num_plaque'] ?? null, $l['emplacement'] ?? null, $l['annee_min'] ?: null, $l['annee_max'] ?: null,
                    $l['ref_classification'] ?? null, $l['titre_classfication'] ?? null,
                    $l['carac_ideologique_id'] ?: null, $l['carac_temporelle_id'] ?: null, $l['carac_geographique_id'] ?: null,
                    $l['type_doc_id'] ?: null, $l['duree_conser'] ?? null, $l['sort_fin_doc_id'] ?: null, 'Transféré',
                ]);
                if ($archiveId) {
                    $stmtMaj->execute([$archiveId]);
                }
            }

            $pdo->commit();
            log_historique('ajout', 'transfert', "Ajout du transfert {$refTrans} (BR: {$numBordereau})");
            flash_set('success', 'تمت الإضافة بنجاح.');
            redirect('view.php?id=' . $idTrans);
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'حدث خطأ أثناء الحفظ: ' . $e->getMessage();
        }
    }
}

$pdo = db();
$services = $pdo->query('SELECT id, nom FROM service ORDER BY nom')->fetchAll();
$depots = $pdo->query('SELECT id_dept, numero FROM depot ORDER BY numero')->fetchAll();
$ideologiques = $pdo->query('SELECT id_ideo, description FROM carac_ideologique ORDER BY description')->fetchAll();
$temporelles = $pdo->query('SELECT id_tmp, description FROM carac_temporelle ORDER BY description')->fetchAll();
$geographiques = $pdo->query('SELECT id_geo, description FROM carac_geographique ORDER BY description')->fetchAll();
$typesDoc = $pdo->query('SELECT id_typ, description FROM type_doc ORDER BY description')->fetchAll();
$sorts = $pdo->query('SELECT id_sort, description FROM sort_fin_doc ORDER BY description')->fetchAll();

$pageTitle = 'إضافة نقل';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-plus-lg"></i> إضافة نقل</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card mb-3">
  <div class="card-body">
    <form method="post" id="trans-form">
      <?= csrf_field() ?>
      <input type="hidden" name="lignes_json" id="lignes_json">
      <div class="row">
        <div class="col-md-2 mb-3">
          <label class="form-label">التاريخ</label>
          <input type="date" name="date_trans" class="form-control" required value="<?= e($_POST['date_trans'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">الخدمة المصدر</label>
          <select name="service_origin" id="service_origin" class="form-select select2" required>
            <option value="">-- اختر --</option>
            <?php foreach ($services as $s): ?>
              <option value="<?= $s['id'] ?>"><?= e($s['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">الموظف المصدر</label>
          <select name="employe_origin" id="employe_origin" class="form-select select2" required>
            <option value="">-- اختر الخدمة أولاً --</option>
          </select>
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">الخدمة المستقبلة</label>
          <select name="service_dest" id="service_dest" class="form-select select2" required>
            <option value="">-- اختر --</option>
            <?php foreach ($services as $s): ?>
              <option value="<?= $s['id'] ?>"><?= e($s['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">الموظف المستقبل</label>
          <select name="employe_dest" id="employe_dest" class="form-select select2" required>
            <option value="">-- اختر الخدمة أولاً --</option>
          </select>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <h6>البحث عن أرشيف لإضافته إلى المذكرة</h6>
    <div class="row g-2 mb-3">
      <div class="col-md-8">
        <input type="text" id="archive-search" class="form-control" placeholder="بحث بعنوان الملف أو رقم الصندوق...">
      </div>
      <div class="col-md-4">
        <button type="button" id="btn-archive-search" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> بحث</button>
      </div>
    </div>
    <div id="archive-search-results"></div>
    <hr>
    <button type="button" id="btn-add-manual-ligne" class="btn btn-outline-secondary"><i class="bi bi-plus-lg"></i> إضافة سطر يدويًا</button>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-bordered table-sm" id="lignes-table">
      <thead>
        <tr>
          <th>عنوان الملف</th><th>رقم الصندوق</th><th>المستودع</th><th>الرف</th><th>اللوحة</th>
          <th>الموقع</th><th>التصنيف</th><th>إجراءات</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
    <button type="submit" form="trans-form" id="btn-save" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
    <a href="list.php" class="btn btn-secondary">إلغاء</a>
  </div>
</div>

<script>
const depots = <?= json_encode($depots, JSON_UNESCAPED_UNICODE) ?>;
let lignes = [];

function cascadeService(serviceSelId, employeSelId) {
  document.getElementById(serviceSelId).addEventListener('change', function () {
    const employeSelect = document.getElementById(employeSelId);
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
cascadeService('service_origin', 'employe_origin');
cascadeService('service_dest', 'employe_dest');

function renderLignes() {
  const tbody = document.querySelector('#lignes-table tbody');
  tbody.innerHTML = '';
  lignes.forEach((l, idx) => {
    const tr = document.createElement('tr');
    tr.innerHTML = '<td>' + (l.titre_dossier || '') + '</td>' +
      '<td>' + (l.num_boite || '') + '</td>' +
      '<td>' + (l.depot_label || '') + '</td>' +
      '<td>' + (l.num_etagere || '') + '</td>' +
      '<td>' + (l.num_plaque || '') + '</td>' +
      '<td>' + (l.emplacement || '') + '</td>' +
      '<td>' + (l.ref_classification || '') + '</td>' +
      '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-ligne" data-idx="' + idx + '"><i class="bi bi-trash"></i></button></td>';
    tbody.appendChild(tr);
  });
  document.querySelectorAll('.btn-remove-ligne').forEach(btn => {
    btn.addEventListener('click', function () {
      lignes.splice(parseInt(this.getAttribute('data-idx'), 10), 1);
      renderLignes();
    });
  });
  document.getElementById('lignes_json').value = JSON.stringify(lignes);
}

document.getElementById('btn-archive-search').addEventListener('click', function () {
  const q = document.getElementById('archive-search').value.trim();
  if (!q) return;
  fetch(window.BASE_URL + '/modules/transfert/archive_lookup_ajax.php?q=' + encodeURIComponent(q))
    .then(r => r.json())
    .then(data => {
      const box = document.getElementById('archive-search-results');
      if (!data.length) {
        box.innerHTML = '<div class="text-muted">لا توجد نتائج</div>';
        return;
      }
      let html = '<table class="table table-sm table-hover"><tbody>';
      data.forEach(it => {
        html += '<tr><td>' + it.titre_dossier + ' (' + (it.num_boite || '') + ')</td><td><button type="button" class="btn btn-sm btn-outline-primary btn-pick-archive" data-json=\'' + JSON.stringify(it).replace(/'/g, '&#39;') + '\'>إضافة</button></td></tr>';
      });
      html += '</tbody></table>';
      box.innerHTML = html;
      document.querySelectorAll('.btn-pick-archive').forEach(btn => {
        btn.addEventListener('click', function () {
          const data = JSON.parse(this.getAttribute('data-json').replace(/&#39;/g, "'"));
          const depot = depots.find(d => d.id_dept == data.num_depot);
          data.depot_label = depot ? depot.numero : '';
          lignes.push(data);
          renderLignes();
        });
      });
    });
});

document.getElementById('btn-add-manual-ligne').addEventListener('click', function () {
  const titre = prompt('عنوان الملف:');
  if (!titre) return;
  lignes.push({ archive_id: null, titre_dossier: titre, num_boite: '', num_depot: '', depot_label: '', num_etagere: '', num_plaque: '', emplacement: '', ref_classification: '' });
  renderLignes();
});

document.getElementById('trans-form').addEventListener('submit', function () {
  document.getElementById('lignes_json').value = JSON.stringify(lignes);
});
</script>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
