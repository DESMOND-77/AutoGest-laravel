# Audit workflow métier — AutoGest-Laravel

Date : 2026-08-12
Périmètre : cycle de vie élève, facturation/paiement, planning (conflits).

---

## 1. Cycle de vie élève

**[INFO] WF-01 — Véritable machine à états, gardée en code**
- Description : `LifecycleStage` (`app/Domain/Students/Enums/LifecycleStage.php`) est un enum à 15 états : `Prospect → PreEnrollment → Enrollment → Payment → DossierSetup → Validation → TheoryCourse → MockExams → CodeObtained → PracticalCourse → ContinuousEvaluation → ReadyForExam → PracticalExam → LicenseObtained → FormerStudent`. Chaque cas définit `allowedNextStages()` (chaîne globalement linéaire, avec une seule boucle de retour : `PracticalExam → ContinuousEvaluation` en cas d'échec à l'examen). `canTransitionTo()` vérifie l'appartenance.
- `LifecycleService::transitionTo()` est documenté comme "le seul endroit autorisé à changer `lifecycle_stage`" : lit l'état courant, vérifie `canTransitionTo()`, lève `InvalidStageTransition` sinon, sauvegarde et déclenche `StudentStageChanged`.
- Conforme à l'exigence CLAUDE.md §11 : "PROSPECT → PERMIS doit être impossible" — c'est bien le cas, la machine à états l'empêche structurellement.
- Preuve : `app/Domain/Students/Services/LifecycleService.php`, `app/Domain/Students/Exceptions/InvalidStageTransition.php`.
- Statut : OK — bon niveau de rigueur métier.

**[MEDIUM] WF-02 — Contournement théorique du garde-fou via mass assignment**
- Description : `lifecycle_stage` est dans `Student::$fillable`. Le seul chemin HTTP qui modifie l'étape (`StudentController::advanceStage()`) passe bien par `LifecycleService::transitionTo()`. Mais `EnrollmentService::update()` appelle directement `$this->students->update($student, $data)` sans passer par le service de transition — non exploitable aujourd'hui car `UpdateStudentRequest::rules()` ne whitelist pas `lifecycle_stage`, mais **fragile** : tout futur code appelant `Student::create()/update()` avec des données brutes pourrait fixer n'importe quelle étape sans validation. `ImportLegacyStudents` le fait déjà **délibérément** (contournement documenté et volontaire pour l'import de données historiques).
- Impact : risque de régression silencieuse si un futur développeur ajoute `lifecycle_stage` à une requête sans repasser par `LifecycleService`.
- Preuve : `app/Domain/Students/Services/EnrollmentService.php:20`, `app/Domain/Students/Http/Requests/UpdateStudentRequest.php`, `app/Console/Commands/ImportLegacyStudents.php:128`.
- Solution recommandée : retirer `lifecycle_stage` du `$fillable` du modèle et exposer une méthode dédiée (`Student::setLifecycleStage()` protégée, appelée uniquement par `LifecycleService`), pour rendre le contournement impossible plutôt que simplement non exploité aujourd'hui.
- Statut : À corriger (durcissement défensif)

**[MEDIUM] WF-03 — `DossierStatus` sans garde de transition**
- Description : contrairement à `LifecycleStage`, l'enum `DossierStatus` (`Incomplete, Complete, Submitted, Validated`) n'a **aucune** méthode `allowedNextStages()`/`canTransitionTo()` — c'est une simple colonne de statut libre, modifiable dans n'importe quel ordre par n'importe quel code.
- Impact : incohérence possible (ex. un dossier "Validated" qui repasse à "Incomplete" sans trace), pas de blocage métier équivalent à celui du lifecycle principal.
- Preuve : `app/Domain/Students/Enums/DossierStatus.php`.
- Solution recommandée : évaluer si ce statut a besoin d'un garde-fou équivalent (probable, car "dossier validé" devrait être une étape difficile à annuler) ou documenter qu'il s'agit d'un champ libre volontairement.
- Statut : À trancher avec le métier

**[MEDIUM] WF-04 — Pas de trace d'audit sur les transitions de cycle de vie**
- Description : `AuditService::log()` existe et est injecté dans `StudentController`, mais **n'est appelé que depuis `destroy()`** (action `student.deleted`) — pas depuis `store`, `update`, ni `advanceStage`. Les changements d'étape ne sont donc tracés que par le listener `LogStageChange`, qui écrit dans les logs applicatifs (`Log::info`), **pas** dans la table `audit_logs` interrogeable depuis l'écran d'audit.
- Impact : contraire à l'exigence CLAUDE.md §11 ("quelle trace d'audit est créée ?" pour chaque transition). Un directeur consultant l'écran Audit ne verra pas l'historique des changements d'étape d'un élève.
- Preuve : `app/Domain/Students/Listeners/LogStageChange.php` (commentaire : "placeholder jusqu'à ce que le domaine Notifications existe" — obsolète, Notifications existe déjà), `app/Http/Controllers` usage de `AuditService` limité à `destroy()`.
- Solution recommandée : faire écrire `LogStageChange` (ou un nouveau listener dédié) dans `AuditService::log()` en plus (ou à la place) du log applicatif.
- Statut : À corriger

## 2. Facturation / Paiement

**[INFO] FIN-00 — Intégrité transactionnelle correcte sur l'enregistrement de paiement**
- Description : `PaymentService::record()` enveloppe l'intégralité de l'opération (création `Payment`, mise à jour `Invoice.amount_paid`/`status` via `bcadd`/`bccomp` — arithmétique décimale exacte, pas de flottant —, création `LedgerEntry`, dispatch `PaymentRecorded`) dans un seul `DB::transaction()`. Conforme à l'exigence CLAUDE.md §10.
- Preuve : `app/Domain/Finance/Services/PaymentService.php:26-54`.
- Statut : OK

**[HIGH] FIN-01 — Pas de plafond sur le paiement (sur-paiement possible sans avertissement)**
- Description : rappel de SEC-08. `StorePaymentRequest` valide `amount` sans lien avec `Invoice::balanceDue()`. `statusFor()` traite tout `amount_paid >= amount_due` comme `Paid`, quel que soit le dépassement.
- Impact : un paiement de 500 000 FCFA sur une facture de 100 000 FCFA serait accepté silencieusement, sans notion de crédit/avoir, sans possibilité de le corriger (voir FIN-02).
- Solution recommandée : ajouter une contrainte de validation (bloquante ou avec confirmation explicite) + réfléchir à un mécanisme de crédit si le sur-paiement est un cas métier légitime (ex. acompte pour formation suivante).
- Statut : **CORRIGÉ (2026-08-12)** — `StorePaymentRequest` rejette désormais tout montant dépassant `Invoice::balanceDue()` (calcul bcmath, cohérent avec `PaymentService`). Tests ajoutés dans `PaymentRecordingTest`.

**[HIGH] FIN-02 — Aucun mécanisme de remboursement / annulation de paiement**
- Description : recherche exhaustive de `refund|Refund|cancel|Cancel` dans `app/Domain/Finance` : aucun résultat. Il n'existe **aucun chemin applicatif** pour annuler ou rembourser un paiement enregistré par erreur, ni pour annuler une facture.
- Impact : contraire à l'exigence CLAUDE.md §10 qui liste explicitement "annulation, remboursement, suppression, modification" parmi les scénarios à tester. Aujourd'hui, une erreur de saisie financière (mauvais montant, mauvaise facture) ne peut être corrigée que par une intervention manuelle en base de données — inacceptable pour un SaaS commercial.
- Solution recommandée : concevoir un workflow d'annulation/remboursement avec sa propre trace d'audit et son propre impact sur le ledger (écriture compensatoire plutôt que suppression, pour préserver l'intégrité comptable).
- Statut : **CORRIGÉ (2026-08-12)** — `PaymentService::cancel()` : annulation transactionnelle d'un paiement (jamais de suppression), rollback de `Invoice.amount_paid`/`status`, écriture compensatoire de type `Expense` dans le ledger. Nouvelle `PaymentPolicy`, route `finance.payments.cancel`, bouton "Annuler" dans la vue facture. Tests dans `PaymentRecordingTest` (annulation simple, double-annulation refusée, solde libéré après annulation) et `InvoiceTenantIsolationTest` (isolation tenant sur l'annulation).

**[INFO] FIN-03 — Séparation propre Invoice / Payment / Ledger**
- Description : `InvoicingService` (création facture, toujours `Unpaid` initialement), `PaymentService` (paiement, transactionnel), `LedgerService::recordManual()` (écritures manuelles hors facture — salaires, dépenses). Bonne séparation des responsabilités, amélioration architecturale confirmée par rapport à l'ancienne app (table `paiements` plate + `transactions` génériques).
- Statut : OK

**[INFO] FIN-04 — Intégrations cross-domaines correctement découplées**
- Description : Store→Finance passe par `DB::transaction` avec `lockForUpdate()` sur `Product` (anti-survente) ; Fleet→Finance passe par un événement (`VehicleExpenseRecorded`) écouté par un listener hors des deux domaines (`app/Listeners/RecordVehicleExpenseInLedger.php`), pour respecter la règle d'architecture "Fleet ne dépend pas de Finance".
- Statut : OK — bon exemple à répliquer pour toute nouvelle intégration cross-domaine.

## 3. Planning — détection de conflits

**[INFO] SCHED-01 — Logique de conflit correcte et documentée**
- Description : `ConflictRule` (`app/Domain/Scheduling/Services/ConflictRule.php`) fait un vrai test de chevauchement de plage horaire (`starts_at < endsAt AND ends_at > startsAt`), scopé par `instructor_id` ou `vehicle_id`, même jour, excluant les séances annulées et la séance en cours d'édition. Remplace un ancien bug de comparaison de chaînes sur grille horaire (documenté dans le code). `SchedulingService::schedule()/reschedule()` appliquent systématiquement ce contrôle avant toute création/modification.
- Statut : OK sur le plan logique.

**[MEDIUM] SCHED-02 — Le contrôle véhicule est ignoré si aucun véhicule n'est assigné**
- Description : `hasVehicleConflict()` n'est appelé que si `vehicleId` est fourni — une séance sans véhicule assigné ne subit aucune vérification de ce type (logique, puisqu'il n'y a rien à vérifier), mais cela signifie aussi qu'aucune alerte n'incite à assigner un véhicule, ce qui peut conduire à des séances "orphelines" de véhicule découvertes seulement à l'heure J.
- Impact : UX/métier plutôt que sécurité — à évaluer si un véhicule doit être obligatoire pour les séances pratiques.
- Solution recommandée : rendre `vehicle_id` obligatoire pour `SessionType::Practical` si c'est la réalité métier des auto-écoles gabonaises (à confirmer).
- Statut : À trancher avec le métier

**[MEDIUM] SCHED-03 — Pas de protection contre les conditions de course (rappel TECH-08)**
- Description : voir `technical-audit.md` TECH-08 — la vérification de conflit et la création de séance ne sont pas atomiques (pas de `DB::transaction`/verrou), contrairement au pattern déjà utilisé dans `OrderService`.
- Statut : À corriger avant mise en production multi-poste.

---

## Synthèse

| ID | Gravité | Sujet |
|---|---|---|
| FIN-02 | HIGH | Aucun mécanisme de remboursement/annulation de paiement |
| FIN-01 | HIGH | Pas de plafond sur les paiements |
| WF-02 | MEDIUM | Contournement théorique du garde-fou de lifecycle (fillable) |
| WF-03 | MEDIUM | `DossierStatus` sans garde de transition |
| WF-04 | MEDIUM | Pas de trace d'audit sur les transitions de cycle de vie |
| SCHED-02 | MEDIUM | Véhicule optionnel = pas de contrôle de conflit véhicule |
| SCHED-03 | MEDIUM | Pas de protection anti-race-condition sur le planning |

Le workflow métier est **globalement bien conçu** (machine à états gardée pour le cycle de vie élève, transactions correctes pour les paiements, détection de conflit fonctionnelle). Les deux lacunes les plus sérieuses sont dans le domaine Finance : **absence totale de remboursement/annulation** et **absence de plafond de paiement**, deux points explicitement identifiés comme critiques par la mission (§10 CLAUDE.md) et à corriger avant toute étape de complétion de domaine supplémentaire.
