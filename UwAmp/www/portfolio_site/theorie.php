<?php
require 'includes/db.php';

$etudiant_id = 1;

// Récupérer toutes les données théorie
$sql = "
    SELECT
        noms.nom_matiere,
        a.annee_id,
        a.annee_label,
        tr.trimestre_id,
        tr.numero_trimestre,
        ts.test_id,
        ts.date_test,
        ts.coefficient,
        nt.note
    FROM t_annee a
    CROSS JOIN (SELECT DISTINCT nom_matiere FROM t_matiere_theorie) noms
    LEFT JOIN t_trimestre tr ON tr.annee_id = a.annee_id
    LEFT JOIN t_matiere_theorie mt 
        ON mt.annee_id = a.annee_id 
        AND mt.trimestre_id = tr.trimestre_id 
        AND mt.nom_matiere = noms.nom_matiere
    LEFT JOIN t_tests ts ON ts.matiere_theorie_id = mt.matiere_theorie_id
    LEFT JOIN t_notes_tests nt ON nt.test_id = ts.test_id AND nt.etudiant_id = :eid
    ORDER BY noms.nom_matiere, a.annee_id, tr.numero_trimestre, ts.date_test
";

$stmt = $pdo->prepare($sql);
$stmt->execute(array(':eid' => $etudiant_id));
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Structurer matière → année → trimestre → tests[]
$data = array();
foreach ($rows as $r) {
    $matiere = isset($r['nom_matiere']) ? $r['nom_matiere'] : null;
    if (!$matiere) continue;

    if (!isset($data[$matiere])) $data[$matiere] = array();
    $aid = $r['annee_id'];
    if (!isset($data[$matiere][$aid])) {
        $data[$matiere][$aid] = array(
            'label'      => $r['annee_label'],
            'trimestres' => array()
        );
    }
    $tid = $r['trimestre_id'];
    if ($tid && !isset($data[$matiere][$aid]['trimestres'][$tid])) {
        $data[$matiere][$aid]['trimestres'][$tid] = array(
            'numero' => $r['numero_trimestre'],
            'tests'  => array()
        );
    }
    if ($tid && $r['test_id']) {
        $data[$matiere][$aid]['trimestres'][$tid]['tests'][] = array(
            'date'        => $r['date_test'],
            'coefficient' => (float)$r['coefficient'],
            'note'        => $r['note'] !== null ? (float)$r['note'] : null,
        );
    }
}

// Calculs
function moy_tests($tests) {
    $s = 0; $c = 0;
    foreach ($tests as $t) {
        if ($t['note'] !== null) {
            $s += $t['note'] * $t['coefficient'];
            $c += $t['coefficient'];
        }
    }
    return $c > 0 ? round($s / $c, 2) : null;
}
function moy_annee($trimestres) {
    $m = array();
    foreach ($trimestres as $tr) {
        $v = moy_tests($tr['tests']);
        if ($v !== null) $m[] = $v;
    }
    return count($m) > 0 ? round(array_sum($m) / count($m), 2) : null;
}
function moy_matiere($annees) {
    $m = array();
    foreach ($annees as $a) {
        $v = moy_annee($a['trimestres']);
        if ($v !== null) $m[] = $v;
    }
    return count($m) > 0 ? round(array_sum($m) / count($m), 2) : null;
}

$moyennesMat = array();
foreach ($data as $mat => $an) {
    $m = moy_matiere($an);
    if ($m !== null) $moyennesMat[] = $m;
}
$moyGenerale = count($moyennesMat) > 0 ? round(array_sum($moyennesMat) / count($moyennesMat), 2) : null;

function suffixe($n) { return $n == 1 ? '1ère' : $n . 'ème'; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portfolio - Théorie</title>
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
            <a href="theorie.php" class="active">THEORIE</a>
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
        <h1>Théorie</h1>
        <p>Notes obtenues par matière, année et trimestre.</p>
    </div>

    <div class="split-view">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-stats">
                <div class="stat-mini">
                    <div class="stat-mini-num"><?php echo $moyGenerale !== null ? number_format($moyGenerale, 1) : '—'; ?></div>
                    <div class="stat-mini-label">Moyenne générale</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-num"><?php echo count($data); ?></div>
                    <div class="stat-mini-label">Matières</div>
                </div>
            </div>

            <div class="sidebar-list">
                <div class="sidebar-group-label">Matières</div>
                <?php $first = true; foreach ($data as $matiere => $annees):
                    $m = moy_matiere($annees);
                ?>
                <div class="sidebar-item <?php echo $first ? 'active' : ''; ?>" data-matiere="<?php echo htmlspecialchars($matiere); ?>">
                    <div class="sidebar-item-title"><?php echo htmlspecialchars($matiere); ?></div>
                    <div class="sidebar-item-meta">
                        Moyenne <?php echo $m !== null ? number_format($m, 1) : '—'; ?> / 6
                    </div>
                </div>
                <?php $first = false; endforeach; ?>
            </div>
        </aside>

        <!-- DÉTAIL -->
        <main class="detail-panel">
            <?php $first = true; foreach ($data as $matiere => $annees):
                $m = moy_matiere($annees);
            ?>
            <div class="detail-content matiere-detail <?php echo $first ? 'active' : ''; ?>" data-matiere="<?php echo htmlspecialchars($matiere); ?>">

                <div class="detail-header">
                    <h2 class="detail-title"><?php echo htmlspecialchars($matiere); ?></h2>
                    <div class="detail-subtitle">
                        <span><?php echo count($annees); ?> années</span>
                        <span class="dot">·</span>
                        <span>Moyenne <?php echo $m !== null ? number_format($m, 1) : '—'; ?> / 6</span>
                    </div>
                </div>

                <!-- Moyenne en grand -->
                <div class="matiere-moyenne-bloc">
                    <div class="matiere-moyenne-label">Moyenne de la matière</div>
                    <div class="matiere-moyenne-value">
                        <?php echo $m !== null ? number_format($m, 1) : '—'; ?><span>/ 6</span>
                    </div>
                </div>

                <!-- Sections par année -->
                <div class="detail-section">
                    <div class="detail-section-title">Notes par année</div>
                    <div class="annee-list">
                    <?php foreach ($annees as $aid => $annee):
                        $moyAn = moy_annee($annee['trimestres']);
                    ?>
                        <div class="annee-block">
                            <div class="annee-head" onclick="this.parentElement.classList.toggle('open')">
                                <div class="annee-title-wrap">
                                    <div class="annee-num"><?php echo suffixe($aid); ?> année</div>
                                    <div class="annee-year"><?php echo htmlspecialchars($annee['label']); ?></div>
                                </div>
                                <div class="annee-right">
                                    <span class="annee-moy"><?php echo $moyAn !== null ? number_format($moyAn, 1) . ' / 6' : '—'; ?></span>
                                    <span class="chevron">›</span>
                                </div>
                            </div>
                            <div class="annee-body">
                                <div class="trimestres-grid">
                                    <?php 
                                    $trimestres = $annee['trimestres'];
                                    ksort($trimestres);
                                    foreach ($trimestres as $tr):
                                        $moyTr = moy_tests($tr['tests']);
                                        $hasNotes = false;
                                        foreach ($tr['tests'] as $t) {
                                            if ($t['note'] !== null) { $hasNotes = true; break; }
                                        }
                                        $pct = $moyTr !== null ? ($moyTr / 6) * 100 : 0;
                                    ?>
                                    <div class="trim-row">
                                        <div class="trim-num">Trimestre <?php echo $tr['numero']; ?></div>
                                        <?php if ($hasNotes): ?>
                                        <div class="trim-bar-wrap">
                                            <div class="trim-bar">
                                                <div class="trim-bar-fill" style="width: <?php echo $pct; ?>%"></div>
                                            </div>
                                            <div class="trim-note"><?php echo number_format($moyTr, 1); ?> / 6</div>
                                        </div>
                                        <?php else: ?>
                                        <div class="trim-empty">Aucun test évalué</div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>

            </div>
            <?php $first = false; endforeach; ?>
        </main>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Navigation sidebar
    document.querySelectorAll(".sidebar-item").forEach(function(item) {
        item.addEventListener("click", function() {
            var mat = item.dataset.matiere;
            document.querySelectorAll(".sidebar-item").forEach(function(i) { i.classList.remove("active"); });
            item.classList.add("active");
            document.querySelectorAll(".matiere-detail").forEach(function(d) {
                d.classList.toggle("active", d.dataset.matiere === mat);
            });
        });
    });
});
</script>
</body>
</html>