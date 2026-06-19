<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('versement.view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT v.*, s.nom AS service_nom, e.nom AS employe_nom, e.renom AS employe_renom, i.description AS institution_nom
     FROM versement v
     INNER JOIN service s ON s.id = v.service
     INNER JOIN employe e ON e.id = v.employe
     LEFT JOIN institution i ON i.id_ins = v.institution
     WHERE v.id_vers = ?'
);
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$lignes = db()->prepare('SELECT * FROM ligne_versement WHERE id_vers = ? ORDER BY id_ligvers');
$lignes->execute([$id]);
$lignes = $lignes->fetchAll();

$pageTitle = 'تفاصيل الإيداع';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-eye"></i> تفاصيل الإيداع</h4>
<div class="card mb-3">
  <div class="card-body">
    <dl class="row">
      <dt class="col-sm-2">#</dt><dd class="col-sm-4"><?= (int) $item['id_vers'] ?></dd>
      <dt class="col-sm-2">التاريخ</dt><dd class="col-sm-4"><?= e($item['date_vers']) ?></dd>
      <dt class="col-sm-2">المرجع</dt><dd class="col-sm-4"><?= e($item['ref_vers']) ?></dd>
      <dt class="col-sm-2">رقم المذكرة</dt><dd class="col-sm-4"><?= e($item['num_bordereau']) ?></dd>
      <dt class="col-sm-2">عدد الوثائق</dt><dd class="col-sm-4"><?= (int) $item['nb_doc'] ?></dd>
      <dt class="col-sm-2">عدد الصناديق</dt><dd class="col-sm-4"><?= (int) $item['nb_boite'] ?></dd>
      <dt class="col-sm-2">المتر الخطي</dt><dd class="col-sm-4"><?= e($item['metrage_lin']) ?></dd>
      <dt class="col-sm-2">الخدمة</dt><dd class="col-sm-4"><?= e($item['service_nom']) ?></dd>
      <dt class="col-sm-2">الموظف</dt><dd class="col-sm-4"><?= e($item['employe_nom']) ?> <?= e($item['employe_renom']) ?></dd>
      <dt class="col-sm-2">المؤسسة</dt><dd class="col-sm-4"><?= e($item['institution_nom']) ?></dd>
      <dt class="col-sm-2">المسؤول عن الاستلام</dt><dd class="col-sm-4"><?= e($item['responsable_reception']) ?></dd>
    </dl>
    <?php if (has_permission('versement.print')): ?>
      <a href="<?= BASE_URL ?>/pdf/versement_view.php?id=<?= $item['id_vers'] ?>" class="btn btn-outline-danger" target="_blank"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <?php endif; ?>
    <a href="list.php" class="btn btn-secondary">رجوع</a>
  </div>
</div>

<div class="card">
  <div class="card-header">قائمة الملفات المودعة</div>
  <div class="card-body table-responsive">
    <table class="table table-bordered table-sm">
      <thead>
        <tr><th>عنوان الملف</th><th>رقم الصندوق</th><th>الرف</th><th>اللوحة</th><th>الموقع</th><th>التصنيف</th></tr>
      </thead>
      <tbody>
      <?php foreach ($lignes as $l): ?>
        <tr>
          <td><?= e($l['titre_dossier']) ?></td>
          <td><?= e($l['num_boite']) ?></td>
          <td><?= e($l['num_etagere']) ?></td>
          <td><?= e($l['num_plaque']) ?></td>
          <td><?= e($l['emplacement']) ?></td>
          <td><?= e($l['ref_classification']) ?> <?= e($l['titre_classfication']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$lignes): ?>
        <tr><td colspan="6" class="text-center text-muted">لا توجد بيانات</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
