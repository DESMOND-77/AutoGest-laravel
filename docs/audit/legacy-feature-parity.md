# Comparaison fonctionnelle — autoecole_jh (legacy) vs AutoGest-Laravel

Date : 2026-08-12
Contexte : `autoecole_jh` est une application PHP vanille (sans framework), rendu serveur, authentification par session, `mysqli`. `AutoGest-laravel` est une reconstruction Laravel 12 en architecture DDD. `autoecole_jh` sert uniquement de référence fonctionnelle/UX — voir CLAUDE.md.

| Fonctionnalité | Ancienne app (autoecole_jh) | AutoGest-laravel | État | Action |
|---|---|---|---|---|
| Élèves (CRUD) | Oui | Oui | OK | Conserver |
| Cycle de vie élève | Statut simple, non gardé | Machine à états gardée (`LifecycleStage`, 15 étapes) | **Amélioré** | Conserver, corriger WF-02/WF-03 |
| Facturation / paiement | Table `paiements` plate + `transactions` génériques | `Invoice`/`Payment`/`LedgerEntry` séparés, transactionnel | **Amélioré** | Conserver, corriger FIN-01/FIN-02 (remboursement absent des deux mais plus grave à corriger vu l'architecture pro visée) |
| Planning — vue grille (jour × heure) | Grille visuelle réelle (`modules/admin/planning.php:216-280`), cellules cliquables, cartes colorées par type/présence, cycle de présence en un clic | Tableau HTML plat (liste), présence via `<select>` | **Régression UX** | Reconstruire en grille (étape 12/13 CLAUDE.md) |
| Détection de conflit planning | Ad hoc, bug connu de comparaison de chaînes sur grille horaire | Logique de chevauchement propre et testée (`ConflictRule`) | **Amélioré** | Conserver, corriger race condition (SCHED-03) |
| Thème clair/sombre | Toggle manuel, persistant en session serveur + `localStorage`, SSR (`data-theme` sur `<html>` dès le premier rendu) | Classes Tailwind `dark:` présentes partout, mais **aucun toggle**, pas de `localStorage`, `tailwind.config.js` sans `darkMode` explicite (mode `media` = OS uniquement) | **Régression** | Restaurer un sélecteur Light/Dark/Système persistant (§15 CLAUDE.md) |
| Import CSV plannings (`etp*.csv`/`ett*.csv`) | **Jamais implémenté** dans l'ancienne app non plus — documenté explicitement comme non géré (`setup/import.php:254-256`) | Non implémenté (feature roadmap restante) | Parité (aucune des deux ne l'a) | Développer selon §25 CLAUDE.md (Preview → Mapping → Confirmation → Import transactionnel) |
| Import CSV inscriptions | `inscription.csv` importé | `ImportLegacyStudents` — port direct et documenté de la même logique | OK | Conserver |
| Notifications flash (succès/erreur) | Round-trip `$_GET['msg']` (persiste au refresh — défaut mineur) | `session('status')` + `$errors` Laravel natif | **Amélioré** (mécanisme plus propre) | Conserver |
| Centre de notifications (cloche) | Polling au clic, marquage "lu" comme effet de bord du GET (bug) | Endpoints séparés `index`/`markRead`, bug corrigé et documenté | **Amélioré** | Conserver |
| Quiz / entraînement au code | Page complète serveur : 40 questions aléatoires, formulaire, correction détaillée, historique de scores — fonctionnelle de bout en bout | Backend complet (`QuizController`, `QuizGradingService`, notation serveur sécurisée) **mais aucune vue Blade** — API JSON seule, `find resources/views -iname "*quiz*"` = 0 résultat | **Régression majeure** | Construire l'interface (§24 CLAUDE.md) — priorité de la roadmap fonctionnelle restante |
| Recyclage (remise à niveau élèves) | Module dédié (`modules/admin/recyclage.php` + table `recyclage`) | Aucune trace (`grep -i recyclage` = rien de pertinent) | **Fonctionnalité disparue** | Évaluer avec le métier si nécessaire au marché gabonais ; si oui, recréer |
| Vente de codes Rousseau | Module dédié + import CSV dédié | Aucune trace | **Fonctionnalité disparue** | Évaluer utilité réelle vs Store générique existant (peut-être couvert par le module Boutique désormais) |
| Feuille de route moniteur | `modules/moniteur/feuille_route.php` | Aucune trace | **Fonctionnalité disparue** | Évaluer utilité (possiblement redondant avec Scheduling/agenda moniteur actuel — à confirmer avec un utilisateur métier avant de recréer) |
| Inscription élève en self-service (formulaire public) | `modules/eleve/inscription.php` | Aucune route `students.*` publique trouvée — création élève admin-only | **Régression potentielle** (à vérifier) | Confirmer avec le métier si le self-service est un besoin réel ou si la création admin-only est le workflow voulu |
| Auto-inscription établissement (nouveau tenant) | N/A (app mono-établissement) | `StructureRegistrationController` — signup public créant un `Structure` en attente de validation superadmin | **Nouveau, spécifique SaaS** | Conserver — nécessaire pour le modèle SaaS multi-tenant |
| Paiements mobiles (Airtel/Moov) | Simples placeholders de config, jamais intégrés | `PaymentMethod` enum inclut les mêmes options, toujours sans intégration réelle | Parité (aucun des deux n'intègre réellement) | Développer selon §26 CLAUDE.md (interface abstraite `PaymentGatewayInterface`), après vérification des API/CGU réelles |
| Flotte véhicules | Présent | Présent, avec alertes (contrôle technique, assurance) via `AlertService` + commande planifiée | **Amélioré** | Conserver |
| CRM / prospects | Non identifié comme module dédié dans l'ancienne app (à confirmer) | `Lead`/`LeadService`, conversion prospect→élève | **Nouveau/Amélioré** | Conserver |

---

## Synthèse des actions prioritaires issues de la comparaison

1. **[HIGH] Interface Quiz manquante** — le backend existe entièrement, seule la vue Blade manque. C'est le gain UX le plus rapide à obtenir (pas de nouvelle logique métier à écrire, juste l'habillage).
2. **[HIGH] Reconstruction de la grille planning** — régression UX la plus visible pour l'utilisateur quotidien (secrétaire/moniteur), explicitement identifiée comme prioritaire en §12 CLAUDE.md.
3. **[MEDIUM] Thème clair/sombre avec persistance** — régression fonctionnelle claire, effort de développement modéré (toggle Alpine + endpoint de préférence, réutilisable du pattern déjà présent dans `Setting.default_theme`, qui existe déjà en base mais n'est apparemment pas exploité côté UI).
4. **[LOW/À CONFIRMER] Recyclage, codes Rousseau, feuille de route, self-inscription élève** — à valider avec le métier gabonais avant de les recréer : certaines peuvent être des besoins réels non couverts, d'autres peuvent être obsolètes ou redondantes avec les modules actuels (Store, Scheduling). Ne pas recréer sans confirmation, conformément à la règle CLAUDE.md "ne pas recréer une fonctionnalité obsolète".

**Point notable** : `Setting.default_theme` existe déjà en base (`app/Domain/Settings/Models/Setting.php`) — la persistance de préférence de thème par tenant est donc déjà à moitié modélisée côté backend ; il manque le toggle utilisateur et le branchement Blade/Alpine pour l'exploiter réellement.
