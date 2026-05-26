<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <title>Intro — Portfolio</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <style>
        /* --- style minimal pour tester --- */
        html,body { height:100%; margin:0; }
        body {
            display:flex;
            align-items:center;
            justify-content:center;
            background:#0f1720;
            font-family:system-ui,Segoe UI,Roboto,"Helvetica Neue",Arial;
        }

        .intro-logo {
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
        }

        .intro-logo img {
            width: 400px;               /* ajuste la taille */
            max-width:60vw;
            opacity: 0;
            transform: scale(0.98);
            animation: fadeInOut 4000ms ease-in-out forwards;
        }

        @keyframes fadeInOut {
            0%   { opacity: 0;   transform: scale(0.90); }
            40%  { opacity: 0.45; transform: scale(0.96); }
            60%  { opacity: 1;    transform: scale(1); }
            80%  { opacity: 1;    transform: scale(1.01); }
            100% { opacity: 0;    transform: scale(1.05); }
        }

        /* message discret pendant le chargement si nécessaire */
        .sr-only {
            position: absolute;
            left: -9999px;
            width: 1px;
            height: 1px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="intro-logo" aria-hidden="false">
        <img id="introLogo" src="logo.jpg" alt="Logo">
        <span class="sr-only">Chargement...</span>
    </div>

    <script>
        (function() {
            const logo = document.getElementById('introLogo');

            // fallback : si l'animation ne déclenche pas "animationend", redirige après la durée
            const ANIM_DURATION_MS = 4000;
            let fallbackTimeout = null;

            function redirectToHome() {
                // évite double redirection
                if (window.__redirecting) return;
                window.__redirecting = true;
                // remplace par le chemin de ta page d'accueil
                window.location.href = "accueil.php";
            }

            // 1)Gestion "animationend" sur l'image (la plupart des navigateurs)
            logo.addEventListener('animationend', (ev) => {
                redirectToHome();
            });

            // 2) fallback : redirection au cas où l'événement animationend ne s'est pas produit
            fallbackTimeout = setTimeout(() => {
                redirectToHome();
            }, ANIM_DURATION_MS + 200); // petit buffer

            // 3) si l'image n'existe pas (404), on veut éviter rester bloqué : on détecte erreur de chargement
            logo.addEventListener('error', () => {
                // image non trouvée -> redirection immédiate
                clearTimeout(fallbackTimeout);
                redirectToHome();
            });

            // 4) si on veut forcer l'animation au rechargement (utile lors de refresh)
            // remove/re-add de la classe d'animation si nécessaire (ici l'animation est en CSS inline)
        })();
    </script>
</body>
</html>
