<?php
declare(strict_types=1);
require __DIR__ . '/../config/config.php';
require_permission('archive.print');
require __DIR__ . '/TcpdfBase.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT a.*, s.nom AS service_nom, e.nom AS employe_nom, e.renom AS employe_renom, d.numero AS depot_numero
     FROM archive a
     INNER JOIN service s ON s.id = a.service_id
     INNER JOIN employe e ON e.id = a.employe_id
     INNER JOIN depot d ON d.id_dept = a.num_depot
     WHERE a.id = ?'
);
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    http_response_code(404);
    die('Archive non trouvée.');
}

$parametres = get_parametres();

$rows = [
    'التاريخ' => $item['date_archive'],
    'الخدمة' => $item['service_nom'],
    'الموظف' => $item['employe_nom'] . ' ' . $item['employe_renom'],
    'عنوان الملف' => $item['titre_dossier'],
    'رقم الصندوق' => $item['num_boite'],
    'المستودع' => $item['depot_numero'],
    'الرف' => $item['num_etagere'],
    'اللوحة' => $item['num_plaque'],
    'الموقع' => $item['emplacement'],
    'السنوات' => $item['annee_min'] . ' - ' . $item['annee_max'],
    'التصنيف' => $item['ref_classification'] . ' - ' . $item['titre_classfication'],
    'مدة الحفظ' => $item['duree_conser'],
    'الحالة' => $item['etat_archive'],
    'ملاحظات' => $item['notes'],
];

$pdf = new TcpdfBase($parametres, 'بطاقة الأرشيف رقم ' . $item['id'], 'P');
$pdf->AddPage();

$html = '<table border="1" cellpadding="6" style="font-size:10pt;">';
foreach ($rows as $label => $value) {
    $html .= '<tr><th style="width:30%; background-color:#f0f0f0;">' . htmlspecialchars($label) . '</th><td>' . htmlspecialchars((string) $value) . '</td></tr>';
}
$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');

log_historique('export_pdf', 'archive', "Export PDF de l'archive #{$id}");
$pdf->Output('archive_' . $id . '.pdf', 'I');
