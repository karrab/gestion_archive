<?php
declare(strict_types=1);
require __DIR__ . '/../config/config.php';
require __DIR__ . '/header_template.php';
auth_check();
require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$type = $_GET['type'] ?? '';
$pdo = db();
$parametres = get_parametres();

$tables = [
    'service' => ['sql' => 'SELECT id, nom, notes FROM service ORDER BY nom', 'titre' => 'قائمة الخدمات', 'cols' => ['#', 'الاسم', 'ملاحظات']],
    'depot' => ['sql' => 'SELECT id_dept, numero, description, notes FROM depot ORDER BY numero', 'titre' => 'قائمة المستودعات', 'cols' => ['#', 'الرقم', 'الوصف', 'ملاحظات']],
    'historique' => ['sql' => "SELECT h.id, u.login, h.action, h.module, h.description, h.created_at FROM historique h LEFT JOIN user u ON u.id = h.user_id ORDER BY h.created_at DESC LIMIT 1000", 'titre' => 'سجل العمليات', 'cols' => ['#', 'المستخدم', 'العملية', 'الوحدة', 'الوصف', 'التاريخ']],
    'annee' => ['sql' => 'SELECT id_annee, annee FROM annee ORDER BY annee', 'titre' => 'قائمة السنوات', 'cols' => ['#', 'السنة']],
    'classification' => ['sql' => 'SELECT id_cls, ref_classification, titre_classfication, notes FROM classification ORDER BY ref_classification', 'titre' => 'قائمة التصنيف', 'cols' => ['#', 'المرجع', 'العنوان', 'ملاحظات']],
    'carac_ideologique' => ['sql' => 'SELECT id_ideo, description, notes FROM carac_ideologique ORDER BY description', 'titre' => 'الخاصية الإيديولوجية', 'cols' => ['#', 'الوصف', 'ملاحظات']],
    'carac_temporelle' => ['sql' => 'SELECT id_tmp, description, notes FROM carac_temporelle ORDER BY description', 'titre' => 'الخاصية الزمنية', 'cols' => ['#', 'الوصف', 'ملاحظات']],
    'carac_geographique' => ['sql' => 'SELECT id_geo, description, notes FROM carac_geographique ORDER BY description', 'titre' => 'الخاصية الجغرافية', 'cols' => ['#', 'الوصف', 'ملاحظات']],
    'type_doc' => ['sql' => 'SELECT id_typ, description, notes FROM type_doc ORDER BY description', 'titre' => 'أنواع الوثائق', 'cols' => ['#', 'الوصف', 'ملاحظات']],
    'sort_fin_doc' => ['sql' => 'SELECT id_sort, description, notes FROM sort_fin_doc ORDER BY description', 'titre' => 'مصير الوثيقة', 'cols' => ['#', 'الوصف', 'ملاحظات']],
    'etat_archive' => ['sql' => 'SELECT id_etat, etat, notes FROM etat_archive ORDER BY etat', 'titre' => 'حالات الأرشيف', 'cols' => ['#', 'الحالة', 'ملاحظات']],
    'institution' => ['sql' => 'SELECT id_ins, description, notes FROM institution ORDER BY description', 'titre' => 'المؤسسات', 'cols' => ['#', 'الوصف', 'ملاحظات']],
    'employe' => ['sql' => "SELECT e.id, e.matricule, e.nom, e.renom, s.nom AS service, e.mail, e.tel1 FROM employe e INNER JOIN service s ON s.id = e.service_id ORDER BY e.nom", 'titre' => 'قائمة الموظفين', 'cols' => ['#', 'الرقم الوظيفي', 'الاسم', 'اللقب', 'الخدمة', 'البريد الإلكتروني', 'الهاتف']],
    'archive' => ['sql' => "SELECT a.id, a.date_archive, a.titre_dossier, a.num_boite, d.numero AS depot, a.ref_classification, a.etat_archive
                             FROM archive a INNER JOIN depot d ON d.id_dept = a.num_depot ORDER BY a.date_archive, a.id",
                  'titre' => 'قائمة الأرشيف', 'cols' => ['#', 'التاريخ', 'عنوان الملف', 'رقم الصندوق', 'المستودع', 'مرجع التصنيف', 'الحالة']],
];

if (!isset($tables[$type])) {
    http_response_code(404);
    die('Export non disponible.');
}

$rows = $pdo->query($tables[$type]['sql'])->fetchAll();

$html = pdf_header_html($parametres, $tables[$type]['titre']);
$html .= '<table style="width:100%; border-collapse:collapse; font-family: DejaVu Sans, sans-serif; direction:rtl;" border="1" cellpadding="4">';
$html .= '<thead><tr style="background:#0d3b66; color:#fff;">';
foreach ($tables[$type]['cols'] as $col) {
    $html .= '<th>' . htmlspecialchars($col) . '</th>';
}
$html .= '</tr></thead><tbody>';
foreach ($rows as $row) {
    $html .= '<tr>';
    foreach ($row as $value) {
        $html .= '<td>' . htmlspecialchars((string) $value) . '</td>';
    }
    $html .= '</tr>';
}
$html .= '</tbody></table>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

log_historique('export_pdf', $type, "Export PDF: {$type}");
$dompdf->stream($type . '_' . date('Ymd_His') . '.pdf', ['Attachment' => false]);
