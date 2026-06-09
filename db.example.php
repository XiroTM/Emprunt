<?php
 
// Renommez ce fichier en db.php et remplissez vos identifiants
// Copiez db.example.php en db.php et configurez vos paramètres
// mes identifiants pour me connecter à la base de données
define('DB_HOST', 'votre_host');       // ex: sql110.infinityfree.com
define('DB_NAME', 'votre_base');       // ex: if0_XXXXX_pret
define('DB_USER', 'votre_user');       // ex: if0_XXXXX
define('DB_PASS', 'votre_mot_de_passe');

try {
    // je crée une instance PDO qui représente la connexion entre PHP et MySQL
    // le dsn contient le type de base, l'hôte, le nom de la base et l'encodage des caractères
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASS
    );

    // je configure PDO pour qu'il lance une exception PHP si une requête SQL échoue
    // sans ce réglage les erreurs SQL passeraient silencieusement
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // je configure PDO pour que les résultats soient retournés sous forme de tableaux associatifs
    // cela permet d'accéder aux colonnes par leur nom ex: $ligne['nom'] plutôt que $ligne[0]
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // si la connexion échoue, j'affiche un message générique
    // je ne révèle pas les détails techniques à l'utilisateur pour des raisons de sécurité
    die('Erreur de connexion à la base de données.');
}

/**
 * Méthode prep() — raccourci pour les requêtes préparées PDO
 *
 * Cette fonction centralise la logique des requêtes préparées.
 * Au lieu de répéter prepare() et execute() dans chaque fichier,
 * j'appelle prep() en une seule ligne depuis n'importe où dans le projet.
 *
 * Les requêtes préparées protègent contre les injections SQL :
 * PDO envoie d'abord la structure de la requête à MySQL,
 * puis les valeurs séparément. MySQL traite les valeurs comme
 * des données pures, jamais comme du code SQL exécutable.
 
 * @param string $sql     La requête SQL avec des paramètres nommés ex: :id, :nom
 * @param array  $params  Le tableau associatif des valeurs à lier aux paramètres
 * @return PDOStatement   Le statement PDO exécuté, prêt pour fetch() ou fetchAll()
 */
function prep(string $sql, array $params = []): PDOStatement
{
    // j'accède à la variable $pdo définie plus haut dans ce même fichier
    // le mot-clé global est nécessaire pour accéder à une variable définie hors de la fonction
    global $pdo;

    // je prépare la requête SQL — MySQL reçoit la structure sans les valeurs
    // cela permet à MySQL d'analyser et optimiser la requête avant d'avoir les données
    $stmt = $pdo->prepare($sql);

    // j'exécute la requête en passant les valeurs séparément
    // PDO lie chaque valeur à son paramètre nommé correspondant
    // si $params est vide, execute() fonctionne quand même sans paramètres
    $stmt->execute($params);

    // je retourne le statement pour pouvoir enchaîner fetch() ou fetchAll() directement
    return $stmt;
}
