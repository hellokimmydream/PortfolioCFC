<?php
require 'includes/db.php';

// Compteurs dynamiques depuis la DB
$nbProjets  = $pdo->query("SELECT COUNT(*) FROM t_projets")->fetchColumn();
$nbModules  = $pdo->query("SELECT COUNT(*) FROM t_modules")->fetchColumn();

// Projets pour la grille hexagonale
$projets = $pdo->query("
    SELECT p.projet_id, p.nom_projet, m.nom_module
    FROM t_projets p
    LEFT JOIN t_modules m ON p.module_id = m.module_id
    ORDER BY p.projet_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Modules pour la grille hexagonale
$modules = $pdo->query("
    SELECT module_id, nom_module
    FROM t_modules
    ORDER BY module_id ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portfolio - Accueil</title>
    <link rel="stylesheet" href="styles.css">
    <script src="comportement.js" defer></script>
    <style>
        /* ── Compteurs hexagonaux ── */
        .hex-counters {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 10px 0 30px 0;
            color: #ffffff;
        }

        .hex-counter {
            width: 130px;
            height: 150px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s ease;
        }

        .hex-counter:hover {
            transform: translateY(-4px);
            color: #f1f1f1;
        }

        .hex-counter svg {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
        }

        .hex-counter svg polygon {
            fill: #424242;
            transition: fill 0.2s ease;
        }

        .hex-counter:hover svg polygon {
            fill: #1b1b1b;
        }

        .hex-counter-inner {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .hex-counter-number {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1;
        }

        .hex-counter-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #e9e9e9;
        }

        /* ── Section état de formation ── */
        .etat-section {
            padding: 10px 30px 0 30px;
            text-align: center;
        }

        .etat-section h2 {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .etat-section p {
            font-size: 13px;
            color: #888;
            margin-bottom: 20px;
        }

        /* ── Grille hexagonale projets/modules ── */
        .hex-grid-section {
            padding: 20px 30px 30px 30px;
        }

        .hex-grid-section h2 {
            font-size: 15px;
            font-weight: 500;
            color: #ffffff;
            margin-bottom: 20px;
            text-align: center;
        }

        .hex-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
            max-width: 520px;
            margin: 0 auto;
        }

        /* rangées décalées */
        .hex-grid-row {
            display: flex;
            gap: 6px;
            justify-content: center;
        }

        .hex-grid-row:nth-child(even) {
            margin-left: 56px;
        }

        .hex-item {
            width: 150px;
            height: 165px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease;
        }

        .hex-item:hover {
            transform: translateY(-3px);
        }

        .hex-item svg {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
        }

        .hex-item.type-module svg polygon { fill: #444444; }
        .hex-item.type-projet svg polygon { fill: #383838; }
        .hex-item.type-module:hover svg polygon { fill: #222222; }
        .hex-item.type-projet:hover svg polygon { fill: #0e0e0e; }

        .hex-item-inner {
            position: relative;
            z-index: 1;
            padding: 8px;
        }

        .hex-item-type {
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #b9b9b9;
            margin-bottom: 3px;
        }

        .hex-item-name {
            font-size: 11px;
            font-weight: 600;
            color: #ffffff;
            line-height: 1.3;
        }
    </style>
</head>
<body>
<div class="page">

    <!-- HEADER -->
    <header class="header">
        <div class="logo-image">
            <img src="logo.jpg" alt="Logo">
        </div>
        <nav class="nav">
            <a href="accueil.php" class="active">HOME</a>
            <a href="projets.php">PROJETS</a>
            <a href="theorie.php">THEORIE</a>
            <a href="modules.php">MODULES</a>
            <a href="admin.php">ADMIN</a>
        </nav>
        <div class="search-container">
            <div class="search"></div>
            <div class="search-box">
                <input type="text" id="search-input" placeholder="Rechercher...">
                <ul id="suggestionBox"></ul>
            </div>
        </div>
    </header>

    <!-- HERO -->
    <div class="hero">
        <div class="hero-title">PORTFOLIO</div>
    </div>

    <!-- ABOUT
    <section class="about">
        <div class="about-img"></div>
        <div class="about-text">
            <h2>About me</h2>
            <p>Camille Rais, étudiante informaticienne en développement d'applications.</p>
        </div>
    </section>
    -->

    <!-- MODAL -->
    <div class="modal" id="bio-modal">
        <div class="modal-content">
            <h2>Camille Rais</h2>
            <p>Etudiante informaticienne en développement d'applications</p>
            <button id="close-modal">Fermer</button>
        </div>
    </div>

    <!-- ÉTAT DE LA FORMATION -->
    <div class="etat-section">
        <h2>Etat de la formation</h2>
        <p>Jusqu'à ce jour</p>

        <div class="hex-counters">
            <a href="projets.php" class="hex-counter">
                <svg viewBox="0 0 130 150" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="65,5 125,37.5 125,112.5 65,145 5,112.5 5,37.5"/>
                </svg>
                <div class="hex-counter-inner">
                    <span class="hex-counter-number"><?php echo $nbProjets; ?></span>
                    <span class="hex-counter-label">Projets</span>
                </div>
            </a>
            <a href="modules.php" class="hex-counter">
                <svg viewBox="0 0 130 150" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="65,5 125,37.5 125,112.5 65,145 5,112.5 5,37.5"/>
                </svg>
                <div class="hex-counter-inner">
                    <span class="hex-counter-number"><?php echo $nbModules; ?></span>
                    <span class="hex-counter-label">Modules</span>
                </div>
            </a>
        </div>
    </div>

    <!-- GRILLE HEXAGONALE -->
    <div class="hex-grid-section">
        <h2>Vue d'ensemble des projets et des modules</h2>
        <?php
        // Mélanger projets et modules en alternant
        $items = [];
        $pi = 0; $mi = 0;
        $totalP = count($projets); $totalM = count($modules);
        while ($pi < $totalP || $mi < $totalM) {
            if ($mi < $totalM) {
                $items[] = ['type' => 'module', 'id' => $modules[$mi]['module_id'], 'nom' => $modules[$mi]['nom_module']];
                $mi++;
            }
            if ($pi < $totalP) {
                $items[] = ['type' => 'projet', 'id' => $projets[$pi]['projet_id'], 'nom' => $projets[$pi]['nom_projet']];
                $pi++;
            }
        }

        // Afficher en rangées de 3
        $perRow = 3;
        $rows = array_chunk($items, $perRow);
        ?>
        <div class="hex-grid">
            <?php foreach ($rows as $row): ?>
            <div class="hex-grid-row">
                <?php foreach ($row as $item):
                    $href = $item['type'] === 'projet' ? 'projets.php' : 'modules.php#module-' . $item['id'];
                ?>
                <a href="<?php echo $href; ?>" class="hex-item type-<?php echo $item['type']; ?>">
                    <svg viewBox="0 0 100 115" xmlns="http://www.w3.org/2000/svg">
                        <polygon points="50,4 96,28 96,87 50,111 4,87 4,28"/>
                    </svg>
                    <div class="hex-item-inner">
                        <div class="hex-item-type"><?php echo $item['type']; ?></div>
                        <div class="hex-item-name"><?php echo htmlspecialchars(mb_strimwidth($item['nom'], 0, 20, '...')); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
</body>
</html>