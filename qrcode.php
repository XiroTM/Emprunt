<?php
// je démarre la session pour vérifier que le magasinier est connecté
session_start();

// si pas connecté, retour au login
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// je charge la lib QR code
require 'libs/phpqrcode.php';
require 'db.php';

// je récupère l'id de l'outil depuis l'URL : qrcode.php?id_outil=3
$id_outil = isset($_GET['id_outil']) ? (int)$_GET['id_outil'] : 0;

// si pas d'id, erreur
if ($id_outil === 0) {
    die('Aucun outil spécifié.');
}

// je récupère le nom de l'outil dans la base
$stmt = $pdo->prepare('SELECT * FROM outils WHERE id = :id');
$stmt->execute([':id' => $id_outil]);
$outil = $stmt->fetch();

// si l'outil n'existe pas
if (!$outil) {
    die('Cet outil n\'existe pas.');
}

// je construis l'URL vers laquelle le QR code va pointer
// __DIR__ = le dossier du projet, on reconstruit l'URL complète
$protocole = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
$domaine   = $_SERVER['HTTP_HOST'];
$chemin    = dirname($_SERVER['PHP_SELF']);
$url       = $protocole . '://' . $domaine . $chemin . '/index.php?id_outil=' . $id_outil;

// je crée le dossier qrcodes s'il n'existe pas encore
if (!is_dir('qrcodes')) {
    mkdir('qrcodes');
}

// nom du fichier image du QR code (un par outil)
$fichier_qr = 'qrcodes/outil_' . $id_outil . '.png';

// je génère le QR code et je le sauvegarde en image PNG
// 3 = niveau de correction d'erreur (L/M/Q/H), 10 = taille des pixels
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
        /* je cache le bouton quand on imprime */
        @media print { .btn { display: none; } }
    </style>
</head>
<body>

    <h1><?= htmlspecialchars($outil['nom']) ?></h1>
    <p>Scannez ce QR code pour emprunter l'outil</p>

    <!-- j'affiche l'image du QR code générée -->
    <br>
    <img class="qr" src="<?= $fichier_qr ?>" alt="QR Code <?= htmlspecialchars($outil['nom']) ?>">
    <br>

    <!-- bouton pour imprimer la page (et coller l'étiquette sur l'outil) -->
    <a class="btn" href="#" onclick="window.print()">Imprimer l'étiquette</a>

</body>
</html>