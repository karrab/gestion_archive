<?php
declare(strict_types=1);
require __DIR__ . '/../config/config.php';
auth_check();
require __DIR__ . '/TcpdfBase.php';

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
    'transfert' => ['sql' => "SELECT t.id_trans, t.date_trans, t.ref_trans, t.num_bordereau, t.nb_doc, t.nb_boite, sd.nom AS service_dest, so.nom AS service_origin
                               FROM transfert t INNER JOIN service sd ON sd.id = t.service_dest INNER JOIN service so ON so.id = t.service_origin
                               ORDER BY t.date_trans, t.id_trans",
                    'titre' => 'قائمة عمليات النقل', 'cols' => ['#', 'التاريخ', 'المرجع', 'رقم المذكرة', 'عدد الوثائق', 'عدد الصناديق', 'الخدمة المستقبلة', 'الخدمة المصدر']],
    'versement' => ['sql' => "SELECT v.id_vers, v.date_vers, v.ref_vers, v.num_bordereau, v.nb_doc, v.nb_boite, s.nom AS service, e.nom AS employe
                               FROM versement v INNER JOIN service s ON s.id = v.service INNER JOIN employe e ON e.id = v.employe
                               ORDER BY v.date_vers, v.id_vers",
                    'titre' => 'قائمة عمليات الإيداع', 'cols' => ['#', 'التاريخ', 'المرجع', 'رقم المذكرة', 'عدد الوثائق', 'عدد الصناديق', 'الخدمة', 'الموظف']],
    'elimination' => ['sql' => "SELECT el.id_elm, el.date_elm, el.ref_elm, el.numero_pv, el.num_bordereau, el.nb_doc, el.nb_boite, s.nom AS service
                                 FROM elimination el INNER JOIN service s ON s.id = el.service
                                 ORDER BY el.date_elm, el.id_elm",
                       'titre' => 'قائمة عمليات الإتلاف', 'cols' => ['#', 'التاريخ', 'المرجع', 'رقم المحضر', 'رقم المذكرة', 'عدد الوثائق', 'عدد الصناديق', 'الخدمة']],
    'user' => ['sql' => "SELECT u.id, u.login, u.nom, u.prenom, u.mail, r.nom AS role FROM user u INNER JOIN role r ON r.id = u.role_id ORDER BY u.nom",
               'titre' => 'قائمة المستخدمين', 'cols' => ['#', 'المستخدم', 'الاسم', 'اللقب', 'البريد الإلكتروني', 'الدور']],
    'version' => ['sql' => "SELECT id_ver, num_ver, developper_par, direction, nouveaute FROM version_app ORDER BY id_ver DESC",
                  'titre' => 'قائمة الإصدارات', 'cols' => ['#', 'الإصدار', 'تطوير', 'الإدارة', 'الجديد']],
];

if (!isset($tables[$type])) {
    http_response_code(404);
    die('Export non disponible.');
}

$permissionMap = [
    'service' => 'reference.print', 'depot' => 'reference.print', 'annee' => 'reference.print',
    'classification' => 'reference.print', 'carac_ideologique' => 'reference.print', 'carac_temporelle' => 'reference.print',
    'carac_geographique' => 'reference.print', 'type_doc' => 'reference.print', 'sort_fin_doc' => 'reference.print',
    'etat_archive' => 'reference.print', 'institution' => 'reference.print', 'employe' => 'reference.print',
    'archive' => 'archive.print', 'transfert' => 'transfert.print', 'versement' => 'versement.print',
    'elimination' => 'elimination.print', 'user' => 'user.print', 'version' => 'version.print',
    'historique' => 'historique.print',
];
require_permission($permissionMap[$type]);

$rows = $pdo->query($tables[$type]['sql'])->fetchAll();

$pdf = new TcpdfBase($parametres, $tables[$type]['titre'], 'L');
$pdf->AddPage();

$html = '<table border="1" cellpadding="4" style="font-size:9pt;"><thead><tr style="background-color:#0d3b66; color:#ffffff;">';
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

$pdf->writeHTML($html, true, false, true, false, '');

log_historique('export_pdf', $type, "Export PDF: {$type}");
$pdf->Output($type . '_' . date('Ymd_His') . '.pdf', 'I');
