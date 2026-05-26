<?php
require 'includes/db.php';

// Terme recherché (nettoyé)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$resultats = array();

if ($q !== '') {
    $like = '%' . $q . '%';

    // ── Recherche dans les projets ──
    $stmt = $pdo->prepare("
        SELECT p.projet_id, p.nom_projet, p.description, m.nom_module
        FROM t_projets p
        LEFT JOIN t_modules m ON p.module_id = m.module_id
        WHERE p.nom_projet LIKE :q OR p.description LIKE :q
        ORDER BY p.nom_projet ASC
    ");
    $stmt->execute(array(':q' => $like));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $resultats[] = array(
            'type'  => 'Projet',
            'titre' => $r['nom_projet'],
            'meta'  => $r['nom_module'] ? $r['nom_module'] : '',
            'lien'  => 'projets.php#projet-' . $r['projet_id'],
        );
    }

    // ── Recherche dans les modules ──
    $stmt = $pdo->prepare("
        SELECT module_id, nom_module, description_module
        FROM t_modules
        WHERE nom_module LIKE :q OR description_module LIKE :q
        ORDER BY nom_module ASC
    ");
    $stmt->execute(array(':q' => $like));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $resultats[] = array(
            'type'  => 'Module',
            'titre' => $r['nom_module'],
            'meta'  => $r['description_module'] ? mb_strimwidth($r['description_module'], 0, 60, '…') : '',
            'lien'  => 'modules.php#module-' . $r['module_id'],
        );
    }

    // ── Recherche dans les matières théorie ──
    $stmt = $pdo->prepare("
        SELECT DISTINCT nom_matiere
        FROM t_matiere_theorie
        WHERE nom_matiere LIKE :q
        ORDER BY nom_matiere ASC
    ");
    $stmt->execute(array(':q' => $like));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $resultats[] = array(
            'type'  => 'Théorie',
            'titre' => $r['nom_matiere'],
            'meta'  => 'Matière théorique',
            'lien'  => 'theorie.php',
        );
    }
}

function badgeType($type) {
    if ($type === 'Projet')  return 'badge badge-large';
    if ($type === 'Module')  return 'badge badge-acquis';
    if ($type === 'Théorie') return 'badge';
    return 'badge';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portfolio - Recherche</title>
    <link rel="stylesheet" href="styles.css">
    <script src="comportement.js" defer></script>
</head>
<body>
<div class="page">

    <header class="header">
        <div class="logo-image">
            <img src="logo.jpg" alt="Logo">
        </div>
        <nav class="nav">
            <a href="accueil.php">HOME</a>
            <a href="projets.php">PROJETS</a>
            <a href="theorie.php">THEORIE</a>
            <a href="modules.php">MODULES</a>
        </nav>
        <div class="search-container">
            <div class="search"></div>
            <div class="search-box">
                <input type="text" id="search-input" placeholder="Rechercher...">
                <ul id="suggestionBox"></ul>
            </div>
        </div>
    </header>

    <div class="page-title-block">
        <h1>Recherche</h1>
        <?php if ($q !== ''): ?>
        <p><?php echo count($resultats); ?> résultat<?php echo count($resultats) > 1 ? 's' : ''; ?> pour « <?php echo htmlspecialchars($q); ?> »</p>
        <?php else: ?>
        <p>Tape un terme pour chercher dans les projets, modules et matières.</p>
        <?php endif; ?>
    </div>

    <div class="search-results">
        <?php if ($q !== '' && count($resultats) === 0): ?>
            <div class="search-empty">
                <div class="search-empty-icon">⌕</div>
                <div class="search-empty-text">Aucun résultat pour « <?php echo htmlspecialchars($q); ?> »</div>
            </div>
        <?php else: ?>
            <?php foreach ($resultats as $res): ?>
            <a href="<?php echo htmlspecialchars($res['lien']); ?>" class="result-card">
                <div class="result-card-main">
                    <div class="result-card-title"><?php echo htmlspecialchars($res['titre']); ?></div>
                    <?php if ($res['meta']): ?>
                    <div class="result-card-meta"><?php echo htmlspecialchars($res['meta']); ?></div>
                    <?php endif; ?>
                </div>
                <span class="<?php echo badgeType($res['type']); ?>"><?php echo $res['type']; ?></span>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</body>
</html>