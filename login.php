<?php
// je démarre la session PHP pour pouvoir stocker des données entre les pages
// la session permet de savoir si le magasinier est connecté ou non
session_start();

// si le magasinier est déjà connecté, je le redirige directement vers le tableau de bord
// inutile de lui afficher le formulaire de connexion s'il est déjà authentifié
if (isset($_SESSION['admin'])) {
    header('Location: admin.php');
    exit; // j'arrête l'exécution du script après la redirection
}

// je stocke le hash bcrypt du mot de passe administrateur
// le mot de passe n'est jamais stocké en clair, seulement son empreinte irréversible
// même si quelqu'un accède à ce fichier, il ne peut pas retrouver le mot de passe original
define('MOT_DE_PASSE', '$2y$10$rNDuzCbrtRpa60aKbj7.deNl2xt78txM0nhC/0iz53XWk1fuMlfW6');

// je prépare une variable pour stocker le message d'erreur éventuel
$erreur = '';

// je vérifie si le formulaire a été soumis via la méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // je récupère le mot de passe saisi par l'utilisateur
    // l'opérateur ?? retourne une chaîne vide si la clé n'existe pas dans $_POST
    $mdp_saisi = $_POST['mot_de_passe'] ?? '';

    // je compare le mot de passe saisi avec le hash stocké
    // password_verify recalcule le hash du mot de passe saisi et compare
    // c'est la seule façon sécurisée de vérifier un mot de passe hashé en bcrypt
    if (password_verify($mdp_saisi, MOT_DE_PASSE)) {

        // le mot de passe est correct
        // je marque la session comme authentifiée en stockant la valeur true
        $_SESSION['admin'] = true;

        // je redirige le magasinier vers son tableau de bord
        header('Location: admin.php');
        exit;

    } else {
        // le mot de passe est incorrect, je prépare le message d'erreur
        // je ne précise pas si c'est le login ou le mot de passe qui est faux
        // pour éviter de donner des informations à un attaquant
        $erreur = 'Mot de passe incorrect.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <!-- viewport permet au site d'être responsive sur mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de prêt</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        <h1>Gestion de prêt</h1>

        <!-- j'affiche le message d'erreur uniquement si la variable est non vide -->
        <?php if ($erreur): ?>
            <div class="erreur"><?= $erreur ?></div>
        <?php endif; ?>

        <!-- formulaire de connexion, envoi en POST pour ne pas exposer le mot de passe dans l'URL -->
        <form action="login.php" method="POST">
            <label for="mdp">Mot de passe :</label>
            <!-- type="password" masque les caractères saisis -->
            <input type="password" id="mdp" name="mot_de_passe" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
    </div>

</body>
</html>
