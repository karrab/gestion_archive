<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('elimination.add');

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
    $lignes = json_decode($_POST['lignes_json'] ?? '[]', true) ?: [];

    if ($dateElm === '' || $serviceId <= 0 || $employeId <= 0) {
        $errors[] = 'التاريخ والخدمة والموظف مطلوبون.';
    }
    if (!$lignes) {
        $errors[] = 'يجب إضافة سطر واحد على الأقل.';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $refElm = generate_numero('EL');
            $numeroPv = generate_numero('PV');
            $numBordereau = generate_numero('BR');
            $nbDoc = count($lignes);
            $nbBoite = count(array_unique(array_filter(array_column($lignes, 'num_boite'))));
            $metrage = array_sum(array_column($lignes, 'metrage_lin'));

            $pdo->prepare(
                'INSERT INTO elimination (date_elm, ref_elm, numero_pv, numero_visa, date_visa, num_bordereau,
                    nb_doc, nb_boite, metrage_lin, service, employe, institution, responsable_reception,
                    president_commission, membres_commission)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $dateElm, $refElm, $numeroPv, $numeroVisa, $dateVisa, $numBordereau, $nbDoc, $nbBoite, $metrage,
                $serviceId, $employeId, $institutionId, $responsable, $president, $membres,
            ]);
            $idElm = (int) $pdo->lastInsertId();

            $stmtLigne = $pdo->prepare(
                'INSERT INTO ligne_elimination (id_elm, archive_id, titre_dossier, num_boite, num_depot, num_etagere,
                    num_plaque, emplacement, annee_min, annee_max, ref_classification, titre_classfication,
                    carac_ideologique_id, carac_temporelle_id, carac_geographique_id, type_doc_id, duree_conser,
                    sort_fin_doc_id, etat_archive)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmtMaj = $pdo->prepare("UPDATE archive SET etat_archive = 'Eliminé' WHERE id = ?");

            foreach ($lignes as $l) {
                $archiveId = !empty($l['archive_id']) ? (int) $l['archive_id'] : null;
                $stmtLigne->execute([
                    $idElm, $archiveId,
                    $l['titre_dossier'] ?? '', $l['num_boite'] ?? null, $l['num_depot'] ?: null, $l['num_etagere'] ?? null,
                    $l['num_plaque'] ?? null, $l['emplacement'] ?? null, $l['annee_min'] ?: null, $l['annee_max'] ?: null,
                    $l['ref_classification'] ?? null, $l['titre_classfication'] ?? null,
                    $l['carac_ideologique_id'] ?: null, $l['carac_temporelle_id'] ?: null, $l['carac_geographique_id'] ?: null,
                    $l['type_doc_id'] ?: null, $l['duree_conser'] ?? null, $l['sort_fin_doc_id'] ?: null, 'Eliminé',
                ]);
                if ($archiveId) {
                    $stmtMaj->execute([$archiveId]);
                }
            }

            $pdo->commit();
            log_historique('ajout', 'elimination', "Ajout de l'élimination {$refElm} (PV: {$numeroPv})");
            flash_set('success', 'تمت الإضافة بنجاح.');
            redirect('view.php?id=' . $idElm);
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'حدث خطأ أثناء الحفظ: ' . $e->getMessage();
        }
    }
}

$pdo = db();
$services = $pdo->query('SELECT id, nom FROM service ORDER BY nom')->fetchAll();
$institutions = $pdo->query('SELECT id_ins, description FROM institution ORDER BY description')->fetchAll();
$depots = $pdo->query('SELECT id_dept, numero FROM depot ORDER BY numero')->fetchAll();

$pageTitle = 'إضافة إتلاف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-plus-lg"></i> إضافة إتلاف</h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>
<div class="card mb-3">
  <div class="card-body">
    <form method="post" id="elm-form">
      <?= csrf_field() ?>
      <input type="hidden" name="lignes_json" id="lignes_json">
      <div class="row">
        <div class="col-md-2 mb-3">
          <label class="form-label">التاريخ</label>
          <input type="date" name="date_elm" class="form-control" required value="<?= e($_POST['date_elm'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">رقم التأشيرة</label>
          <input type="text" name="numero_visa" class="form-control" value="<?= e($_POST['numero_visa'] ?? '') ?>">
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">تاريخ التأشيرة</label>
          <input type="date" name="date_visa" class="form-control" value="<?= e($_POST['date_visa'] ?? '') ?>">
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">الخدمة</label>
          <select name="service" id="service" class="form-select select2" required>
            <option value="">-- اختر --</option>
            <?php foreach ($services as $s): ?>
              <option value="<?= $s['id'] ?>"><?= e($s['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">الموظف</label>
          <select name="employe" id="employe" class="form-select select2" required>
            <option value="">-- اختر الخدمة أولاً --</option>
          </select>
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">المؤسسة</label>
          <select name="institution" class="form-select select2">
            <option value="">--</option>
            <?php foreach ($institutions as $it): ?>
              <option value="<?= $it['id_ins'] ?>"><?= e($it['description']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">المسؤول عن الاستلام</label>
          <input type="text" name="responsable_reception" class="form-control" value="<?= e($_POST['responsable_reception'] ?? '') ?>">
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">رئيس اللجنة</label>
          <input type="text" name="president_commission" class="form-control" value="<?= e($_POST['president_commission'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">أعضاء اللجنة</label>
          <textarea name="membres_commission" class="form-control" rows="2"><?= e($_POST['membres_commission'] ?? '') ?></textarea>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <h6>البحث عن أرشيف لإضافته إلى المحضر</h6>
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
    <button type="submit" form="elm-form" id="btn-save" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
    <a href="list.php" class="btn btn-secondary">إلغاء</a>
  </div>
</div>

<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
<script>
const depots = <?= json_encode($depots, JSON_UNESCAPED_UNICODE) ?>;
let lignes = [];

jQuery('#service').on('change', function () {
  const $sel = jQuery('#employe');
  if (!this.value) {
    $sel.html('<option value="">-- اختر الخدمة أولاً --</option>').trigger('change.select2');
    return;
  }
  $sel.html('<option value="">جاري التحميل...</option>').trigger('change.select2');
  fetch(window.BASE_URL + '/modules/employe/by_service_ajax.php?service_id=' + this.value)
    .then(r => {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(data => {
      let html = '<option value="">-- اختر --</option>';
      if (Array.isArray(data) && data.length > 0) {
        data.forEach(it => {
          html += '<option value="' + it.id + '">' + it.text + '</option>';
        });
      } else {
        html += '<option value="" disabled>لا توجد موظفون</option>';
      }
      $sel.html(html).trigger('change.select2');
    })
    .catch(err => {
      console.error('Error loading employees:', err);
      $sel.html('<option value="" disabled>خطأ في التحميل</option>').trigger('change.select2');
    });
});

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
  fetch(window.BASE_URL + '/modules/elimination/archive_lookup_ajax.php?q=' + encodeURIComponent(q))
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

document.getElementById('elm-form').addEventListener('submit', function () {
  document.getElementById('lignes_json').value = JSON.stringify(lignes);
});
</script>
