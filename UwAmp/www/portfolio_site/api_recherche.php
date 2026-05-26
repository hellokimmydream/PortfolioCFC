<?php
require 'includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$out = array();

if ($q !== '') {
    $like = '%' . $q . '%';

    // Projets
    $stmt = $pdo->prepare("
        SELECT projet_id, nom_projet
        FROM t_projets
        WHERE nom_projet LIKE :q
        ORDER BY nom_projet ASC
        LIMIT 5
    ");
    $stmt->execute(array(':q' => $like));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $out[] = array(
            'type'  => 'Projet',
            'titre' => $r['nom_projet'],
            'lien'  => 'projets.php#projet-' . $r['projet_id'],
        );
    }

    // Modules
    $stmt = $pdo->prepare("
        SELECT module_id, nom_module
        FROM t_modules
        WHERE nom_module LIKE :q
        ORDER BY nom_module ASC
        LIMIT 5
    ");
    $stmt->execute(array(':q' => $like));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $out[] = array(
            'type'  => 'Module',
            'titre' => $r['nom_module'],
            'lien'  => 'modules.php#module-' . $r['module_id'],
        );
    }

    // Matières théorie
    $stmt = $pdo->prepare("
        SELECT DISTINCT nom_matiere
        FROM t_matiere_theorie
        WHERE nom_matiere LIKE :q
        ORDER BY nom_matiere ASC
        LIMIT 5
    ");
    $stmt->execute(array(':q' => $like));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $out[] = array(
            'type'  => 'Théorie',
            'titre' => $r['nom_matiere'],
            'lien'  => 'theorie.php',
        );
    }
}

echo json_encode($out);