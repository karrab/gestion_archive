<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('transfert.view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT t.*, sd.nom AS service_dest_nom, ed.nom AS employe_dest_nom, ed.renom AS employe_dest_renom,
            so.nom AS service_origin_nom, eo.nom AS employe_origin_nom, eo.renom AS employe_origin_renom
     FROM transfert t
     INNER JOIN service sd ON sd.id = t.service_dest
     INNER JOIN employe ed ON ed.id = t.employe_dest
     INNER JOIN service so ON so.id = t.service_origin
     INNER JOIN employe eo ON eo.id = t.employe_origin
     WHERE t.id_trans = ?'
);
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$lignes = db()->prepare('SELECT * FROM ligne_transfert WHERE id_trans = ? ORDER BY id_ligtrans');
$lignes->execute([$id]);
$lignes = $lignes->fetchAll();

$pageTitle = 'تفاصيل النقل';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-eye"></i> تفاصيل النقل</h4>
<div class="card mb-3">
  <div class="card-body">
    <dl class="row">
      <dt class="col-sm-2">#</dt><dd class="col-sm-4"><?= (int) $item['id_trans'] ?></dd>
      <dt class="col-sm-2">التاريخ</dt><dd class="col-sm-4"><?= e($item['date_trans']) ?></dd>
      <dt class="col-sm-2">المرجع</dt><dd class="col-sm-4"><?= e($item['ref_trans']) ?></dd>
      <dt class="col-sm-2">رقم المذكرة</dt><dd class="col-sm-4"><?= e($item['num_bordereau']) ?></dd>
      <dt class="col-sm-2">عدد الوثائق</dt><dd class="col-sm-4"><?= (int) $item['nb_doc'] ?></dd>
      <dt class="col-sm-2">عدد الصناديق</dt><dd class="col-sm-4"><?= (int) $item['nb_boite'] ?></dd>
      <dt class="col-sm-2">المتر الخطي</dt><dd class="col-sm-4"><?= e($item['metrage_lin']) ?></dd>
      <dt class="col-sm-2">الخدمة المصدر</dt><dd class="col-sm-4"><?= e($item['service_origin_nom']) ?></dd>
      <dt class="col-sm-2">الموظف المصدر</dt><dd class="col-sm-4"><?= e($item['employe_origin_nom']) ?> <?= e($item['employe_origin_renom']) ?></dd>
      <dt class="col-sm-2">الخدمة المستقبلة</dt><dd class="col-sm-4"><?= e($item['service_dest_nom']) ?></dd>
      <dt class="col-sm-2">الموظف المستقبل</dt><dd class="col-sm-4"><?= e($item['employe_dest_nom']) ?> <?= e($item['employe_dest_renom']) ?></dd>
    </dl>
    <?php if (has_permission('transfert.print')): ?>
      <a href="<?= BASE_URL ?>/pdf/transfert_view.php?id=<?= $item['id_trans'] ?>" class="btn btn-outline-danger" target="_blank"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <?php endif; ?>
    <a href="list.php" class="btn btn-secondary">رجوع</a>
  </div>
</div>

<div class="card">
  <div class="card-header">قائمة الملفات المنقولة</div>
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
