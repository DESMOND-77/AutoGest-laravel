# Changelog

Toutes les modifications notables apportées à ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

## [Non publié]

### À venir

- Domaine Instructeurs : gestion complète des affectations et statistiques par moniteur
- Interface front-end interactive pour l'entraînement au code (actuellement API JSON uniquement)
- Import automatisé des grilles de planning historiques (`etp*.csv`, `ett*.csv`)

## [1.0.0] - 2026-07-22

### Ajouté

- Architecture DDD modulaire sous `app/Domain/` avec 15 domaines métier (Tenancy, Users, Students, Instructors, Training, Scheduling, Fleet, Finance, Store, CRM, Reports, Notifications, Documents, Audit, Settings)
- Authentification multi-rôle (Super-Admin, Admin, Moniteur, Élève) via Laravel Breeze et Spatie `laravel-permission`
- Isolation multi-tenant structurelle via le trait `BelongsToTenant` (scope Eloquent global + tenant middleware)
- Inscription publique d'établissement avec validation par le Super-Admin avant activation
- Cycle de vie complet de l'élève (15 étapes, de `Prospect` à `AncienEleve`) piloté par événements
- Module Finance : factures, paiements, journal comptable (`invoices` / `payments` / `ledger_entries`), forfaits de formation
- Module Planning : détection de conflits d'horaires moniteur **et** véhicule, présence aux séances
- Module Formation : compétences, évaluations, examens (code et conduite)
- Module Flotte : véhicules, entretiens, carburant, alertes unifiées d'expiration (contrôle technique / assurance à 30 jours)
- Module Boutique : catalogue produits, fournisseurs, commandes
- Module CRM : gestion des prospects et conversion en élève
- Modules Documents (GED polymorphe versionnée), Notifications (pilotées par événements), Audit (journal des actions sensibles)
- Module Rapports : tableau de bord BI (chiffre d'affaires, taux de réussite, alertes flotte) et exports CSV
- Module Instructeurs : profils moniteurs, disponibilités hebdomadaires
- Module Paramètres : configuration par établissement (identité, devise, fuseau horaire)
- Entraînement au code théorique : questions/réponses avec notation strictement calculée côté serveur
- Commande d'import des données historiques (`php artisan import:legacy-students`)
- Suite de tests Pest (unitaires, fonctionnels, architecture) avec vérification automatisée des frontières entre domaines
- Intégration continue GitHub Actions (tests, style de code, CodeQL)

### Sécurité

- Correction de la faille d'isolation inter-tenant identifiée sur l'ancienne implémentation (accès en modification à un élève d'un autre établissement)
- Correction de la faille IDOR sur l'évaluation d'élève par un moniteur non assigné
- Contraintes d'unicité recomposées par tenant (`email`, `immatriculation`) au lieu d'une unicité globale
- Notation du quiz de code strictement côté serveur (aucune réponse correcte transmise au client avant soumission)

[Non publié]: https://github.com/DESMOND-77/AutoGest/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/DESMOND-77/AutoGest/releases/tag/v1.0.0
