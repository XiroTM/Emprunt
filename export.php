<?php
// je démarre la session pour vérifier que le magasinier est connecté
session_start();

// seul le magasinier authentifié peut exporter les données
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// j'inclus le fichier de connexion PDO et la fonction prep()
require 'db.php';

// je charge la librairie FPDF qui génère des fichiers PDF en PHP
require 'libs/fpdf.php';

// je récupère le format demandé depuis l'URL : export.php?format=csv ou export.php?format=pdf
$format = isset($_GET['format']) ? $_GET['format'] : '';

// je récupère l'historique complet des emprunts avec le nom des outils
// JOIN permet de récupérer le nom de l'outil depuis la table outils
// ORDER BY date_emprunt DESC trie du plus récent au plus ancien
$emprunts = prep('
    SELECT 
        outils.nom AS nom_outil,
        emprunts.nom_emprunteur,
        emprunts.date_emprunt,
        emprunts.date_retour_prevue,
        emprunts.date_retour_reelle
    FROM emprunts
    JOIN outils ON emprunts.outil_id = outils.id
    ORDER BY emprunts.date_emprunt DESC
')->fetchAll();

// -------------------------------------------------------
// EXPORT CSV
// -------------------------------------------------------
if ($format === 'csv') {
    // je définis les headers HTTP pour indiquer au navigateur que c'est un fichier CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="historique_emprunts.csv"');

    // j'ouvre un flux de sortie direct vers le navigateur
    $sortie = fopen('php://output', 'w');

    // j'ajoute le BOM UTF-8 pour que Excel reconnaisse correctement les accents
    fprintf($sortie, chr(0xEF).chr(0xBB).chr(0xBF));

    // j'écris la ligne d'en-tête avec le point-virgule comme séparateur
    fputcsv($sortie, ['Outil', 'Emprunteur', 'Date emprunt', 'Retour prévu', 'Rendu le'], ';');

    // je parcours chaque emprunt pour l'écrire dans le CSV
    foreach ($emprunts as $emprunt) {
        // je formate les dates au format français jour/mois/année
        $date_emprunt       = date('d/m/Y', strtotime($emprunt['date_emprunt']));
        $date_retour_prevue = date('d/m/Y', strtotime($emprunt['date_retour_prevue']));

        // si l'outil n'est pas encore rendu, date_retour_reelle est NULL
        $date_retour_reelle = $emprunt['date_retour_reelle']
            ? date('d/m/Y', strtotime($emprunt['date_retour_reelle']))
            : 'En cours';

        fputcsv($sortie, [
            $emprunt['nom_outil'],
            $emprunt['nom_emprunteur'],
            $date_emprunt,
            $date_retour_prevue,
            $date_retour_reelle
        ], ';');
    }

    fclose($sortie);
    exit;
}

// -------------------------------------------------------
// EXPORT PDF avec FPDF
// -------------------------------------------------------
if ($format === 'pdf') {

    // je crée un document PDF en format paysage pour avoir plus de largeur
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();

    // titre du document en gras taille 16, centré
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Historique des emprunts', 0, 1, 'C');

    // date de génération en dessous du titre
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 8, 'Genere le ' . date('d/m/Y'), 0, 1, 'C');
    $pdf->Ln(4);

    // en-tête du tableau avec fond gris foncé et texte blanc
    $pdf->SetFillColor(50, 50, 50);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);

    // je définis les largeurs de chaque colonne en millimètres
    $pdf->Cell(70, 8, 'Outil',        1, 0, 'L', true);
    $pdf->Cell(55, 8, 'Emprunteur',   1, 0, 'L', true);
    $pdf->Cell(35, 8, 'Date emprunt', 1, 0, 'L', true);
    $pdf->Cell(35, 8, 'Retour prevu', 1, 0, 'L', true);
    // le deuxième 1 indique un saut de ligne après cette cellule
    $pdf->Cell(72, 8, 'Rendu le',     1, 1, 'L', true);

    // je repasse en noir pour les lignes de données
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // compteur pour alterner la couleur de fond des lignes
    $i = 0;

    foreach ($emprunts as $emprunt) {
        // une ligne sur deux en gris très clair pour faciliter la lecture
        $pdf->SetFillColor($i % 2 === 0 ? 245 : 255, $i % 2 === 0 ? 245 : 255, $i % 2 === 0 ? 245 : 255);

        $date_emprunt       = date('d/m/Y', strtotime($emprunt['date_emprunt']));
        $date_retour_prevue = date('d/m/Y', strtotime($emprunt['date_retour_prevue']));
        $date_retour_reelle = $emprunt['date_retour_reelle']
            ? date('d/m/Y', strtotime($emprunt['date_retour_reelle']))
            : 'En cours';

        // j'écris chaque cellule avec les mêmes largeurs que les en-têtes
        $pdf->Cell(70, 7, $emprunt['nom_outil'],      1, 0, 'L', true);
        $pdf->Cell(55, 7, $emprunt['nom_emprunteur'], 1, 0, 'L', true);
        $pdf->Cell(35, 7, $date_emprunt,              1, 0, 'L', true);
        $pdf->Cell(35, 7, $date_retour_prevue,        1, 0, 'L', true);
        $pdf->Cell(72, 7, $date_retour_reelle,        1, 1, 'L', true);

        $i++;
    }

    // j'envoie le PDF au navigateur en forçant le téléchargement
    $pdf->Output('D', 'historique_emprunts.pdf');
    exit;
}

// si le format passé en paramètre n'est ni csv ni pdf
die('Format invalide. Utilisez ?format=csv ou ?format=pdf');
