# Prompt — Réservation de créneau en libre-service par l'élève

## Contexte

`docs/audit/etude-marche-fonctionnalites.md` §1 : c'est la fonctionnalité standard la plus systématiquement présente chez les concurrents SaaS (Rdv360, Colibri, Goldie) et **absente** de la version Laravel actuelle — aujourd'hui, seul l'admin/moniteur planifie une séance (`SchedulingService::schedule()`). La logique de détection de conflit backend (`ConflictRule`, transactionnelle, avec verrou pessimiste — voir `docs/audit/business-workflow.md` SCHED-01/03) est déjà solide et **prête à être réutilisée** pour ce nouveau flux, ce n'est pas à reconstruire.

## Objectif

Permettre à un élève connecté de réserver lui-même un créneau de séance disponible avec un moniteur, dans la limite de son forfait (`TrainingPackage`), avec décompte automatique des heures restantes.

## Périmètre exact

- Définir la notion d'« heures de forfait restantes » si elle n'existe pas encore explicitement : probablement `TrainingPackage.hours_included - somme des durées des LessonSession Present/Planned liées à ce forfait`. Vérifier d'abord si cette donnée est déjà calculable proprement à partir du modèle existant, sinon l'ajouter (colonne ou méthode calculée sur `Invoice`/`TrainingPackage`).
- `app/Domain/Scheduling/Services/SchedulingService.php` : ajouter une méthode dédiée au flux élève (ex. `bookByStudent()`), qui **réutilise** `ConflictRule` et le verrouillage transactionnel déjà en place, mais avec des règles supplémentaires propres au self-service : délai minimum avant la séance (ex. pas de réservation à moins de X heures, valeur à définir avec le métier), plafond de réservations simultanées en attente, vérification du solde d'heures de forfait avant d'autoriser la réservation.
- Nouvelle route `eleve.planning.book` (ou nom cohérent), groupe `role:eleve`, avec vue de sélection de créneau (afficher les disponibilités des moniteurs — réutiliser `InstructorAvailability` déjà modélisé).
- Annulation par l'élève : définir une règle de délai minimum avant annulation (même remarque que ci-dessus, à confirmer avec le métier) — ne pas permettre l'annulation illimitée jusqu'à la dernière minute sans règle.

## Contraintes

- **Ne pas dupliquer `ConflictRule`** — l'élève ne doit pas pouvoir créer un conflit qu'un admin ne pourrait pas créer non plus ; la même vérification doit s'appliquer strictement.
- Un élève ne doit réserver que pour lui-même (jamais un `student_id` arbitraire dans la requête — le résoudre depuis `Auth::user()`, même remarque que dans `04-espace-eleve-progression-paiements-dossier.md`).
- Isolation tenant stricte (même remarque que partout ailleurs).
- Décrémenter le solde de forfait doit être **transactionnel** avec la création de la séance (pas deux opérations séparées qui pourraient diverger).

## Étapes suggérées (TDD)

1. **Avant tout code** : faire trancher par le métier les règles non ambiguës (délai minimum réservation/annulation, plafond de réservations simultanées) — ne pas les inventer.
2. Lire `SchedulingService.php`, `ConflictRule.php`, `InstructorAvailability.php`, `TrainingPackage.php`, `Invoice.php` en entier.
3. Tests Feature d'abord : réservation réussie avec décompte du solde ; réservation refusée si solde insuffisant ; réservation refusée si conflit moniteur/véhicule (réutilisation de `ConflictRule`, même comportement que le flux admin) ; réservation refusée si délai minimum non respecté ; annulation par l'élève selon la règle définie ; isolation tenant et par élève (un élève ne peut réserver que pour lui-même).
4. Implémenter le calcul de solde de forfait.
5. Étendre `SchedulingService` avec le flux self-service.
6. Implémenter route, contrôleur, vue.
7. `php artisan test --compact --filter=SelfBooking` (ou nom choisi).
8. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- Un élève ne peut réserver qu'un créneau réellement disponible (même logique de conflit que côté admin).
- Le solde de forfait est décrémenté de façon atomique et cohérente avec la réservation.
- Toutes les règles de délai/plafond sont explicitement validées par le métier avant d'être codées en dur.
- Aucune régression sur le flux de planification admin/moniteur existant.
