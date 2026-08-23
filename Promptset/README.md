# Promptset — prompts d'implémentation pour agents IA spécialisés

Ce répertoire contient un prompt par tâche, prêt à être copié tel quel dans un agent IA (Claude Code ou équivalent) pour reprendre les éléments **ajoutés ou non terminés** de `docs/audit/roadmap.md`.

Chaque fichier est **autonome** : contexte, périmètre exact, contraintes projet (issues de `CLAUDE.md`), étapes suggérées, critères d'acceptation. Un agent qui reçoit un seul fichier n'a besoin d'aucune information supplémentaire pour démarrer.

## Règles communes à tout le Promptset

Avant toute implémentation, chaque prompt suppose que l'agent :
1. Lit `CLAUDE.md` (conventions du projet) et le fichier source de la tâche (`docs/audit/roadmap.md` et le document d'audit référencé).
2. Applique **TDD** : test d'abord, puis implémentation (`docs/audit/roadmap.md` + skill `pest-testing`/`laravel-best-practices`).
3. Respecte l'architecture DDD existante (`app/Domain/<Domaine>/{Models,Services,Http,Policies,Enums,Database}`).
4. Ne modifie jamais `lifecycle_stage`/`dossier_status` hors des services de garde (`LifecycleService`/`DossierStatusService`).
5. Ne crée aucune dépendance Composer/npm nouvelle sans validation explicite préalable.
6. Termine par `vendor/bin/pint --dirty --format agent` puis `php artisan test --compact` (filtré sur les tests concernés).
7. N'invente aucune intégration externe (API mobile money, WhatsApp, DGTT...) sans avoir vérifié la disponibilité réelle des CGU/API — voir les prompts concernés pour le détail.

## Index des prompts

| Fichier | Tâche | Source roadmap |
|---|---|---|
| `01-ecran-utilisateurs.md` | Écran unifié de gestion des comptes (admin/moniteur/élève) | Étape 12 pt.6 / 13bis #1 |
| `02-details-examen-ui.md` | Exposer lieu/inspecteur/fautes/commentaire sur l'écran Examens | Étape 13bis #2 |
| `03-revue-dossier-admin.md` | Écran de revue du dossier administratif (`dossier_status`) | Étape 13bis #3 |
| `04-espace-eleve-progression-paiements-dossier.md` | Espace élève : Ma Progression / Paiements / Mon Dossier | Étape 13bis #4, 13ter #6 |
| `05-feuille-route-moniteur.md` | Vue consolidée par élève côté moniteur (feuille de route) | Étape 13bis #5 |
| `06-dashboard-kpis-caisse-activite.md` | KPIs caisse + flux d'activité du jour sur le tableau de bord admin | Étape 13bis #6, 13ter #5 |
| `07-competences-categories-date-validation.md` | Regroupement des compétences par catégorie + date de validation | Étape 13bis #7 |
| `08-inscription-atomique.md` | Inscription élève en une seule soumission (dossier + facture + paiement) | Étape 13bis #8 |
| `09-module-recyclage-tests.md` | Module Recyclage & Tests (sous réserve de confirmation métier) | Étape 13bis #9 |
| `10-module-code-rousseau.md` | Module Code Rousseau (sous réserve de confirmation métier) | Étape 13bis #10 |
| `11-notifications-sms.md` | Notifications SMS (rappels, alertes) | Étape 13 pt.3 |
| `12-rappels-whatsapp-seances.md` | Rappels de séance automatiques via WhatsApp Business API | Étape 13ter #1 |
| `13-reservation-libre-service-eleve.md` | Réservation de créneau en libre-service par l'élève | Étape 13ter #2 |
| `14-paiement-mobile-money.md` | Paiement Airtel Money / Moov Money via agrégateur | Étape 13 pt.4, 13ter #3 |
| `15-pwa-offline.md` | PWA avec cache offline (espace élève, agenda moniteur) | Étape 13ter #4 |
| `16-api-publique-v1.md` | API publique versionnée (`/api/v1/`) avec Sanctum | Étape 13 pt.5 |
| `17-preparation-production.md` | Alignement PHP/Docker/CI-CD/monitoring avant mise en production | Étape 14-15 |
| `18-veille-dgtt-cnepc.md` | Veille réglementaire — permis digitalisé gabonais (aucun développement) | Étape 13ter #7 |

## Ordre d'exécution recommandé

Ne pas paralléliser aveuglément : certains prompts partagent des fichiers (ex. `04` et `06` touchent tous deux Finance/Reports ; `09`/`10` sont conditionnés à une décision métier préalable). Ordre suggéré, du moins risqué/plus rapide au plus structurant :

1. `02`, `03` — activer des champs déjà en base, aucun nouveau domaine.
2. `06`, `07` — enrichissement d'écrans existants (Reports, Training).
3. `01` — implémente le domaine `Users`, prérequis de `04` (comptes élèves) si l'auto-inscription ne suffit pas.
4. `04`, `05` — espaces élève/moniteur.
5. `08` — change le workflow d'inscription, à faire après validation métier du besoin (cf. §3.1 de `comparaison-vanilla-vs-laravel.md`).
6. `09`, `10` — uniquement après confirmation explicite du métier gabonais que ces modules répondent à un besoin réel.
7. `11`, `12`, `13` — engagement élève (notifications, réservation).
8. `14` — paiement mobile money, uniquement après vérification CGU/API réelles.
9. `15`, `16`, `17` — infrastructure/plateforme, en fin de roadmap.
10. `18` — veille continue, sans date de fin, aucun développement tant qu'aucune spécification officielle DGTT n'existe.
