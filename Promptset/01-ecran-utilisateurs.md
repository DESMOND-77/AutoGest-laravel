# Prompt — Écran unifié de gestion des comptes (domaine `Users`)

## Contexte

Le domaine `App\Domain\Users` est un **scaffold vide** : `Models/`, `Services/`, `Policies/`, `Http/Controllers/` ne contiennent que des `.gitkeep`. C'est une décision explicitement différée dans `docs/audit/roadmap.md` (étape 12, point 6 — TECH-01) : « décider : implémenter réellement le domaine `Users` (...) ou assumer que Spatie + 4 rôles suffisent et retirer le scaffold mort ».

La navigation réelle de la version PHP vanilla de référence (`docs/audit/comparaison-vanilla-vs-laravel.md` §1.5) montre qu'un écran unifié existe côté admin : liste de tous les comptes (admin/moniteur/élève) de l'établissement avec compteur par rôle, création d'un compte de n'importe quel rôle **y compris un élève**, réinitialisation de mot de passe, filtre par rôle, désactivation/suppression. Aujourd'hui côté Laravel, les moniteurs se gèrent via `/instructors` (déjà en place, ne pas dupliquer), mais **il n'existe aucun moyen pour l'admin de créer lui-même un compte de connexion pour un élève**, ni de gérer les comptes admin, ni de réinitialiser un mot de passe depuis l'interface.

Ce prompt tranche la décision différée : **implémenter le domaine `Users`**, sous la forme d'un écran d'administration des comptes qui **complète** (ne remplace pas) `/instructors`.

## Objectif

Donner à l'admin un écran `/settings/users` (ou `/utilisateurs`, à choisir en cohérence avec le préfixe de routes existant) permettant de :
- Lister tous les `User` du tenant courant, groupés/filtrables par rôle (admin, moniteur, élève).
- Créer un compte élève (associé à un `Student` existant sans compte, ou lors de la création d'un nouvel élève).
- Créer un compte admin (usage rare mais présent dans la version de référence — équipe élargie).
- Réinitialiser le mot de passe d'un utilisateur (génère un mot de passe temporaire ou envoie un lien de réinitialisation Laravel standard — préférer le lien, cohérent avec Breeze déjà en place).
- Désactiver un compte (ne pas juste le supprimer — vérifier s'il existe déjà une notion de compte actif/inactif sur `User`, sinon l'ajouter).

## Périmètre exact

- `app/Domain/Users/Models/` : probablement pas de nouveau modèle — `User` (`app/Models/User.php`) reste le modèle central, ce domaine porte la **logique de gestion**, pas une nouvelle entité. Vérifier d'abord si un `UserService` a du sens ou si le contrôleur suffit.
- `app/Domain/Users/Http/Controllers/UserManagementController.php` (nom à ajuster) : `index` (liste + filtres), `store` (création), `destroy` ou `update` (désactivation), action dédiée pour la réinitialisation de mot de passe.
- `app/Domain/Users/Policies/UserPolicy.php` : `viewAny`/`create`/`update`/`delete`, réservés au rôle `admin` du même tenant (jamais cross-tenant — s'appuyer sur `BelongsToTenant`/le pattern déjà utilisé partout ailleurs).
- Routes dans `routes/web.php`, groupe `role:admin`, probablement sous un nouveau préfixe `users.*`.
- Vue(s) Blade sous `resources/views/users/` (ou `settings/users/`) : liste avec filtre par rôle, formulaire de création, action de reset.
- Lien vers le domaine `Students` : si un élève créé via `/students` doit pouvoir obtenir un compte de connexion a posteriori, prévoir une action « Créer un compte » depuis la fiche élève qui redirige/pré-remplit ce nouvel écran plutôt que de dupliquer la logique.
- Mettre à jour `resources/views/layouts/partials/sidebar-nav.blade.php` : ajouter l'entrée de navigation dans le bloc « Administration ».

## Contraintes

- **Ne pas dupliquer `/instructors`** : ce domaine gère déjà la création/modification de moniteurs avec leurs spécificités (disponibilités). Le nouvel écran Users doit soit s'appuyer dessus (lien croisé), soit se limiter aux rôles admin/élève et laisser `/instructors` gérer les moniteurs — à trancher en lisant `InstructorController` avant de commencer, pour éviter deux sources de vérité sur la création d'un compte `moniteur`.
- Respecter l'isolation tenant stricte (`structure_id`) sur toute requête/policy — c'est la classe de faille la plus fréquemment corrigée dans `docs/audit/multi-tenancy-audit.md` (MT-01, MT-03, MT-05), à ne pas réintroduire.
- Utiliser `spatie/laravel-permission` (déjà en place, `RoleSeeder`) pour l'assignation de rôle — ne pas réinventer un champ `role` sur `User`.
- Suivre `laravel-best-practices` (policies, form requests, pas de logique métier dans les contrôleurs).

## Étapes suggérées (TDD)

1. Lire `app/Domain/Instructors/*` en entier (modèle de référence le plus proche : création d'un `User` avec rôle assigné, scopé tenant).
2. Lire `app/Models/User.php`, `database/seeders/RoleSeeder.php`, et la façon dont `PublicStudentRegistrationController`/`Student` créent (ou non) un `User` lié aujourd'hui.
3. Écrire les tests Feature en premier : `UserManagementTest` — création d'un compte élève par un admin, création d'un compte admin, isolation tenant (un admin ne voit/gère que les comptes de son tenant), policy (un moniteur ne peut pas accéder à cet écran).
4. Implémenter policy, form requests, contrôleur, routes.
5. Implémenter les vues.
6. `php artisan test --compact --filter=UserManagement`.
7. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- Un admin peut créer un compte élève et un compte admin depuis un seul écran, avec rôle assigné via Spatie.
- Un admin peut déclencher une réinitialisation de mot de passe pour un utilisateur de son tenant.
- Aucun accès cross-tenant possible (test d'isolation explicite).
- `/instructors` reste la seule source de vérité pour la création des moniteurs — pas de duplication.
- Navigation mise à jour, visible uniquement pour le rôle `admin`.
