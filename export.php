<?php
session_start();

// si pas connecté, retour au login
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// je charge la lib FPDF
require 'libs/fpdf.php';

$format = isset($_GET['format']) ? $_GET['format'] : '';

// je récupère tout l'historique des emprunts
$stmt = $pdo->query('
    SELECT 
        outils.nom AS nom_outil,
        emprunts.nom_emprunteur,
        emprunts.date_emprunt,
        emprunts.date_retour_prevue,
        emprunts.date_retour_reelle
    FROM emprunts
    JOIN outils ON emprunts.outil_id = outils.id
    ORDER BY emprunts.date_emprunt DESC
');
$emprunts = $stmt->fetchAll();

// -------------------------------------------------------
// EXPORT CSV
// -------------------------------------------------------
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="historique_emprunts.csv"');
    $sortie = fopen('php://output', 'w');
    fprintf($sortie, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($sortie, ['Outil', 'Emprunteur', 'Date emprunt', 'Retour prévu', 'Rendu le'], ';');

    foreach ($emprunts as $emprunt) {
        $date_emprunt       = date('d/m/Y', strtotime($emprunt['date_emprunt']));
        $date_retour_prevue = date('d/m/Y', strtotime($emprunt['date_retour_prevue']));
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
// EXPORT PDF avec FPDF.php
// -------------------------------------------------------
if ($format === 'pdf') {

    // je crée un nouveau document PDF en format paysage (L) pour avoir plus de place
    $pdf = new FPDF('L', 'mm', 'A4');

    // j'ajoute une page
    $pdf->AddPage();

    // je choisis la police Arial, taille 16, en gras pour le titre
    $pdf->SetFont('Arial', 'B', 16);

    // j'écris le titre centré
    $pdf->Cell(0, 10, 'Historique des emprunts', 0, 1, 'C');

    // je passe en taille 10 pour la date de génération
    $pdf->SetFont('Arial', '', 10);

    // j'écris la date de génération centré sous le titre
    $pdf->Cell(0, 8, 'Genere le ' . date('d/m/Y'), 0, 1, 'C');

    // je laisse un espace avant le tableau
    $pdf->Ln(4);

    // --- EN-TÊTE DU TABLEAU ---

    // fond gris foncé pour l'en-tête
    $pdf->SetFillColor(50, 50, 50);

    // texte blanc
    $pdf->SetTextColor(255, 255, 255);

    // police gras taille 10
    $pdf->SetFont('Arial', 'B', 10);

    // je définis les largeurs de chaque colonne en mm
    $pdf->Cell(70, 8, 'Outil',          1, 0, 'L', true);
    $pdf->Cell(55, 8, 'Emprunteur',     1, 0, 'L', true);
    $pdf->Cell(35, 8, 'Date emprunt',   1, 0, 'L', true);
    $pdf->Cell(35, 8, 'Retour prevu',   1, 0, 'L', true);
    $pdf->Cell(72, 8, 'Rendu le',       1, 1, 'L', true); // le 1 final = saut de ligne

    // --- LIGNES DU TABLEAU ---

    // je repasse le texte en noir
    $pdf->SetTextColor(0, 0, 0);

    // police normale taille 9
    $pdf->SetFont('Arial', '', 9);

    // compteur pour alterner la couleur des lignes
    $i = 0;

    foreach ($emprunts as $emprunt) {

        // une ligne sur deux en gris très clair pour la lisibilité
        if ($i % 2 === 0) {
            $pdf->SetFillColor(245, 245, 245); // gris clair
        } else {
            $pdf->SetFillColor(255, 255, 255); // blanc
        }

        // je formate les dates
        $date_emprunt       = date('d/m/Y', strtotime($emprunt['date_emprunt']));
        $date_retour_prevue = date('d/m/Y', strtotime($emprunt['date_retour_prevue']));
        $date_retour_reelle = $emprunt['date_retour_reelle']
            ? date('d/m/Y', strtotime($emprunt['date_retour_reelle']))
            : 'En cours';

        // j'écris chaque cellule avec la même largeur que l'en-tête
        $pdf->Cell(70, 7, $emprunt['nom_outil'],      1, 0, 'L', true);
        $pdf->Cell(55, 7, $emprunt['nom_emprunteur'], 1, 0, 'L', true);
        $pdf->Cell(35, 7, $date_emprunt,              1, 0, 'L', true);
        $pdf->Cell(35, 7, $date_retour_prevue,        1, 0, 'L', true);
        $pdf->Cell(72, 7, $date_retour_reelle,        1, 1, 'L', true);

        $i++;
    }

    // j'envoie le PDF directement au navigateur pour téléchargement
    // 'D' = download, 'historique_emprunts.pdf' = nom du fichier
    $pdf->Output('D', 'historique_emprunts.pdf');
    exit;
}

// si le format n'est ni csv ni pdf
die('Format invalide. Utilisez ?format=csv ou ?format=pdf');