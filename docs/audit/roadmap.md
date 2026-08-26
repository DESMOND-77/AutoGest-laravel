# Roadmap recommandée post-audit - AutoGest-Laravel → AUTO-GESTBOARD

Date : 2026-08-12
Source : synthèse de `technical-audit.md`, `security-audit.md`, `multi-tenancy-audit.md`, `business-workflow.md`, `legacy-feature-parity.md`, `ux-audit.md`.

Cette roadmap suit strictement l'ordre d'exécution imposé (§41 CLAUDE.md) : ne pas sauter à la roadmap fonctionnelle (Airtel/Moov, mobile, API publique) tant que sécurité, multi-tenancy, workflow métier et UX critiques ne sont pas traités.

---

## Étape 8 - Corrections Critical/High (priorité immédiate)

### Bloquants absolus (à traiter avant tout le reste)

1. **MT-01 [CRITICAL]** - Vérifier exhaustivement que chaque contrôleur avec route-model binding implicite appelle bien sa Policy avant tout accès aux données (protection actuelle = discipline manuelle, pas garantie structurelle). Produire une checklist domaine par domaine.
2. **MT-05 [CRITICAL]** - Écrire les tests d'isolation tenant manquants : `SchedulingTenantIsolationTest`, `PaymentTenantIsolationTest`, `DocumentTenantIsolationTest`, `InstructorTenantIsolationTest`, `LeadTenantIsolationTest`, `OrderTenantIsolationTest`, `QuizTenantIsolationTest`.
3. **TECH-05 [HIGH]** - Provisionner un environnement complet (`composer install` + MySQL) et exécuter réellement `php artisan test --compact` pour obtenir un état des lieux vérifié des 96+ tests existants avant de considérer quoi que ce soit comme "validé".
4. **FIN-02 [HIGH]** - Concevoir et implémenter un mécanisme de remboursement/annulation de paiement (écriture compensatoire + trace d'audit).
5. **FIN-01 / SEC-08 [HIGH]** - Ajouter un contrôle de sur-paiement (plafond ou confirmation explicite).
6. **MT-03 / SEC-07 / TECH-04 [HIGH]** - Contraindre `instructor_id` au tenant courant dans `StoreStudentRequest`.

### Corrections MEDIUM à embarquer dans la même vague

7. TECH-08 / SCHED-03 - Transaction + verrou sur la vérification de conflit planning.
8. WF-02 - Retirer `lifecycle_stage` du `$fillable` de `Student`, exposer une méthode dédiée.
9. WF-04 - Faire écrire les transitions de lifecycle dans `AuditService::log()`.
10. SEC-11 - Vérifier exhaustivement les règles `mimes:`/`max:` sur tous les formulaires d'upload.
11. UX-02 - Corriger `overflow-hidden` → `overflow-x-auto` sur les 17 vues à tableau (correction mécanique, faible risque, gain immédiat).

## Étape 9 - Tests

- Une fois l'environnement de test opérationnel (point 3 ci-dessus), exécuter la suite complète et consigner un état réel (pass/fail) dans ce document.
- Ajouter les tests listés au point 2.
- Ajouter un test `Notification::assertSentTo()` pour `NotifyAdminsOnPaymentReceived` et `NotifyInstructorOnStageChange` (TECH-07).

## Étape 10 - Reconstruction UI/UX

Ordre recommandé, du plus impactant au moins impactant :

1. **UX-01** - **FAIT (2026-08-12)** - Sélecteur de thème Light/Dark/Système persistant (exploite `Setting.default_theme` déjà existant en base).
2. **Planning en grille** (legacy-feature-parity) - **FAIT (2026-08-12)** - vue jour/semaine visuelle façon grille (`resources/views/components/planning-grid.blade.php`), remplaçant le tableau plat de `scheduling/index.blade.php`, `moniteur/agenda.blade.php`, `eleve/planning.blade.php`. Positionnement par horaire exact (pas de calage sur l'heure pleine, contrairement au bug legacy), filtres élève/moniteur/véhicule, clic pour préremplir, repli en cartes sur mobile.
3. **Interface Quiz** (legacy-feature-parity) - **FAIT (2026-08-12)** - le backend (`QuizController`, `QuizGradingService`) était déjà complet ; vues Blade ajoutées (question → réponses → chronomètre → correction → score → historique) sous `resources/views/eleve/quiz/`.
4. UX-04 - États vides explicites avec CTA sur toutes les listes.
5. UX-05 - Généraliser le composant de bannière aux 4 niveaux (success/error/warning/info).
6. TECH-03 - Fixer la version Tailwind avant de commencer (risque de build cassé sinon).

## Étape 11 - Tests UX + régression

- Parcours navigateur réel sur les breakpoints listés (§18 CLAUDE.md) une fois l'environnement dev opérationnel.
- Revalidation de la navigation (UX-03, non auditée visuellement dans cette passe).

## Étape 12 - Complétion des domaines (ordre imposé §23 CLAUDE.md)

Ne pas avancer au domaine suivant tant que le précédent n'a pas : logique métier cohérente + validation + autorisation + multi-tenancy + UI + tests.

1. **Students** - corriger WF-02/WF-03/WF-04, sinon déjà solide.
2. **Scheduling** - corriger SCHED-02 (véhicule obligatoire ou non, à trancher avec le métier) + SCHED-03, puis reconstruire la grille (étape 10).
3. **Finance** - corriger FIN-01/FIN-02 avant tout, c'est le domaine le plus à risque.
4. **Training** - construire l'UI Quiz.
5. **Fleet** - RAS majeur, corriger TECH-02 (policy manquante si applicable).
6. **Users/RBAC** - décider : implémenter réellement le domaine `Users` (actuellement vide) ou assumer que Spatie + 4 rôles suffisent et retirer le scaffold mort.
7. **Documents** - vérifier SEC-11 exhaustivement.
8. **Notifications** - ajouter les tests de dispatch (TECH-07).
9. **Reports** - RAS, documenter la dépendance MySQL (TECH-06).
10. **CRM** - étoffer la couverture de test (actuellement un seul test).
11. **Store** - créer `SupplierPolicy` (TECH-02), étoffer les tests Product/Supplier.

## Étape 13 - Fonctionnalités restantes de la roadmap produit

**Uniquement après** validation des étapes 8-12. Ordre suggéré par effort/valeur :
1. Interface quiz - **FAIT (2026-08-12)**, voir étape 10.
2. Import CSV plannings historiques (`etp*.csv`/`ett*.csv`) - **FAIT (2026-08-12)** - n'avait jamais existé (ni dans l'ancienne app, ni dans celle-ci), conçu de zéro après analyse réelle des 5 fichiers `autoecole_jh/data/*.csv`. Commande `php artisan import:legacy-planning {structure} {path} [--dry-run]`, workflow Preview(`--dry-run`)→Rapport conforme à §25 CLAUDE.md. Le format réel s'est avéré plus ambigu que prévu : certaines cellules regroupent plusieurs élèves ou moniteurs sans séparateur fiable (ex. `"MOUBOTY      YACKOUNDA"`) - ces lignes sont explicitement ignorées et listées dans le rapport plutôt que devinées. Vérifié sur les fichiers réels (32 séances importées, 5 lignes ambiguës correctement écartées) et testé (idempotence incluse - y compris pour les séances "Annulé", qui échappent normalement à la détection de conflit). Export CSV symétrique ajouté sur l'écran planning (`GET planning/export.csv`).
3. Notifications SMS.
4. Paiements Airtel Money / Moov Money - vérifier au préalable la disponibilité réelle des API/sandbox/CGU avant tout développement (§26 CLAUDE.md - ne jamais inventer une API). `PaymentMethod` enum est déjà prêt à les recevoir.
5. API publique versionnée (`/api/v1/`) avec Sanctum - seulement quand le domaine interne est stable.
6. Application mobile éventuelle.

**Fonctionnalités legacy à trancher avec le métier avant réintégration** (voir `legacy-feature-parity.md`) : Recyclage, vente de codes Rousseau, feuille de route moniteur, inscription élève en self-service. Ne pas les recréer par défaut - confirmer d'abord qu'elles répondent à un besoin réel du marché gabonais actuel.

### Étape 13bis - Écarts confirmés par navigation réelle (vanilla vs Laravel)

Source : `docs/audit/comparaison-vanilla-vs-laravel.md` (navigation réelle des deux applications avec comptes superadmin/admin/moniteur, recoupée avec le code). Complète et affine la liste ci-dessus - notamment en confirmant que Recyclage et Code Rousseau sont **réellement utilisés et alimentés en données** dans la version vanilla (pas des écrans morts), ce qui renforce l'hypothèse d'un besoin réel plutôt qu'une fonctionnalité obsolète.

| # | Élément | Nature | Effort relatif |
|---|---|---|---|
| 1 | Écran unifié Utilisateurs (créer/gérer comptes admin/moniteur/élève, reset mot de passe) | Absent - domaine `Users` vide | Élevé (tranche TECH-01) |
| 2 | Champs examen (lieu, inspecteur, fautes, commentaire) dans le formulaire | Backend prêt, UI manquante | Faible |
| 3 | Écran de revue de dossier (`dossier_status`) côté admin | Backend prêt, UI manquante | Faible-moyen |
| 4 | Espace élève : Ma Progression / Paiements / Mon Dossier | Absent | Moyen (réutilise des données déjà exposées côté admin/moniteur) |
| 5 | Feuille de route moniteur (vue consolidée par élève) | Absent | Moyen |
| 6 | KPIs caisse (solde, reste à collecter) + flux d'activité du jour sur le dashboard admin | Absent | Faible (données déjà calculables) |
| 7 | Compétences groupées par catégorie + date de validation | Backend partiel (category existe, pas de date de validation) | Faible-moyen |
| 8 | Inscription atomique (élève + dossier + facture + paiement en un écran) | Différence de workflow | Moyen-élevé (implique de revoir `EnrollmentService`) |
| 9 | Module Recyclage & Tests | Absent | Moyen - à confirmer avec le métier avant recréation |
| 10 | Module Code Rousseau | Absent | Moyen - à confirmer avec le métier, le module Store pourrait suffire selon le besoin réel |

### Étape 13ter - Recommandations issues de l'étude de marché (Gabon / Afrique centrale)

Source : `docs/audit/etude-marche-fonctionnalites.md` (comparaison avec le marché SaaS auto-école international + spécificités réglementaires/technologiques gabonaises). Le socle métier actuel est déjà au niveau ou au-dessus des concurrents ; les manques se situent côté engagement élève en libre-service.

| # | Fonctionnalité | Justification marché/local | Effort estimé | Dépendance |
|---|---|---|---|---|
| 1 | Rappels automatiques de séance (WhatsApp Business API en priorité, SMS en repli) | Canal réellement utilisé au Gabon (85%+ des échanges PME↔clients), gain no-show démontré ; infrastructure `Notifications` déjà prête à recevoir un nouveau canal | Moyen | Vérifier CGU/coût Meta Cloud API avant de s'engager (§26 CLAUDE.md) |
| 2 | Réservation de créneau en libre-service par l'élève, avec décompte du forfait | Fonctionnalité standard chez tous les concurrents (Rdv360, Colibri, Goldie) ; la détection de conflit backend est prête à la recevoir | Moyen-élevé | Définir règles métier (délai min. avant annulation, plafond de réservations simultanées) |
| 3 | Paiement mobile money via agrégateur (type PVit) plutôt qu'intégration directe double Airtel/Moov | Mode de paiement dominant et incontournable au Gabon (4 087 milliards FCFA de volume 2024) ; un agrégateur réduit le risque technique et couvre la zone CEMAC | Élevé | Vérifier CGU/API réelles avant développement (affine le point 4 de l'étape 13) |
| 4 | PWA avec cache offline pour l'espace élève et l'agenda moniteur | Contrainte de connectivité/coût de la donnée en Afrique centrale ; alternative moins coûteuse qu'une app mobile native | Moyen | À positionner avant/à la place du point "Application mobile éventuelle" de l'étape 13 |
| 5 | Tableau de bord dirigeant avec KPIs visuels (taux de réussite, occupation véhicules/moniteurs, CA) | Standard marché ; recoupe le point 6 de l'étape 13bis (KPIs caisse) | Faible-moyen | Aucune, données déjà disponibles via `ReportsController` |
| 6 | Solde de forfait visible côté élève (heures restantes, montant dû) | Attendu par défaut dans les espaces élève concurrents ; recoupe le point 4 de l'étape 13bis (écran Paiements élève) | Faible | `TrainingPackage`/`Invoice` déjà modélisés |
| 7 | Veille sur une future API DGTT/CNEPC (permis digitalisé gabonais, lancé le 24/03/2026) | Trop récent pour qu'une API publique existe ; à surveiller, ne rien développer avant spécification officielle | Nul aujourd'hui | Clause de veille uniquement |
| 8 | Mettre à jour `legacy-feature-parity.md` : marquer l'auto-inscription élève comme faite, dépriorisier "Recyclage"/"Codes Rousseau" sauf confirmation métier | Cohérence documentaire | Nul (doc uniquement) | - |

**À ne pas faire** : copier telle quelle une fonctionnalité "France" (codes Rousseau, stage de récupération de points) sans vérifier son existence réglementaire au Gabon ; développer une intégration Airtel/Moov/API DGTT avant vérification concrète des CGU/coûts/disponibilité ; prioriser une app mobile native avant d'avoir chiffré le coût/bénéfice d'une PWA offline.

## Étape 14-15 - Préparation production / audit pré-production

- Aligner `composer.json` PHP `^8.2` vs CLAUDE.md "8.5" (TECH-09).
- Vérifier `APP_DEBUG=false` en configuration de production + ajouter un contrôle CI/CD (SEC-12).
- Compléter Docker/CI-CD/monitoring/sauvegardes une fois les étapes précédentes closes, selon la checklist §46 CLAUDE.md.

---

## Vue d'ensemble des constats par gravité

| Gravité | Nombre | Statut |
|---|---|---|
| CRITICAL | 2 | **Corrigés (2026-08-12)** - voir `multi-tenancy-audit.md` |
| HIGH | 7 + 2 découverts en cours de route | **Corrigés (2026-08-12)** |
| MEDIUM | 9 actionnables + 3 décisions documentées | **Corrigés (2026-08-12)**, sauf TECH-01 (Users/RBAC - différé à l'étape 12, c'est une fonctionnalité pas un correctif) et TECH-06 (couplage MySQL - accepté par choix technique) |
| LOW | 4 | **Corrigés (2026-08-12)**, sauf SEC-10 (URL signées documents - accepté, pas de besoin identifié) |
| INFO | 13 (dont TECH-09, écart de version PHP composer.json vs CLAUDE.md, jamais formellement corrigé - voir note) | Constats positifs, aucune action requise |

**Conclusion générale (mise à jour 2026-08-12)** : les deux CRITICAL, les 7 HIGH initiaux, 2 HIGH découverts en cours de correction (mêmes classes de vulnérabilité, corrigés par le même patron), les 9 MEDIUM actionnables et les 3 LOW actionnables sont **tous corrigés et couverts par des tests** (141 tests passants, vérifiés dans un environnement complet). Les seuls éléments non traités par un correctif de code sont des décisions produit documentées comme différées (Users/RBAC, dépendance MySQL des rapports, URL signées documents) plutôt que des bugs. Reste ouvert : TECH-09 (écart de version PHP, à clarifier plutôt qu'à corriger) et la roadmap fonctionnelle (quiz UI, grille planning visuelle, import CSV historique, mobile money, API publique) - à traiter dans les étapes suivantes du plan (§10 à §14).
