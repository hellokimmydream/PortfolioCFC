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
    // L'utilisateur choisit année → trimestre → matière (matiere_theorie_id)
    // La matière porte déjà son annee_id et trimestre_id, on les relit côté serveur.
    if ($action === 'nouveau_test') {
        $matiere_id  = (int)$_POST['matiere_theorie_id'];
        $date_test   = $_POST['date_test'];
        $coefficient = (float)($_POST['coefficient'] ?: 1);
        $note        = (float)$_POST['note'];

        // Récupérer annee_id + trimestre_id depuis la matière choisie (cohérence garantie)
        $infoMat = $pdo->prepare("SELECT annee_id, trimestre_id FROM t_matiere_theorie WHERE matiere_theorie_id = ?");
        $infoMat->execute([$matiere_id]);
        $mat = $infoMat->fetch();

        if ($mat) {
            $stmt = $pdo->prepare("
                INSERT INTO t_tests (matiere_theorie_id, annee_id, trimestre_id, coefficient, date_test)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$matiere_id, $mat['annee_id'], $mat['trimestre_id'], $coefficient, $date_test]);
            $test_id = $pdo->lastInsertId();

            $stmt2 = $pdo->prepare("INSERT INTO t_notes_tests (test_id, etudiant_id, note) VALUES (?, 1, ?)");
            $stmt2->execute([$test_id, $note]);
            $message = "Nouveau test créé et note ajoutée.";
        } else {
            $erreur = "Matière introuvable.";
        }
    }

    // ── 3. Créer OU modifier un projet ──
    if ($action === 'projet') {
        $projet_id   = (int)$_POST['projet_id']; // 0 = nouveau projet
        $nom_projet  = trim($_POST['nom_projet']);
        $module_id   = (int)$_POST['module_id'];
        $prof_id     = (int)$_POST['professeur_id'];
        $annee_id    = (int)$_POST['annee_id'];
        $trimestre_id= (int)$_POST['trimestre_id'];
        $description = trim(isset($_POST['description']) ? $_POST['description'] : '');
        $date_debut  = $_POST['date_projet_debut'] ?: null;
        $date_fin    = $_POST['date_fin_projet'] ?: null;
        $nb_periode  = $_POST['nb_periode'] !== '' ? (int)$_POST['nb_periode'] : null;
        $resultat    = isset($_POST['resultat']) ? $_POST['resultat'] : '';

        if ($projet_id > 0) {
            // MODIFIER un projet existant
            $stmt = $pdo->prepare("
                UPDATE t_projets
                SET nom_projet = ?, module_id = ?, professeur_id = ?, annee_id = ?, trimestre_id = ?,
                    description = ?, date_projet_debut = ?, date_fin_projet = ?, nb_periode = ?
                WHERE projet_id = ?
            ");
            $stmt->execute([$nom_projet, $module_id, $prof_id, $annee_id, $trimestre_id,
                            $description, $date_debut, $date_fin, $nb_periode, $projet_id]);
            $message = "Projet modifié.";
        } else {
            // CRÉER un nouveau projet
            $stmt = $pdo->prepare("
                INSERT INTO t_projets (module_id, professeur_id, annee_id, trimestre_id, nom_projet, description, nb_periode, date_projet_debut, date_fin_projet)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$module_id, $prof_id, $annee_id, $trimestre_id, $nom_projet,
                            $description, $nb_periode, $date_debut, $date_fin]);
            $projet_id = $pdo->lastInsertId();
            $message = "Nouveau projet créé.";
        }

        // Gérer le résultat (insert ou update) si renseigné
        if ($resultat !== '') {
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
                    VALUES (?, 1, 'initiale', ?, CURDATE())
                ");
                $stmt2->execute([$projet_id, $resultat]);
            }
            $message .= " Résultat enregistré.";
        }
    }

    // ── 4. Créer OU modifier un module ──
    if ($action === 'module') {
        $module_id   = (int)$_POST['module_id']; // 0 = nouveau module
        $nom_module  = trim($_POST['nom_module']);
        $description = trim(isset($_POST['description_module']) ? $_POST['description_module'] : '');

        if ($module_id > 0) {
            $stmt = $pdo->prepare("UPDATE t_modules SET nom_module = ?, description_module = ? WHERE module_id = ?");
            $stmt->execute([$nom_module, $description, $module_id]);
            $message = "Module modifié.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO t_modules (nom_module, description_module) VALUES (?, ?)");
            $stmt->execute([$nom_module, $description]);
            $message = "Nouveau module créé.";
        }
    }

    // ── 5. Ajouter une note à un module ──
    // Cascade année → trimestre comme pour la théorie
    if ($action === 'note_module') {
        $module_id    = (int)$_POST['module_id'];
        $note         = (float)$_POST['note'];
        $trimestre_id = (int)$_POST['trimestre_id'];
        $prof_id      = (int)$_POST['professeur_id'];
        $date_test    = $_POST['date_test'];

        // Retrouver l'année à partir du trimestre choisi (trimestre_id est global et unique)
        $infoTr = $pdo->prepare("SELECT annee_id FROM t_trimestre WHERE trimestre_id = ?");
        $infoTr->execute([$trimestre_id]);
        $tr = $infoTr->fetch();
        $annee_id = $tr ? $tr['annee_id'] : 0;

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

// Années
$annees = $pdo->query("SELECT annee_id, annee_label FROM t_annee ORDER BY annee_id")->fetchAll();

// Trimestres (avec leur année et leur numéro affiché 1,2,3,4)
$trimestres = $pdo->query("
    SELECT tr.annee_id, tr.trimestre_id, tr.numero_trimestre, a.annee_label
    FROM t_trimestre tr
    JOIN t_annee a ON tr.annee_id = a.annee_id
    ORDER BY tr.annee_id, tr.numero_trimestre
")->fetchAll();

// Matières théorie (chacune liée à année + trimestre)
$matieres = $pdo->query("
    SELECT matiere_theorie_id, nom_matiere, annee_id, trimestre_id
    FROM t_matiere_theorie
    ORDER BY annee_id, trimestre_id, nom_matiere
")->fetchAll();

// Tests existants avec leur matière et note actuelle
$tests = $pdo->query("
    SELECT ts.test_id, ts.date_test, mt.nom_matiere, a.annee_label,
           tr.numero_trimestre, nt.note AS note_actuelle
    FROM t_tests ts
    JOIN t_matiere_theorie mt ON ts.matiere_theorie_id = mt.matiere_theorie_id
    JOIN t_annee a ON ts.annee_id = a.annee_id
    JOIN t_trimestre tr ON tr.trimestre_id = ts.trimestre_id
    LEFT JOIN t_notes_tests nt ON nt.test_id = ts.test_id AND nt.etudiant_id = 1
    ORDER BY ts.date_test DESC
")->fetchAll();

// Projets (avec toutes les infos pour préremplir le formulaire de modification)
$projets = $pdo->query("
    SELECT p.projet_id, p.nom_projet, p.description, p.module_id, p.professeur_id,
           p.annee_id, p.trimestre_id, p.nb_periode, p.date_projet_debut, p.date_fin_projet,
           m.nom_module, rp.resultat
    FROM t_projets p
    LEFT JOIN t_modules m ON p.module_id = m.module_id
    LEFT JOIN t_resultats_projets rp ON rp.projet_id = p.projet_id AND rp.etudiant_id = 1
    ORDER BY p.projet_id DESC
")->fetchAll();

// Modules
$modules = $pdo->query("
    SELECT module_id, nom_module, description_module
    FROM t_modules
    ORDER BY nom_module
")->fetchAll();

// Professeurs
$professeurs = $pdo->query("
    SELECT professeur_id, prenom, nom FROM t_professeur ORDER BY nom
")->fetchAll();

// Encoder en JSON pour le JS (préremplissage des projets)
$projetsJson = json_encode($projets);
$trimestresJson = json_encode($trimestres);
$matieresJson = json_encode($matieres);
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
        .form-group select:disabled { opacity: .45; cursor: not-allowed; }
        .btn-submit { background: #727296; color: #fff; border: none; padding: .6rem 1.4rem; border-radius: 8px; cursor: pointer; font-size: .95rem; font-weight: 500; }
        .btn-submit:hover { background: #8b5cf6; }
        .message { background: #14532d; color: #86efac; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .erreur { background: #5c1a1a; color: #fca5a5; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .tabs { display: flex; gap: .5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .tab-btn { background: #1a1a2e; border: 1px solid #444; color: #ccc; padding: .5rem 1.1rem; border-radius: 8px; cursor: pointer; font-size: .9rem; }
        .tab-btn.active { background: #727296; color: #fff; border-color: #61617c; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .note-actuelle { font-size: .8rem; color: #a78bfa; margin-left: .5rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-hint { font-size: .78rem; color: #8b8b95; margin-top: .3rem; }
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
        <?php if ($erreur): ?>
            <div class="erreur">✗ <?php echo htmlspecialchars($erreur); ?></div>
        <?php endif; ?>

        <!-- ONGLETS -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('theorie')">Notes théorie</button>
            <button class="tab-btn" onclick="showTab('projets')">Projets</button>
            <button class="tab-btn" onclick="showTab('modules')">Modules</button>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- ONGLET 1 — NOTES THÉORIE                  -->
        <!-- ══════════════════════════════════════════ -->
        <div id="tab-theorie" class="tab-content active">

            <!-- Nouveau test : année → trimestre → matière → note -->
            <div class="admin-section">
                <h2>Créer un nouveau test</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="nouveau_test">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Année de formation</label>
                            <select id="th-annee" required>
                                <option value="">— Choisir l'année —</option>
                                <?php foreach ($annees as $a): ?>
                                <option value="<?php echo $a['annee_id']; ?>"><?php echo htmlspecialchars($a['annee_label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Trimestre</label>
                            <select id="th-trimestre" required disabled>
                                <option value="">— Choisir d'abord l'année —</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Matière</label>
                        <select name="matiere_theorie_id" id="th-matiere" required disabled>
                            <option value="">— Choisir d'abord le trimestre —</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Note (sur 6)</label>
                            <input type="number" name="note" min="1" max="6" step="0.1" required placeholder="ex: 4.5">
                        </div>
                        <div class="form-group">
                            <label>Date du test</label>
                            <input type="date" name="date_test" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Coefficient (optionnel, défaut 1)</label>
                        <input type="number" name="coefficient" min="0.5" max="5" step="0.5" placeholder="1">
                    </div>
                    <button type="submit" class="btn-submit">Créer et enregistrer</button>
                </form>
            </div>

            <!-- Note sur un test existant -->
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
                                T<?php echo $t['numero_trimestre']; ?> —
                                <?php echo $t['date_test']; ?>
                                (<?php echo htmlspecialchars($t['annee_label']); ?>)
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

        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- ONGLET 2 — PROJETS (créer ou modifier)    -->
        <!-- ══════════════════════════════════════════ -->
        <div id="tab-projets" class="tab-content">
            <div class="admin-section">
                <h2>Créer un nouveau projet ou modifier un projet existant</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="projet">

                    <div class="form-group">
                        <label>Projet</label>
                        <select id="projet-select" onchange="chargerProjet(this.value)">
                            <option value="0">➕ Nouveau projet</option>
                            <?php foreach ($projets as $p): ?>
                            <option value="<?php echo $p['projet_id']; ?>">
                                <?php echo htmlspecialchars($p['nom_projet']); ?>
                                (<?php echo htmlspecialchars($p['nom_module'] ? $p['nom_module'] : '-'); ?>)
                                <?php if ($p['resultat']): ?> — <?php echo htmlspecialchars($p['resultat']); ?><?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint">Choisis « Nouveau projet » pour créer, ou un projet pour le modifier.</div>
                    </div>

                    <!-- projet_id caché : 0 = nouveau, sinon = modification -->
                    <input type="hidden" name="projet_id" id="projet-id" value="0">

                    <div class="form-group">
                        <label>Nom du projet</label>
                        <input type="text" name="nom_projet" id="projet-nom" required placeholder="ex: P_183 Secured Webshop">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Module</label>
                            <select name="module_id" id="projet-module" required>
                                <option value="">— Choisir —</option>
                                <?php foreach ($modules as $m): ?>
                                <option value="<?php echo $m['module_id']; ?>"><?php echo htmlspecialchars($m['nom_module']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Professeur</label>
                            <select name="professeur_id" id="projet-prof" required>
                                <option value="">— Choisir —</option>
                                <?php foreach ($professeurs as $pr): ?>
                                <option value="<?php echo $pr['professeur_id']; ?>"><?php echo htmlspecialchars($pr['prenom'] . ' ' . $pr['nom']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Année de formation</label>
                            <select name="annee_id" id="projet-annee" required onchange="majTrimestres('projet-annee','projet-trim')">
                                <option value="">— Choisir —</option>
                                <?php foreach ($annees as $a): ?>
                                <option value="<?php echo $a['annee_id']; ?>"><?php echo htmlspecialchars($a['annee_label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Trimestre</label>
                            <select name="trimestre_id" id="projet-trim" required disabled>
                                <option value="">— Choisir d'abord l'année —</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description du projet</label>
                        <textarea name="description" id="projet-desc" placeholder="Décris ce que tu as fait dans ce projet..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Date de début</label>
                            <input type="date" name="date_projet_debut" id="projet-debut">
                        </div>
                        <div class="form-group">
                            <label>Date de fin</label>
                            <input type="date" name="date_fin_projet" id="projet-fin">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre de périodes (optionnel)</label>
                            <input type="number" name="nb_periode" id="projet-periode" min="0" placeholder="ex: 12">
                        </div>
                        <div class="form-group">
                            <label>Résultat (optionnel)</label>
                            <select name="resultat" id="projet-resultat">
                                <option value="">— Pas encore évalué —</option>
                                <option value="non acquis">Non acquis</option>
                                <option value="acquis">Acquis</option>
                                <option value="largement acquis">Largement acquis</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Enregistrer le projet</button>
                </form>
            </div>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- ONGLET 3 — MODULES (créer/modifier + note) -->
        <!-- ══════════════════════════════════════════ -->
        <div id="tab-modules" class="tab-content">

            <!-- Créer ou modifier un module -->
            <div class="admin-section">
                <h2>Créer un nouveau module ou modifier un module existant</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="module">

                    <div class="form-group">
                        <label>Module</label>
                        <select id="module-select" onchange="chargerModule(this.value)">
                            <option value="0">➕ Nouveau module</option>
                            <?php foreach ($modules as $m): ?>
                            <option value="<?php echo $m['module_id']; ?>"
                                data-nom="<?php echo htmlspecialchars($m['nom_module']); ?>"
                                data-desc="<?php echo htmlspecialchars(isset($m['description_module']) ? $m['description_module'] : ''); ?>">
                                <?php echo htmlspecialchars($m['nom_module']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint">Choisis « Nouveau module » pour créer, ou un module pour le modifier.</div>
                    </div>

                    <input type="hidden" name="module_id" id="module-id" value="0">

                    <div class="form-group">
                        <label>Nom du module</label>
                        <input type="text" name="nom_module" id="module-nom" required placeholder="ex: I319">
                    </div>

                    <div class="form-group">
                        <label>Description du module</label>
                        <textarea name="description_module" id="module-desc" placeholder="Décris le module..."></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Enregistrer le module</button>
                </form>
            </div>

            <!-- Ajouter une note à un module -->
            <div class="admin-section">
                <h2>Ajouter une note à un module</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="note_module">

                    <div class="form-group">
                        <label>Module</label>
                        <select name="module_id" required>
                            <option value="">— Choisir un module —</option>
                            <?php foreach ($modules as $m): ?>
                            <option value="<?php echo $m['module_id']; ?>"><?php echo htmlspecialchars($m['nom_module']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Année de formation</label>
                            <select id="modnote-annee" required onchange="majTrimestres('modnote-annee','modnote-trim')">
                                <option value="">— Choisir —</option>
                                <?php foreach ($annees as $a): ?>
                                <option value="<?php echo $a['annee_id']; ?>"><?php echo htmlspecialchars($a['annee_label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Trimestre</label>
                            <select name="trimestre_id" id="modnote-trim" required disabled>
                                <option value="">— Choisir d'abord l'année —</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Professeur</label>
                            <select name="professeur_id" required>
                                <option value="">— Choisir —</option>
                                <?php foreach ($professeurs as $pr): ?>
                                <option value="<?php echo $pr['professeur_id']; ?>"><?php echo htmlspecialchars($pr['prenom'] . ' ' . $pr['nom']); ?></option>
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

                    <button type="submit" class="btn-submit">Enregistrer la note</button>
                </form>
            </div>

        </div>

    </div><!-- /admin-wrapper -->
</div><!-- /page -->

<script>
// Données passées depuis PHP
var TRIMESTRES = <?php echo $trimestresJson; ?>;
var MATIERES   = <?php echo $matieresJson; ?>;
var PROJETS    = <?php echo $projetsJson; ?>;

// Onglets
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(function(t){ t.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
    document.getElementById('tab-' + name).classList.add('active');
    event.target.classList.add('active');
}

// Remplir une liste de trimestres selon l'année choisie
// idAnnee = id du select année, idTrim = id du select trimestre
function majTrimestres(idAnnee, idTrim) {
    var annee = document.getElementById(idAnnee).value;
    var selTrim = document.getElementById(idTrim);
    selTrim.innerHTML = '<option value="">— Choisir le trimestre —</option>';

    if (!annee) {
        selTrim.disabled = true;
        return;
    }
    TRIMESTRES.filter(function(tr){ return String(tr.annee_id) === String(annee); })
        .forEach(function(tr){
            var o = document.createElement('option');
            o.value = tr.trimestre_id;
            o.textContent = 'Trimestre ' + tr.numero_trimestre;
            selTrim.appendChild(o);
        });
    selTrim.disabled = false;
}

// ── THÉORIE : cascade année → trimestre → matière ──
document.getElementById('th-annee').addEventListener('change', function() {
    majTrimestres('th-annee', 'th-trimestre');
    // réinitialiser les matières tant que le trimestre n'est pas choisi
    var selMat = document.getElementById('th-matiere');
    selMat.innerHTML = '<option value="">— Choisir d\'abord le trimestre —</option>';
    selMat.disabled = true;
});

document.getElementById('th-trimestre').addEventListener('change', function() {
    var trim = this.value;
    var selMat = document.getElementById('th-matiere');
    selMat.innerHTML = '<option value="">— Choisir la matière —</option>';

    if (!trim) { selMat.disabled = true; return; }

    MATIERES.filter(function(m){ return String(m.trimestre_id) === String(trim); })
        .forEach(function(m){
            var o = document.createElement('option');
            o.value = m.matiere_theorie_id;
            o.textContent = m.nom_matiere;
            selMat.appendChild(o);
        });
    selMat.disabled = false;
});

// ── PROJETS : charger un projet existant dans le formulaire ──
function chargerProjet(id) {
    document.getElementById('projet-id').value = id;

    if (id === '0' || id === 0) {
        // Réinitialiser pour un nouveau projet
        document.getElementById('projet-nom').value = '';
        document.getElementById('projet-module').value = '';
        document.getElementById('projet-prof').value = '';
        document.getElementById('projet-annee').value = '';
        majTrimestres('projet-annee', 'projet-trim');
        document.getElementById('projet-desc').value = '';
        document.getElementById('projet-debut').value = '';
        document.getElementById('projet-fin').value = '';
        document.getElementById('projet-periode').value = '';
        document.getElementById('projet-resultat').value = '';
        return;
    }

    var p = PROJETS.find(function(x){ return String(x.projet_id) === String(id); });
    if (!p) return;

    document.getElementById('projet-nom').value     = p.nom_projet || '';
    document.getElementById('projet-module').value  = p.module_id || '';
    document.getElementById('projet-prof').value    = p.professeur_id || '';
    document.getElementById('projet-annee').value   = p.annee_id || '';
    // Mettre à jour les trimestres puis sélectionner le bon
    majTrimestres('projet-annee', 'projet-trim');
    document.getElementById('projet-trim').value    = p.trimestre_id || '';
    document.getElementById('projet-desc').value    = p.description || '';
    document.getElementById('projet-debut').value   = p.date_projet_debut || '';
    document.getElementById('projet-fin').value     = p.date_fin_projet || '';
    document.getElementById('projet-periode').value = p.nb_periode || '';
    document.getElementById('projet-resultat').value = p.resultat || '';
}

// ── MODULES : charger un module existant ──
function chargerModule(id) {
    document.getElementById('module-id').value = id;
    var sel = document.getElementById('module-select');
    var opt = sel.options[sel.selectedIndex];

    if (id === '0' || id === 0) {
        document.getElementById('module-nom').value = '';
        document.getElementById('module-desc').value = '';
        return;
    }
    document.getElementById('module-nom').value  = opt.dataset.nom || '';
    document.getElementById('module-desc').value = opt.dataset.desc || '';
}
</script>
</body>
</html>