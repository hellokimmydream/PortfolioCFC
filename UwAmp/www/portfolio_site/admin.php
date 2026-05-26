<?php
require 'includes/db.php';

$message = '';
$erreur  = '';

// ─────────────────────────────────────────────
// TRAITEMENT DES FORMULAIRES
// ─────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // ── 1. Ajouter une note à un test théorie existant ──
    if ($action === 'note_test_existant') {
        $test_id = (int)$_POST['test_id'];
        $note    = (float)$_POST['note'];
        // Vérifier si une note existe déjà pour cet étudiant
        $existe = $pdo->prepare("SELECT note_id FROM t_notes_tests WHERE test_id = ? AND etudiant_id = 1");
        $existe->execute([$test_id]);
        if ($existe->fetch()) {
            $stmt = $pdo->prepare("UPDATE t_notes_tests SET note = ? WHERE test_id = ? AND etudiant_id = 1");
            $stmt->execute([$note, $test_id]);
            $message = "Note mise à jour.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO t_notes_tests (test_id, etudiant_id, note) VALUES (?, 1, ?)");
            $stmt->execute([$test_id, $note]);
            $message = "Note ajoutée.";
        }
    }

    // ── 2. Créer un nouveau test théorie + sa note ──
    if ($action === 'nouveau_test') {
        $matiere_id  = (int)$_POST['matiere_theorie_id'];
        $annee_id    = (int)$_POST['annee_id'];
        $trimestre_id= (int)$_POST['trimestre_id'];
        $date_test   = $_POST['date_test'];
        $coefficient = (float)($_POST['coefficient'] ?: 1);
        $note        = (float)$_POST['note'];

        $stmt = $pdo->prepare("
            INSERT INTO t_tests (matiere_theorie_id, annee_id, trimestre_id, coefficient, date_test)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$matiere_id, $annee_id, $trimestre_id, $coefficient, $date_test]);
        $test_id = $pdo->lastInsertId();

        $stmt2 = $pdo->prepare("INSERT INTO t_notes_tests (test_id, etudiant_id, note) VALUES (?, 1, ?)");
        $stmt2->execute([$test_id, $note]);
        $message = "Nouveau test créé et note ajoutée.";
    }

    // ── 3. Résultat projet ──
    if ($action === 'resultat_projet') {
        $projet_id  = (int)$_POST['projet_id'];
        $resultat   = $_POST['resultat'];
        $description= trim(isset($_POST['description']) ? $_POST['description'] : '');
        $prof_id    = (int)$_POST['professeur_id'];

        // Mettre à jour description + prof dans t_projets
        $stmt = $pdo->prepare("UPDATE t_projets SET description = ?, professeur_id = ? WHERE projet_id = ?");
        $stmt->execute([$description, $prof_id, $projet_id]);

        // Insérer ou mettre à jour le résultat
        $existe = $pdo->prepare("SELECT resultat_id FROM t_resultats_projets WHERE projet_id = ? AND etudiant_id = 1");
        $existe->execute([$projet_id]);
        if ($existe->fetch()) {
            $stmt2 = $pdo->prepare("
                UPDATE t_resultats_projets
                SET resultat = ?, date_evaluation = CURDATE()
                WHERE projet_id = ? AND etudiant_id = 1
            ");
            $stmt2->execute([$resultat, $projet_id]);
        } else {
            $stmt2 = $pdo->prepare("
                INSERT INTO t_resultats_projets (projet_id, etudiant_id, type_evaluation, resultat, date_evaluation)
                VALUES (?, 1, 'projet', ?, CURDATE())
            ");
            $stmt2->execute([$projet_id, $resultat]);
        }
        $message = "Projet mis à jour.";
    }

    // ── 4. Note module ──
    if ($action === 'note_module') {
        $module_id = (int)$_POST['module_id'];
        $note      = (float)$_POST['note'];
        $annee_id  = (int)$_POST['annee_id'];
        $trimestre_id = (int)$_POST['trimestre_id'];
        $prof_id   = (int)$_POST['professeur_id'];
        $date_test = $_POST['date_test'];

        // Créer un test_module + note
        $stmt = $pdo->prepare("
            INSERT INTO t_tests_modules (module_id, professeur_id, annee_id, trimestre_id, date_test, coefficient)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$module_id, $prof_id, $annee_id, $trimestre_id, $date_test]);
        $test_id = $pdo->lastInsertId();

        $stmt2 = $pdo->prepare("INSERT INTO t_notes_modules (test_id, etudiant_id, note) VALUES (?, 1, ?)");
        $stmt2->execute([$test_id, $note]);
        $message = "Note de module ajoutée.";
    }
}

// ─────────────────────────────────────────────
// DONNÉES POUR LES FORMULAIRES
// ─────────────────────────────────────────────

// Matières théorie
$matieres = $pdo->query("
    SELECT mt.matiere_theorie_id, mt.nom_matiere, a.annee_label,
           mt.annee_id, mt.trimestre_id
    FROM t_matiere_theorie mt
    JOIN t_annee a ON mt.annee_id = a.annee_id
    ORDER BY mt.annee_id, mt.trimestre_id, mt.nom_matiere
")->fetchAll();

// Tests existants avec leur matière
$tests = $pdo->query("
    SELECT ts.test_id, ts.date_test, mt.nom_matiere, a.annee_label,
           nt.note AS note_actuelle
    FROM t_tests ts
    JOIN t_matiere_theorie mt ON ts.matiere_theorie_id = mt.matiere_theorie_id
    JOIN t_annee a ON ts.annee_id = a.annee_id
    LEFT JOIN t_notes_tests nt ON nt.test_id = ts.test_id AND nt.etudiant_id = 1
    ORDER BY ts.date_test DESC
")->fetchAll();

// Projets
$projets = $pdo->query("
    SELECT p.projet_id, p.nom_projet, p.description, p.professeur_id,
           m.nom_module,
           rp.resultat
    FROM t_projets p
    LEFT JOIN t_modules m ON p.module_id = m.module_id
    LEFT JOIN t_resultats_projets rp ON rp.projet_id = p.projet_id AND rp.etudiant_id = 1
    ORDER BY p.projet_id DESC
")->fetchAll();

// Modules
$modules = $pdo->query("
    SELECT m.module_id, m.nom_module, m.annee_id, m.trimestre_id, m.professeur_id,
           a.annee_label
    FROM t_modules m
    LEFT JOIN t_annee a ON m.annee_id = a.annee_id
    ORDER BY m.module_id
")->fetchAll();

// Professeurs
$professeurs = $pdo->query("
    SELECT professeur_id, prenom, nom FROM t_professeur ORDER BY nom
")->fetchAll();

// Années
$annees = $pdo->query("SELECT annee_id, annee_label FROM t_annee ORDER BY annee_id")->fetchAll();

// Trimestres
$trimestres = $pdo->query("SELECT annee_id, trimestre_id, numero_trimestre FROM t_trimestre ORDER BY annee_id, trimestre_id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portfolio — Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .admin-wrapper { max-width: 860px; margin: 2rem auto; padding: 0 1.5rem; }
        .admin-section { background: #3c3c46; border: 1px solid #333; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .admin-section h2 { font-size: 1.1rem; margin-bottom: 1.2rem; color: #9d9ca0; border-bottom: 1px solid #333; padding-bottom: .6rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: .85rem; opacity: .7; margin-bottom: .3rem;color: #fff; }
        .form-group select,
        .form-group input,
        .form-group textarea { width: 100%; padding: .5rem .75rem; background: #313138; border: 1px solid #444; border-radius: 8px; color: #fff; font-size: .95rem; box-sizing: border-box; }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus { outline: none; border-color: #666686; }
        .btn-submit { background: #727296; color: #fff; border: none; padding: .6rem 1.4rem; border-radius: 8px; cursor: pointer; font-size: .95rem; font-weight: 500; }
        .btn-submit:hover { background: #8b5cf6; }
        .message { background: #14532d; color: #86efac; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .tabs { display: flex; gap: .5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .tab-btn { background: #1a1a2e; border: 1px solid #444; color: #ccc; padding: .5rem 1.1rem; border-radius: 8px; cursor: pointer; font-size: .9rem; }
        .tab-btn.active { background: #727296; color: #fff; border-color: #61617c; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .note-actuelle { font-size: .8rem; color: #a78bfa; margin-left: .5rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media(max-width: 600px){ .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="page">

    <header class="header">
        <div class="logo-image"><img src="logo.jpg" alt="Logo"></div>
        <nav class="nav">
            <a href="accueil.php">HOME</a>
            <a href="projets.php">PROJETS</a>
            <a href="theorie.php">THÉORIE</a>
            <a href="modules.php">MODULES</a>
            <a href="admin.php" class="active">ADMIN</a>
        </nav>
    </header>

    <div class="admin-wrapper">

        <div class="page-title-block">
            <h1>Administration</h1>
            <p>Saisie des notes et résultats.</p>
            <div class="line"></div>
        </div>

        <?php if ($message): ?>
            <div class="message">✓ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- ONGLETS -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('theorie')">Notes théorie</button>
            <button class="tab-btn" onclick="showTab('projets')">Résultats projets</button>
            <button class="tab-btn" onclick="showTab('modules')">Notes modules</button>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- ONGLET 1 — NOTES THÉORIE                  -->
        <!-- ══════════════════════════════════════════ -->
        <div id="tab-theorie" class="tab-content active">

            <!-- Test existant -->
            <div class="admin-section">
                <h2>Ajouter / modifier une note sur un test existant</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="note_test_existant">
                    <div class="form-group">
                        <label>Test</label>
                        <select name="test_id" required>
                            <option value="">— Choisir un test —</option>
                            <?php foreach ($tests as $t): ?>
                            <option value="<?php echo $t['test_id']; ?>">
                                <?php echo htmlspecialchars($t['nom_matiere']); ?> —
                                <?php echo $t['date_test']; ?>
                                (<?php echo $t['annee_label']; ?>)
                                <?php if ($t['note_actuelle'] !== null): ?>
                                    → note actuelle : <?php echo $t['note_actuelle']; ?>
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Note (sur 6)</label>
                        <input type="number" name="note" min="1" max="6" step="0.1" required placeholder="ex: 4.5">
                    </div>
                    <button type="submit" class="btn-submit">Enregistrer</button>
                </form>
            </div>

            <!-- Nouveau test -->
            <div class="admin-section">
                <h2>Créer un nouveau test</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="nouveau_test">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Matière</label>
                            <select name="matiere_theorie_id" required>
                                <option value="">— Choisir —</option>
                                <?php foreach ($matieres as $m): ?>
                                <option value="<?php echo $m['matiere_theorie_id']; ?>">
                                    <?php echo htmlspecialchars($m['nom_matiere']); ?> —
                                    <?php echo $m['annee_label']; ?> /
                                    <?php echo htmlspecialchars($m['nom_trimestre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date du test</label>
                            <input type="date" name="date_test" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Année</label>
                            <select name="annee_id" required>
                                <?php foreach ($annees as $a): ?>
                                <option value="<?php echo $a['annee_id']; ?>"><?php echo $a['annee_label']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Trimestre</label>
                            <select name="trimestre_id" required>
                                <?php foreach ($trimestres as $t): ?>
                                <option value="<?php echo $t['trimestre_id']; ?>">
                                    <?php echo htmlspecialchars($t['nom_trimestre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Note (sur 6)</label>
                            <input type="number" name="note" min="1" max="6" step="0.1" required placeholder="ex: 4.5">
                        </div>
                        <div class="form-group">
                            <label>Coefficient (optionnel, défaut 1)</label>
                            <input type="number" name="coefficient" min="0.5" max="5" step="0.5" placeholder="1">
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Créer et enregistrer</button>
                </form>
            </div>

        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- ONGLET 2 — RÉSULTATS PROJETS              -->
        <!-- ══════════════════════════════════════════ -->
        <div id="tab-projets" class="tab-content">
            <div class="admin-section">
                <h2>Ajouter / modifier le résultat d'un projet</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="resultat_projet">
                    <div class="form-group">
                        <label>Projet</label>
                        <select name="projet_id" id="projet-select" required onchange="remplirProjet(this)">
                            <option value="">— Choisir un projet —</option>
                            <?php foreach ($projets as $p): ?>
                            <option value="<?php echo $p['projet_id']; ?>"
                                data-desc="<?php echo htmlspecialchars(isset($p['description']) ? $p['description'] : ''); ?>"
                                data-prof="<?php echo isset($p['professeur_id']) ? $p['professeur_id'] : ''; ?>"
                                data-resultat="<?php echo isset($p['resultat']) ? $p['resultat'] : ''; ?>">
                                <?php echo htmlspecialchars($p['nom_projet']); ?>
                                (<?php echo htmlspecialchars(isset($p['nom_module']) ? $p['nom_module'] : '-'); ?>)
                                <?php if ($p['resultat']): ?>
                                    — <?php echo $p['resultat']; ?>
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description du projet</label>
                        <textarea name="description" id="projet-desc" placeholder="Décris ce que tu as fait dans ce projet..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Professeur</label>
                        <select name="professeur_id" id="projet-prof" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($professeurs as $pr): ?>
                            <option value="<?php echo $pr['professeur_id']; ?>">
                                <?php echo htmlspecialchars($pr['prenom'] . ' ' . $pr['nom']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Résultat</label>
                        <select name="resultat" id="projet-resultat" required>
                            <option value="">— Choisir —</option>
                            <option value="non acquis">Non acquis</option>
                            <option value="acquis">Acquis</option>
                            <option value="largement acquis">Largement acquis</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit">Enregistrer</button>
                </form>
            </div>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- ONGLET 3 — NOTES MODULES                  -->
        <!-- ══════════════════════════════════════════ -->
        <div id="tab-modules" class="tab-content">
            <div class="admin-section">
                <h2>Ajouter une note à un module</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="note_module">
                    <div class="form-group">
                        <label>Module</label>
                        <select name="module_id" id="module-select" required onchange="remplirModule(this)">
                            <option value="">— Choisir un module —</option>
                            <?php foreach ($modules as $m): ?>
                            <option value="<?php echo $m['module_id']; ?>"
                                data-annee="<?php echo isset($m['annee_id']) ? $m['annee_id'] : ''; ?>"
                                data-trim="<?php echo isset($m['trimestre_id']) ? $m['trimestre_id'] : ''; ?>"
                                data-prof="<?php echo isset($m['professeur_id']) ? $m['professeur_id'] : ''; ?>">
                                <?php echo htmlspecialchars($m['nom_module']); ?>
                                <?php echo $m['annee_label'] ? '— ' . $m['annee_label'] : ''; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Année</label>
                            <select name="annee_id" id="module-annee" required>
                                <?php foreach ($annees as $a): ?>
                                <option value="<?php echo $a['annee_id']; ?>"><?php echo $a['annee_label']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Trimestre</label>
                            <select name="trimestre_id" id="module-trim" required>
                                <?php foreach ($trimestres as $t): ?>
                                <option value="<?php echo $t['trimestre_id']; ?>">
                                    <?php echo htmlspecialchars($t['nom_trimestre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Professeur</label>
                            <select name="professeur_id" id="module-prof" required>
                                <option value="">— Choisir —</option>
                                <?php foreach ($professeurs as $pr): ?>
                                <option value="<?php echo $pr['professeur_id']; ?>">
                                    <?php echo htmlspecialchars($pr['prenom'] . ' ' . $pr['nom']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date de l'évaluation</label>
                            <input type="date" name="date_test" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Note (sur 6)</label>
                        <input type="number" name="note" min="1" max="6" step="0.1" required placeholder="ex: 4.5">
                    </div>
                    <button type="submit" class="btn-submit">Enregistrer</button>
                </form>
            </div>
        </div>

    </div><!-- /admin-wrapper -->
</div><!-- /page -->

<script>
// Onglets
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.target.classList.add('active');
}

// Préremplir description + prof + résultat quand on choisit un projet
function remplirProjet(select) {
    const opt = select.options[select.selectedIndex];
    document.getElementById('projet-desc').value     = opt.dataset.desc    || '';
    document.getElementById('projet-resultat').value = opt.dataset.resultat || '';
    const profSelect = document.getElementById('projet-prof');
    if (opt.dataset.prof) {
        profSelect.value = opt.dataset.prof;
    }
}

// Préremplir année + trimestre + prof quand on choisit un module
function remplirModule(select) {
    const opt = select.options[select.selectedIndex];
    if (opt.dataset.annee) document.getElementById('module-annee').value = opt.dataset.annee;
    if (opt.dataset.trim)  document.getElementById('module-trim').value  = opt.dataset.trim;
    if (opt.dataset.prof)  document.getElementById('module-prof').value  = opt.dataset.prof;
}
</script>
</body>
</html>