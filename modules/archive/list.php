<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('archive.view');

$pdo = db();

$dateDebut = trim($_GET['date_debut'] ?? '');
$dateFin = trim($_GET['date_fin'] ?? '');
$refCls = trim($_GET['ref_classification'] ?? '');
$titreCls = trim($_GET['titre_classfication'] ?? '');
$ideo = (int) ($_GET['carac_ideologique_id'] ?? 0);
$tmp = (int) ($_GET['carac_temporelle_id'] ?? 0);
$geo = (int) ($_GET['carac_geographique_id'] ?? 0);
$etat = trim($_GET['etat_archive'] ?? '');
$perPage = $_GET['per_page'] ?? '50';

$sortable = ['id', 'date_archive', 'titre_dossier', 'num_boite'];
$sort = in_array($_GET['sort'] ?? '', $sortable) ? $_GET['sort'] : 'date_archive';
$dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$where = [];
$params = [];
if ($dateDebut !== '') { $where[] = 'a.date_archive >= :date_debut'; $params['date_debut'] = $dateDebut; }
if ($dateFin !== '') { $where[] = 'a.date_archive <= :date_fin'; $params['date_fin'] = $dateFin; }
if ($refCls !== '') { $where[] = 'a.ref_classification = :ref_cls'; $params['ref_cls'] = $refCls; }
if ($titreCls !== '') { $where[] = 'a.titre_classfication LIKE :titre_cls'; $params['titre_cls'] = '%' . $titreCls . '%'; }
if ($ideo > 0) { $where[] = 'a.carac_ideologique_id = :ideo'; $params['ideo'] = $ideo; }
if ($tmp > 0) { $where[] = 'a.carac_temporelle_id = :tmp'; $params['tmp'] = $tmp; }
if ($geo > 0) { $where[] = 'a.carac_geographique_id = :geo'; $params['geo'] = $geo; }
if ($etat !== '') { $where[] = 'a.etat_archive = :etat'; $params['etat'] = $etat; }

foreach (['f_titre_dossier' => 'a.titre_dossier', 'f_num_boite' => 'a.num_boite'] as $key => $col) {
    if (trim($_GET[$key] ?? '') !== '') {
        $where[] = "{$col} LIKE :{$key}";
        $params[$key] = '%' . trim($_GET[$key]) . '%';
    }
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT a.id, a.date_archive, a.titre_dossier, a.num_boite, a.num_etagere, a.num_plaque, a.emplacement,
               a.annee_min, a.annee_max, a.ref_classification, a.titre_classfication, a.etat_archive, a.fichier,
               s.nom AS service_nom, e.nom AS employe_nom, e.renom AS employe_renom, d.numero AS depot_numero
        FROM archive a
        INNER JOIN service s ON s.id = a.service_id
        INNER JOIN employe e ON e.id = a.employe_id
        INNER JOIN depot d ON d.id_dept = a.num_depot
        {$whereSql}
        ORDER BY a.{$sort} {$dir}, a.id {$dir}";
if ($perPage !== 'all') {
    $sql .= ' LIMIT ' . (int) $perPage;
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$etats = $pdo->query('SELECT etat FROM etat_archive ORDER BY etat')->fetchAll(PDO::FETCH_COLUMN);
$ideologiques = $pdo->query('SELECT id_ideo, description FROM carac_ideologique ORDER BY description')->fetchAll();
$temporelles = $pdo->query('SELECT id_tmp, description FROM carac_temporelle ORDER BY description')->fetchAll();
$geographiques = $pdo->query('SELECT id_geo, description FROM carac_geographique ORDER BY description')->fetchAll();

$pageTitle = 'الأرشيف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4><i class="bi bi-archive"></i> الأرشيف</h4>
  <div>
    <?php if (has_permission('archive.print')): ?>
      <a href="<?= BASE_URL ?>/pdf/export.php?type=archive" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <?php endif; ?>
    <?php if (has_permission('archive.add')): ?>
      <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> إضافة</a>
    <?php endif; ?>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2">
      <div class="col-md-2">
        <label class="form-label small">من تاريخ</label>
        <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= e($dateDebut) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small">إلى تاريخ</label>
        <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= e($dateFin) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small">مرجع التصنيف</label>
        <input type="text" name="ref_classification" class="form-control form-control-sm" value="<?= e($refCls) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small">عنوان التصنيف</label>
        <input type="text" name="titre_classfication" class="form-control form-control-sm" value="<?= e($titreCls) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small">الخاصية الإيديولوجية</label>
        <select name="carac_ideologique_id" class="form-select form-select-sm">
          <option value="">--</option>
          <?php foreach ($ideologiques as $it): ?>
            <option value="<?= $it['id_ideo'] ?>" <?= $ideo === (int) $it['id_ideo'] ? 'selected' : '' ?>><?= e($it['description']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">الخاصية الزمنية</label>
        <select name="carac_temporelle_id" class="form-select form-select-sm">
          <option value="">--</option>
          <?php foreach ($temporelles as $it): ?>
            <option value="<?= $it['id_tmp'] ?>" <?= $tmp === (int) $it['id_tmp'] ? 'selected' : '' ?>><?= e($it['description']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">الخاصية الجغرافية</label>
        <select name="carac_geographique_id" class="form-select form-select-sm">
          <option value="">--</option>
          <?php foreach ($geographiques as $it): ?>
            <option value="<?= $it['id_geo'] ?>" <?= $geo === (int) $it['id_geo'] ? 'selected' : '' ?>><?= e($it['description']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">حالة الأرشيف</label>
        <select name="etat_archive" class="form-select form-select-sm">
          <option value="">--</option>
          <?php foreach ($etats as $it): ?>
            <option value="<?= e($it) ?>" <?= $etat === $it ? 'selected' : '' ?>><?= e($it) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">عدد العناصر</label>
        <select name="per_page" class="form-select form-select-sm">
          <?php foreach (['10','50','100','200','500','1000','all'] as $opt): ?>
            <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt === 'all' ? 'الكل' : $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> بحث</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-hover table-bordered" data-grid data-sort-col="<?= e($sort) ?>" data-sort-dir="<?= e(strtolower($dir)) ?>">
      <thead>
        <tr>
          <th data-col="id">#</th>
          <th data-col="date_archive">التاريخ</th>
          <th data-col="titre_dossier">عنوان الملف</th>
          <th data-col="num_boite">رقم الصندوق</th>
          <th>المستودع</th>
          <th>الرف</th>
          <th>اللوحة</th>
          <th>الموقع</th>
          <th>السنوات</th>
          <th>التصنيف</th>
          <th>الخدمة</th>
          <th>الموظف</th>
          <th>الحالة</th>
          <th>الملف</th>
          <th>إجراءات</th>
        </tr>
        <tr class="column-search">
          <td></td><td></td>
          <td><input type="text" class="form-control form-control-sm" data-col="titre_dossier" value="<?= e($_GET['f_titre_dossier'] ?? '') ?>"></td>
          <td><input type="text" class="form-control form-control-sm" data-col="num_boite" value="<?= e($_GET['f_num_boite'] ?? '') ?>"></td>
          <td colspan="10"></td>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= (int) $it['id'] ?></td>
          <td><?= e($it['date_archive']) ?></td>
          <td><?= e($it['titre_dossier']) ?></td>
          <td><?= e($it['num_boite']) ?></td>
          <td><?= e($it['depot_numero']) ?></td>
          <td><?= e($it['num_etagere']) ?></td>
          <td><?= e($it['num_plaque']) ?></td>
          <td><?= e($it['emplacement']) ?></td>
          <td><?= e($it['annee_min']) ?> - <?= e($it['annee_max']) ?></td>
          <td><?= e($it['ref_classification']) ?> - <?= e($it['titre_classfication']) ?></td>
          <td><?= e($it['service_nom']) ?></td>
          <td><?= e($it['employe_nom']) ?> <?= e($it['employe_renom']) ?></td>
          <td><span class="badge bg-secondary"><?= e($it['etat_archive']) ?></span></td>
          <td>
            <?php if ($it['fichier']): ?>
              <a href="download.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i></a>
            <?php endif; ?>
          </td>
          <td class="text-nowrap">
            <a href="view.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <?php if (has_permission('archive.edit')): ?>
              <a href="edit.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <?php if (has_permission('archive.print')): ?>
              <a href="<?= BASE_URL ?>/pdf/archive_view.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-outline-danger" target="_blank"><i class="bi bi-file-earmark-pdf"></i></a>
            <?php endif; ?>
            <?php if (has_permission('archive.delete')): ?>
              <form method="post" action="delete.php" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $it['id'] ?>">
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?>
        <tr><td colspan="14" class="text-center text-muted">لا توجد بيانات</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
