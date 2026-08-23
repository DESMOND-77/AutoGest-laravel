# Prompt — Espace élève : Ma Progression / Paiements / Mon Dossier

## Contexte

L'espace élève Laravel n'expose aujourd'hui que deux écrans : Mon planning (`eleve.planning`) et Entraînement au code (`quiz.*`). La navigation réelle de la version PHP vanilla de référence (`docs/audit/comparaison-vanilla-vs-laravel.md` §1.4) montre trois écrans supplémentaires côté élève :
- **Ma Progression** : le même livret de compétences que celui du moniteur, en lecture seule, avec pourcentage global.
- **Paiements** : historique de ses factures/paiements et solde restant dû.
- **Mon Dossier** : statut du dossier administratif (`dossier_status`) et documents attendus/déposés.

Toutes les données existent déjà côté backend (`SkillProgress`, `Invoice`/`Payment`, `Student.dossier_status`, `Document`) — c'est un travail de nouveaux écrans en **lecture seule**, scopés à l'élève connecté lui-même, pas de nouvelle logique métier.

Recoupe aussi `docs/audit/etude-marche-fonctionnalites.md` recommandation #6 (solde de forfait visible côté élève) — à couvrir dans l'écran Paiements.

## Objectif

Ajouter trois routes/écrans dans le groupe `role:eleve` :
- `eleve.progression` → compétences de l'élève connecté (regroupées par catégorie si `07-competences-categories-date-validation.md` est déjà fait, sinon liste simple).
- `eleve.paiements` → factures de l'élève connecté, statut, solde dû total, historique des paiements.
- `eleve.dossier` → statut du dossier (`dossier_status`), liste des documents déposés avec leur type, éventuellement une liste des types de documents encore manquants (comparer aux `DocumentType` attendus — décision produit à documenter si la liste des documents obligatoires n'est pas déjà formalisée quelque part).

## Périmètre exact

- `app/Domain/Students/Http/Controllers/StudentSelfServiceController.php` (nom à ajuster, ou trois contrôleurs légers si plus cohérent avec le style du projet — regarder comment `StudentPlanningController` à une seule action `__invoke` est déjà fait pour `eleve.planning`, et répliquer ce pattern).
- Résolution de l'élève courant : `Auth::user()` a un rôle `eleve` — il faut retrouver le `Student` qui lui est lié. **Vérifier d'abord comment ce lien existe aujourd'hui** (`Student.user_id` existe dans `$fillable`, à confirmer que c'est bien rempli au moment de la création/auto-inscription — si ce n'est pas le cas, c'est un prérequis bloquant à traiter avant ce prompt, potentiellement en coordination avec `01-ecran-utilisateurs.md`).
- Routes dans `routes/web.php`, groupe `['auth', 'role:eleve']`, à côté des routes `eleve.*` existantes.
- Vues sous `resources/views/eleve/progression.blade.php`, `resources/views/eleve/paiements.blade.php`, `resources/views/eleve/dossier.blade.php`.
- Mettre à jour `resources/views/eleve/dashboard.blade.php` et le bloc de navigation `eleve` dans `sidebar-nav.blade.php` (actuellement seulement « Mon planning » + « Entraînement au code »).

## Contraintes

- **Lecture seule stricte** : un élève ne doit jamais pouvoir modifier son propre `dossier_status`, ses compétences ou ses factures depuis ces écrans — uniquement consulter. Pas de policy `update` à créer ici.
- Isolation : un élève ne doit voir **que ses propres données** — pas de paramètre d'URL avec un ID de `Student` manipulable ; résoudre systématiquement le `Student` à partir de `Auth::user()`, jamais depuis la requête.
- Réutiliser les composants d'affichage déjà utilisés côté admin/moniteur (barres de progression de compétences, badges de statut de facture) plutôt que d'en recréer de nouveaux.

## Étapes suggérées (TDD)

1. Vérifier le lien `User` ↔ `Student` (`Student.user_id`) : écrire un test qui confirme qu'un élève auto-inscrit (`PublicStudentRegistrationController`) obtient bien un `Student.user_id` renseigné. Si ce n'est pas le cas, le signaler explicitement et le corriger en préalable (peut nécessiter une décision produit sur le moment où le compte élève est créé — coordination avec `01-ecran-utilisateurs.md`).
2. Écrire les tests Feature : un élève voit ses propres compétences/factures/dossier ; un élève ne peut pas voir/accéder aux données d'un autre élève même en forgeant une URL ; un moniteur/admin n'a pas accès à ces routes `eleve.*`.
3. Implémenter les contrôleurs et routes.
4. Implémenter les trois vues.
5. Mettre à jour dashboard + navigation.
6. `php artisan test --compact --filter=EleveSelfService` (ou nom choisi).
7. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- Un élève connecté voit sa progression, ses paiements/solde et son dossier sans pouvoir accéder aux données d'un autre élève.
- Aucune action d'écriture n'est possible depuis ces écrans.
- Le lien `User` ↔ `Student` est fiable pour tout élève, qu'il vienne de l'auto-inscription publique ou d'une création admin.
