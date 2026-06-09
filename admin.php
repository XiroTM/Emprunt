<?php
// je démarre la session pour vérifier que le magasinier est connecté
session_start();

// j'inclus le fichier de connexion PDO et la fonction prep()
require 'db.php';

// si la session admin n'est pas définie, je redirige vers la connexion
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// si le magasinier clique sur déconnecter : admin.php?deconnexion=1
if (isset($_GET['deconnexion'])) {
    // je détruis toutes les données de session
    session_destroy();
    header('Location: login.php');
    exit;
}

// si le magasinier valide le retour d'un outil
if (isset($_POST['retour_outil'])) {
    $id_emprunt = (int)$_POST['id_emprunt'];

    // je mets à jour la date de retour réelle avec la date et l'heure actuelles
    // NOW() est une fonction MySQL qui retourne le timestamp courant
    prep('UPDATE emprunts SET date_retour_reelle = NOW() WHERE id = :id', [':id' => $id_emprunt]);

    header('Location: admin.php');
    exit;
}

// si le magasinier ajoute un nouvel outil
if (isset($_POST['ajouter_outil'])) {
    $nom = trim($_POST['nom_outil']);
    $photo = '';

    // je gère l'upload de la photo si une image a été sélectionnée
    if (!empty($_FILES['photo']['name'])) {
        // je récupère l'extension du fichier original ex: jpg, png
        $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);

        // je génère un nom de fichier unique pour éviter les conflits
        // uniqid() génère un identifiant unique basé sur le timestamp
        $nom_fichier = uniqid() . '.' . $extension;

        // je déplace le fichier temporaire vers le dossier photos
        move_uploaded_file($_FILES['photo']['tmp_name'], 'photos/' . $nom_fichier);
        $photo = $nom_fichier;
    }

    // j'insère le nouvel outil dans la base via prep()
    prep('INSERT INTO outils (nom, photo) VALUES (:nom, :photo)', [':nom' => $nom, ':photo' => $photo]);

    header('Location: admin.php');
    exit;
}

// si le magasinier supprime un outil
if (isset($_POST['supprimer_outil'])) {
    $id_outil = (int)$_POST['id_outil'];

    // je supprime l'outil de la base via prep()
    prep('DELETE FROM outils WHERE id = :id', [':id' => $id_outil]);

    header('Location: admin.php');
    exit;
}

// je récupère tous les emprunts en cours (date_retour_reelle est NULL = pas encore rendu)
// JOIN me permet de récupérer le nom de l'outil depuis la table outils en une seule requête
// ORDER BY date_retour_prevue ASC trie par date de retour la plus proche en premier
$emprunts_en_cours = prep('
    SELECT emprunts.*, outils.nom AS nom_outil 
    FROM emprunts 
    JOIN outils ON emprunts.outil_id = outils.id 
    WHERE emprunts.date_retour_reelle IS NULL
    ORDER BY emprunts.date_retour_prevue ASC
')->fetchAll();

// je récupère l'historique complet des emprunts rendus
// ORDER BY date_retour_reelle DESC trie du plus récent au plus ancien
$historique = prep('
    SELECT emprunts.*, outils.nom AS nom_outil 
    FROM emprunts 
    JOIN outils ON emprunts.outil_id = outils.id 
    WHERE emprunts.date_retour_reelle IS NOT NULL
    ORDER BY emprunts.date_retour_reelle DESC
')->fetchAll();

// je récupère la liste de tous les outils triés par ordre alphabétique
$outils = prep('SELECT * FROM outils ORDER BY nom ASC')->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Magasinier</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="admin-header">
        <h1>Tableau de bord</h1>
        <!-- la déconnexion passe par un paramètre GET dans l'URL -->
        <a href="admin.php?deconnexion=1" class="btn-deconnexion">Se déconnecter</a>
    </div>

    <!-- liste des emprunts actuellement en cours -->
    <h2>Emprunts en cours</h2>
    <?php if (empty($emprunts_en_cours)): ?>
        <p>Aucun emprunt en cours.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Outil</th>
                    <th>Emprunteur</th>
                    <th>Emprunté le</th>
                    <th>Retour prévu</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emprunts_en_cours as $emprunt): ?>
                <tr>
                    <!-- htmlspecialchars protège contre les attaques XSS sur toutes les données affichées -->
                    <td><?= htmlspecialchars($emprunt['nom_outil']) ?></td>
                    <td><?= htmlspecialchars($emprunt['nom_emprunteur']) ?></td>
                    <!-- je formate les dates au format français jour/mois/année -->
                    <td><?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($emprunt['date_retour_prevue'])) ?></td>
                    <td>
                        <!-- formulaire avec champ caché pour valider le retour -->
                        <form method="POST">
                            <input type="hidden" name="id_emprunt" value="<?= $emprunt['id'] ?>">
                            <button type="submit" name="retour_outil">Rendu</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- historique de tous les emprunts rendus -->
    <h2>Historique des retours</h2>
    <?php if (empty($historique)): ?>
        <p>Aucun retour enregistré.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Outil</th>
                    <th>Emprunteur</th>
                    <th>Emprunté le</th>
                    <th>Retour prévu</th>
                    <th>Rendu le</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historique as $emprunt): ?>
                <tr>
                    <td><?= htmlspecialchars($emprunt['nom_outil']) ?></td>
                    <td><?= htmlspecialchars($emprunt['nom_emprunteur']) ?></td>
                    <td><?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($emprunt['date_retour_prevue'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($emprunt['date_retour_reelle'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- gestion du stock : ajout, suppression, génération QR code -->
    <h2>Gestion des outils</h2>

    <!-- formulaire d'ajout avec upload de photo -->
    <!-- enctype multipart/form-data est obligatoire pour envoyer des fichiers -->
    <form method="POST" enctype="multipart/form-data" class="form-ajout">
        <input type="text" name="nom_outil" placeholder="Nom de l'outil" required>
        <input type="file" name="photo" accept="image/*">
        <button type="submit" name="ajouter_outil">Ajouter</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Photo</th>
                <th>Nom</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($outils as $outil): ?>
            <tr>
                <td>
                    <?php if (!empty($outil['photo'])): ?>
                        <img src="photos/<?= htmlspecialchars($outil['photo']) ?>"
                             style="width:50px; height:50px; object-fit:cover;">
                    <?php else: ?>
                        Pas de photo
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($outil['nom']) ?></td>
                <td>
                    <!-- lien vers la page de génération du QR code, ouvre dans un nouvel onglet -->
                    <a href="qrcode.php?id_outil=<?= $outil['id'] ?>" target="_blank">QR Code</a>

                    <!-- formulaire de suppression avec confirmation JavaScript -->
                    <form method="POST"
                          onsubmit="return confirm('Etes-vous sur de vouloir supprimer cet outil ?')"
                          style="display:inline;">
                        <input type="hidden" name="id_outil" value="<?= $outil['id'] ?>">
                        <button type="submit" name="supprimer_outil">Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- liens vers l'export, le format est passé en paramètre GET -->
    <h2>Export</h2>
    <a href="export.php?format=csv">Exporter en CSV</a>
    <a href="export.php?format=pdf">Exporter en PDF</a>

</div>

</body>
</html>
