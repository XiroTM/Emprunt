<?php
// je récupère la connexion PDO
require 'db.php';

// je vérifie que le formulaire a bien été envoyé en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Accès interdit.');
}

// je récupère les données envoyées par le formulaire
$id_outil = isset($_POST['id_outil']) ? (int)$_POST['id_outil'] : 0;
$nom_emprunteur = isset($_POST['nom_emprunteur']) ? trim($_POST['nom_emprunteur']) : '';
$date_retour_prevue = isset($_POST['date_retour_prevue']) ? $_POST['date_retour_prevue'] : '';

// je vérifie que toutes les données sont bien remplies
if ($id_outil === 0 || empty($nom_emprunteur) || empty($date_retour_prevue)) {
    die('Erreur : tous les champs sont obligatoires.');
}

// je vérifie que la date de retour est pas dans le passé
if ($date_retour_prevue < date('Y-m-d')) {
    die('Erreur : la date de retour doit être dans le futur.');
}

// je vérifie que l'outil existe bien dans la DB
$stmt = $pdo->prepare('SELECT id FROM outils WHERE id = :id');
$stmt->execute([':id' => $id_outil]);
if (!$stmt->fetch()) {
    die('Erreur : cet outil n\'existe pas.');
}

// je vérifie que l'outil est pas déjà emprunté
$stmt2 = $pdo->prepare('SELECT id FROM emprunts WHERE outil_id = :id AND date_retour_reelle IS NULL');
$stmt2->execute([':id' => $id_outil]);
if ($stmt2->fetch()) {
    die('Erreur : cet outil est déjà emprunté.');
}

// tout est bon, j'enregistre l'emprunt dans la DB
$stmt3 = $pdo->prepare('INSERT INTO emprunts (outil_id, nom_emprunteur, date_retour_prevue) VALUES (:outil_id, :nom, :date)');
$stmt3->execute([
    ':outil_id' => $id_outil,
    ':nom'      => $nom_emprunteur,
    ':date'     => $date_retour_prevue
]);

// je redirige vers index.php avec un message de succès
header('Location: index.php?id_outil=' . $id_outil . '&succes=1');
exit;