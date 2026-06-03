<?php
 
// Renommez ce fichier en db.php et remplissez vos identifiants
// Copiez db.example.php en db.php et configurez vos paramètres
 
define('DB_HOST', 'votre_host');       // ex: sql110.infinityfree.com
define('DB_NAME', 'votre_base');       // ex: if0_XXXXX_pret
define('DB_USER', 'votre_user');       // ex: if0_XXXXX
define('DB_PASS', 'votre_mot_de_passe');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
 
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données.');
}
 