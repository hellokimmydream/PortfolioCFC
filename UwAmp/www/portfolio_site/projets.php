<?php
require 'includes/db.php';

$etudiant_id = 1;

$sql = "
    SELECT 
        p.projet_id,
        p.nom_projet,
        p.description,
        p.date_projet_debut,
        p.date_fin_projet,
        p.nb_periode,
        m.module_id,
        m.nom_module,
        m.description_module,
        pr.nom AS prof_nom,
        pr.prenom AS prof_prenom,
        a.annee_id,
        a.annee_label,
        tr.numero_trimestre,
        r.type_evaluation,
        r.resultat,
        r.pourcentage_resultat,
        r.date_evaluation
    FROM t_projets p
    LEFT JOIN t_modules m ON p.module_id = m.module_id
    LEFT JOIN t_professeur pr ON p.professeur_id = pr.professeur_id
    LEFT JOIN t_annee a ON p.annee_id = a.annee_id
    LEFT JOIN t_trimestre tr ON tr.annee_id = p.annee_id AND tr.trimestre_id = p.trimestre_id
    LEFT JOIN t_resultats_projets r ON r.projet_id = p.projet_id AND r.etudiant_id = :eid
    ORDER BY p.annee_id DESC, tr.numero_trimestre DESC, p.date_projet_debut DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute(array(':eid' => $etudiant_id));
$projets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nbAcquis = 0;
foreach ($projets as $p) {
    if ($p['resultat'] === 'acquis' || $p['resultat'] === 'largement acquis') $nbAcquis++;
}

function suffixe($n) { return $n == 1 ? '1ère' : $n . 'ème'; }
function dateFr($d) { return $d ? date('d.m.Y', strtotime($d)) : '—'; }
function badgeClass($r) {
    if ($r === 'acquis') return 'badge badge-acquis';
    if ($r === 'largement acquis') return 'badge badge-large';
    if ($r === 'non acquis') return 'badge badge-non';
    return 'badge';
}

// Grouper par année pour le sidebar
$projetsParAnnee = array();
foreach ($projets as $p) {
    $aid = $p['annee_id'];
    if (!isset($projetsParAnnee[$aid])) $projetsParAnnee[$aid] = array();
    $projetsParAnnee[$aid][] = $p;
}
ksort($projetsParAnnee);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portfolio - Projets</title>
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
            <a href="projets.php" class="active">PROJETS</a>
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
        <h1>Projets</h1>
        <p>Tous mes projets réalisés au cours de ma formation.</p>
    </div>

    <div class="split-view">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-search">
                    <span class="search-icon">⌕</span>
                    <input type="text" id="search-projet" placeholder="Rechercher un projet...">
                </div>
                <div class="sidebar-filters">
                    <span class="filter-chip active" data-filter="all">Tous</span>
                    <?php foreach ($projetsParAnnee as $aid => $list): ?>
                    <span class="filter-chip" data-filter="<?php echo $aid; ?>"><?php echo suffixe($aid); ?> année</span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="sidebar-stats">
                <div class="stat-mini">
                    <div class="stat-mini-num"><?php echo count($projets); ?></div>
                    <div class="stat-mini-label">Projets</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-num"><?php echo $nbAcquis; ?></div>
                    <div class="stat-mini-label">Acquis</div>
                </div>
            </div>

            <div class="sidebar-list" id="projets-list">
                <?php foreach ($projetsParAnnee as $aid => $list): ?>
                <div class="sidebar-group" data-annee="<?php echo $aid; ?>">
                    <div class="sidebar-group-label"><?php echo suffixe($aid); ?> année <?php echo htmlspecialchars($list[0]['annee_label']); ?></div>
                    <?php foreach ($list as $proj): ?>
                    <div class="sidebar-item" 
                         data-projet="<?php echo $proj['projet_id']; ?>" 
                         data-annee="<?php echo $aid; ?>"
                         data-nom="<?php echo htmlspecialchars(strtolower($proj['nom_projet'])); ?>">
                        <div class="sidebar-item-title"><?php echo htmlspecialchars($proj['nom_projet']); ?></div>
                        <div class="sidebar-item-meta">
                            <?php 
                                $meta = array();
                                if ($proj['nom_module']) $meta[] = htmlspecialchars($proj['nom_module']);
                                if ($proj['numero_trimestre']) $meta[] = 'T' . $proj['numero_trimestre'];
                                echo implode(' · ', $meta);
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                <div class="sidebar-empty" id="no-result" style="display:none;">Aucun projet trouvé.</div>
            </div>
        </aside>

        <!-- DÉTAIL -->
        <main class="detail-panel">
            <div class="detail-empty" id="detail-empty">
                <div class="detail-empty-icon">⌕</div>
                <div class="detail-empty-text">Sélectionne un projet dans la liste pour voir les détails</div>
            </div>

            <?php foreach ($projets as $proj): ?>
            <div class="detail-content" data-projet="<?php echo $proj['projet_id']; ?>">

                <div class="detail-header">
                    <h2 class="detail-title"><?php echo htmlspecialchars($proj['nom_projet']); ?></h2>
                    <div class="detail-subtitle">
                        <?php if ($proj['nom_module']): ?>
                        <span><?php echo htmlspecialchars($proj['nom_module']); ?></span>
                        <span class="dot">·</span>
                        <?php endif; ?>
                        <?php if ($proj['annee_label']): ?>
                        <span><?php echo suffixe($proj['annee_id']); ?> année · <?php echo htmlspecialchars($proj['annee_label']); ?></span>
                        <?php endif; ?>
                        <?php if ($proj['numero_trimestre']): ?>
                        <span class="dot">·</span>
                        <span>Trimestre <?php echo $proj['numero_trimestre']; ?></span>
                        <?php endif; ?>
                        <?php if ($proj['resultat']): ?>
                        <span class="dot">·</span>
                        <span class="<?php echo badgeClass($proj['resultat']); ?>"><?php echo htmlspecialchars($proj['resultat']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="detail-section-title">Description</div>
                    <div class="detail-description">
                        <?php echo $proj['description'] 
                            ? nl2br(htmlspecialchars($proj['description'])) 
                            : '<em>Pas de description disponible.</em>'; ?>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="detail-section-title">Informations</div>
                    <div class="detail-info-list">
                        <?php if ($proj['nom_module']): ?>
                        <div class="info-row">
                            <div class="info-row-key">Module</div>
                            <div class="info-row-val"><?php echo htmlspecialchars($proj['nom_module']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($proj['description_module']): ?>
                        <div class="info-row">
                            <div class="info-row-key">Description module</div>
                            <div class="info-row-val" style="font-weight:400; color:#555;"><?php echo htmlspecialchars($proj['description_module']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($proj['prof_nom']): ?>
                        <div class="info-row">
                            <div class="info-row-key">Professeur</div>
                            <div class="info-row-val"><?php echo htmlspecialchars(trim($proj['prof_prenom'] . ' ' . $proj['prof_nom'])); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($proj['annee_label']): ?>
                        <div class="info-row">
                            <div class="info-row-key">Année</div>
                            <div class="info-row-val"><?php echo htmlspecialchars($proj['annee_label']); ?> (<?php echo suffixe($proj['annee_id']); ?> année)</div>
                        </div>
                        <?php endif; ?>

                        <?php if ($proj['numero_trimestre']): ?>
                        <div class="info-row">
                            <div class="info-row-key">Trimestre</div>
                            <div class="info-row-val"><?php echo $proj['numero_trimestre']; ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($proj['date_projet_debut'] || $proj['date_fin_projet']): ?>
                        <div class="info-row">
                            <div class="info-row-key">Période</div>
                            <div class="info-row-val"><?php echo dateFr($proj['date_projet_debut']); ?> → <?php echo dateFr($proj['date_fin_projet']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($proj['nb_periode']): ?>
                        <div class="info-row">
                            <div class="info-row-key">Périodes</div>
                            <div class="info-row-val"><?php echo $proj['nb_periode']; ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="detail-section-title">Évaluation</div>
                    <?php if ($proj['resultat']): ?>
                    <div class="detail-info-list">
                        <div class="info-row">
                            <div class="info-row-key">Résultat</div>
                            <div class="info-row-val">
                                <span class="<?php echo badgeClass($proj['resultat']); ?>"><?php echo htmlspecialchars($proj['resultat']); ?></span>
                            </div>
                        </div>
                        <?php if ($proj['pourcentage_resultat']): ?>
                        <div class="info-row">
                            <div class="info-row-key">Pourcentage</div>
                            <div class="info-row-val"><?php echo number_format($proj['pourcentage_resultat'], 0); ?>%</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($proj['type_evaluation']): ?>
                        <div class="info-row">
                            <div class="info-row-key">Type</div>
                            <div class="info-row-val"><?php echo ucfirst($proj['type_evaluation']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($proj['date_evaluation']): ?>
                        <div class="info-row">
                            <div class="info-row-key">Date</div>
                            <div class="info-row-val"><?php echo dateFr($proj['date_evaluation']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="detail-description"><em>Pas encore évalué.</em></div>
                    <?php endif; ?>
                </div>

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
    var searchInput = document.getElementById("search-projet");
    var noResult = document.getElementById("no-result");

    // Sélection d'un projet
    items.forEach(function(item) {
        item.addEventListener("click", function() {
            var pid = item.dataset.projet;
            items.forEach(function(i) { i.classList.remove("active"); });
            item.classList.add("active");
            emptyMsg.style.display = "none";
            details.forEach(function(d) {
                d.classList.toggle("active", d.dataset.projet === pid);
            });
            // Scroll panneau détail en haut
            document.querySelector(".detail-panel").scrollTop = 0;
        });
    });

    // Filtre par année (chips)
    chips.forEach(function(chip) {
        chip.addEventListener("click", function() {
            chips.forEach(function(c) { c.classList.remove("active"); });
            chip.classList.add("active");
            applyFilters();
        });
    });

    // Recherche
    searchInput.addEventListener("input", applyFilters);

    function applyFilters() {
        var activeChip = document.querySelector(".filter-chip.active");
        var filter = activeChip ? activeChip.dataset.filter : "all";
        var q = searchInput.value.toLowerCase().trim();
        var totalVisible = 0;

        // Filtrer items
        items.forEach(function(item) {
            var matchAnnee = filter === "all" || item.dataset.annee === filter;
            var matchSearch = !q || item.dataset.nom.indexOf(q) !== -1;
            var visible = matchAnnee && matchSearch;
            item.style.display = visible ? "" : "none";
            if (visible) totalVisible++;
        });

        // Cacher groupes vides
        document.querySelectorAll(".sidebar-group").forEach(function(g) {
            var visibleItems = g.querySelectorAll(".sidebar-item:not([style*='display: none'])").length;
            g.style.display = visibleItems > 0 ? "" : "none";
        });

        noResult.style.display = totalVisible === 0 ? "block" : "none";
    }

    // Ouvrir automatiquement le projet ciblé par l'ancre (#projet-X)
    if (window.location.hash.indexOf("#projet-") === 0) {
        var idCible = window.location.hash.replace("#projet-", "");
        var cible = document.querySelector(".sidebar-item[data-projet='" + idCible + "']");
        if (cible) cible.click();
    }
});
</script>
</body>
</html>