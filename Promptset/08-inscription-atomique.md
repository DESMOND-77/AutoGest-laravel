# Prompt — Inscription élève en une seule soumission (dossier + facture + paiement initial)

## Contexte

Sur Laravel, créer un élève « prêt à démarrer » nécessite aujourd'hui **trois actions séparées dans trois modules différents** : créer l'élève (`/students/create`), créer une facture (`/finance/students/{id}/invoices/create`), enregistrer un paiement (`/finance/invoices/{id}`). La version PHP vanilla de référence fait tout cela **en une seule soumission de formulaire** : identité élève + moniteur assigné + statut du dossier + montant total + montant reçu + mode de paiement + frais de dossier séparé + dossier soldé (`docs/audit/comparaison-vanilla-vs-laravel.md` §3.1).

C'est le changement de workflow le plus significatif du Promptset — il touche trois domaines (`Students`, `Finance`, potentiellement `Training` pour l'assignation moniteur déjà supportée). **À valider avec le métier avant implémentation** : le workflow en 3 écrans peut être un choix délibéré (séparation des responsabilités secrétariat/comptabilité) plutôt qu'une régression — confirmer avant de coder.

## Objectif

Une fois le besoin confirmé : permettre à l'admin de créer un élève avec sa facture initiale et, optionnellement, un premier paiement, en une seule soumission — sans supprimer les écrans existants (création élève seule, création facture seule doivent continuer à fonctionner pour les cas où l'admin ne veut pas tout saisir d'un coup).

## Périmètre exact

- `app/Domain/Students/Http/Requests/StoreStudentRequest.php` : étendre pour accepter des champs optionnels de facturation (`training_package_id`, `amount_due`, `amount_paid`, `payment_method`, `dossier_fee`) — tous `nullable`, le formulaire doit rester utilisable sans eux (création élève seule, comportement actuel préservé).
- `app/Domain/Students/Services/EnrollmentService::register()` : orchestrer, dans une **transaction unique** (`DB::transaction`, cohérent avec le pattern déjà utilisé dans `PaymentService::record()`), la création du `Student` puis, si les champs de facturation sont présents, l'appel à `InvoicingService::createForStudent()` et `PaymentService::record()`. Injecter ces deux services dans `EnrollmentService`.
- Nouveau champ à évaluer : `dossier_fee` (frais de dossier séparé du montant du forfait) — décision produit à documenter : soit une colonne dédiée sur `Invoice` (`dossier_fee`, ajoutée par migration, incluse dans `amount_due` au total), soit une seconde ligne de facturation. Recommandation : colonne dédiée simple, une facture reste un seul document — mais **confirmer avec le métier gabonais** si un devis/facture séparé pour les frais de dossier est une pratique attendue avant de trancher.
- `app/Domain/Students/Http/Controllers/StudentController.php` : `store()` doit rester la même route, juste avec plus de données possibles en entrée.
- `resources/views/students/create.blade.php` : ajouter une section optionnelle « Facturation initiale » (repliable/optionnelle dans l'UI, pour ne pas complexifier le cas simple).

## Contraintes

- **Ne jamais court-circuiter les garde-fous existants** : `EnrollmentService::register()` ne doit toujours pas pouvoir positionner `lifecycle_stage`/`dossier_status` directement (ces colonnes restent hors `$fillable`, voir WF-02/WF-03 dans `docs/audit/business-workflow.md`) — la facturation initiale ne change pas cette règle.
- Respecter le plafond anti-sur-paiement déjà en place (`StorePaymentRequest`, FIN-01) même dans ce nouveau chemin — ne pas dupliquer la validation, réutiliser `PaymentService::record()` tel quel plutôt que d'écrire un raccourci qui le contournerait.
- Toute l'opération (élève + facture + paiement) doit être atomique : si le paiement échoue (ex. dépassement de solde), la création de l'élève ne doit **pas** être annulée pour autant si l'admin préfère créer l'élève sans facturation complète — clarifier ce comportement avec le métier (rollback total vs rollback partiel) avant d'implémenter, car c'est une décision produit, pas un détail technique.

## Étapes suggérées (TDD)

1. **Avant tout code** : confirmer avec le métier que ce changement de workflow est souhaité (voir préambule) — documenter la décision dans `docs/audit/legacy-feature-parity.md` ou `roadmap.md`.
2. Lire `EnrollmentService.php`, `InvoicingService.php`, `PaymentService.php` en entier.
3. Écrire les tests Feature : création élève seule (comportement actuel préservé) ; création élève + facture ; création élève + facture + paiement complet ; création élève + facture + paiement partiel ; tentative de sur-paiement rejetée sans casser la création de l'élève (comportement à définir précisément avec le métier, voir contrainte ci-dessus) ; isolation tenant sur toute la chaîne.
4. Étendre les form requests, le service, le contrôleur.
5. Étendre la vue.
6. `php artisan test --compact --filter=Enrollment`.
7. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- La création d'un élève sans données de facturation fonctionne exactement comme avant (non-régression).
- Un admin peut créer élève + facture + paiement en une seule soumission quand il le souhaite.
- Les garde-fous financiers (anti-sur-paiement) et de lifecycle restent intacts.
- Comportement en cas d'échec partiel documenté et testé explicitement.
