<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('elimination.view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT el.*, s.nom AS service_nom, e.nom AS employe_nom, e.renom AS employe_renom, i.description AS institution_nom
     FROM elimination el
     INNER JOIN service s ON s.id = el.service
     INNER JOIN employe e ON e.id = el.employe
     LEFT JOIN institution i ON i.id_ins = el.institution
     WHERE el.id_elm = ?'
);
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$lignes = db()->prepare('SELECT * FROM ligne_elimination WHERE id_elm = ? ORDER BY id_ligelm');
$lignes->execute([$id]);
$lignes = $lignes->fetchAll();

$pageTitle = 'تفاصيل الإتلاف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-eye"></i> تفاصيل الإتلاف</h4>
<div class="card mb-3">
  <div class="card-body">
    <dl class="row">
      <dt class="col-sm-2">#</dt><dd class="col-sm-4"><?= (int) $item['id_elm'] ?></dd>
      <dt class="col-sm-2">التاريخ</dt><dd class="col-sm-4"><?= e($item['date_elm']) ?></dd>
      <dt class="col-sm-2">المرجع</dt><dd class="col-sm-4"><?= e($item['ref_elm']) ?></dd>
      <dt class="col-sm-2">رقم المحضر</dt><dd class="col-sm-4"><?= e($item['numero_pv']) ?></dd>
      <dt class="col-sm-2">رقم التأشيرة</dt><dd class="col-sm-4"><?= e($item['numero_visa']) ?></dd>
      <dt class="col-sm-2">تاريخ التأشيرة</dt><dd class="col-sm-4"><?= e($item['date_visa']) ?></dd>
      <dt class="col-sm-2">رقم المذكرة</dt><dd class="col-sm-4"><?= e($item['num_bordereau']) ?></dd>
      <dt class="col-sm-2">عدد الوثائق</dt><dd class="col-sm-4"><?= (int) $item['nb_doc'] ?></dd>
      <dt class="col-sm-2">عدد الصناديق</dt><dd class="col-sm-4"><?= (int) $item['nb_boite'] ?></dd>
      <dt class="col-sm-2">الخدمة</dt><dd class="col-sm-4"><?= e($item['service_nom']) ?></dd>
      <dt class="col-sm-2">الموظف</dt><dd class="col-sm-4"><?= e($item['employe_nom']) ?> <?= e($item['employe_renom']) ?></dd>
      <dt class="col-sm-2">المؤسسة</dt><dd class="col-sm-4"><?= e($item['institution_nom']) ?></dd>
      <dt class="col-sm-2">المسؤول عن الاستلام</dt><dd class="col-sm-4"><?= e($item['responsable_reception']) ?></dd>
      <dt class="col-sm-2">رئيس اللجنة</dt><dd class="col-sm-4"><?= e($item['president_commission']) ?></dd>
      <dt class="col-sm-2">أعضاء اللجنة</dt><dd class="col-sm-4"><?= nl2br(e($item['membres_commission'])) ?></dd>
    </dl>
    <?php if (has_permission('elimination.print')): ?>
      <a href="<?= BASE_URL ?>/pdf/elimination_view.php?id=<?= $item['id_elm'] ?>" class="btn btn-outline-danger" target="_blank"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <?php endif; ?>
    <a href="list.php" class="btn btn-secondary">رجوع</a>
  </div>
</div>

<div class="card">
  <div class="card-header">قائمة الملفات المتلفة</div>
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
