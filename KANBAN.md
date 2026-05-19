# EcoRide — Kanban de suivi de projet

> Projet ECF DWWM · Plateforme de covoiturage  
> Dernière mise à jour : 2026-05-18

---

## 📋 Backlog

| ID | User Story | Priorité | Points |
|----|-----------|----------|--------|
| — | Intégration paiement réel (Stripe) | Faible | 8 |
| — | Notifications push / email temps réel | Faible | 5 |
| — | Application mobile native | Faible | 13 |
| — | Système de parrainage | Faible | 3 |
| — | Export CSV des statistiques admin | Faible | 2 |

---

## 📌 To Do

| ID | User Story | Rôle | Priorité |
|----|-----------|------|----------|
| — | Déploiement Heroku production | DevOps | Haute |
| — | Tests unitaires PHPUnit | Dev | Haute |
| — | Rapport de couverture de code | Dev | Moyenne |

---

## 🔄 In Progress

*Aucune tâche en cours actuellement — toutes les US sont livrées.*

---

## ✅ Done (develop)

| ID | User Story | Branche | Commit |
|----|-----------|---------|--------|
| US1 | Accueil — Hero + recherche rapide | `feature/US1-accueil` | ✅ mergé |
| US2 | Affichage covoiturages disponibles (visiteur) | `feature/US2-covoiturages-visiteur` | ✅ mergé |
| US3 | Recherche par ville départ/arrivée et date | `feature/US3-recherche` | ✅ mergé |
| US4 | Filtres avancés (écologique, prix, durée, note) | `feature/US4-filtres` | ✅ mergé |
| US5 | Détail d'un covoiturage | `feature/US5-detail` | ✅ mergé |
| US6 | Avis conducteur sur la fiche trajet | `feature/US6-avis-conducteur` | ✅ mergé |
| US7 | Inscription avec validation + 20 crédits offerts | `feature/US7-inscription` | ✅ mergé |
| US8 | Espace utilisateur — profil, véhicules, préférences | `feature/US8-espace-utilisateur` | ✅ mergé |
| US9 | Saisir un covoiturage (chauffeur) | `feature/US9-saisir-voyage` | ✅ mergé |
| US10 | Historique trajet — démarrer / terminer / annuler | `feature/US10-historique-chauffeur` | ✅ mergé |
| US11 | Historique passager — annuler / valider / litige / avis | `feature/US11-historique-passager` | ✅ mergé |
| US12 | Espace employé — modération avis et litiges | `feature/US12-espace-employe` | ✅ mergé |
| US13 | Espace admin — stats, création employé, suspension | `feature/US13-espace-admin` | ✅ mergé |

---

## 🚀 Released (main)

| Version | Date | Contenu |
|---------|------|---------|
| v1.0.0 | 2026-05-18 | Livraison complète ECF — 13 US, 33 fichiers, stack PHP+MySQL+MongoDB |

---

## 📊 Résumé

| Colonne | Nb tâches |
|---------|-----------|
| Backlog | 5 |
| To Do | 3 |
| In Progress | 0 |
| Done (develop) | **13** |
| Released (main) | **13** |

---

## 🏷️ Labels utilisés dans le projet

| Label | Couleur | Description |
|-------|---------|-------------|
| `feature` | 🟢 Vert | Nouvelle fonctionnalité |
| `bug` | 🔴 Rouge | Correction de bug |
| `hotfix` | 🟠 Orange | Correction urgente production |
| `docs` | 🔵 Bleu | Documentation |
| `security` | 🟣 Violet | Sécurité CSRF/XSS/injection |
| `ux` | 🟡 Jaune | Amélioration interface |

---

## 👥 Équipe

| Rôle | Responsable |
|------|-------------|
| Développeur Full-Stack | Candidat ECF |
| Product Owner | Jury DWWM |
| Scrum Master | Candidat ECF |

---

## 📅 Sprints

### Sprint 1 (Sem. 1-2) — Fondations
- [x] US1 Accueil
- [x] US7 Inscription / Connexion
- [x] Base de données (SQL + seed)
- [x] Architecture MVC-lite

### Sprint 2 (Sem. 3-4) — Recherche & Consultation
- [x] US2 Liste covoiturages visiteur
- [x] US3 Recherche
- [x] US4 Filtres
- [x] US5 Détail trajet
- [x] US6 Avis sur fiche

### Sprint 3 (Sem. 5-6) — Espace utilisateur & Chauffeur
- [x] US8 Espace utilisateur (profil, véhicules, préférences)
- [x] US9 Saisir voyage
- [x] US10 Historique chauffeur

### Sprint 4 (Sem. 7-8) — Passager, Employé, Admin
- [x] US11 Historique passager (valider, litige, avis)
- [x] US12 Espace employé (modération)
- [x] US13 Espace admin (stats, création employé, suspension)

### Sprint 5 (Sem. 9) — Livraison ECF
- [x] Maquettes HTML (desktop + mobile)
- [x] Diagrammes (MCD, UC, Séquences)
- [x] Documentation technique
- [x] Manuel utilisateur
- [x] Kanban
- [ ] Déploiement Heroku
