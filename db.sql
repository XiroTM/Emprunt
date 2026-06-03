-- table des outils du stock--
CREATE TABLE outils (
    id INT AUTO_INCREMENT PRIMARY KEY, -- id unique de chaque outil --
    nom VARCHAR(255) NOT NULL, -- nom de l'outil ex: Milwaukee visseuse n°1 --
    photo VARCHAR(255) NULL -- nom du fichier photo ex: visseuse1.jpg --
);

-- table des emprunts --
CREATE TABLE emprunts (
    id INT AUTO_INCREMENT PRIMARY KEY, -- l'id unique de chaque emprunt --
    outil_id INT NOT NULL, -- id de l'outil emprunté, lié à la table outils --
    nom_emprunteur VARCHAR(255) NOT NULL, -- le nom du technicien qui emprunte --
    date_emprunt DATETIME DEFAULT CURRENT_TIMESTAMP, -- la date se met automatiquement --
    date_retour_prevue DATE NOT NULL, -- la date que le technicien choisit --
    date_retour_reelle DATETIME NULL DEFAULT NULL, -- rempli quand l'outil est rendu --
    FOREIGN KEY (outil_id) REFERENCES outils(id) -- je lie outil_id à la table outils --
);