-- ============================================================
-- EcoRide — Création des tables MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS ecoride CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecoride;

-- ------------------------------------------------------------
-- Rôles
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS role (
    role_id   INT AUTO_INCREMENT PRIMARY KEY,
    libelle   VARCHAR(50) NOT NULL UNIQUE
);

-- ------------------------------------------------------------
-- Utilisateurs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateur (
    utilisateur_id  INT AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(50),
    prenom          VARCHAR(50),
    email           VARCHAR(100) UNIQUE NOT NULL,
    password        VARCHAR(255) NOT NULL,
    telephone       VARCHAR(20),
    adresse         VARCHAR(150),
    date_naissance  DATE,
    photo           VARCHAR(255),
    pseudo          VARCHAR(50) UNIQUE NOT NULL,
    credits         INT DEFAULT 20,
    statut          ENUM('actif','suspendu') DEFAULT 'actif',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Pivot utilisateur <-> role
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateur_role (
    utilisateur_id  INT NOT NULL,
    role_id         INT NOT NULL,
    PRIMARY KEY (utilisateur_id, role_id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id)        REFERENCES role(role_id)               ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Marques de véhicule
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS marque (
    marque_id   INT AUTO_INCREMENT PRIMARY KEY,
    libelle     VARCHAR(50) NOT NULL UNIQUE
);

-- ------------------------------------------------------------
-- Voitures
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS voiture (
    voiture_id                  INT AUTO_INCREMENT PRIMARY KEY,
    modele                      VARCHAR(50),
    immatriculation             VARCHAR(20) UNIQUE NOT NULL,
    energie                     ENUM('electrique','essence','diesel','hybride') NOT NULL,
    couleur                     VARCHAR(30),
    date_premiere_immatriculation VARCHAR(10),
    nb_place                    INT NOT NULL DEFAULT 4,
    utilisateur_id              INT NOT NULL,
    marque_id                   INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (marque_id)      REFERENCES marque(marque_id)
);

-- ------------------------------------------------------------
-- Covoiturages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS covoiturage (
    covoiturage_id  INT AUTO_INCREMENT PRIMARY KEY,
    date_depart     DATE        NOT NULL,
    heure_depart    TIME        NOT NULL,
    lieu_depart     VARCHAR(100) NOT NULL,
    date_arrivee    DATE        NOT NULL,
    heure_arrivee   TIME        NOT NULL,
    lieu_arrivee    VARCHAR(100) NOT NULL,
    statut          ENUM('planifie','en_cours','termine','annule') DEFAULT 'planifie',
    nb_place        INT         NOT NULL,
    prix_personne   FLOAT       NOT NULL,
    voiture_id      INT         NOT NULL,
    chauffeur_id    INT         NOT NULL,
    created_at      DATETIME    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voiture_id)   REFERENCES voiture(voiture_id),
    FOREIGN KEY (chauffeur_id) REFERENCES utilisateur(utilisateur_id)
);

-- ------------------------------------------------------------
-- Participations (passagers ↔ covoiturages)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS participe (
    utilisateur_id      INT NOT NULL,
    covoiturage_id      INT NOT NULL,
    statut              ENUM('confirme','annule','valide','litige') DEFAULT 'confirme',
    avis_envoye         TINYINT(1) DEFAULT 0,
    PRIMARY KEY (utilisateur_id, covoiturage_id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Avis (stockés aussi dans MongoDB — ici pour requêtes rapides)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS avis (
    avis_id         INT AUTO_INCREMENT PRIMARY KEY,
    commentaire     TEXT,
    note            TINYINT CHECK (note BETWEEN 1 AND 5),
    statut          ENUM('en_attente','valide','refuse') DEFAULT 'en_attente',
    chauffeur_id    INT NOT NULL,
    passager_id     INT NOT NULL,
    covoiturage_id  INT NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chauffeur_id)   REFERENCES utilisateur(utilisateur_id),
    FOREIGN KEY (passager_id)    REFERENCES utilisateur(utilisateur_id),
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id)
);

-- ------------------------------------------------------------
-- Paramètres de préférences conducteur
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS parametre (
    parametre_id    INT AUTO_INCREMENT PRIMARY KEY,
    propriete       VARCHAR(50) NOT NULL,
    valeur          VARCHAR(50) NOT NULL
);

-- ------------------------------------------------------------
-- Configuration (profil préférences d'un utilisateur)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuration (
    id_configuration    INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id      INT NOT NULL UNIQUE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Pivot configuration <-> parametre
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS dispose (
    id_configuration    INT NOT NULL,
    parametre_id        INT NOT NULL,
    PRIMARY KEY (id_configuration, parametre_id),
    FOREIGN KEY (id_configuration) REFERENCES configuration(id_configuration) ON DELETE CASCADE,
    FOREIGN KEY (parametre_id)     REFERENCES parametre(parametre_id)         ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Paramètres personnalisés libres (texte libre conducteur)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS preference_libre (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT NOT NULL,
    texte           VARCHAR(255) NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE
);
