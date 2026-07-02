<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('archive.view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT a.*, s.nom AS service_nom, e.nom AS employe_nom, e.renom AS employe_renom, d.numero AS depot_numero,
            so.nom AS service_origin_nom, eo.nom AS employe_origin_nom, eo.renom AS employe_origin_renom,
            ci.description AS ideo_desc, ct.description AS tmp_desc, cg.description AS geo_desc,
            td.description AS type_desc, sf.description AS sort_desc
     FROM archive a
     INNER JOIN service s ON s.id = a.service_id
     INNER JOIN employe e ON e.id = a.employe_id
     LEFT JOIN service so ON so.id = a.service_origin
     LEFT JOIN employe eo ON eo.id = a.employe_origin
     INNER JOIN depot d ON d.id_dept = a.num_depot
     LEFT JOIN carac_ideologique ci ON ci.id_ideo = a.carac_ideologique_id
     LEFT JOIN carac_temporelle ct ON ct.id_tmp = a.carac_temporelle_id
     LEFT JOIN carac_geographique cg ON cg.id_geo = a.carac_geographique_id
     LEFT JOIN type_doc td ON td.id_typ = a.type_doc_id
     LEFT JOIN sort_fin_doc sf ON sf.id_sort = a.sort_fin_doc_id
     WHERE a.id = ?'
);
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash_set('danger', 'العنصر غير موجود.');
    redirect('list.php');
}

$pageTitle = 'تفاصيل الأرشيف';
require __DIR__ . '/../../includes/layout_header.php';
?>
<h4 class="mb-3"><i class="bi bi-eye"></i> تفاصيل الأرشيف</h4>
<div class="card">
  <div class="card-body">
    <dl class="row">
      <dt class="col-sm-3">#</dt><dd class="col-sm-9"><?= (int) $item['id'] ?></dd>
      <dt class="col-sm-3">التاريخ</dt><dd class="col-sm-9"><?= e($item['date_archive']) ?></dd>
      <dt class="col-sm-3">الخدمة</dt><dd class="col-sm-9"><?= e($item['service_nom']) ?></dd>
      <dt class="col-sm-3">الموظف</dt><dd class="col-sm-9"><?= e($item['employe_nom']) ?> <?= e($item['employe_renom']) ?></dd>
      <dt class="col-sm-3">خدمة المصدر</dt><dd class="col-sm-9"><?= e($item['service_origin_nom'] ?? '') ?></dd>
      <dt class="col-sm-3">موظف المصدر</dt><dd class="col-sm-9"><?= e($item['employe_origin_nom'] ?? '') ?> <?= e($item['employe_origin_renom'] ?? '') ?></dd>
      <dt class="col-sm-3">عنوان الملف</dt><dd class="col-sm-9"><?= e($item['titre_dossier']) ?></dd>
      <dt class="col-sm-3">رقم الصندوق</dt><dd class="col-sm-9"><?= e($item['num_boite']) ?></dd>
      <dt class="col-sm-3">المستودع</dt><dd class="col-sm-9"><?= e($item['depot_numero']) ?></dd>
      <dt class="col-sm-3">الرف</dt><dd class="col-sm-9"><?= e($item['num_etagere']) ?></dd>
      <dt class="col-sm-3">اللوحة</dt><dd class="col-sm-9"><?= e($item['num_plaque']) ?></dd>
      <dt class="col-sm-3">الموقع</dt><dd class="col-sm-9"><?= e($item['emplacement']) ?></dd>
      <dt class="col-sm-3">السنوات</dt><dd class="col-sm-9"><?= e($item['annee_min']) ?> - <?= e($item['annee_max']) ?></dd>
      <dt class="col-sm-3">التصنيف</dt><dd class="col-sm-9"><?= e($item['ref_classification']) ?> - <?= e($item['titre_classfication']) ?></dd>
      <dt class="col-sm-3">الخاصية الإيديولوجية</dt><dd class="col-sm-9"><?= e($item['ideo_desc']) ?></dd>
      <dt class="col-sm-3">الخاصية الزمنية</dt><dd class="col-sm-9"><?= e($item['tmp_desc']) ?></dd>
      <dt class="col-sm-3">الخاصية الجغرافية</dt><dd class="col-sm-9"><?= e($item['geo_desc']) ?></dd>
      <dt class="col-sm-3">نوع الوثيقة</dt><dd class="col-sm-9"><?= e($item['type_desc']) ?></dd>
      <dt class="col-sm-3">مدة الحفظ</dt><dd class="col-sm-9"><?= e($item['duree_conser']) ?></dd>
      <dt class="col-sm-3">مصير الوثيقة</dt><dd class="col-sm-9"><?= e($item['sort_desc']) ?></dd>
      <dt class="col-sm-3">الحالة</dt><dd class="col-sm-9"><span class="badge bg-secondary"><?= e($item['etat_archive']) ?></span></dd>
      <dt class="col-sm-3">الملف</dt>
      <dd class="col-sm-9">
        <?php if ($item['fichier']): ?>
          <a href="download.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i> تحميل</a>
        <?php else: ?>
          <span class="text-muted">لا يوجد ملف</span>
        <?php endif; ?>
      </dd>
      <dt class="col-sm-3">ملاحظات</dt><dd class="col-sm-9"><?= nl2br(e($item['notes'])) ?></dd>
    </dl>
    <?php if (has_permission('archive.print')): ?>
      <a href="<?= BASE_URL ?>/pdf/archive_view.php?id=<?= $item['id'] ?>" class="btn btn-outline-danger" target="_blank"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <?php endif; ?>
    <a href="list.php" class="btn btn-secondary">رجوع</a>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
