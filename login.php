<?php
// je démarre la session pour pouvoir stocker le fait que le magasinier est connecté
session_start();

// si le magasinier est déjà connecté, je le redirige direct vers l'admin
if (isset($_SESSION['admin'])) {
    header('Location: admin.php');
    exit;
}

// le mots de passe est hashé pour la sécurité
define('MOT_DE_PASSE', '$2y$10$rNDuzCbrtRpa60aKbj7.deNl2xt78txM0nhC/0iz53XWk1fuMlfW6');

$erreur = '';

// si le formulaire est envoyé
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mdp_saisi = $_POST['mot_de_passe'] ?? '';

    // je vérifie le mot de passe avec la version hashée
    if (password_verify($mdp_saisi, MOT_DE_PASSE)) {
        // mot de passe correct, le magasinier peut ce connecter
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        // mot de passe incorrect
        $erreur = 'Mot de passe incorrect.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de prêt</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        <h1>Gestion de prêt</h1>

        <!-- j'affiche l'erreur si le mot de passe est mauvais -->
        <?php if ($erreur): ?>
            <div class="erreur"><?= $erreur ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="mdp">Mot de passe :</label>
            <input type="password" id="mdp" name="mot_de_passe" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
    </div>

</body>
</html>