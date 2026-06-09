<?php
// j'inclus le fichier de connexion PDO et la fonction prep()
require 'db.php';

// je vérifie que la page a bien été appelée via un formulaire POST
// si quelqu'un accède directement à traitement.php via le navigateur, je bloque l'accès
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Accès interdit.');
}

// je récupère et sécurise les données envoyées par le formulaire
// le cast (int) force la conversion en entier, ce qui protège contre les injections via ce champ
$id_outil = isset($_POST['id_outil']) ? (int)$_POST['id_outil'] : 0;

// trim() supprime les espaces inutiles en début et fin de chaîne
$nom_emprunteur = isset($_POST['nom_emprunteur']) ? trim($_POST['nom_emprunteur']) : '';

// je récupère la date de retour prévue choisie par le technicien
$date_retour_prevue = isset($_POST['date_retour_prevue']) ? $_POST['date_retour_prevue'] : '';

// je vérifie que tous les champs obligatoires sont remplis
if ($id_outil === 0 || empty($nom_emprunteur) || empty($date_retour_prevue)) {
    die('Erreur : tous les champs sont obligatoires.');
}

// je vérifie que la date de retour n'est pas dans le passé
// date('Y-m-d') retourne la date du jour au format AAAA-MM-JJ
if ($date_retour_prevue < date('Y-m-d')) {
    die('Erreur : la date de retour doit être dans le futur.');
}

// je vérifie que l'outil demandé existe bien dans la base de données
// prep() prépare et exécute la requête, fetch() retourne false si aucun résultat
if (!prep('SELECT id FROM outils WHERE id = :id', [':id' => $id_outil])->fetch()) {
    die('Erreur : cet outil n\'existe pas.');
}

// je vérifie que l'outil n'est pas déjà emprunté et non rendu
// un emprunt en cours est un emprunt dont date_retour_reelle est NULL
if (prep('SELECT id FROM emprunts WHERE outil_id = :id AND date_retour_reelle IS NULL', [':id' => $id_outil])->fetch()) {
    die('Erreur : cet outil est déjà emprunté.');
}

// toutes les vérifications sont passées, j'enregistre l'emprunt dans la base
// les trois valeurs sont passées séparément pour éviter toute injection SQL
prep(
    'INSERT INTO emprunts (outil_id, nom_emprunteur, date_retour_prevue) VALUES (:outil_id, :nom, :date)',
    [
        ':outil_id' => $id_outil,          // l'identifiant de l'outil emprunté
        ':nom'      => $nom_emprunteur,    // le nom du technicien qui emprunte
        ':date'     => $date_retour_prevue // la date de retour prévue
    ]
);

// je redirige le technicien vers la page de l'outil après l'enregistrement
header('Location: index.php?id_outil=' . $id_outil . '&succes=1');
exit;
