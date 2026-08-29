# Étude de marché — SaaS de gestion d'auto-école & spécificités Afrique centrale (Gabon)

Date : 2026-08-23
Objectif : confronter le périmètre fonctionnel actuel (voir `docs/guide-utilisation-client.md`, `docs/audit/roadmap.md`, `docs/audit/legacy-feature-parity.md`) aux pratiques du marché SaaS international et aux réalités réglementaires/technologiques du Gabon et d'Afrique centrale, pour nourrir la roadmap évolutive avec des fonctionnalités et workflows pertinents — pas des copier-coller de fonctionnalités occidentales hors-sol.

Méthode : recherche web (marché SaaS auto-école, réglementation gabonaise du permis, écosystème mobile money CEMAC, contraintes de connectivité) recoupée avec l'état réel du code (`app/Domain/*`) et les décisions déjà actées dans les audits précédents.

---

## 1. Ce que le marché SaaS "auto-école" propose en standard

Solutions observées : Rdv360 (France), Colibri, Goldie, Total Driving School Management, SimplyBook.me, Booknetic, et les comparatifs Capterra/GetApp 2026.

| Brique standard du marché | État dans ce projet |
|---|---|
| Planification élève/moniteur/véhicule avec détection de conflit | **Déjà présent et solide** (`ConflictRule`, transactionnel, verrous — voir `business-workflow.md` SCHED-01/03) |
| Auto-réservation en ligne par l'élève (24/7, décompte automatique des heures d'un forfait) | **Absent** — aujourd'hui seule l'inscription initiale est en self-service (§4 ci-dessous) ; la prise de créneau reste faite par l'administrateur/moniteur |
| Paiement en ligne (dépôt, carte, crédit d'heures prépayées) | **Partiel** — enregistrement de paiement en présentiel géré (`PaymentService`), mais aucun paiement déclenché par l'élève lui-même |
| Rappels automatiques (email/SMS/WhatsApp) avant chaque séance | **Absent** — le domaine `Notifications` existe (SMS gateway déjà scaffoldé, `SmsGateway`/`SmsChannel`) mais rien n'envoie de rappel de séance à J-1 |
| Espace élève avec historique complet + solde d'heures restantes | **Partiel** — planning et quiz existent côté élève, mais pas de vue "solde de forfait" ni de récapitulatif de progression complet |
| Espace moniteur mobile (agenda temps réel, feuille de route/évaluation en mobilité) | **Partiel** — agenda web existe ; pas d'app mobile ni de mode déconnecté |
| Signature électronique du contrat pédagogique | **Absent** |
| Statistiques/tableau de bord dirigeant (taux de réussite, CA, occupation véhicules/moniteurs) | **Partiel** — exports CSV existent (`ReportsController`), pas de tableau de bord visuel avec KPIs |
| Multi-établissement / multi-agence pour une même enseigne | **Partiel** — le modèle est multi-tenant (une structure = un établissement indépendant), mais aucune notion de "groupe" avec plusieurs agences sous une même raison sociale partageant élèves/flotte |

**Constat général** : le socle métier "cœur" (élèves, planning, finances, flotte) est déjà au niveau ou au-dessus des standards du marché (machine à états gardée, anti-survente, anti-race-condition — la plupart des concurrents grand public n'ont pas ce niveau de rigueur). Les manques se situent presque tous côté **engagement élève en libre-service** (réservation, paiement, rappels) — c'est la zone d'investissement la plus alignée avec ce que le marché appelle "features".

---

## 2. Spécificités réglementaires du Gabon — impact direct sur le produit

### 2.1 Le permis de conduire digitalisé (lancé le 24 mars 2026)

Le Gabon a lancé le **permis de conduire digitalisé**, porté par la DGTT (Direction Générale des Transports Terrestres), l'ANINF (Agence Nationale des Infrastructures Numériques et des Fréquences) et l'opérateur technique Rengus Digital. Éléments clés :

- Processus : vérification des données → enrôlement → **collecte biométrique** (photo, empreintes) → signature → validation → délivrance sous 7 jours.
- Phase d'enrôlement national de 6 mois, paiement **uniquement en mobile money** sur place (10 000 FCFA catégorie B, 20 000 FCFA autres catégories).
- Après ce délai, seul le permis digitalisé est reconnu valide.

**Implication produit** : c'est l'événement le plus structurant pour la roadmap. À terme, une auto-école qui présente ses candidats à l'examen aura intérêt à ce que son SaaS puisse :
- Exporter/transmettre un **dossier candidat conforme** au format attendu par le CNEPC/DGTT (nom, catégorie, pièces, photo) plutôt que de ressaisir ailleurs.
- Anticiper une **intégration API** (si/quand une API publique DGTT existe) pour vérifier un numéro de permis ou pousser un résultat d'examen — **ne pas l'inventer aujourd'hui** (aucune API publique documentée trouvée), mais concevoir le domaine `Documents`/`Training` de façon à ce qu'un futur connecteur n'exige pas de refonte (le `DocumentType` enum actuel a déjà de la marge : `IdCard`, `Photo`, etc. — cohérent avec les pièces demandées à l'enrôlement).
- Le champ `LicenseCategory` (`A, B, C, D, E`) déjà présent colle aux catégories citées (`A/B/C/D/E/F/G` selon les sources) — vérifier si `F`/`G` (motos légères, agricole) doivent être ajoutées selon la clientèle réelle de l'établissement.

### 2.2 Examen théorique/pratique — format réel

- Épreuve code : **40 questions, 30 bonnes réponses minimum pour réussir**. Le module Quiz actuel (`QuizGradingService`, banque de questions) est donc déjà structurellement aligné — vérifier que le seuil de réussite configuré correspond bien à 30/40 (75%) et non à un seuil générique.
- Épreuve pratique : ~20 minutes de conduite en circulation réelle avec un examinateur DGTT (externe à l'auto-école). Le module `Training/Exam` (`ExamType::Driving`, `ExamResult`) colle à ce besoin ; s'assurer que le résultat de cet examen externe (pas noté par l'auto-école elle-même) reste bien saisi comme un **constat** et non comme une évaluation interne, pour ne pas mélanger évaluation pédagogique et résultat officiel.

### 2.3 Paiement — mobile money incontournable, pas optionnel

- Deux opérateurs dominants : **Airtel Money** (dominant) et **Moov Money**. Volume 2024 : 4 087 milliards FCFA de transactions mobile money au Gabon — c'est le mode de paiement de référence, pas un canal secondaire.
- Intégration technique réaliste : API marchand directe (delai 3-5 jours, coût 350 000-600 000 FCFA pour un checkout avec webhook) ou agrégateur type PVit (multi-opérateurs Airtel/Moov/GIMAC Bank, pensé CEMAC).
- `PaymentMethod` enum a déjà `AirtelMoney`/`MoovMoney` — actuellement **enregistrés manuellement** (l'admin saisit "j'ai reçu tel montant par Airtel Money") sans intégration réelle, ce qui est le comportement correct *aujourd'hui* : la roadmap (`roadmap.md` étape 13, point 4) prévoit bien de vérifier la disponibilité réelle de l'API/CGU avant tout développement, conformément à la règle CLAUDE.md de ne jamais inventer une intégration.
- **Recommandation** : privilégier un agrégateur (type PVit) plutôt que deux intégrations directes séparées — réduit le risque projet et couvre nativement Airtel + Moov + banque, cohérent avec le contexte CEMAC (utile si l'auto-école étend son activité à un pays voisin).

### 2.4 Canal de communication réel : WhatsApp, pas l'email

- Plus de 85% des échanges commerciaux PME↔clients en Afrique subsaharienne passent par WhatsApp. Les rappels WhatsApp réduisent les no-shows de 25% à 5-8% dans le secteur des rendez-vous.
- Le domaine `Notifications` actuel a un canal SMS (`SmsChannel`/`SmsGateway`, avec un `LogSmsGateway` de test) mais pas de canal WhatsApp, et surtout **aucun rappel de séance automatique** n'est déclenché (seule l'alerte véhicule quotidienne existe, `CheckFleetAlerts`).
- **Recommandation forte** : avant SMS, évaluer WhatsApp Business API (Meta Cloud API) pour les rappels de séance J-1 — c'est le canal réellement utilisé par la clientèle gabonaise, avec un coût d'usage souvent inférieur au SMS classique et un taux de lecture bien supérieur. Le `NotificationChannel` (interface `Contracts`) déjà en place dans le domaine permet d'ajouter un `WhatsAppChannel` sans réécrire l'existant.

### 2.5 Connectivité et coût de la donnée

- La connexion mobile reste chère et instable pour une partie des usagers (élèves, moniteurs en déplacement) ; les solutions locales à succès (ex. SmartMifin) sont conçues dès l'origine pour la connectivité limitée : mise en cache, PWA installable consommant ~70% de données en moins qu'un site classique rechargé à chaque visite.
- **Implication** : l'espace élève et l'agenda moniteur (consultés en mobilité, souvent en 3G/4G instable) sont les écrans les plus exposés à ce problème. Une **PWA avec cache offline** (Service Worker + IndexedDB pour le planning déjà chargé) apporterait un vrai avantage concurrentiel local, plus qu'une application mobile native coûteuse à développer/maintenir — à positionner **avant** "Application mobile éventuelle" dans `roadmap.md` étape 13, ou en remplacement si le budget est contraint.

---

## 3. Fonctionnalités "legacy" à trancher — relecture à la lumière du marché

`legacy-feature-parity.md` liste plusieurs fonctionnalités disparues à valider avec le métier avant réintégration. Éléments nouveaux issus de cette recherche :

- **Auto-inscription élève (self-service)** — déjà marquée "à confirmer" dans l'audit legacy, mais **déjà implémentée** dans le code actuel (`PublicStudentRegistrationController`, lien + QR code généré par l'admin). Ce point de `legacy-feature-parity.md` est donc obsolète et devrait être mis à jour comme "fait" plutôt que "à confirmer".
- **Recyclage (remise à niveau)** — aucune mention trouvée dans la réglementation gabonaise actuelle (pas de "stage de récupération de points" à la française, système de points non identifié au Gabon dans les sources consultées) ; probablement un vestige stylistique de l'ancienne app inspirée d'un modèle français. Confirmer avec le métier gabonais avant de recréer — rien dans la recherche ne justifie sa priorité.
- **Vente de codes Rousseau** — spécifique au marché français (éditeur Codes Rousseau). Aucune mention d'un équivalent gabonais. Le module Boutique générique actuel (`Store`) suffit si l'auto-école vend des supports pédagogiques quelconques — ne pas recréer un module dédié à une marque française.
- **Feuille de route moniteur** — les solutions concurrentes (Colibri, Total DSM) ont toutes un équivalent ("drive sheets", évaluation en mobilité) ; cohérent avec le module `Training/Evaluation` déjà existant côté web. Le vrai gain serait la **version mobile/hors-ligne** de cet écran plutôt qu'un nouveau module.

---

## 4. Recommandations priorisées pour la roadmap

À intégrer dans `docs/audit/roadmap.md` étape 13 (fonctionnalités produit, après stabilisation sécurité/UX déjà actée) :

| # | Fonctionnalité | Justification marché/local | Effort estimé | Dépendance |
|---|---|---|---|---|
| 1 | **Rappels automatiques de séance (WhatsApp Business API en priorité, SMS en repli)** | Canal réellement utilisé au Gabon, gain no-show démontré ; infrastructure `Notifications` déjà prête à recevoir un nouveau canal | Moyen | Vérifier CGU/coût Meta Cloud API avant de s'engager (règle CLAUDE.md §26) |
| 2 | **Réservation de créneau en libre-service par l'élève, avec décompte du forfait** | Fonctionnalité standard chez tous les concurrents (Rdv360, Colibri, Goldie) ; absente ici alors que la détection de conflit backend est prête à la recevoir | Moyen-élevé | Nécessite de définir des règles métier (délai min. avant annulation, plafond de réservations simultanées) |
| 3 | **Paiement mobile money via agrégateur (type PVit) plutôt qu'intégration directe double Airtel/Moov** | Mode de paiement dominant et incontournable au Gabon ; un agrégateur réduit le risque technique et couvre aussi la zone CEMAC | Élevé | Vérifier CGU/API réelles avant développement (déjà noté en roadmap actuelle, ce point l'affine) |
| 4 | **PWA avec cache offline pour l'espace élève et l'agenda moniteur** | Contrainte de connectivité réelle et coût de la donnée en Afrique centrale ; alternative moins coûteuse qu'une app mobile native | Moyen | À positionner avant/à la place de "Application mobile éventuelle" |
| 5 | **Tableau de bord dirigeant avec KPIs visuels** (taux de réussite examen, occupation véhicules/moniteurs, CA) | Standard marché ("Statistics and Analytics" chez tous les concurrents cités) ; les exports CSV existants sont une base de données suffisante, il manque la restitution visuelle | Faible-moyen | Aucune, données déjà disponibles via `ReportsController` |
| 6 | **Solde de forfait visible côté élève** (heures restantes, montant dû) | Attendu par défaut dans les espaces élève concurrents ; renforce la transparence et réduit les appels au secrétariat | Faible | `TrainingPackage`/`Invoice` déjà modélisés, calcul déjà possible côté backend |
| 7 | **Veille sur une future API DGTT/CNEPC** (pas de développement immédiat) | Le permis digitalisé gabonais est trop récent (mars 2026) pour qu'une API publique existe ; à surveiller pour anticiper une intégration dossier candidat | Nul aujourd'hui | Ne rien développer tant qu'aucune spécification officielle n'est publiée — inscrire seulement une clause de veille |
| 8 | Mise à jour de `legacy-feature-parity.md` : marquer l'auto-inscription élève comme **faite**, dépriorisier "Recyclage" et "Codes Rousseau" sauf confirmation explicite du métier | Cohérence documentaire | Nul (doc uniquement) | — |

---

## 5. Ce qui ne doit **pas** être fait

- Ne pas copier telle quelle une fonctionnalité "France" (codes Rousseau, stage de récupération de points) sans vérifier son existence réglementaire au Gabon — le système de points n'apparaît dans aucune source consultée sur la réglementation gabonaise.
- Ne pas développer d'intégration Airtel/Moov/API DGTT tant que les CGU, coûts réels et disponibilité d'un environnement de test n'ont pas été vérifiés concrètement (cohérent avec la règle déjà actée en `roadmap.md`).
- Ne pas prioriser une application mobile native avant d'avoir évalué le rapport coût/bénéfice d'une PWA offline, qui répond au même besoin de mobilité à moindre coût de développement et de maintenance.

---

## Sources consultées

- [8 best driving school software for 2026 — Guideflow](https://www.guideflow.com/blog/driving-school-software)
- [Top 10 Driving School Management Software for 2026 — Booknetic](https://www.booknetic.com/blog/driving-school-management-software)
- [Rdv360 — Le logiciel de gestion d'auto-écoles tout-en-un](https://logiciel-auto-ecole.rdv360.com/)
- [Best Driving School Software with SMS Messaging 2026 — GetApp](https://www.getapp.com/education-childcare-software/driving-school/f/sms-integration/)
- [Driving School Management Software: 9 Must-Have Features — AcadifyOS](https://acadifyos.com/blog/academy-types/driving-school-management-software/)
- [Permis de conduire Gabon 2026 : prix, catégories A/B/C/G — Atek](https://www.atekbot.space/blog/permis-conduire-gabon)
- [Permis de Conduire Digitalisé Gabon 2026 : Guide Complet — Atek](https://www.atekbot.space/blog/permis-conduire-digitalise-gabon-2026)
- [Lancement officiel du permis de conduire digitalisé — Ministère des Transports du Gabon](https://transports.gouv.ga/lancement-officiel-du-permis-de-conduire-digitalise-par-le-president-de-la-republique-chef-de-letat-chef-du-gouvernement/)
- [TRANSFORMATION NUMÉRIQUE : LE GABON ENTRE DANS L'ÈRE DU PERMIS DIGITAL — Gouvernement.ga](https://gouvernement.ga/2026/03/24/transformation-numerique-le-gabon-entre-dans-lere-du-permis-digital/)
- [Nouveau permis digitalisé : 6 mois pour se conformer — Gabonactu](https://gabonactu.com/blog/2026/03/25/gabon-six-mois-pour-convertir-les-anciens-permis-en-format-numerique/)
- [Mobile Money Gabon 2026 : Comparatif Airtel Money vs Moov Money — Atek](https://www.atekbot.space/blog/gabon-mobile-money-gabon-guide-2026)
- [Intégrer Airtel Money site web Gabon — Guide 2026 — Kolonell](https://kolonell.com/fr/blog/integrer-airtel-money-site-web-gabon-libreville-2026)
- [PVit — API Gateway de Paiement Mobile Money & Marchand](https://mypvit.pro/)
- [WhatsApp Business : l'outil de vente n°1 des PME africaines — ITvities](https://itvities.com/blog/whatsapp-business-outil-vente-pme-afrique/)
- [Chatbot WhatsApp IA PME Afrique 2026 — Kolonell](https://kolonell.com/en/blog/whatsapp-ai-chatbot-sme-africa-2026)
- [SmartMifin et connectivité limitée — Webgram](https://www.agencewebgram.com/2026/08/smartmifin-et-connectivite-limitee-un-outil-pense-pour-lafrique-rurale.html)
- [PWA : Comprendre le Offline / hors-ligne et ses enjeux — SFEIR](https://wiki.sfeir.com/pwa/what/offline/)
