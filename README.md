Système de Gestion des Prêts d'Outils

Application web PHP/MySQL développée dans le cadre d'un stage BTS SIO SLAM — Session 2026


Présentation :
Ce projet a été développé lors d'un stage en entreprise dans le cadre du BTS SIO option SLAM. L'objectif était de résoudre un problème concret : le magasinier d'une entreprise gérait les prêts d'outils manuellement sur papier, ce qui causait des pertes, des oublis et une perte de temps importante.
Solution apportée : une application web accessible depuis n'importe quel smartphone via un QR code collé sur chaque outil. Le technicien scanne le QR code, remplit un formulaire en quelques secondes, et le magasinier suit tout depuis son tableau de bord.
Application en ligne : pret.free.nf

 
Fonctionnalités :

Côté technicien (public)

 Scan QR Code → page d'emprunt instantanée
 Formulaire d'emprunt → nom + date de retour prévue
 Outil indisponible → affichage du nom de l'emprunteur et date de retour
 Confirmation après emprunt enregistré

Côté magasinier (admin)

 Connexion sécurisée → mot de passe hashé bcrypt
 Dashboard → vue des emprunts en cours triés par date de retour
 Historique → tous les retours enregistrés
 Gestion des outils → ajouter/supprimer avec photo
 Génération QR code → un QR code par outil, imprimable
 Export PDF → historique formaté pour impression
 Export CSV → données exploitables dans Excel


Technologies utilisées : 
TechnologieUsagePHP 8+Backend, logique métierMySQLBase de donnéesPDOConnexion sécurisée à la BDDphpqrcodeGénération des QR codesFPDFGénération des PDFHTML/CSSInterface utilisateur responsive

Structure de la base de données :
sqloutils (id, nom, photo)
emprunts (id, outil_id, nom_emprunteur, date_emprunt, date_retour_prevue, date_retour_reelle)
Relation : un outil peut avoir plusieurs emprunts. Un emprunt est lié à un seul outil.

Structure du projet :
gestion-prets/
├── index.php           # Page emprunt utilisateur (accès via QR code)
├── login.php           # Connexion magasinier
├── admin.php           # Tableau de bord magasinier
├── traitement.php      # Traitement formulaire d'emprunt
├── qrcode.php          # Génération et affichage QR code
├── export.php          # Export CSV et PDF
├── db.example.php      # Exemple de configuration BDD
├── style.css           # Feuille de styles
├── db.sql              # Structure SQL de la base de données
└── libs/
    ├── phpqrcode.php   # Librairie génération QR code
    └── fpdf.php        # Librairie génération PDF

Installation :
Prérequis

PHP 8+
MySQL 8+
Serveur web (Apache, Nginx ou VPS)

Étapes :

1. Cloner le projet
bashgit clone https://github.com/XiroTM/gestion-prets.git1
cd gestion-prets

2. Importer la base de données
bashmysql -u votre_user -p votre_base < db.sql

3. Configurer la connexion
Copier le fichier exemple et le renommer :
bashcp db.example.php db.php
Puis renseigner vos identifiants dans db.php :
phpdefine('DB_HOST', 'localhost');
define('DB_NAME', 'votre_base');
define('DB_USER', 'votre_user');
define('DB_PASS', 'votre_mot_de_passe');

4. Définir le mot de passe admin
Dans login.php, générer votre hash et remplacer la constante :
php// Générer un hash (à faire une seule fois)
echo password_hash('votre_mot_de_passe', PASSWORD_DEFAULT);
// Puis remplacer dans login.php :
define('MOT_DE_PASSE', 'votre_hash_généré');

5. Créer les dossiers nécessaires
bashmkdir photos qrcodes
chmod 755 photos qrcodes

6. Accéder à l'application

Interface technicien : https://votre-domaine.com/index.php?id_outil=1
Interface admin : https://votre-domaine.com/login.php


Sécurité :
MesureDétailHashage bcryptMots de passe hashés avec password_hash() PASSWORD_DEFAULTRequêtes préparées PDOProtection contre les injections SQLhtmlspecialchars()Protection contre les attaques XSSSessions PHPAuthentification magasinier sécuriséeValidation serveurToutes les entrées vérifiées côté serveurVérification méthode POSTAccès direct aux pages de traitement bloqué

Fonctionnement :
1. Admin ajoute un outil → génère QR code → imprime l'étiquette → colle sur l'outil
2. Technicien scanne le QR code avec son téléphone
3. Page d'emprunt s'affiche avec le nom de l'outil
4. Si disponible → formulaire (nom + date retour)
5. Si indisponible → affiche qui l'a et quand il revient
6. Admin valide le retour depuis son dashboard
7. Admin exporte l'historique en PDF ou CSV

Aperçu :
Page emprunt (mobile)Dashboard adminFormulaire responsive optimisé pour smartphoneVue complète des emprunts en cours et historique

Auteur :
Grégory KSAS
BTS SIO option SLAM — Session 2026
Formation : CNED
Stage réalisé en entreprise — Montpellier

Licence :
Ce projet est Open source.
