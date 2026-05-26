<?php
require 'includes/db.php';

$etudiant_id = 1;

// Tous les modules
$modules = $pdo->query("
    SELECT module_id, nom_module, description_module
    FROM t_modules
    ORDER BY nom_module ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Tous les projets pour les associer aux modules
$stmt = $pdo->prepare("
    SELECT 
        p.projet_id,
        p.module_id,
        p.nom_projet,
        p.date_projet_debut,
        p.date_fin_projet,
        a.annee_label,
        a.annee_id,
        tr.numero_trimestre,
        r.resultat,
        r.pourcentage_resultat
    FROM t_projets p
    LEFT JOIN t_annee a ON p.annee_id = a.annee_id
    LEFT JOIN t_trimestre tr ON tr.annee_id = p.annee_id AND tr.trimestre_id = p.trimestre_id
    LEFT JOIN t_resultats_projets r ON r.projet_id = p.projet_id AND r.etudiant_id = :eid
    ORDER BY p.date_projet_debut DESC
");
$stmt->execute(array(':eid' => $etudiant_id));
$tousProjets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Grouper projets par module
$projetsParModule = array();
foreach ($tousProjets as $p) {
    $mid = $p['module_id'];
    if (!isset($projetsParModule[$mid])) $projetsParModule[$mid] = array();
    $projetsParModule[$mid][] = $p;
}

// Tests modules (pour compter combien de notes par module)
$testsModules = $pdo->query("
    SELECT 
        tm.module_id,
        nm.note,
        tm.date_test
    FROM t_tests_modules tm
    LEFT JOIN t_notes_modules nm ON nm.test_id = tm.test_id
    ORDER BY tm.date_test
")->fetchAll(PDO::FETCH_ASSOC);

$notesParModule = array();
foreach ($testsModules as $t) {
    $mid = $t['module_id'];
    if (!isset($notesParModule[$mid])) $notesParModule[$mid] = array();
    if ($t['note'] !== null) $notesParModule[$mid][] = (float)$t['note'];
}

function moyenneArr($arr) {
    if (!count($arr)) return null;
    return round(array_sum($arr) / count($arr), 2);
}

function suffixe($n) { return $n == 1 ? '1ère' : $n . 'ème'; }
function dateFr($d) { return $d ? date('d.m.Y', strtotime($d)) : '—'; }
function badgeClass($r) {
    if ($r === 'acquis') return 'badge badge-acquis';
    if ($r === 'largement acquis') return 'badge badge-large';
    if ($r === 'non acquis') return 'badge badge-non';
    return 'badge';
}

$nbAvecProjets = 0;
foreach ($modules as $m) {
    if (isset($projetsParModule[$m['module_id']]) && count($projetsParModule[$m['module_id']]) > 0) {
        $nbAvecProjets++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portfolio - Modules</title>
    <link rel="stylesheet" href="styles.css">
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
            <a href="modules.php" class="active">MODULES</a>
        </nav>
        <div class="search-container">
            <div class="search"></div>
        </div>
    </header>

    <div class="page-title-block">
        <h1>Modules</h1>
        <p>Tous les modules suivis durant ma formation, avec leurs projets associés.</p>
    </div>

    <div class="split-view">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-search">
                    <span class="search-icon">⌕</span>
                    <input type="text" id="search-module" placeholder="Rechercher un module...">
                </div>
                <div class="sidebar-filters">
                    <span class="filter-chip active" data-filter="all">Tous</span>
                    <span class="filter-chip" data-filter="with-projects">Avec projets</span>
                </div>
            </div>

            <div class="sidebar-stats">
                <div class="stat-mini">
                    <div class="stat-mini-num"><?php echo count($modules); ?></div>
                    <div class="stat-mini-label">Modules</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-num"><?php echo $nbAvecProjets; ?></div>
                    <div class="stat-mini-label">Réalisés</div>
                </div>
            </div>

            <div class="sidebar-list" id="modules-list">
                <?php foreach ($modules as $mod):
                    $nbProj = isset($projetsParModule[$mod['module_id']]) ? count($projetsParModule[$mod['module_id']]) : 0;
                ?>
                <div class="sidebar-item" 
                     data-module="<?php echo $mod['module_id']; ?>"
                     data-with-projects="<?php echo $nbProj > 0 ? '1' : '0'; ?>"
                     data-nom="<?php echo htmlspecialchars(strtolower($mod['nom_module'])); ?>">
                    <div class="sidebar-item-title"><?php echo htmlspecialchars($mod['nom_module']); ?></div>
                    <div class="sidebar-item-meta">
                        <?php echo $nbProj > 0 ? $nbProj . ' projet' . ($nbProj > 1 ? 's' : '') : 'Aucun projet'; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="sidebar-empty" id="no-result" style="display:none;">Aucun module trouvé.</div>
            </div>
        </aside>

        <!-- DÉTAIL -->
        <main class="detail-panel">
            <div class="detail-empty" id="detail-empty">
                <div class="detail-empty-icon">⌕</div>
                <div class="detail-empty-text">Sélectionne un module dans la liste pour voir les détails</div>
            </div>

            <?php foreach ($modules as $mod):
                $mid = $mod['module_id'];
                $projetsLies = isset($projetsParModule[$mid]) ? $projetsParModule[$mid] : array();
                $notes = isset($notesParModule[$mid]) ? $notesParModule[$mid] : array();
                $moyMod = moyenneArr($notes);
            ?>
            <div class="detail-content" data-module="<?php echo $mid; ?>">

                <div class="detail-header">
                    <h2 class="detail-title"><?php echo htmlspecialchars($mod['nom_module']); ?></h2>
                    <div class="detail-subtitle">
                        <span><?php echo count($projetsLies); ?> projet<?php echo count($projetsLies) > 1 ? 's' : ''; ?></span>
                        <?php if ($moyMod !== null): ?>
                        <span class="dot">·</span>
                        <span>Moyenne <?php echo number_format($moyMod, 1); ?> / 6</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="detail-section-title">Description du module</div>
                    <div class="detail-description">
                        <?php echo $mod['description_module'] 
                            ? nl2br(htmlspecialchars($mod['description_module'])) 
                            : '<em>Pas de description disponible.</em>'; ?>
                    </div>
                </div>

                <?php if (!empty($projetsLies)): ?>
                <div class="detail-section">
                    <div class="detail-section-title">Projets associés</div>
                    <div class="detail-info-list">
                        <?php foreach ($projetsLies as $p): ?>
                        <div class="info-row" style="align-items: flex-start;">
                            <div class="info-row-key" style="min-width: 140px;">
                                <?php if ($p['annee_id']): ?>
                                    <?php echo suffixe($p['annee_id']); ?> · T<?php echo $p['numero_trimestre']; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </div>
                            <div class="info-row-val">
                                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                                    <span><?php echo htmlspecialchars($p['nom_projet']); ?></span>
                                    <?php if ($p['resultat']): ?>
                                    <span class="<?php echo badgeClass($p['resultat']); ?>"><?php echo htmlspecialchars($p['resultat']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($p['date_projet_debut'] || $p['date_fin_projet']): ?>
                                <div style="font-size:11px; color:#888; font-weight:400; margin-top:3px;">
                                    <?php echo dateFr($p['date_projet_debut']); ?> → <?php echo dateFr($p['date_fin_projet']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($notes)): ?>
                <div class="detail-section">
                    <div class="detail-section-title">Notes du module</div>
                    <div class="detail-info-list">
                        <?php foreach ($notes as $i => $n): ?>
                        <div class="info-row">
                            <div class="info-row-key">Évaluation <?php echo $i + 1; ?></div>
                            <div class="info-row-val"><?php echo number_format($n, 1); ?> / 6</div>
                        </div>
                        <?php endforeach; ?>
                        <div class="info-row" style="background:#fafafc; margin-top:6px; padding:10px 12px; border-radius:7px; border:none;">
                            <div class="info-row-key" style="font-weight:600; color:#111;">Moyenne</div>
                            <div class="info-row-val" style="font-weight:700; font-size:16px;"><?php echo number_format($moyMod, 1); ?> / 6</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
            <?php endforeach; ?>
        </main>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var items = document.querySelectorAll(".sidebar-item");
    var details = document.querySelectorAll(".detail-content");
    var emptyMsg = document.getElementById("detail-empty");
    var chips = document.querySelectorAll(".filter-chip");
    var searchInput = document.getElementById("search-module");
    var noResult = document.getElementById("no-result");

    items.forEach(function(item) {
        item.addEventListener("click", function() {
            var mid = item.dataset.module;
            items.forEach(function(i) { i.classList.remove("active"); });
            item.classList.add("active");
            emptyMsg.style.display = "none";
            details.forEach(function(d) {
                d.classList.toggle("active", d.dataset.module === mid);
            });
            document.querySelector(".detail-panel").scrollTop = 0;
        });
    });

    chips.forEach(function(chip) {
        chip.addEventListener("click", function() {
            chips.forEach(function(c) { c.classList.remove("active"); });
            chip.classList.add("active");
            applyFilters();
        });
    });

    searchInput.addEventListener("input", applyFilters);

    function applyFilters() {
        var activeChip = document.querySelector(".filter-chip.active");
        var filter = activeChip ? activeChip.dataset.filter : "all";
        var q = searchInput.value.toLowerCase().trim();
        var totalVisible = 0;

        items.forEach(function(item) {
            var matchFilter = filter === "all" || (filter === "with-projects" && item.dataset.withProjects === "1");
            var matchSearch = !q || item.dataset.nom.indexOf(q) !== -1;
            var visible = matchFilter && matchSearch;
            item.style.display = visible ? "" : "none";
            if (visible) totalVisible++;
        });

        noResult.style.display = totalVisible === 0 ? "block" : "none";
    }
});
</script>
</body>
</html>