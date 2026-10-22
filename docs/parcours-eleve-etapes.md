# Le parcours de l'élève, étape par étape

Ce document détaille ce qui se passe concrètement à **chacune des 15 étapes** du cycle de vie d'un élève, telles que définies dans le code (`LifecycleStage`). Il complète `docs/guide-utilisation-client.md` en explicitant, pour chaque étape : ce qu'elle signifie, qui agit, quelles actions sont possibles sur l'écran, et ce qui se passe automatiquement (ou non) dans le système.

## Principe général

Le champ `lifecycle_stage` d'un élève ne peut avancer **que d'une étape autorisée à la suivante**, jamais en sautant une étape et jamais en arrière - à une seule exception près (voir étape 13). Ce contrôle est fait par `LifecycleService::transitionTo()`, le seul point du système qui a le droit de modifier cette valeur : toute tentative de saut est rejetée (`InvalidStageTransition`).

**À chaque changement d'étape, deux choses se produisent automatiquement, et rien d'autre** :
1. Une ligne est écrite dans le journal applicatif (`Log::info`).
2. Une entrée est créée dans le **journal d'audit** (`audit.index`), visible par l'administrateur : qui a fait le changement, ancienne étape, nouvelle étape, quand.

**Ce qui n'est PAS automatique** : passer à l'étape « Paiement » ne crée pas de facture tout seul, passer à « Cours pratique » ne planifie pas de séance, etc. Chaque étape est un **repère de statut** que l'administrateur ou le moniteur fait avancer manuellement, en cohérence avec les actions réellement effectuées dans les autres modules (Finance, Planning, Formation, Documents). Le système garantit l'**ordre**, pas l'automatisation des tâches sous-jacentes.

La progression se fait sur la fiche élève (`/students/{id}`), via le bouton de changement d'étape, réservé aux rôles **administrateur** et **moniteur**.

---

## 1. Prospect

**Ce que ça signifie** : un contact a manifesté un intérêt mais n'est pas encore élève à part entière. C'est l'état de départ de tout élève créé dans le système (valeur par défaut en base).

**Comment on y arrive** :
- Un prospect est créé dans le module CRM (`/crm/leads`) puis **converti** en fiche élève (l'action « Convertir » crée directement l'élève à l'étape Prospect) ; ou
- Un candidat s'inscrit lui-même via le formulaire public (`/register/student`) ; ou
- L'administrateur crée directement une fiche élève depuis `/students`.

**Ce qu'on peut faire à cette étape** : renseigner/compléter les informations personnelles (identité, contact, catégorie de permis visée, type de formation).

**Étape suivante autorisée** : Pré-inscription (aucune autre transition possible).

---

## 2. Pré-inscription

**Ce que ça signifie** : l'intérêt du prospect est confirmé, l'auto-école entame formellement le processus d'inscription.

**Ce qu'on peut faire à cette étape** : vérifier les informations saisies, engager les premiers échanges commerciaux/administratifs avant de formaliser l'inscription.

**Étape suivante autorisée** : Inscription.

---

## 3. Inscription

**Ce que ça signifie** : l'élève est officiellement inscrit à l'auto-école.

**Ce qu'on peut faire à cette étape** : c'est le moment habituel pour créer la première **facture** (`/finance/students/{id}/invoices/create`), typiquement liée à un forfait de formation (`/finance/packages`). La facture est créée séparément, dans le module Finance - l'avancement de l'étape ne la génère pas automatiquement.

**Étape suivante autorisée** : Paiement.

---

## 4. Paiement

**Ce que ça signifie** : l'élève doit régler (au moins en partie) les frais d'inscription/formation avant de poursuivre.

**Ce qu'on peut faire à cette étape** :
- Enregistrer un ou plusieurs paiements sur la facture (`/finance/invoices/{id}`), avec le mode de règlement (espèces, Airtel Money, Moov Money, virement, chèque).
- Le système empêche tout paiement qui dépasserait le solde dû sur la facture.
- La facture passe automatiquement de `Impayée` à `Partiellement réglée` puis `Soldée` au fil des paiements - ce mécanisme est indépendant du `lifecycle_stage` (rien n'empêche techniquement de faire avancer l'élève avant que la facture soit soldée ; c'est une discipline opérationnelle, pas un blocage du système).

**Étape suivante autorisée** : Constitution du dossier.

---

## 5. Constitution du dossier

**Ce que ça signifie** : l'élève doit fournir les pièces administratives nécessaires.

**Ce qu'on peut faire à cette étape** : téléverser les **documents** de l'élève sur sa fiche - pièce d'identité, justificatif de domicile, photo, contrat, etc. (`documents.store`). En parallèle, le **statut du dossier** (`dossier_status`, distinct du `lifecycle_stage`) peut être suivi via son propre cycle : `Incomplet → Complet → Soumis → Validé` (avec un retour possible de `Soumis` à `Incomplet` si le dossier est rejeté). Ce sous-statut existe déjà dans le système (`DossierStatusService`) mais **aucun écran dédié** n'est encore branché dessus à ce jour - seule la valeur par défaut (`Incomplet`) est posée à la création de l'élève.

**Étape suivante autorisée** : Validation.

---

## 6. Validation

**Ce que ça signifie** : le dossier administratif de l'élève est jugé complet et validé par l'auto-école, avant le démarrage effectif de la formation.

**Ce qu'on peut faire à cette étape** : vérification finale des pièces et informations avant d'ouvrir l'accès à la formation théorique.

**Étape suivante autorisée** : Cours théorique.

---

## 7. Cours théorique

**Ce que ça signifie** : l'élève entame les cours de code de la route.

**Ce qu'on peut faire à cette étape** :
- Planifier des séances de type « Cours théorique » sur le planning (`/planning`), avec vérification automatique des conflits d'emploi du temps du moniteur.
- L'élève peut s'entraîner de son côté via le module **Quiz** (`/quiz/play`) : série de questions notées automatiquement, avec historique des tentatives.

**Étape suivante autorisée** : Examens blancs.

---

## 8. Examens blancs

**Ce que ça signifie** : l'élève passe des examens blancs de code pour valider son niveau avant le passage officiel.

**Ce qu'on peut faire à cette étape** :
- Planifier des séances de type « Examen blanc ».
- Suivre les résultats du quiz de l'élève côté administrateur/moniteur (`/quiz/students/{id}/results`).
- Enregistrer un examen de type Code dans le module Examens (`/training/exams`) avec son résultat (`En attente / Réussi / Échoué / Annulé`).

**Étape suivante autorisée** : Code obtenu.

---

## 9. Code obtenu

**Ce que ça signifie** : l'élève a réussi l'épreuve théorique (code).

**Ce qu'on peut faire à cette étape** : constater/enregistrer officiellement le résultat dans le module Examens si ce n'est pas déjà fait, avant de basculer vers la formation pratique.

**Étape suivante autorisée** : Cours pratique.

---

## 10. Cours pratique

**Ce que ça signifie** : l'élève entame les leçons de conduite.

**Ce qu'on peut faire à cette étape** :
- Planifier des séances de type « Conduite », avec un **véhicule obligatoire** (règle métier appliquée à la validation) en plus du moniteur - les deux sont vérifiés pour éviter tout double-booking.
- Marquer la présence de l'élève à chaque séance (`Présent / Absent / Reporté / Annulé`).

**Étape suivante autorisée** : Évaluation continue.

---

## 11. Évaluation continue

**Ce que ça signifie** : le moniteur évalue progressivement la maîtrise des compétences de conduite par l'élève.

**Ce qu'on peut faire à cette étape** : noter chaque compétence du référentiel (`/training/students/{id}/evaluation`) sur l'échelle `Non travaillé / En cours / Acquis`, séance après séance, jusqu'à ce que l'élève soit jugé prêt.

**Étape suivante autorisée** : Prêt pour l'examen.

**Particularité** : c'est aussi l'étape de **retour** en cas d'échec à l'examen pratique (voir étape 13) - un élève qui échoue reprend ici pour retravailler ses compétences avant une nouvelle tentative.

---

## 12. Prêt pour l'examen

**Ce que ça signifie** : le moniteur/l'auto-école considère que l'élève a le niveau requis pour se présenter à l'examen pratique officiel.

**Ce qu'on peut faire à cette étape** : dernières vérifications (compétences acquises, dossier à jour) avant la présentation à l'examen.

**Étape suivante autorisée** : Examen pratique.

---

## 13. Examen pratique

**Ce que ça signifie** : l'élève passe l'épreuve de conduite officielle (évaluée par un examinateur externe à l'auto-école, pas par l'application).

**Ce qu'on peut faire à cette étape** : enregistrer le résultat dans le module Examens (`ExamType::Driving`, résultat `Réussi/Échoué/Annulé`).

**C'est la seule étape avec deux issues possibles** :
- **Réussite** → transition vers *Permis obtenu*.
- **Échec** → retour vers *Évaluation continue* (unique boucle arrière autorisée dans tout le cycle) pour reprendre des leçons avant une nouvelle tentative.

---

## 14. Permis obtenu

**Ce que ça signifie** : l'élève a réussi l'examen pratique et obtenu son permis.

**Ce qu'on peut faire à cette étape** : clôturer administrativement le dossier (vérifier que la facturation est soldée, que les documents sont complets).

**Étape suivante autorisée** : Ancien élève.

---

## 15. Ancien élève

**Ce que ça signifie** : étape finale et terminale - l'élève a terminé son parcours avec l'auto-école. Aucune transition suivante n'est possible (`allowedNextStages()` renvoie une liste vide).

**Ce qu'on peut faire à cette étape** : la fiche reste consultable (historique complet : paiements, séances, documents, résultats d'examens) à des fins d'archivage et de statistiques (ex. taux de réussite exportable via `/reports`).

---

## Résumé visuel

```
Prospect
   │
Pré-inscription
   │
Inscription ──────────────► (créer la facture, module Finance)
   │
Paiement ──────────────────► (encaisser, module Finance)
   │
Constitution du dossier ───► (téléverser documents)
   │
Validation
   │
Cours théorique ───────────► (planifier séances + quiz élève)
   │
Examens blancs ─────────────► (résultats code)
   │
Code obtenu
   │
Cours pratique ─────────────► (planifier séances, véhicule obligatoire)
   │
Évaluation continue ◄──────────────────┐  (noter les compétences)
   │                                    │
Prêt pour l'examen                      │
   │                                    │ (échec)
Examen pratique ─────────────────────────┘
   │ (réussite)
Permis obtenu
   │
Ancien élève  [fin de parcours]
```

## Bon à savoir

- **Toute transition en dehors de cet ordre est techniquement impossible** - même en cas d'erreur de manipulation, le système lève une erreur plutôt que d'accepter un saut d'étape.
- **Aucune étape ne déclenche automatiquement une action dans un autre module** (facture, planning, document) : l'étape est un indicateur d'avancement que l'utilisateur fait progresser en cohérence avec le travail réellement effectué ailleurs dans l'application.
- **Chaque changement d'étape est tracé** dans le journal d'audit (`/audit`), avec l'auteur, l'ancienne et la nouvelle étape, et l'horodatage.
- Le **statut du dossier administratif** (`Incomplet/Complet/Soumis/Validé`) est un suivi parallèle et indépendant du cycle de vie principal - il concerne uniquement les pièces justificatives, pas l'avancement pédagogique.
