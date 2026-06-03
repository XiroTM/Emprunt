<?php
// je démarre la session
session_start();
require 'db.php';

// si le magasinier est pas connecté, je le redirige vers login
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// si le magasinier clique sur déconnecter
if (isset($_GET['deconnexion'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// si le magasinier valide un retour d'outil
if (isset($_POST['retour_outil'])) {
    $id_emprunt = (int)$_POST['id_emprunt'];
    $stmt = $pdo->prepare('UPDATE emprunts SET date_retour_reelle = NOW() WHERE id = :id');
    $stmt->execute([':id' => $id_emprunt]);
    header('Location: admin.php');
    exit;
}

// si le magasinier ajoute un outil
if (isset($_POST['ajouter_outil'])) {
    $nom = trim($_POST['nom_outil']);
    $photo = '';

    // je gère l'upload de la photo
    if (!empty($_FILES['photo']['name'])) {
        $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $nom_fichier = uniqid() . '.' . $extension;
        move_uploaded_file($_FILES['photo']['tmp_name'], 'photos/' . $nom_fichier);
        $photo = $nom_fichier;
    }

    $stmt = $pdo->prepare('INSERT INTO outils (nom, photo) VALUES (:nom, :photo)');
    $stmt->execute([':nom' => $nom, ':photo' => $photo]);
    header('Location: admin.php');
    exit;
}

// si le magasinier supprime un outil
if (isset($_POST['supprimer_outil'])) {
    $id_outil = (int)$_POST['id_outil'];
    $stmt = $pdo->prepare('DELETE FROM outils WHERE id = :id');
    $stmt->execute([':id' => $id_outil]);
    header('Location: admin.php');
    exit;
}

// je récupère tous les emprunts en cours
$emprunts_en_cours = $pdo->query('
    SELECT emprunts.*, outils.nom AS nom_outil 
    FROM emprunts 
    JOIN outils ON emprunts.outil_id = outils.id 
    WHERE emprunts.date_retour_reelle IS NULL
    ORDER BY emprunts.date_retour_prevue ASC
')->fetchAll();

// je récupère tout l'historique des emprunts
$historique = $pdo->query('
    SELECT emprunts.*, outils.nom AS nom_outil 
    FROM emprunts 
    JOIN outils ON emprunts.outil_id = outils.id 
    WHERE emprunts.date_retour_reelle IS NOT NULL
    ORDER BY emprunts.date_retour_reelle DESC
')->fetchAll();

// je récupère tous les outils
$outils = $pdo->query('SELECT * FROM outils ORDER BY nom ASC')->fetchAll();
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
        <a href="admin.php?deconnexion=1" class="btn-deconnexion">Se déconnecter</a>
    </div>

    <!-- section emprunts en cours -->
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
                    <td><?= htmlspecialchars($emprunt['nom_outil']) ?></td>
                    <td><?= htmlspecialchars($emprunt['nom_emprunteur']) ?></td>
                    <td><?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($emprunt['date_retour_prevue'])) ?></td>
                    <td>
                        <!-- bouton pour marquer l'outil comme rendu -->
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

    <!-- section historique -->
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

    <!-- section gestion des outils -->
    <h2>Gestion des outils</h2>

    <!-- formulaire pour ajouter un outil -->
    <form method="POST" enctype="multipart/form-data" class="form-ajout">
        <input type="text" name="nom_outil" placeholder="Nom de l'outil" required>
        <input type="file" name="photo" accept="image/*">
        <button type="submit" name="ajouter_outil">Ajouter</button>
    </form>

    <!-- liste des outils -->
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
                    <!-- lien vers qrcode.php avec l'id de l'outil, ouvre dans un nouvel onglet -->
                    <a href="qrcode.php?id_outil=<?= $outil['id'] ?>" target="_blank">QR Code</a>

                    <!-- bouton supprimer avec confirmation, style inline pour être à côté du lien -->
                    <form method="POST"
                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet outil ?')"
                          style="display:inline;">
                        <input type="hidden" name="id_outil" value="<?= $outil['id'] ?>">
                        <button type="submit" name="supprimer_outil">Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- export en CSV et PDF -->
    <h2>Export</h2>
    <a href="export.php?format=csv">Exporter en CSV</a>
    <a href="export.php?format=pdf">Exporter en PDF</a>

</div>

</body>
</html>