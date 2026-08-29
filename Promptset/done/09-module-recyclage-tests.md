# Prompt - Module Recyclage & Tests

## ⚠️ Prérequis bloquant

**Ne pas implémenter avant confirmation explicite du métier gabonais.** `docs/audit/legacy-feature-parity.md` classe cette fonctionnalité comme « à trancher avec le métier avant réintégration ». La navigation réelle de la version PHP vanilla de référence (`docs/audit/comparaison-vanilla-vs-laravel.md` §1.1) confirme que ce module est **activement utilisé et alimenté en données réelles** (pas un écran mort), ce qui renforce l'hypothèse d'un besoin réel - mais ne remplace pas la confirmation métier. Si ce prompt est exécuté sans cette confirmation, **s'arrêter et la demander** avant d'écrire du code.

## Contexte (une fois confirmé)

La version vanilla a un écran séparé (`/modules/admin/recyclage.php`) pour enregistrer une prestation ponctuelle facturable pour une personne qui **n'est pas un élève inscrit** dans le cycle de vie principal : remise à niveau (« Recyclage ») ou passage d'un test isolé (« Test »). Champs observés : nom complet, motif (Test/Recyclage), téléphone, moniteur assigné, date de séance, montant. Chaque entrée alimente directement la caisse.

C'est un objet métier **distinct** d'un `Student` - pas de cycle de vie, pas de dossier, juste une transaction ponctuelle avec un contact.

## Objectif

Un nouveau petit domaine (ou sous-domaine de `Finance`, à trancher) permettant d'enregistrer ces entrées et de les voir remonter dans la caisse/le journal comptable, exactement comme le fait déjà l'intégration Fleet→Finance (`VehicleExpenseRecorded` + listener, voir `docs/audit/business-workflow.md` FIN-04 - même patron d'intégration cross-domaine à répliquer ici).

## Périmètre exact

- Nouveau domaine `app/Domain/Recyclage` (ou nom français cohérent avec le reste du projet, à valider) avec la structure standard : `Models/RecyclageEntry.php`, `Enums/RecyclageMotif.php` (`Test`, `Recyclage`), `Http/Controllers/RecyclageController.php`, `Policies/RecyclageEntryPolicy.php`, `Database/Factories/`.
- Migration `recyclage_entries` : `structure_id`, `full_name`, `motif`, `phone` (nullable), `instructor_id` (nullable, FK vers `users`), `session_date`, `amount`.
- Événement `RecyclageEntryRecorded`, écouté par un listener **hors des deux domaines** (`app/Listeners/RecordRecyclageEntryInLedger.php`) qui crée une `LedgerEntry` de type `Income` - répliquer exactement le pattern `RecordVehicleExpenseInLedger` déjà en place pour Fleet→Finance, ne pas coupler `Recyclage` à `Finance` directement dans le service.
- Route `resources/views` + routes `web.php` sous `role:admin`, préfixe `recyclage.*`.
- Entrée de navigation dans `sidebar-nav.blade.php`, bloc « Gestion » ou nouveau bloc dédié.

## Contraintes

- Suivre strictement l'architecture DDD du projet (comparer avec `app/Domain/Fleet` comme référence de structure, c'est le domaine le plus proche en taille/complexité).
- Isolation tenant obligatoire (`BelongsToTenant` sur le modèle, policy scoping).
- Ne pas coupler ce domaine à `Students` - une entrée de recyclage n'est **pas** un `Student`, ne pas essayer de la faire pointer vers ce modèle même si la personne est par ailleurs un ancien élève.
- Intégration au ledger via événement/listener découplé, jamais par appel direct cross-domaine (règle d'architecture déjà actée en FIN-04).

## Étapes suggérées (TDD)

1. Une fois la confirmation métier obtenue, lire `app/Domain/Fleet` en entier comme gabarit de structure, et `app/Listeners/RecordVehicleExpenseInLedger.php` comme gabarit d'intégration ledger.
2. Écrire les tests Feature en premier : création d'une entrée par un admin, apparition automatique dans le ledger, isolation tenant, policy (rôle admin uniquement).
3. Migration + modèle + enum + factory.
4. Service (si la logique le justifie) + événement + listener.
5. Contrôleur + form request + routes.
6. Vue + navigation.
7. `php artisan test --compact --filter=Recyclage`.
8. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- Une entrée de recyclage/test créée par l'admin apparaît immédiatement dans le journal comptable comme une recette.
- Isolation tenant testée explicitement.
- Aucun couplage direct entre le nouveau domaine et `Finance` (uniquement via événement).
