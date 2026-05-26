<?php
$host = "localhost";   // serveur MySQL sur UwAmp
$db   = "db_etml";     // nom de ta base
$user = "root";        // utilisateur par défaut UwAmp
$pass = "";            // mot de passe (vide par défaut)

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}
// Connexion réussie, prêt à exécuter des requêtes
?>
