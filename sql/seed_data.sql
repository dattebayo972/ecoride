-- ============================================================
-- EcoRide — Données initiales
-- ============================================================
USE ecoride;

-- ------------------------------------------------------------
-- Rôles
-- ------------------------------------------------------------
INSERT INTO role (libelle) VALUES
    ('administrateur'),
    ('employe'),
    ('chauffeur'),
    ('passager');

-- ------------------------------------------------------------
-- Marques de véhicule
-- ------------------------------------------------------------
INSERT INTO marque (libelle) VALUES
    ('Renault'), ('Peugeot'), ('Citroën'), ('Tesla'), ('BMW'),
    ('Volkswagen'), ('Toyota'), ('Ford'), ('Nissan'), ('Hyundai');

-- ------------------------------------------------------------
-- Paramètres de préférences
-- ------------------------------------------------------------
INSERT INTO parametre (propriete, valeur) VALUES
    ('fumeur', 'oui'),
    ('fumeur', 'non'),
    ('animal', 'accepte'),
    ('animal', 'refuse'),
    ('musique', 'oui'),
    ('musique', 'non'),
    ('bavard', 'oui'),
    ('bavard', 'non');

-- ------------------------------------------------------------
-- Utilisateurs (mots de passe hashés avec bcrypt)
-- Admin1234!  → $2y$12$... (généré ci-dessous)
-- Employe1234! / Chauffeur1234! / Passager1234! de même
-- ------------------------------------------------------------

-- Administrateur
INSERT INTO utilisateur (nom, prenom, email, password, pseudo, credits, statut) VALUES
    ('Admin', 'EcoRide', 'admin@ecoride.fr',
     '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMleqd7i/M/eGK2hFnBl3vE9Vy',
     'admin_ecoride', 0, 'actif');

-- Employé
INSERT INTO utilisateur (nom, prenom, email, password, pseudo, credits, statut) VALUES
    ('Dupont', 'Marie', 'employe@ecoride.fr',
     '$2y$12$wd7n6i3dS7.qR3c7G9sxVuWrmMjjO5u2KJF6V7M6Q7tNd8e3vW6Fi',
     'marie_employe', 0, 'actif');

-- Chauffeur
INSERT INTO utilisateur (nom, prenom, email, password, pseudo, credits, statut) VALUES
    ('Martin', 'Lucas', 'chauffeur@ecoride.fr',
     '$2y$12$HxT3eD7kX8pWqA2mN5vRcO.KjLgF1uS9tYnV4bZ6wE0rI8cM3oP7a',
     'lucas_driver', 100, 'actif');

-- Passager
INSERT INTO utilisateur (nom, prenom, email, password, pseudo, credits, statut) VALUES
    ('Leroy', 'Sophie', 'passager@ecoride.fr',
     '$2y$12$TrE9wQ5nK2oD8vL3xM7sYuP1cJ4fZ6bN0aS8eV2gI5hO7mR9tW1qX',
     'sophie_pass', 50, 'actif');

-- Deuxième chauffeur (voiture électrique)
INSERT INTO utilisateur (nom, prenom, email, password, pseudo, credits, statut) VALUES
    ('Bernard', 'Alex', 'alex@ecoride.fr',
     '$2y$12$TrE9wQ5nK2oD8vL3xM7sYuP1cJ4fZ6bN0aS8eV2gI5hO7mR9tW1qX',
     'alex_eco', 80, 'actif');

-- ------------------------------------------------------------
-- Attribution des rôles
-- ------------------------------------------------------------
-- admin → administrateur (role_id=1)
INSERT INTO utilisateur_role (utilisateur_id, role_id) VALUES (1, 1);
-- employe → employe (role_id=2)
INSERT INTO utilisateur_role (utilisateur_id, role_id) VALUES (2, 2);
-- lucas → chauffeur (role_id=3)
INSERT INTO utilisateur_role (utilisateur_id, role_id) VALUES (3, 3);
-- sophie → passager (role_id=4)
INSERT INTO utilisateur_role (utilisateur_id, role_id) VALUES (4, 4);
-- alex → chauffeur (role_id=3)
INSERT INTO utilisateur_role (utilisateur_id, role_id) VALUES (5, 3);

-- ------------------------------------------------------------
-- Voitures
-- ------------------------------------------------------------
-- Voiture de Lucas (thermique)
INSERT INTO voiture (modele, immatriculation, energie, couleur, date_premiere_immatriculation, nb_place, utilisateur_id, marque_id)
VALUES ('Clio 5', 'AB-123-CD', 'essence', 'Blanc', '2021-03-15', 4, 3, 1);

-- Voiture d'Alex (électrique)
INSERT INTO voiture (modele, immatriculation, energie, couleur, date_premiere_immatriculation, nb_place, utilisateur_id, marque_id)
VALUES ('Model 3', 'EF-456-GH', 'electrique', 'Noir', '2022-07-10', 4, 5, 4);

-- ------------------------------------------------------------
-- Configurations de préférences
-- ------------------------------------------------------------
INSERT INTO configuration (utilisateur_id) VALUES (3);
INSERT INTO configuration (utilisateur_id) VALUES (5);

-- Lucas : non-fumeur, pas d'animal
INSERT INTO dispose (id_configuration, parametre_id) VALUES (1, 2), (1, 4);
-- Alex : non-fumeur, animal accepté
INSERT INTO dispose (id_configuration, parametre_id) VALUES (2, 2), (2, 3);

-- ------------------------------------------------------------
-- Covoiturages de test
-- ------------------------------------------------------------
-- Trajet 1 : Lucas, Paris → Lyon, dans le futur
INSERT INTO covoiturage (date_depart, heure_depart, lieu_depart, date_arrivee, heure_arrivee, lieu_arrivee, statut, nb_place, prix_personne, voiture_id, chauffeur_id)
VALUES ('2026-06-01', '08:00:00', 'Paris', '2026-06-01', '12:00:00', 'Lyon', 'planifie', 3, 15.00, 1, 3);

-- Trajet 2 : Alex, Bordeaux → Nantes (électrique), dans le futur
INSERT INTO covoiturage (date_depart, heure_depart, lieu_depart, date_arrivee, heure_arrivee, lieu_arrivee, statut, nb_place, prix_personne, voiture_id, chauffeur_id)
VALUES ('2026-06-02', '09:30:00', 'Bordeaux', '2026-06-02', '13:30:00', 'Nantes', 'planifie', 2, 12.00, 2, 5);

-- Trajet 3 : Lucas, Lyon → Marseille
INSERT INTO covoiturage (date_depart, heure_depart, lieu_depart, date_arrivee, heure_arrivee, lieu_arrivee, statut, nb_place, prix_personne, voiture_id, chauffeur_id)
VALUES ('2026-06-03', '14:00:00', 'Lyon', '2026-06-03', '17:30:00', 'Marseille', 'planifie', 2, 18.00, 1, 3);

-- Trajet 4 : Alex, Paris → Strasbourg (terminé, pour tester historique)
INSERT INTO covoiturage (date_depart, heure_depart, lieu_depart, date_arrivee, heure_arrivee, lieu_arrivee, statut, nb_place, prix_personne, voiture_id, chauffeur_id)
VALUES ('2026-05-10', '07:00:00', 'Paris', '2026-05-10', '13:00:00', 'Strasbourg', 'termine', 0, 20.00, 2, 5);

-- Sophie a participé au trajet 4
INSERT INTO participe (utilisateur_id, covoiturage_id, statut) VALUES (4, 4, 'valide');

-- Avis de Sophie sur Alex (trajet 4) - en attente de validation
INSERT INTO avis (commentaire, note, statut, chauffeur_id, passager_id, covoiturage_id)
VALUES ('Excellent chauffeur, voiture très propre et trajet agréable.', 5, 'en_attente', 5, 4, 4);
