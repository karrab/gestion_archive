<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('versement.edit');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM versement WHERE id_vers = ?');
$stmt->execute([$id]);
$vers = $stmt->fetch();
if (!$vers) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_ligne') {
    csrf_verify();
    $archiveId = !empty($_POST['archive_id']) ? (int) $_POST['archive_id'] : null;
    $titre = trim($_POST['titre_dossier'] ?? '');
    if ($titre === '') {
        $errors[] = 'عنوان الملف مطلوب.';
    } else {
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO ligne_versement (id_vers, archive_id, titre_dossier, num_boite, num_depot, num_etagere,
                    num_plaque, emplacement, annee_min, annee_max, ref_classification, titre_classfication,
                    carac_ideologique_id, carac_temporelle_id, carac_geographique_id, type_doc_id, duree_conser,
                    sort_fin_doc_id, etat_archive)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $id, $archiveId, $titre,
                trim($_POST['num_boite'] ?? '') ?: null,
                (int) ($_POST['num_depot'] ?? 0) ?: null,
                trim($_POST['num_etagere'] ?? '') ?: null,
                trim($_POST['num_plaque'] ?? '') ?: null,
                trim($_POST['emplacement'] ?? '') ?: null,
                (int) ($_POST['annee_min'] ?? 0) ?: null,
                (int) ($_POST['annee_max'] ?? 0) ?: null,
                trim($_POST['ref_classification'] ?? '') ?: null,
                trim($_POST['titre_classfication'] ?? '') ?: null,
                (int) ($_POST['carac_ideologique_id'] ?? 0) ?: null,
                (int) ($_POST['carac_temporelle_id'] ?? 0) ?: null,
                (int) ($_POST['carac_geographique_id'] ?? 0) ?: null,
                (int) ($_POST['type_doc_id'] ?? 0) ?: null,
                trim($_POST['duree_conser'] ?? '') ?: null,
                (int) ($_POST['sort_fin_doc_id'] ?? 0) ?: null,
                'Versé',
            ]);
            if ($archiveId) {
                $pdo->prepare("UPDATE archive SET etat_archive = 'Versé' WHERE id = ?")->execute([$archiveId]);
            }
            recalculer_versement($pdo, $id);
            $pdo->commit();
            log_historique('ajout', 'versement', "Ajout d'une ligne au versement #{$id}");
            flash_set('success', 'تمت إضافة الوثيقة بنجاح.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'حدث خطأ أثناء الحفظ.';
        }
        redirect('lignes.php?id=' . $id);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_ligne') {
    csrf_verify();
    $ligneId = (int) ($_POST['id_ligvers'] ?? 0);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT archive_id FROM ligne_versement WHERE id_ligvers = ? AND id_vers = ?');
        $stmt->execute([$ligneId, $id]);
        $archiveId = $stmt->fetchColumn();

        $pdo->prepare('DELETE FROM ligne_versement WHERE id_ligvers = ? AND id_vers = ?')->execute([$ligneId, $id]);
        if ($archiveId) {
            $pdo->prepare("UPDATE archive SET etat_archive = 'Disponible' WHERE id = ?")->execute([$archiveId]);
        }
        recalculer_versement($pdo, $id);
        $pdo->commit();
        log_historique('suppression', 'versement', "Suppression d'une ligne du versement #{$id}");
        flash_set('success', 'تم الحذف بنجاح.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash_set('danger', 'حدث خطأ أثناء الحذف.');
    }
    redirect('lignes.php?id=' . $id);
}

function recalculer_versement(PDO $pdo, int $idVers): void
{
    $stmt = $pdo->prepare('SELECT num_boite FROM ligne_versement WHERE id_vers = ?');
    $stmt->execute([$idVers]);
    $rows = $stmt->fetchAll();
    $nbDoc = count($rows);
    $nbBoite = count(array_unique(array_filter(array_column($rows, 'num_boite'))));
    $pdo->prepare('UPDATE versement SET nb_doc = ?, nb_boite = ? WHERE id_vers = ?')->execute([$nbDoc, $nbBoite, $idVers]);
}

$lignes = $pdo->prepare('SELECT * FROM ligne_versement WHERE id_vers = ? ORDER BY id_ligvers');
$lignes->execute([$id]);
$lignes = $lignes->fetchAll();

$depots = $pdo->query('SELECT id_dept, numero FROM depot ORDER BY numero')->fetchAll();

$pageTitle = 'وثائق الإيداع';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-list"></i> وثائق الإيداع رقم <?= e($vers['num_bordereau']) ?></h4>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card mb-3">
  <div class="card-body">
    <h6>البحث عن أرشيف لإضافته</h6>
    <div class="row g-2 mb-3">
      <div class="col-md-8">
        <input type="text" id="archive-search" class="form-control" placeholder="بحث بعنوان الملف أو رقم الصندوق...">
      </div>
      <div class="col-md-4">
        <button type="button" id="btn-archive-search" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> بحث</button>
      </div>
    </div>
    <div id="archive-search-results"></div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header">إضافة سطر يدويًا</div>
  <div class="card-body">
    <form method="post" id="manual-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_ligne">
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <input type="hidden" name="archive_id" id="m_archive_id" value="">
      <div class="row">
        <div class="col-md-3 mb-2"><label class="form-label small">عنوان الملف</label><input type="text" name="titre_dossier" id="m_titre_dossier" class="form-control form-control-sm" required></div>
        <div class="col-md-2 mb-2"><label class="form-label small">رقم الصندوق</label><input type="text" name="num_boite" id="m_num_boite" class="form-control form-control-sm"></div>
        <div class="col-md-2 mb-2">
          <label class="form-label small">المستودع</label>
          <select name="num_depot" id="m_num_depot" class="form-select form-select-sm">
            <option value="">--</option>
            <?php foreach ($depots as $d): ?><option value="<?= $d['id_dept'] ?>"><?= e($d['numero']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 mb-2"><label class="form-label small">الرف</label><input type="text" name="num_etagere" id="m_num_etagere" class="form-control form-control-sm"></div>
        <div class="col-md-2 mb-2"><label class="form-label small">اللوحة</label><input type="text" name="num_plaque" id="m_num_plaque" class="form-control form-control-sm"></div>
        <div class="col-md-3 mb-2"><label class="form-label small">الموقع</label><input type="text" name="emplacement" id="m_emplacement" class="form-control form-control-sm"></div>
        <div class="col-md-2 mb-2"><label class="form-label small">مرجع التصنيف</label><input type="text" name="ref_classification" id="m_ref_classification" class="form-control form-control-sm"></div>
        <div class="col-md-2 mb-2"><label class="form-label small">عنوان التصنيف</label><input type="text" name="titre_classfication" id="m_titre_classfication" class="form-control form-control-sm" readonly></div>
      </div>
      <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> إضافة</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">الوثائق (<?= count($lignes) ?>)</div>
  <div class="card-body table-responsive">
    <table class="table table-bordered table-sm">
      <thead>
        <tr><th>#</th><th>عنوان الملف</th><th>رقم الصندوق</th><th>الموقع</th><th>التصنيف</th><th>إجراءات</th></tr>
      </thead>
      <tbody>
      <?php foreach ($lignes as $l): ?>
        <tr>
          <td><?= (int) $l['id_ligvers'] ?></td>
          <td><?= e($l['titre_dossier']) ?></td>
          <td><?= e($l['num_boite']) ?></td>
          <td><?= e($l['emplacement']) ?></td>
          <td><?= e($l['ref_classification']) ?> - <?= e($l['titre_classfication']) ?></td>
          <td>
            <form method="post" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_ligne">
              <input type="hidden" name="id" value="<?= (int) $id ?>">
              <input type="hidden" name="id_ligvers" value="<?= (int) $l['id_ligvers'] ?>">
              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$lignes): ?>
        <tr><td colspan="6" class="text-center text-muted">لا توجد وثائق</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">رجوع</a>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
<script>
document.getElementById('btn-archive-search').addEventListener('click', function () {
  const q = document.getElementById('archive-search').value.trim();
  if (!q) return;
  fetch(window.BASE_URL + '/modules/versement/archive_lookup_ajax.php?q=' + encodeURIComponent(q))
    .then(r => r.json())
    .then(data => {
      const box = document.getElementById('archive-search-results');
      if (!data.length) {
        box.innerHTML = '<div class="text-muted">لا توجد نتائج</div>';
        return;
      }
      let html = '<table class="table table-sm table-hover"><tbody>';
      data.forEach((it, idx) => {
        html += '<tr><td>' + it.titre_dossier + ' (' + (it.num_boite || '') + ')</td><td><button type="button" class="btn btn-sm btn-outline-primary btn-pick-archive" data-idx="' + idx + '">إضافة</button></td></tr>';
      });
      html += '</tbody></table>';
      box.innerHTML = html;
      box.dataset.items = JSON.stringify(data);
      document.querySelectorAll('.btn-pick-archive').forEach(btn => {
        btn.addEventListener('click', function () {
          const items = JSON.parse(box.dataset.items);
          const d = items[parseInt(this.getAttribute('data-idx'), 10)];
          document.getElementById('m_archive_id').value = d.archive_id || '';
          document.getElementById('m_titre_dossier').value = d.titre_dossier || '';
          document.getElementById('m_num_boite').value = d.num_boite || '';
          document.getElementById('m_num_depot').value = d.num_depot || '';
          document.getElementById('m_num_etagere').value = d.num_etagere || '';
          document.getElementById('m_num_plaque').value = d.num_plaque || '';
          document.getElementById('m_emplacement').value = d.emplacement || '';
          document.getElementById('m_ref_classification').value = d.ref_classification || '';
          document.getElementById('m_titre_classfication').value = d.titre_classfication || '';
          document.getElementById('manual-form').submit();
        });
      });
    });
});
</script>
