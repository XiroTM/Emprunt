<?php
// je démarre la session pour vérifier que le magasinier est connecté
session_start();

// seul le magasinier authentifié peut générer des QR codes
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// je charge la librairie phpqrcode qui génère les images QR code
require 'libs/phpqrcode.php';

// j'inclus le fichier de connexion PDO et la fonction prep()
require 'db.php';

// je récupère l'identifiant de l'outil depuis l'URL ex: qrcode.php?id_outil=3
$id_outil = isset($_GET['id_outil']) ? (int)$_GET['id_outil'] : 0;

// si aucun identifiant n'est passé dans l'URL
if ($id_outil === 0) {
    die('Aucun outil spécifié.');
}

// je récupère les informations de l'outil en utilisant prep()
$outil = prep('SELECT * FROM outils WHERE id = :id', [':id' => $id_outil])->fetch();

// si l'outil n'existe pas dans la base
if (!$outil) {
    die('Cet outil n\'existe pas.');
}

// je construis l'URL complète vers laquelle le QR code va pointer
// cette URL sera celle que le technicien ouvrira en scannant le QR code

// je détecte si le site utilise HTTPS ou HTTP
$protocole = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';

// je récupère le nom de domaine du serveur ex: pret.free.nf
$domaine = $_SERVER['HTTP_HOST'];

// dirname() extrait le répertoire parent du script actuel
$chemin = dirname($_SERVER['PHP_SELF']);

// je construis l'URL finale avec le protocole, le domaine, le chemin et l'id de l'outil
// exemple : https://pret.free.nf/index.php?id_outil=3
$url = $protocole . '://' . $domaine . $chemin . '/index.php?id_outil=' . $id_outil;

// je crée le dossier qrcodes s'il n'existe pas encore sur le serveur
if (!is_dir('qrcodes')) {
    mkdir('qrcodes');
}

// je définis le nom du fichier image pour ce QR code, un fichier par outil
$fichier_qr = 'qrcodes/outil_' . $id_outil . '.png';

// je génère le QR code et je le sauvegarde en image PNG
// QR_ECLEVEL_M = niveau de correction d'erreur moyen
// 10 = taille de chaque pixel du QR code
QRcode::png($url, $fichier_qr, QR_ECLEVEL_M, 10);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>QR Code - <?= htmlspecialchars($outil['nom']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 40px; }
        h1 { font-size: 20px; margin-bottom: 5px; }
        p { color: #555; font-size: 13px; margin-bottom: 20px; }
        /* taille fixe pour que le QR code soit bien lisible à l'impression */
        img.qr { width: 250px; height: 250px; border: 2px solid #ccc; padding: 10px; }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #222;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        /* je cache le bouton d'impression quand on imprime la page */
        @media print { .btn { display: none; } }
    </style>
</head>
<body>

    <h1><?= htmlspecialchars($outil['nom']) ?></h1>
    <p>Scannez ce QR code pour emprunter l'outil</p>

    <!-- j'affiche l'image du QR code généré par phpqrcode -->
    <br>
    <img class="qr" src="<?= $fichier_qr ?>" alt="QR Code <?= htmlspecialchars($outil['nom']) ?>">
    <br>

    <!-- bouton qui déclenche l'impression via JavaScript pour coller l'étiquette sur l'outil -->
    <a class="btn" href="#" onclick="window.print()">Imprimer l'étiquette</a>

</body>
</html>
