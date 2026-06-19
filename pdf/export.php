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
