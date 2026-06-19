<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
auth_check();

header('Content-Type: application/json; charset=utf-8');
$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = db()->prepare(
    "SELECT id AS archive_id, titre_dossier, num_boite, num_depot, num_etagere, num_plaque, emplacement,
            annee_min, annee_max, ref_classification, titre_classfication, carac_ideologique_id, carac_temporelle_id,
            carac_geographique_id, type_doc_id, duree_conser, sort_fin_doc_id
     FROM archive
     WHERE etat_archive = 'Disponible' AND (titre_dossier LIKE :q OR num_boite LIKE :q)
     LIMIT 20"
);
$stmt->execute(['q' => '%' . $q . '%']);
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
