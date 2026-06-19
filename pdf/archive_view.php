<?php
declare(strict_types=1);
require __DIR__ . '/../config/config.php';
require __DIR__ . '/header_template.php';
auth_check();
require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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
$html = pdf_header_html($parametres, 'بطاقة الأرشيف رقم ' . $item['id']);

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

$html .= '<table style="width:100%; border-collapse:collapse; font-family: DejaVu Sans, sans-serif; direction:rtl;" border="1" cellpadding="6">';
foreach ($rows as $label => $value) {
    $html .= '<tr><th style="width:30%; background:#f0f0f0; text-align:right;">' . htmlspecialchars($label) . '</th><td>' . htmlspecialchars((string) $value) . '</td></tr>';
}
$html .= '</table>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

log_historique('export_pdf', 'archive', "Export PDF de l'archive #{$id}");
$dompdf->stream('archive_' . $id . '.pdf', ['Attachment' => false]);
