# Comparaison fonctionnelle vérifiée - Auto-GestBoard (PHP vanilla situe dans `/home/foxtrot/Documents/autoecole/autoecole_jh/`) vs AutoGest-Laravel `./`

Date : 2026-08-23
Méthode : navigation réelle des deux applications en environnement local (`http://autogest.local/` et `http://localhost:8000/`), avec les comptes fournis (superadmin, admin et moniteur de l'établissement « Auto-École J/H »), recoupée avec une lecture du code Laravel (`app/Domain/*`, vues Blade) pour distinguer précisément ce qui **n'existe pas du tout** de ce qui **existe en base/backend mais n'est pas exposé à l'écran**.

Ce document ne reliste pas ce qui est déjà couvert par `docs/audit/legacy-feature-parity.md` (rédigé sans accès à l'app vanilla en fonctionnement) sauf pour corriger ou préciser ses constats à la lumière de cette navigation réelle.

---

## 1. Fonctionnalités totalement absentes de la version Laravel

### 1.1 Module « Recyclage & Tests »

Écran dédié (`/modules/admin/recyclage.php`) permettant d'enregistrer une prestation ponctuelle facturable pour une personne qui **n'est pas un élève inscrit** : remise à niveau (« Recyclage ») ou passage d'un test isolé (« Test »). Champs : nom complet, motif (Test/Recyclage), téléphone, moniteur assigné, date de séance, montant. Chaque entrée alimente directement la caisse (visible dans « Dernières Opérations Financières » du tableau de bord et dans Caisse & Dépenses).

**Absent en Laravel** : aucune trace de `recyclage` dans `app/Domain` (confirmé par recherche exhaustive). Pas de table, pas de service, pas d'écran. Le domaine `Store` (boutique) ne couvre pas ce besoin - il gère un catalogue produits/fournisseurs/commandes, pas des prestations ponctuelles facturées à une personne non-élève.

### 1.2 Module « Code Rousseau »

Écran dédié (`/modules/admin/code_rousseau.php`) : suivi des ventes de codes Rousseau (support pédagogique physique/numérique de révision du code de la route), avec acheteur, nombre de codes, montant total, montant encaissé, reste à percevoir - logique d'encaissement partiel similaire à celle des factures élèves, mais sur un objet métier distinct.

**Absent en Laravel** : aucune trace (`grep -i "rousseau"` ne renvoie rien de pertinent dans `app/`). Le module `Store` générique pourrait théoriquement absorber ce besoin (produit + commande), mais ne reproduit pas le suivi encaissé/reste propre à cette vente.

### 1.3 « Feuille de route » moniteur (vue consolidée par élève)

Écran dédié (`/modules/moniteur/feuille_route.php`) : pour chaque élève du moniteur, une fiche consolidée affichant séances totales, présences, absences, **heures de conduite effectuées** (calcul cumulé), historique détaillé des séances (date/type/horaire/lieu/présence/note), et un résumé de la progression des compétences (« 4/10 acquises - 40% ») avec lien direct vers l'écran d'évaluation.

**Absent en Laravel** : le moniteur dispose de `/moniteur/agenda` (planning brut) et d'un accès à `/students` (liste), mais **aucune vue consolidée par élève** ne cumule séances/présences/heures effectuées. L'information existe en base (table `lesson_sessions` avec `presence_status`) mais aucune requête d'agrégation ni écran ne la restitue sous cette forme.

### 1.4 Espace élève incomplet

La version vanilla expose cinq écrans dans l'espace élève : **Ma Progression**, Mon Planning, Entraînement Code, **Paiements**, **Mon Dossier**. La version Laravel n'en expose que deux : Mon planning (`eleve.planning`) et Entraînement au code (`quiz.*`).

Concrètement, un élève connecté sur Laravel **ne peut pas** :
- Consulter sa propre progression de compétences (l'écran « Ma Progression » de la version vanilla affiche le même livret de compétences que le moniteur, en lecture seule, avec pourcentage global).
- Consulter l'historique de ses factures/paiements ni son solde restant dû (écran « Paiements »).
- Consulter le statut de son dossier administratif (Incomplet/Complet/Soumis/Validé) ni les documents encore attendus (écran « Mon Dossier »).

C'est cohérent avec un constat déjà fait dans `docs/parcours-eleve-etapes.md` : le `dossier_status` existe en base et a même son service de garde-fou (`DossierStatusService`), mais **aucun écran, ni admin ni élève**, ne l'expose aujourd'hui.

### 1.5 Écran unifié « Utilisateurs » (gestion des comptes, tous rôles)

Écran dédié (`/modules/admin/utilisateurs.php`) permettant à l'admin de :
- Voir tous les comptes de son établissement en un seul endroit (administrateurs, moniteurs, élèves), avec compteur par rôle.
- Créer un nouvel utilisateur de n'importe quel rôle (y compris un compte de connexion pour un élève).
- Réinitialiser le mot de passe d'un utilisateur (icône 🔒).
- Filtrer par rôle, désactiver/supprimer un compte.

**Absent en Laravel** : le domaine `App\Domain\Users` est un **scaffold vide** (uniquement des fichiers `.gitkeep` dans `Models/Services/Policies/Http/Controllers` - confirmé dans `docs/audit/roadmap.md` étape 12, point 6, déjà identifié comme décision différée). La gestion des moniteurs se fait via `/instructors` (CRUD dédié), mais **il n'existe aucun écran pour créer un compte élève avec identifiants de connexion**, ni pour gérer les comptes admin, ni pour réinitialiser un mot de passe depuis l'interface admin. Un élève ne peut obtenir un compte que via l'auto-inscription publique (`/register/student`), qui crée la fiche élève mais dont le lien avec la création effective d'un `User` de rôle `eleve` reste à vérifier - dans tous les cas, l'admin n'a aujourd'hui aucun moyen de créer lui-même un compte élève depuis l'écran `/students`.

---

## 2. Fonctionnalités présentes en base/backend côté Laravel mais non exposées à l'écran

Ces éléments existent déjà dans le code (modèle, migration, validation) mais aucune vue Blade ne les affiche ni ne permet de les saisir - un point important car cela signifie qu'ils sont **rapides à activer** (pas de nouvelle logique métier à concevoir, juste l'habillage UI), contrairement au §1.

### 2.1 Détails d'examen (lieu, inspecteur, nombre de fautes, commentaire)

Le modèle Laravel `Exam` a déjà les colonnes `location`, `inspector`, `fault_count`, `comment` (`app/Domain/Training/Models/Exam.php`), et `StoreExamRequest` valide déjà `location`/`inspector`. Mais la vue `resources/views/training/exams/index.blade.php` n'affiche/ne saisit que : élève, type, date, résultat - **aucun champ pour lieu, inspecteur, nombre de fautes ou commentaire**, alors que ce sont exactement les informations demandées par la version vanilla à l'écran « Attribuer un examen ».

### 2.2 Statut du dossier administratif (`dossier_status`)

Comme noté en §1.4 : `DossierStatusService`/`DossierStatus` (Incomplet/Complet/Soumis/Validé, avec garde de transition) sont **prêts côté backend** mais aucune route HTTP ni aucun écran ne permet de les modifier - dans la version vanilla, ce statut est saisi directement dans le formulaire d'inscription et peut ensuite (a priori) être mis à jour au fil du dossier.

### 2.3 Statistiques agrégées de flotte (carburant, échéances d'entretien)

Le modèle `FuelLog` a `liters`/`cost`/`mileage`, `MaintenanceLog` a `next_due_on` - les données existent. Mais la vue véhicule (`resources/views/fleet/show.blade.php`) n'affiche que le kilométrage courant et l'historique brut des entretiens (sans date de prochaine échéance par ligne), sans les totaux consolidés que la version vanilla affiche directement (coût carburant total, litres consommés cumulés, prochain contrôle technique en évidence).

---

## 3. Différences de workflow (logique métier, pas juste UI)

### 3.1 Inscription atomique élève + dossier + facture + paiement initial

C'est la différence de workflow la plus significative. Le formulaire « Nouvelle inscription » de la version vanilla crée en une seule soumission :
- la fiche élève (identité, contact, type de cours, catégorie),
- l'assignation d'un moniteur,
- le statut du dossier (Incomplet/Complet/Soumis/Validé),
- **et** les données financières initiales : montant total, montant reçu, mode de paiement, frais de dossier séparé, et si le dossier est soldé.

Sur Laravel, ce même parcours nécessite **trois actions séparées dans trois modules différents** : créer l'élève (`/students/create`), puis créer une facture pour cet élève (`/finance/students/{id}/invoices/create`), puis enregistrer un paiement sur cette facture (`/finance/invoices/{id}`). Rien n'empêche fonctionnellement de le faire, mais c'est trois écrans et trois soumissions au lieu d'un - un coût de friction réel pour le secrétariat en usage quotidien, et une notion de « frais de dossier » distincte du montant du forfait qui n'a pas d'équivalent dans le modèle Laravel (`Invoice.amount_due` est un seul montant, pas de ventilation forfait/frais de dossier).

### 3.2 Compétences groupées par catégorie avec sous-totaux

La version vanilla affiche le livret de compétences groupé par catégorie (« Circulation 1/3 », « Maniabilité 3/3 », « Sécurité 0/1 »...) avec une date de validation par compétence acquise (« ✓ Validé le 21/07/2026 »). La version Laravel a bien un champ `category` sur `Skill` (donc la donnée existe), mais l'écran d'évaluation (`training/evaluation/show.blade.php`) affiche une liste plate sans regroupement ni sous-totaux par catégorie, et ne trace pas de date de validation par compétence (seul le niveau actuel - Non travaillé/En cours/Acquis - est stocké, sans horodatage de passage à « Acquis »).

### 3.3 Tableau de bord admin - indicateurs financiers de caisse manquants

Le tableau de bord vanilla affiche en premier rang : Élèves inscrits, **Solde caisse**, **Reste à collecter** (total des impayés tous élèves confondus), Reçus à l'examen, plus un encart Recyclage/Tests, un flux « Séances aujourd'hui » et un flux « Dernières opérations financières ».

Le tableau de bord Laravel (`admin.dashboard`, `ReportsController::dashboard`) est déjà riche (élèves actifs, anciens élèves, taux de réussite examens, alertes flotte, graphique recettes 6 mois, examens à venir, actions rapides) mais **n'affiche ni le solde de caisse courant, ni le total des impayés en cours, ni un flux d'activité du jour (séances/opérations financières récentes)** - ces trois indicateurs sont pourtant calculables directement à partir des domaines `Finance`/`Scheduling` déjà en place.

---

## 4. Ce qui est déjà à parité ou supérieur côté Laravel (pour éviter tout contresens)

À l'inverse, plusieurs points vérifiés en navigation réelle montrent que Laravel est **déjà au niveau ou au-dessus** de la version vanilla - à ne pas retravailler :

- **Filtres élèves** : Laravel propose recherche texte, filtre par étape de cycle de vie, catégorie, type de cours, moniteur **et** plage de dates d'inscription - la version vanilla ne filtre que sur type de cours/catégorie/statut dossier.
- **Documents véhicule/élève** : gestion de versions de documents avec téléchargement, absente de la version vanilla (aucun équivalent identifié dans les écrans navigués).
- **Cycle de vie élève gardé** (15 étapes, transitions bloquées) : la version vanilla n'a qu'un statut de dossier simple (Incomplet/Complet/Validé/Soumis), sans machine à états équivalente sur la progression pédagogique globale.
- **Planning global** : grille visuelle déjà reconstruite à l'identique (`docs/audit/roadmap.md`, étape 10) - parité confirmée en navigation.
- **Structure d'inscription d'établissement (self-service SaaS)** : les champs du formulaire (`/modules/auth/inscription_structure.php` vanilla vs `StructureRegistrationController` Laravel) sont équivalents - parité confirmée.

---

## 5. Synthèse - actions à évaluer avec le métier avant développement

| # | Élément | Nature | Effort relatif |
|---|---|---|---|
| 1 | Écran unifié Utilisateurs (créer/gérer comptes admin/moniteur/élève, reset mot de passe) | Absent - domaine `Users` vide | Élevé (nécessite de trancher TECH-01, déjà noté en roadmap) |
| 2 | Champs examen (lieu, inspecteur, fautes, commentaire) dans le formulaire | Backend prêt, UI manquante | Faible |
| 3 | Écran de revue de dossier (`dossier_status`) côté admin | Backend prêt, UI manquante | Faible-moyen |
| 4 | Espace élève : Ma Progression / Paiements / Mon Dossier | Absent | Moyen (réutilise des données déjà exposées côté admin/moniteur) |
| 5 | Feuille de route moniteur (vue consolidée par élève) | Absent | Moyen |
| 6 | KPIs caisse (solde, reste à collecter) + flux d'activité du jour sur le dashboard admin | Absent | Faible (données déjà calculables) |
| 7 | Compétences groupées par catégorie + date de validation | Backend partiel (category existe, pas de date de validation) | Faible-moyen |
| 8 | Inscription atomique (élève + dossier + facture + paiement en un écran) | Différence de workflow | Moyen-élevé (implique de revoir `EnrollmentService`) |
| 9 | Module Recyclage & Tests | Absent | Moyen - **à confirmer avec le métier avant recréation** (cf. `legacy-feature-parity.md`, non tranché) |
| 10 | Module Code Rousseau | Absent | Moyen - **à confirmer avec le métier avant recréation**, le module Store pourrait suffire selon le besoin réel |

Les points 9 et 10 restent, comme déjà noté dans `legacy-feature-parity.md`, à valider avec le métier gabonais avant réintégration - cette navigation confirme qu'ils sont **réellement utilisés et alimentés en données** dans la version vanilla (pas des écrans morts), ce qui renforce l'hypothèse qu'ils répondent à un besoin réel plutôt qu'à une fonctionnalité obsolète.
