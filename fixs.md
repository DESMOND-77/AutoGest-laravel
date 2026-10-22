# Journal des corrections de bugs - Auto-École J/H

Ce fichier recense les bugs corrigés dans l'application, avec leur cause, le correctif appliqué et le fichier concerné. Les nouvelles corrections doivent être ajoutées en haut de la liste (ordre antéchronologique).

> À partir de #4, les corrections listées ne modifient plus les fichiers PHP legacy de ce dépôt : l'application est en cours de refonte vers Laravel dans le dépôt séparé `autoecole-jh-laravel` (voir `TODO.md`). Ces entrées documentent que les bugs identifiés ici pendant l'audit de refonte sont corrigés **par construction** dans la nouvelle architecture, pas par un patch appliqué au code legacy ci-dessous (qui continue de tourner tel quel jusqu'à la bascule).

---

## #6 - La cloche de notifications marquait tout comme lu en simple lecture

- **Date :** 2026-07-22
- **Fichier legacy concerné :** `api/notifications.php` (lignes ~20-30).
- **Symptôme :** ouvrir la cloche de notifications (une requête `GET`) marquait silencieusement toutes les notifications comme lues côté serveur, alors qu'une requête `GET` ne devrait jamais avoir d'effet de bord. Un utilisateur qui rouvrait la cloche une seconde plus tard ne pouvait plus savoir ce qui était réellement nouveau depuis sa dernière visite.
- **Cause :** la même requête faisait la lecture (`SELECT ... LIMIT 10`) et l'écriture (`UPDATE notifications SET lu=1 ...`) dans le même handler, sans distinguer les deux actions.
- **Correctif (refonte Laravel) :** deux endpoints distincts et correctement verbés - `GET /notifications` (lecture seule, `NotificationController::index`) et `POST /notifications/read` (`NotificationController::markRead`). Le dropdown de la barre de navigation n'appelle `markRead()` qu'au clic explicite sur la cloche, jamais au simple chargement périodique de la liste.
- **Régression couverte par test :** `tests/Feature/Notifications/NotificationFlowTest.php` (« does not mark notifications as read on a plain GET » / « marks notifications as read only via the explicit POST endpoint »).

---

## #5 - Page véhicule qui écrit directement dans la comptabilité

- **Date :** 2026-07-21
- **Fichier legacy concerné :** `modules/admin/flotte.php` (lignes 73–82, 101–110).
- **Symptôme :** ce n'est pas un bug au sens « comportement incorrect visible » mais un défaut de conception relevé pendant l'audit de refonte : la page véhicule crée elle-même une ligne dans `transactions` (« sortie ») dès qu'un entretien ou un plein a un coût. La logique financière est donc dupliquée hors de tout module financier, sans passer par la même validation/format que le reste de la comptabilité.
- **Cause :** aucune séparation entre le module flotte et le module financier - la page flotte a un accès direct et non arbitré à la table `transactions`.
- **Correctif (refonte Laravel) :** `App\Domain\Fleet` n'écrit jamais dans `App\Domain\Finance` - un test d'architecture (`Fleet domain does not depend on Students, Scheduling, Finance or CRM`) l'interdit structurellement. `FleetService::logMaintenance()`/`logFuel()` se contentent d'émettre l'événement `VehicleExpenseRecorded` ; c'est `App\Listeners\RecordVehicleExpenseInLedger`, qui vit délibérément en dehors des deux domaines, qui traduit l'événement en `LedgerEntry` via `LedgerService`. Fleet et Finance peuvent évoluer sans se connaître.
- **Régression couverte par test :** `tests/Feature/Fleet/VehicleExpenseLedgerTest.php` (« journals a maintenance cost to the ledger without Fleet depending on Finance ») + `tests/Architecture/DomainBoundariesTest.php`.

---

## #4 - Règle « CT/assurance expire sous 30 jours » dupliquée deux fois

- **Date :** 2026-07-21
- **Fichier legacy concerné :** `modules/admin/dashboard.php` (lignes 49-54, requête SQL en `UNION ALL`) et `modules/admin/flotte.php` (lignes 140-152, 200, 203, boucle PHP avec `DateTime`).
- **Symptôme :** la même règle métier (alerte à 30 jours) existe en deux implémentations indépendantes qui peuvent diverger silencieusement - une correction ou un changement de seuil apporté à l'une ne se propage pas à l'autre.
- **Cause :** aucune fonction/service partagé ; chaque page qui a besoin de la règle la ré-écrit à sa façon (SQL d'un côté, PHP de l'autre).
- **Correctif (refonte Laravel) :** `App\Domain\Fleet\Services\AlertService::expiringSoon()` est la seule implémentation de la règle dans tout le projet ; la page flotte l'utilise directement aujourd'hui, et le futur tableau de bord (phase Reports) l'appellera de la même façon plutôt que de la ré-implémenter.
- **Régression couverte par test :** `tests/Feature/Fleet/VehicleExpenseLedgerTest.php` (« computes expiring-soon vehicles from a single alert rule »).

---

## #3 - Grille de planning à l'heure pleine, incapable de représenter une demi-heure

- **Date :** 2026-07-21
- **Fichier legacy concerné :** `modules/admin/planning.php` (ligne 213 - voir la note de suivi de l'entrée #2 ci-dessous, qui signalait cette limite comme non résolue par le correctif de format d'heure).
- **Symptôme :** même après le correctif du format d'heure (#2), la grille de planning ne peut physiquement afficher que des séances commençant à l'heure pleine (`07:00`→`17:00`, une ligne par heure). Une séance à `08:30` n'a aucune case où apparaître.
- **Cause :** la détection de créneau reposait sur une correspondance exacte chaîne-à-chaîne entre l'heure de la séance et le libellé de la ligne de la grille - un modèle de données (grille figée par heure) qui ne peut pas représenter un horaire à la demi-heure, pas seulement un bug de formatage.
- **Correctif (refonte Laravel) :** abandon du modèle de grille figée. `App\Domain\Scheduling\Services\ConflictRule::hasConflict()` fait une vraie comparaison de plage horaire (chevauchement `starts_at < fin_existante AND ends_at > debut_existant`) directement en base, pour n'importe quel horaire de début/fin. La vue `resources/views/scheduling/index.blade.php` liste les séances triées par date/heure au lieu de les positionner dans des cases figées. La règle n'existe qu'à un seul endroit (`ConflictRule`), réutilisé par `SchedulingService` pour la création et la replanification, au lieu d'être ré-implémentée par chaque page qui en a besoin.
- **Régression couverte par test :** `tests/Unit/Scheduling/ConflictRuleTest.php` (conflit détecté entre deux séances de 30 minutes qui se chevauchent, pas de conflit entre deux séances de 30 minutes consécutives).

---

## #2 - Édition d'un élève : le paiement n'était jamais mis à jour

- **Date :** 2026-07-21
- **Fichier legacy concerné :** `modules/admin/eleves.php` - remarque annexe non corrigée dans l'entrée #1 ci-dessous.
- **Symptôme :** le modal d'édition d'un élève affiche des champs de paiement (`f_total`, `f_mont`, `f_dos`, `f_mode`, `f_dsol`), mais `editEleve()` ne les charge jamais depuis la ligne sélectionnée, et le handler `action === 'edit'` ne met à jour que la table `eleves` - jamais `paiements`. Modifier un élève via ce formulaire ne peut donc jamais changer ses conditions de paiement, malgré l'apparence du formulaire.
- **Cause :** un seul et même formulaire/handler mélangeait deux responsabilités (identité de l'élève et facturation), et seule la première était effectivement branchée à une requête d'écriture.
- **Correctif (refonte Laravel) :** `Student` (identité/dossier/cycle de vie) et `Invoice`/`Payment` (facturation) sont deux agrégats distincts dans deux domaines DDD séparés (`Students` et `Finance`), chacun avec son propre contrôleur, sa propre policy et son propre formulaire. `StudentController::update()` ne touche jamais `Invoice`/`Payment` ; l'enregistrement d'un paiement passe exclusivement par `PaymentService::record()` (transaction unique : `Payment` + recalcul du solde de `Invoice` + écriture `LedgerEntry`). Il n'existe donc plus de formulaire capable de laisser croire qu'il modifie un paiement sans le faire.
- **Régression couverte par test :** `tests/Feature/Finance/PaymentRecordingTest.php` (« editing a student never changes any invoice or payment »).

---

## #1 - IDOR : un moniteur peut évaluer un élève hors de son périmètre

- **Date :** 2026-07-21
- **Fichier legacy concerné :** `modules/moniteur/evaluation.php` (ligne 68)
- **Symptôme :** en modifiant l'id dans `?eleve=<id>` d'une URL d'évaluation, un moniteur peut consulter **et enregistrer** (`save_eval`) une progression pour un élève qui n'est ni le sien, ni même dans son établissement.
- **Cause :** `$selEleve = Database::fetchOne("SELECT * FROM eleves WHERE id = ?", [$selEleveId])` ne filtre ni par `structure_id`, ni par `moniteur_id`, contrairement à la liste « mes élèves » affichée juste au-dessus dans la même page, qui elle est correctement filtrée. Le filtrage est appliqué par convention page par page, pas structurellement - ici, il a été oublié.
- **Correctif (refonte Laravel) :** `App\Domain\Students\Models\Student` porte le trait `BelongsToTenant` (scope global Eloquent `structure_id`), et `App\Domain\Students\Policies\StudentPolicy::view()` vérifie en plus que `student->instructor_id === $user->id` pour un moniteur. Toute route passe par `$this->authorize()` - impossible d'atteindre le contrôleur sans cette double vérification, quel que soit l'id fourni dans l'URL.
- **Régression couverte par test :** `tests/Feature/Students/StudentTenantIsolationTest.php` (« lets a moniteur view only students assigned to them ») et, une fois l'écran d'évaluation lui-même construit (phase Scheduling+Training), `tests/Feature/Training/EvaluationAuthorizationTest.php`, qui teste directement la route `training.evaluation.*` plutôt que le modèle `Student` seul : un moniteur non assigné reçoit 403 en lecture **et** en écriture, y compris depuis un autre établissement.

---
