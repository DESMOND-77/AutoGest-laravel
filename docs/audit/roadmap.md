# Roadmap recommandée post-audit — AutoGest-Laravel → AUTO-GESTBOARD

Date : 2026-08-12
Source : synthèse de `technical-audit.md`, `security-audit.md`, `multi-tenancy-audit.md`, `business-workflow.md`, `legacy-feature-parity.md`, `ux-audit.md`.

Cette roadmap suit strictement l'ordre d'exécution imposé (§41 CLAUDE.md) : ne pas sauter à la roadmap fonctionnelle (Airtel/Moov, mobile, API publique) tant que sécurité, multi-tenancy, workflow métier et UX critiques ne sont pas traités.

---

## Étape 8 — Corrections Critical/High (priorité immédiate)

### Bloquants absolus (à traiter avant tout le reste)

1. **MT-01 [CRITICAL]** — Vérifier exhaustivement que chaque contrôleur avec route-model binding implicite appelle bien sa Policy avant tout accès aux données (protection actuelle = discipline manuelle, pas garantie structurelle). Produire une checklist domaine par domaine.
2. **MT-05 [CRITICAL]** — Écrire les tests d'isolation tenant manquants : `SchedulingTenantIsolationTest`, `PaymentTenantIsolationTest`, `DocumentTenantIsolationTest`, `InstructorTenantIsolationTest`, `LeadTenantIsolationTest`, `OrderTenantIsolationTest`, `QuizTenantIsolationTest`.
3. **TECH-05 [HIGH]** — Provisionner un environnement complet (`composer install` + MySQL) et exécuter réellement `php artisan test --compact` pour obtenir un état des lieux vérifié des 96+ tests existants avant de considérer quoi que ce soit comme "validé".
4. **FIN-02 [HIGH]** — Concevoir et implémenter un mécanisme de remboursement/annulation de paiement (écriture compensatoire + trace d'audit).
5. **FIN-01 / SEC-08 [HIGH]** — Ajouter un contrôle de sur-paiement (plafond ou confirmation explicite).
6. **MT-03 / SEC-07 / TECH-04 [HIGH]** — Contraindre `instructor_id` au tenant courant dans `StoreStudentRequest`.

### Corrections MEDIUM à embarquer dans la même vague

7. TECH-08 / SCHED-03 — Transaction + verrou sur la vérification de conflit planning.
8. WF-02 — Retirer `lifecycle_stage` du `$fillable` de `Student`, exposer une méthode dédiée.
9. WF-04 — Faire écrire les transitions de lifecycle dans `AuditService::log()`.
10. SEC-11 — Vérifier exhaustivement les règles `mimes:`/`max:` sur tous les formulaires d'upload.
11. UX-02 — Corriger `overflow-hidden` → `overflow-x-auto` sur les 17 vues à tableau (correction mécanique, faible risque, gain immédiat).

## Étape 9 — Tests

- Une fois l'environnement de test opérationnel (point 3 ci-dessus), exécuter la suite complète et consigner un état réel (pass/fail) dans ce document.
- Ajouter les tests listés au point 2.
- Ajouter un test `Notification::assertSentTo()` pour `NotifyAdminsOnPaymentReceived` et `NotifyInstructorOnStageChange` (TECH-07).

## Étape 10 — Reconstruction UI/UX

Ordre recommandé, du plus impactant au moins impactant :

1. **UX-01** — Sélecteur de thème Light/Dark/Système persistant (exploite `Setting.default_theme` déjà existant en base).
2. **Planning en grille** (legacy-feature-parity) — reconstruire une vue jour/semaine visuelle façon grille, remplaçant le tableau plat actuel de `scheduling/index.blade.php`, `moniteur/agenda.blade.php`, `eleve/planning.blade.php`.
3. **Interface Quiz** (legacy-feature-parity) — le backend (`QuizController`, `QuizGradingService`) est déjà complet ; il ne manque que les vues Blade (question → réponses → timer → correction → score → historique). Le plus rapide des trois gains UX majeurs.
4. UX-04 — États vides explicites avec CTA sur toutes les listes.
5. UX-05 — Généraliser le composant de bannière aux 4 niveaux (success/error/warning/info).
6. TECH-03 — Fixer la version Tailwind avant de commencer (risque de build cassé sinon).

## Étape 11 — Tests UX + régression

- Parcours navigateur réel sur les breakpoints listés (§18 CLAUDE.md) une fois l'environnement dev opérationnel.
- Revalidation de la navigation (UX-03, non auditée visuellement dans cette passe).

## Étape 12 — Complétion des domaines (ordre imposé §23 CLAUDE.md)

Ne pas avancer au domaine suivant tant que le précédent n'a pas : logique métier cohérente + validation + autorisation + multi-tenancy + UI + tests.

1. **Students** — corriger WF-02/WF-03/WF-04, sinon déjà solide.
2. **Scheduling** — corriger SCHED-02 (véhicule obligatoire ou non, à trancher avec le métier) + SCHED-03, puis reconstruire la grille (étape 10).
3. **Finance** — corriger FIN-01/FIN-02 avant tout, c'est le domaine le plus à risque.
4. **Training** — construire l'UI Quiz.
5. **Fleet** — RAS majeur, corriger TECH-02 (policy manquante si applicable).
6. **Users/RBAC** — décider : implémenter réellement le domaine `Users` (actuellement vide) ou assumer que Spatie + 4 rôles suffisent et retirer le scaffold mort.
7. **Documents** — vérifier SEC-11 exhaustivement.
8. **Notifications** — ajouter les tests de dispatch (TECH-07).
9. **Reports** — RAS, documenter la dépendance MySQL (TECH-06).
10. **CRM** — étoffer la couverture de test (actuellement un seul test).
11. **Store** — créer `SupplierPolicy` (TECH-02), étoffer les tests Product/Supplier.

## Étape 13 — Fonctionnalités restantes de la roadmap produit

**Uniquement après** validation des étapes 8-12. Ordre suggéré par effort/valeur :
1. Interface quiz (déjà couverte étape 10, gain rapide).
2. Import CSV plannings historiques (`etp*.csv`/`ett*.csv`) — n'a jamais existé, à concevoir de zéro (Preview → Mapping → Confirmation → Import transactionnel, §25 CLAUDE.md), priorité basse (aucune régression, pure nouveauté).
3. Notifications SMS.
4. Paiements Airtel Money / Moov Money — vérifier au préalable la disponibilité réelle des API/sandbox/CGU avant tout développement (§26 CLAUDE.md — ne jamais inventer une API). `PaymentMethod` enum est déjà prêt à les recevoir.
5. API publique versionnée (`/api/v1/`) avec Sanctum — seulement quand le domaine interne est stable.
6. Application mobile éventuelle.

**Fonctionnalités legacy à trancher avec le métier avant réintégration** (voir `legacy-feature-parity.md`) : Recyclage, vente de codes Rousseau, feuille de route moniteur, inscription élève en self-service. Ne pas les recréer par défaut — confirmer d'abord qu'elles répondent à un besoin réel du marché gabonais actuel.

## Étape 14-15 — Préparation production / audit pré-production

- Aligner `composer.json` PHP `^8.2` vs CLAUDE.md "8.5" (TECH-09).
- Vérifier `APP_DEBUG=false` en configuration de production + ajouter un contrôle CI/CD (SEC-12).
- Compléter Docker/CI-CD/monitoring/sauvegardes une fois les étapes précédentes closes, selon la checklist §46 CLAUDE.md.

---

## Vue d'ensemble des constats par gravité

| Gravité | Nombre | Domaines principaux |
|---|---|---|
| CRITICAL | 2 | Multi-tenancy (binding implicite, couverture de tests) |
| HIGH | 7 | Finance (remboursement, plafond), Multi-tenancy (validation instructor_id, commandes Artisan), Tests (env non exécutable), UX (thème, tableaux responsive) |
| MEDIUM | 12 | Workflow (audit trail, garde-fous), Scheduling (race condition, véhicule optionnel), RBAC (domaine vide), Policies manquantes, uploads, reporting SQL |
| LOW | 5 | Rate limiting générique, versions Tailwind, URL signées, écart version PHP |
| INFO | 12 | Constats positifs à conserver (architecture DDD, transactions financières, stockage privé documents, etc.) |

**Conclusion générale** : le socle technique est **nettement au-dessus de la moyenne** pour ce stade de projet — architecture DDD disciplinée et testée, machine à états métier réelle, transactions financières correctes, documents stockés en privé. Les manques sont **ciblés et connus** : couverture de test d'isolation tenant trop faible face à un mécanisme de protection qui repose sur la discipline humaine, deux trous financiers sérieux (remboursement, plafond), et une UX en retrait par rapport à la référence legacy sur trois points précis (thème, grille planning, quiz). Aucun de ces manques ne remet en cause l'architecture choisie — tous sont corrigeables sans refonte, conformément à la règle absolue de ne pas réécrire l'application.
