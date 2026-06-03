<?php
// je récupère la connexion PDO
require 'db.php';

// je récupère l'id de l'outil depuis l'url ex: index.php?id_outil=1
$id_outil = isset($_GET['id_outil']) ? (int)$_GET['id_outil'] : 0;

// si pas d'id dans l'url, j'affiche une erreur
if ($id_outil === 0) {
    die('QR code invalide. Aucun outil trouvé.');
}

// je cherche l'outil dans la base de données
$stmt = $pdo->prepare('SELECT * FROM outils WHERE id = :id');
$stmt->execute([':id' => $id_outil]);
$outil = $stmt->fetch();

// si l'outil n'existe pas dans la db
if (!$outil) {
    die('Cet outil n\'existe pas dans la base de données.');
}

// je vérifie si l'outil est déjà emprunté et pas encore rendu
$stmt2 = $pdo->prepare('SELECT * FROM emprunts WHERE outil_id = :id AND date_retour_reelle IS NULL');
$stmt2->execute([':id' => $id_outil]);
$emprunt_en_cours = $stmt2->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <!--Pour que le site soit responsive (mobile)-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($outil['nom']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">

        <!--je vérifie si l'outil a une photo, sinon j'affiche rien-->
        <?php if (!empty($outil['photo'])): ?>
            <img src="photos/<?= htmlspecialchars($outil['photo']) ?>" alt="<?= htmlspecialchars($outil['nom']) ?>">
        <?php endif; ?>

        <!--nom de l'outil -->
        <h1><?= htmlspecialchars($outil['nom']) ?></h1>

        <?php if ($emprunt_en_cours): ?>
            <!--si l'outil est déjà emprunté, j'affiche un message-->
            <div class="indisponible">
                 Cet outil est actuellement indisponible.
                <p>Emprunté par : <strong><?= htmlspecialchars($emprunt_en_cours['nom_emprunteur']) ?></strong></p>
                <p>Retour prévu le : <strong><?= date('d/m/Y', strtotime($emprunt_en_cours['date_retour_prevue'])) ?></strong></p>
            </div>

        <?php else: ?>
            <!--sinon j'affiche le formulaire d'emprunt-->
            <form action="traitement.php" method="POST">

                <!--je passe l'id de l'outil en caché, le technicien le voit pas-->
                <input type="hidden" name="id_outil" value="<?= $id_outil ?>">

                <!--text box pour le nom et prenom-->
                <label for="nom">Votre nom :</label> 
                <input type="text" id="nom" name="nom_emprunteur" placeholder="Nom Prénom" required>
                
                <!--calendrier pour date de retour prévue-->
                <label for="date">Date de retour prévue :</label>
                <input type="date" id="date" name="date_retour_prevue" required>

                <!--Bouton d'emprunt-->
                <button type="submit">Emprunter</button>

            </form>
        <?php endif; ?>

    </div>

</body>
</html>