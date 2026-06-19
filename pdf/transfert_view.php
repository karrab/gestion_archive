<?php
declare(strict_types=1);
require __DIR__ . '/../config/config.php';
require_permission('transfert.print');
require __DIR__ . '/TcpdfBase.php';

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
    http_response_code(404);
    die('Transfert non trouvé.');
}

$lignes = db()->prepare('SELECT * FROM ligne_transfert WHERE id_trans = ? ORDER BY id_ligtrans');
$lignes->execute([$id]);
$lignes = $lignes->fetchAll();

$parametres = get_parametres();
$pdf = new TcpdfBase($parametres, 'مذكرة نقل رقم ' . $item['num_bordereau'], 'L');
$pdf->AddPage();

$entete = [
    'المرجع' => $item['ref_trans'],
    'التاريخ' => $item['date_trans'],
    'الخدمة المصدر' => $item['service_origin_nom'] . ' - ' . $item['employe_origin_nom'] . ' ' . $item['employe_origin_renom'],
    'الخدمة المستقبلة' => $item['service_dest_nom'] . ' - ' . $item['employe_dest_nom'] . ' ' . $item['employe_dest_renom'],
    'عدد الوثائق' => $item['nb_doc'],
    'عدد الصناديق' => $item['nb_boite'],
];
$html = '<table border="1" cellpadding="5" style="font-size:10pt;">';
foreach ($entete as $label => $value) {
    $html .= '<tr><th style="width:25%; background-color:#f0f0f0;">' . htmlspecialchars($label) . '</th><td>' . htmlspecialchars((string) $value) . '</td></tr>';
}
$html .= '</table><br>';

$html .= '<table border="1" cellpadding="4" style="font-size:9pt;"><thead><tr style="background-color:#0d3b66; color:#ffffff;">
    <th>عنوان الملف</th><th>رقم الصندوق</th><th>الرف</th><th>اللوحة</th><th>الموقع</th><th>التصنيف</th></tr></thead><tbody>';
foreach ($lignes as $l) {
    $html .= '<tr><td>' . htmlspecialchars($l['titre_dossier']) . '</td><td>' . htmlspecialchars((string) $l['num_boite']) . '</td>'
        . '<td>' . htmlspecialchars((string) $l['num_etagere']) . '</td><td>' . htmlspecialchars((string) $l['num_plaque']) . '</td>'
        . '<td>' . htmlspecialchars((string) $l['emplacement']) . '</td><td>' . htmlspecialchars((string) $l['ref_classification']) . '</td></tr>';
}
$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');

log_historique('export_pdf', 'transfert', "Export PDF du transfert #{$id}");
$pdf->Output('transfert_' . $id . '.pdf', 'I');
