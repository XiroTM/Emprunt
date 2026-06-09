<?php
// j'inclus le fichier de connexion PDO et la fonction prep()
require 'db.php';

// je récupère l'identifiant de l'outil depuis l'URL ex: index.php?id_outil=3
// le cast (int) force la conversion en entier pour éviter toute injection via ce paramètre
$id_outil = isset($_GET['id_outil']) ? (int)$_GET['id_outil'] : 0;

// si aucun id n'est présent dans l'URL, le QR code est invalide
if ($id_outil === 0) {
    die('QR code invalide. Aucun outil trouvé.');
}

// je récupère les informations de l'outil en utilisant la fonction prep()
// prep() prépare et exécute la requête en une seule ligne
// les injections SQL sont impossibles car la valeur $id_outil est passée séparément
$outil = prep('SELECT * FROM outils WHERE id = :id', [':id' => $id_outil])->fetch();

// si aucun outil ne correspond à cet identifiant dans la base
if (!$outil) {
    die('Cet outil n\'existe pas dans la base de données.');
}

// je vérifie si l'outil est actuellement emprunté et pas encore rendu
// un emprunt en cours est un emprunt dont date_retour_reelle est NULL
$emprunt_en_cours = prep(
    'SELECT * FROM emprunts WHERE outil_id = :id AND date_retour_reelle IS NULL',
    [':id' => $id_outil]
)->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <!-- viewport rend la page utilisable sur smartphone, essentiel pour le scan QR code -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- htmlspecialchars protège contre les attaques XSS en échappant les caractères spéciaux -->
    <title><?= htmlspecialchars($outil['nom']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">

        <!-- j'affiche la photo de l'outil seulement si elle existe en base de données -->
        <?php if (!empty($outil['photo'])): ?>
            <img src="photos/<?= htmlspecialchars($outil['photo']) ?>" alt="<?= htmlspecialchars($outil['nom']) ?>">
        <?php endif; ?>

        <!-- j'affiche le nom de l'outil -->
        <h1><?= htmlspecialchars($outil['nom']) ?></h1>

        <?php if ($emprunt_en_cours): ?>
            <!-- l'outil est déjà emprunté, j'affiche les informations sur l'emprunt en cours -->
            <div class="indisponible">
                Cet outil est actuellement indisponible.
                <p>Emprunté par : <strong><?= htmlspecialchars($emprunt_en_cours['nom_emprunteur']) ?></strong></p>
                <!-- je formate la date au format français jour/mois/année -->
                <p>Retour prévu le : <strong><?= date('d/m/Y', strtotime($emprunt_en_cours['date_retour_prevue'])) ?></strong></p>
            </div>

        <?php else: ?>
            <!-- l'outil est disponible, j'affiche le formulaire d'emprunt -->
            <form action="traitement.php" method="POST">

                <!-- je passe l'identifiant de l'outil en champ caché, le technicien ne le voit pas -->
                <input type="hidden" name="id_outil" value="<?= $id_outil ?>">

                <!-- champ texte pour le nom et prénom de la personne qui emprunte -->
                <label for="nom">Votre nom :</label>
                <input type="text" id="nom" name="nom_emprunteur" placeholder="Nom Prénom" required>

                <!-- sélecteur de date pour la date de retour prévue -->
                <label for="date">Date de retour prévue :</label>
                <input type="date" id="date" name="date_retour_prevue" required>

                <button type="submit">Emprunter</button>

            </form>
        <?php endif; ?>

    </div>

</body>
</html>
