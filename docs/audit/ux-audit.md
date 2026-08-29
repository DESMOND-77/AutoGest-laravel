# Audit UX/UI - AutoGest-Laravel

Date : 2026-08-12
Méthode : revue statique des layouts Blade et de la config Tailwind (l'application n'a pas pu être démarrée/parcourue visuellement dans cet environnement d'audit - voir note en fin de document).

---

## 1. Thème clair / sombre

**[HIGH] UX-01 - Pas de sélecteur de thème manuel, pas de persistance**
- Description : `tailwind.config.js` ne définit **aucune clé `darkMode`**, donc Tailwind utilise sa stratégie par défaut `media` : le thème sombre ne s'active que via la préférence OS (`prefers-color-scheme`), sans possibilité de forcer un choix manuel. Confirmé : zéro occurrence de `localStorage` dans `resources/`, aucun bouton de bascule de thème trouvé dans `layouts/navigation.blade.php`.
- Impact : régression directe par rapport à l'ancienne application (`autoecole_jh`), qui avait un vrai toggle Light/Dark, persistant en session serveur ET en `localStorage`, actif dès le rendu serveur (`data-theme` sur `<html>`). Contraire à l'exigence explicite CLAUDE.md §15 (Light/Dark/Système, persistant).
- Point positif à exploiter : `Setting.default_theme` **existe déjà** en base (`app/Domain/Settings/Models/Setting.php`) - la modélisation backend de la préférence est à moitié faite, il manque le toggle et le branchement.
- Solution recommandée : `tailwind.config.js` → `darkMode: 'class'`, ajouter un toggle Alpine.js qui bascule une classe sur `<html>`, persiste en `localStorage` + éventuellement `PATCH settings` pour la préférence par défaut du compte.
- Statut : **CORRIGÉ (2026-08-12)** - `darkMode: 'class'` activé, toggle Light/Dark/Système (`components/theme-toggle.blade.php`) ajouté à la navigation desktop et mobile, persistance `localStorage`, application pré-peinture via `components/theme-init-script.blade.php` (évite le flash du mauvais thème). La synchronisation avec `Setting.default_theme` (préférence par tenant) reste un fast-follow non traité ici.

## 2. Tableaux et responsive

**[HIGH] UX-02 - Tous les tableaux utilisent `overflow-hidden` au lieu de `overflow-x-auto`**
- Description : vérification systématique sur les 17 vues contenant un `<table>` (Students, Finance/Invoices, Scheduling, Fleet, Store, Training, CRM, Audit, Tenancy...) - le conteneur autour de chaque tableau utilise la classe `overflow-hidden`, jamais `overflow-x-auto`. Pattern identique et répété : `<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden"><table class="w-full text-sm text-left">`.
- Impact : sur un écran étroit (mobile 390/430, cf. §18 CLAUDE.md), un tableau plus large que le viewport n'est **pas défilable** - son contenu est purement et simplement **coupé/masqué** (`overflow-hidden` cache tout dépassement au lieu de permettre un scroll). C'est un défaut systématique, pas un cas isolé, qui touche potentiellement toutes les listes de l'application sur mobile.
- Preuve : `resources/views/students/index.blade.php:24-25`, `resources/views/finance/invoices/index.blade.php:15-16`, `resources/views/scheduling/index.blade.php:78-79`, `resources/views/fleet/index.blade.php:47-48` (et 13 autres vues au même pattern).
- Solution recommandée : remplacer `overflow-hidden` par `overflow-x-auto` sur le conteneur de scroll horizontal (en conservant un wrapper externe `overflow-hidden` séparé pour les coins arrondis si nécessaire - pattern classique : wrapper externe `rounded-lg overflow-hidden`, wrapper interne `overflow-x-auto` autour du `<table>`). Envisager une vue carte (stack) pour mobile sur les tableaux les plus denses (Finance, Scheduling), conformément à §18 CLAUDE.md.
- Statut : **CORRIGÉ (2026-08-12)** - un wrapper `overflow-x-auto` a été ajouté autour du `<table>` dans les 16 vues concernées (le wrapper externe `rounded-lg overflow-hidden` est conservé pour les coins arrondis). La vue carte mobile pour les tableaux denses reste une amélioration future, pas traitée ici.

## 3. Planning - reconstruction en grille

Voir `docs/audit/legacy-feature-parity.md` - le planning est actuellement un tableau plat (même défaut `overflow-hidden` que ci-dessus), alors que l'ancienne application avait une grille jour × heure cliquable. C'est la régression UX la plus visible pour l'usage quotidien (secrétaire planifiant des séances). Priorité de reconstruction confirmée en §12/§13 CLAUDE.md.

## 4. Navigation

**[INFO] UX-03 - Navigation existante, non auditée visuellement**
- Description : `resources/views/layouts/navigation.blade.php` (185 lignes) définit la structure de menu ; son contenu exact (profondeur, regroupement par domaine métier vs technique, responsive burger menu) n'a pas pu être validé visuellement dans cet environnement (application non démarrée).
- Solution recommandée : lors de la prochaine session avec environnement fonctionnel (`npm run dev` + `php artisan serve`), effectuer une revue visuelle complète de la navigation aux breakpoints listés en §18 CLAUDE.md, et la comparer à la structure cible proposée en §16 (Gestion / Formation / Finances / Flotte / Documents / CRM / Boutique / Rapports / Paramètres).
- Statut : À valider visuellement - non bloquant pour ce rapport écrit.

## 5. États d'interface (vide / chargement / erreur)

**[MEDIUM] UX-04 - États "vide" non vérifiés systématiquement**
- Description : la mission (§20 CLAUDE.md) exige que chaque liste vide affiche un message explicatif + action ("Aucun élève trouvé. Commencez par inscrire votre premier élève. [Ajouter un élève]") plutôt qu'un simple "No data". Cela n'a pas été vérifié vue par vue dans cette passe statique (nécessite soit une lecture exhaustive de chaque template, soit un parcours visuel avec base de données vide).
- Solution recommandée : lors de la reconstruction UI (étape 10), auditer chaque vue `index.blade.php` pour vérifier la présence d'un état vide explicite avec CTA, et l'ajouter systématiquement sinon.
- Statut : **CORRIGÉ (2026-08-12)** - nouveau composant `x-empty-table-row` (titre + message explicatif + CTA optionnel) appliqué aux 12 vues de liste CRUD principales (Élèves, Planning, Flotte, Moniteurs, Factures, Forfaits, Journal, Prospects, Produits, Ventes, Compétences, Examens), avec lien vers le formulaire de création (page dédiée ou ancre vers le formulaire inline selon le cas). Audit et Établissements (lecture seule) ont un message explicatif sans CTA. Les vues de planning filtrées par semaine (élève/moniteur) ont été laissées telles quelles : une semaine vide y est un état normal, pas une invitation à agir.

## 6. Messages utilisateur

**[INFO] UX-05 - Mécanisme de flash cohérent**
- Description : `session('status')` + `$errors` Laravel natif, rendu en bannière verte/rouge en haut de page (pattern uniforme observé dans `scheduling/index.blade.php:11-15`, `moniteur/agenda.blade.php:9-11` et ~24 autres vues). Fonctionnellement équivalent à l'ancien système, avec un avantage : pas de re-affichage du message au rechargement de page (l'ancienne app utilisait `$_GET['msg']`, qui persistait au refresh).
- Reste à vérifier : présence systématique des 4 niveaux (success/error/warning/info) exigés en §19/§7 CLAUDE.md - seuls success/error semblent confirmés par les vues inspectées ; `warning`/`info` non confirmés.
- Solution recommandée : vérifier/étendre le composant de bannière pour couvrir les 4 niveaux de manière uniforme (probablement déjà un composant Blade réutilisable à généraliser plutôt qu'à dupliquer, cf. règle "ne pas dupliquer 20 variantes du même bouton").
- Statut : À compléter

## 7. Landing page

**[INFO] UX-06 - Landing page jugée acceptable, ne pas retoucher**
- Conforme à la directive CLAUDE.md §13 : la landing page (`resources/views/welcome.blade.php`) est explicitement hors périmètre de la reconstruction UX. Aucune action requise ici.

---

## Note méthodologique

Cet audit UX est **basé sur une lecture statique du code Blade et Tailwind**, pas sur un parcours navigateur réel (l'application n'a pas été démarrée dans cet environnement - `vendor/` absent, cf. `technical-audit.md` TECH-05). Les constats UX-01 et UX-02 sont vérifiés avec certitude par preuve de code (config Tailwind, classes CSS répétées). Les constats UX-03/UX-04/UX-05 nécessitent une confirmation visuelle lors d'une session avec environnement complet (`npm run dev`/`composer run dev`) avant d'être traités comme définitivement validés - conformément à la règle CLAUDE.md §43 de ne jamais prétendre qu'une vérification a été faite si elle ne l'a pas réellement été.
