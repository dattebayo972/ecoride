# EcoRide

Plateforme de covoiturage écologique — TP DWWM

## Stack technique

- **Front** : HTML5, Bootstrap 5, JavaScript
- **Back** : PHP 8 + PDO
- **BDD relationnelle** : MySQL (via XAMPP)
- **BDD NoSQL** : MongoDB (optionnel)

---

## Déploiement local

### Prérequis

- [XAMPP](https://www.apachefriends.org/) (Apache + PHP 8 + MySQL)
- PHP extension `pdo_mysql` activée dans `php.ini`
- (Optionnel) MongoDB Community Server + extension PHP `mongodb`

### Étapes

1. **Cloner le dépôt** dans le dossier `htdocs` de XAMPP :

```bash
git clone <url_du_repo> C:/xampp/htdocs/ecoride
```

2. **Créer la base de données** via phpMyAdmin ou en ligne de commande :

```bash
mysql -u root -p < sql/create_tables.sql
mysql -u root -p ecoride < sql/seed_data.sql
```

3. **Configurer l'environnement** :

```bash
cp .env.example .env
```

Ouvrir `.env` et vérifier les valeurs (laisser `DB_PASS` vide pour XAMPP par défaut).

4. **Démarrer XAMPP** : Apache + MySQL

5. **Accéder à l'application** :  
   [http://localhost/ecoride/public/](http://localhost/ecoride/public/)

---

## Identifiants de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Administrateur | admin@ecoride.fr | Admin1234! |
| Employé | employe@ecoride.fr | Employe1234! |
| Chauffeur | chauffeur@ecoride.fr | Chauffeur1234! |
| Passager | passager@ecoride.fr | Passager1234! |

> **Note** : les mots de passe dans `seed_data.sql` sont des hashes bcrypt pré-générés.  
> Si la connexion échoue, régénérez les hashes via `password_hash('Admin1234!', PASSWORD_BCRYPT)` et mettez à jour la BDD.

---

## Structure du projet

```
ecoride/
├── public/              ← Point d'entrée (pages PHP)
│   ├── index.php
│   ├── covoiturages.php
│   ├── detail.php
│   ├── connexion.php
│   ├── inscription.php
│   ├── espace-utilisateur.php
│   ├── saisir-voyage.php
│   ├── historique.php
│   ├── espace-employe.php
│   ├── espace-admin.php
│   ├── mentions-legales.php
│   ├── contact.php
│   └── assets/css/js/img/
├── src/
│   ├── config/          ← Connexions BDD
│   ├── models/          ← Accès aux données
│   ├── helpers/         ← Auth, CSRF, mail, fonctions
│   └── components/      ← Composants réutilisables
├── sql/
│   ├── create_tables.sql
│   └── seed_data.sql
├── .env.example
└── README.md
```

---

## Branches Git

```
main        ← production
develop     ← intégration
feature/*   ← une branche par US
```

---

## Déploiement en production

Application déployée sur Heroku :  
**https://ecoride-tristan-56cd9852c03c.herokuapp.com/**

Voir `docs/documentation_technique.pdf` pour les étapes détaillées de déploiement.
