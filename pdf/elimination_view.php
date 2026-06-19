<?php
declare(strict_types=1);
require __DIR__ . '/../config/config.php';
require_permission('elimination.print');
require __DIR__ . '/TcpdfBase.php';

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
    http_response_code(404);
    die('Elimination non trouvée.');
}

$lignes = db()->prepare('SELECT * FROM ligne_elimination WHERE id_elm = ? ORDER BY id_ligelm');
$lignes->execute([$id]);
$lignes = $lignes->fetchAll();

$parametres = get_parametres();
$pdf = new TcpdfBase($parametres, 'محضر إتلاف رقم ' . $item['numero_pv'], 'L');
$pdf->AddPage();

$entete = [
    'المرجع' => $item['ref_elm'],
    'التاريخ' => $item['date_elm'],
    'رقم التأشيرة' => $item['numero_visa'],
    'تاريخ التأشيرة' => $item['date_visa'],
    'الخدمة' => $item['service_nom'] . ' - ' . $item['employe_nom'] . ' ' . $item['employe_renom'],
    'المؤسسة' => $item['institution_nom'],
    'رئيس اللجنة' => $item['president_commission'],
    'أعضاء اللجنة' => $item['membres_commission'],
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

log_historique('export_pdf', 'elimination', "Export PDF de l'élimination #{$id}");
$pdf->Output('elimination_' . $id . '.pdf', 'I');
