# Guide d'utilisation — Plateforme de gestion d'auto-école

Ce document décrit, de bout en bout, le fonctionnement de l'application : qui fait quoi, dans quel ordre, et comment naviguer dans chaque espace. Il s'adresse aux équipes qui utilisent la plateforme au quotidien (gérants, secrétaires, moniteurs, élèves) ainsi qu'à l'administrateur de la plateforme.

---

## 1. Vue d'ensemble

La plateforme est un **SaaS multi-établissements** : chaque auto-école ("structure") dispose de son propre espace cloisonné (élèves, véhicules, finances, planning, etc.), invisible des autres établissements. Quatre profils d'utilisateurs existent :

| Rôle | Qui | Ce qu'il peut faire |
|---|---|---|
| **Super-administrateur** | L'éditeur de la plateforme | Valide/suspend les auto-écoles inscrites, supervise l'ensemble des établissements |
| **Administrateur (gérant)** | Le dirigeant ou secrétariat de l'auto-école | Pilote tout : élèves, planning, finances, flotte, boutique, paramètres |
| **Moniteur** | L'enseignant de conduite | Consulte son agenda, ses élèves, saisit les évaluations et les présences |
| **Élève** | Le candidat au permis | Consulte son planning, s'entraîne au code, suit sa progression |

Chaque rôle voit un menu latéral adapté à ses droits — un moniteur ne voit jamais les écrans Finance ou Administration, par exemple.

---

## 2. Étape 0 — Créer un compte auto-école

1. Sur la page d'accueil, le futur gérant clique sur **« Inscrire mon auto-école »** (`/register`).
2. Il renseigne les informations de son établissement (nom, coordonnées, etc.) et crée son compte administrateur.
3. **Le compte n'est pas actif immédiatement** : le message de confirmation indique que l'établissement est *« en attente de validation par l'administrateur de la plateforme »*.
4. Le **super-administrateur** se connecte à son espace (`/superadmin/structures`) et voit la liste des établissements avec leur statut :
   - `Pending` (en attente) → `Active` (validé, connexion autorisée)
   - `Suspended` (suspendu temporairement) ou `Deactivated` (désactivé) à tout moment si nécessaire.
5. Une fois le statut passé à **Actif**, le gérant peut se connecter (`/login`) et commencer à configurer son auto-école.

> Tant que la structure n'est pas active, aucun utilisateur de cet établissement ne peut se connecter.

---

## 3. Espace Administrateur (gérant / secrétariat)

C'est l'espace le plus complet. Menu latéral organisé en 5 blocs : **Gestion**, **Formation**, **Finances**, **Flotte & Boutique**, **Administration**.

### 3.1 Paramétrage initial (`Administration > Paramètres`)

Avant toute activité, il est recommandé de :
- Renseigner les informations générales de l'établissement (`/settings`).
- Générer le **lien d'inscription publique** (`/settings/student-registration`) : un lien unique et un QR code que l'auto-école peut diffuser (site web, réseaux sociaux, affiche) pour que les prospects s'inscrivent eux-mêmes en ligne. Ce lien peut être régénéré (invalide l'ancien) ou révoqué à tout moment.

### 3.2 Acquisition — CRM Prospects (`Gestion > Prospects`)

- Chaque contact entrant (appel, visite, formulaire) peut être enregistré comme **prospect** (`/crm/leads`) avec un statut : `Nouveau → Contacté → Qualifié → Converti` (ou `Perdu`).
- Quand un prospect est prêt à s'inscrire, l'action **« Convertir »** crée automatiquement sa fiche élève, sans ressaisie.
- Alternative : le prospect s'inscrit lui-même via le **lien public** généré en 3.1, sans qu'un employé de l'auto-école n'intervienne.

### 3.3 Le parcours de l'élève (`Gestion > Élèves`)

C'est le cœur du système : chaque élève suit un **cycle de vie encadré en 15 étapes obligatoires**, dans un ordre strict (impossible de « sauter » une étape) :

```
Prospect → Pré-inscription → Inscription → Paiement → Constitution du dossier
   → Validation → Cours théorique → Examens blancs → Code obtenu
   → Cours pratique → Évaluation continue → Prêt pour l'examen
   → Examen pratique → Permis obtenu → Ancien élève
```

Un seul retour en arrière est autorisé : en cas d'**échec à l'examen pratique**, l'élève repasse automatiquement en « Évaluation continue » pour reprendre des leçons avant une nouvelle tentative.

Concrètement, sur la fiche élève (`/students/{id}`), l'administrateur ou le moniteur :
1. Fait avancer l'élève étape par étape via le bouton dédié — le système bloque toute tentative de saut d'étape.
2. Gère en parallèle le **dossier administratif** (pièce d'identité, justificatif de domicile, photo, contrat...) avec son propre statut : `Incomplet → Complet → Soumis → Validé` (retour possible si le dossier soumis est rejeté).
3. Téléverse les **documents** de l'élève (carte d'identité, photo, contrat, etc.) directement sur sa fiche.
4. Suit la progression de ses **compétences de conduite** (voir §3.4) et ses résultats aux **examens blancs de code** (voir §3.5).

Chaque changement d'étape est tracé dans le journal d'audit (voir §3.9).

### 3.4 Planning et pédagogie (`Formation`)

- **Planning** (`/planning`) : vue calendrier des séances (théorie, code, conduite, examen blanc). À la création d'une séance, le système **vérifie automatiquement les conflits** : un moniteur ou un véhicule ne peut pas être réservé deux fois sur le même créneau. Pour les séances de conduite, un véhicule est obligatoire.
- Chaque séance peut être **dupliquée** (pour répéter un cours récurrent) et sa présence marquée (`Présent / Absent / Reporté / Annulé`) par le moniteur ou l'administrateur.
- **Compétences** (`/training/skills`) : référentiel des savoir-faire à acquérir (créneaux, priorité à droite, etc.), noté `Non travaillé / En cours / Acquis` sur chaque élève via l'écran d'évaluation.
- **Examens** (`/training/exams`) : suivi des tentatives (code / conduite) avec résultat `En attente / Réussi / Échoué / Annulé`.

### 3.5 Entraînement au code — Espace Élève (voir §5)

L'administrateur crée la banque de questions et suit les résultats des élèves via `/quiz/students/{id}/results`.

### 3.6 Finances (`Finances`)

Trois écrans complémentaires :

- **Factures** (`/finance/invoices`) : une facture est créée pour un élève (forfait ou prestation), toujours à l'état `Impayée` initialement, puis `Partiellement réglée` ou `Soldée` au fil des paiements.
- **Forfaits** (`/finance/packages`) : catalogue des formules proposées (ex. « Permis B accéléré 10 jours ») utilisées pour facturer rapidement.
- **Encaissement d'un paiement** : depuis la facture, on enregistre un paiement (espèces, Airtel Money, Moov Money, virement, chèque). Le système empêche tout **sur-paiement** au-delà du solde dû. Une erreur de saisie peut être **annulée** (jamais supprimée) — l'annulation restaure le solde de la facture et inscrit une écriture compensatoire dans le journal comptable, pour garder une traçabilité complète.
- **Journal comptable** (`/finance/ledger`) : toutes les écritures (recettes, dépenses, dépôts et retraits bancaires), y compris les dépenses de flotte enregistrées automatiquement (voir §3.7) et les écritures manuelles (salaires, charges diverses).

### 3.7 Flotte de véhicules (`Flotte & Boutique > Véhicules`)

- Chaque véhicule (`/fleet`) a un statut : `En service / En entretien / Hors service`.
- On y enregistre l'**entretien** (maintenance) et le **carburant** — chaque dépense de flotte se répercute automatiquement dans le journal comptable (§3.6), sans double saisie.
- **Alerte automatique quotidienne (7h)** : un contrôle vérifie les véhicules dont le contrôle technique ou l'assurance expire sous 30 jours et notifie les administrateurs de l'établissement.

### 3.8 Boutique interne (`Flotte & Boutique > Boutique`)

- Catalogue de **produits** (supports pédagogiques, fournitures) et **fournisseurs**.
- **Commandes fournisseurs** (`/store/orders`) suivies par statut : `En attente → Confirmée → Livrée` (ou `Annulée`).

### 3.9 Administration et supervision

- **Journal d'audit** (`/audit`, admin + super-admin) : trace des actions sensibles (suppression d'élève, changement d'étape du cycle de vie, etc.) — utile pour comprendre « qui a fait quoi, quand ».
- **Instructeurs / Moniteurs** (`/instructors`) : fiches moniteurs et gestion de leurs **disponibilités** (créneaux horaires), utilisées par le module Planning pour détecter les conflits.
- **Rapports** (`Admin > Tableau de bord`, exports CSV) : export du chiffre d'affaires, des résultats d'examens, de la répartition des élèves par étape du cycle de vie.
- **Notifications** (`/notifications`) : centre de notifications internes (alertes véhicules, etc.), consultable par tout utilisateur connecté.

---

## 4. Espace Moniteur

Menu réduit à l'essentiel :

- **Mon agenda** (`/moniteur/agenda`) : vue des séances qui lui sont assignées (théorie, code, conduite, examens blancs).
- **Mes élèves** (`/students`) : accès en lecture/écriture aux fiches des élèves qu'il encadre — il peut y saisir les évaluations de compétences et marquer les présences des séances.

Le moniteur ne voit ni les écrans Finances, ni Flotte/Boutique, ni Administration.

---

## 5. Espace Élève

Le plus simple des quatre :

- **Mon planning** (`/eleve/planning`) : ses séances à venir (théorie, code, conduite).
- **Entraînement au code** (`/quiz`) : série de questions à choix multiples, notée automatiquement ; l'élève consulte ses résultats et l'historique de ses tentatives (`/quiz/results`, `/quiz/attempts/{id}`).

Un élève n'a jamais accès aux données des autres élèves, ni à la moindre information financière ou administrative.

---

## 6. Parcours complet — de l'inscription au permis

Résumé du cheminement type d'un candidat, de bout en bout, en reliant les écrans concernés :

1. **Découverte** : le prospect remplit le formulaire public (`/register/student`, lien généré en §3.1) *ou* est saisi manuellement comme lead par le secrétariat (§3.2).
2. **Conversion** : le lead est converti en fiche élève (étape *Prospect → Pré-inscription → Inscription*).
3. **Paiement initial** : une facture liée à un forfait est créée et réglée (au moins partiellement) → étape *Paiement*.
4. **Dossier administratif** : les documents sont téléversés et le dossier suit son propre circuit de validation → étape *Constitution du dossier → Validation*.
5. **Formation théorique** : séances de code planifiées, examens blancs passés → étapes *Cours théorique → Examens blancs → Code obtenu*.
6. **Formation pratique** : séances de conduite planifiées avec un moniteur et un véhicule, compétences évaluées progressivement → étapes *Cours pratique → Évaluation continue → Prêt pour l'examen*.
7. **Examen officiel** : résultat saisi dans le module Examens → *Permis obtenu* (fin de parcours) ou retour en *Évaluation continue* en cas d'échec, pour reprendre des leçons avant une nouvelle tentative.
8. **Archivage** : une fois le permis obtenu et le suivi terminé, l'élève passe *Ancien élève*.

Tout au long de ce parcours, chaque paiement, changement d'étape et document est journalisé — l'établissement dispose d'une traçabilité complète pour chaque candidat.

---

## 7. Bon à savoir

- **Isolation des données** : chaque auto-école ne voit strictement que ses propres élèves, véhicules, factures, etc. — aucune fuite de données entre établissements n'est possible.
- **Aucune suppression destructive en finance** : un paiement erroné s'annule, ne se supprime jamais — l'historique comptable reste intègre et auditable.
- **Notifications automatiques** : au-delà des alertes véhicules quotidiennes, le centre de notifications (`/notifications`) centralise les informations à ne pas manquer.
- **Exports** : les rapports d'activité (chiffre d'affaires, examens, répartition des élèves) sont exportables en CSV pour un usage externe (comptabilité, reporting direction).
