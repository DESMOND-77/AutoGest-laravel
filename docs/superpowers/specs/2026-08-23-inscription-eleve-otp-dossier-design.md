# Design - Parcours d'inscription élève : auto-création de compte, OTP, constitution de dossier

Date : 2026-08-23
Statut : approuvé par l'utilisateur, prêt pour planification d'implémentation.

## Contexte et objectif

Le parcours d'auto-inscription élève actuel (`PublicStudentRegistrationController`, formulaire one-shot derrière un lien tokenisé par établissement) crée directement une fiche `Student` complète en une seule soumission, sans vérification d'email ni processus de constitution de dossier différencié par pièce.

Ce design remplace entièrement ce parcours par un flux en plusieurs étapes, aligné sur un nouvel ordre du cycle de vie élève, avec :
- création de compte élève en self-service + validation d'email par OTP,
- constitution de dossier en self-service, avec documents requis **personnalisables par établissement**,
- revue et validation/rejet **par document individuel** par le secrétariat (pas au niveau du dossier global),
- transitions automatiques du `lifecycle_stage` sur les premières étapes du parcours, transitions manuelles ensuite (comportement inchangé à partir de l'étape Inscription).

Ce document remplace/complète le prompt `Promptset/04-espace-eleve-progression-paiements-dossier.md` (section « Mon Dossier ») et `Promptset/03-revue-dossier-admin.md`, qui décrivaient une version plus simple (dossier global) de ce même besoin.

## Nouvel ordre du cycle de vie

`App\Domain\Students\Enums\LifecycleStage` conserve ses 11 cas existants (aucun renommage), seul le graphe de transitions (`allowedNextStages()`) change d'ordre :

```
1  Prospect               - compte créé, email non vérifié
2  Pré-inscription        - email vérifié (OTP)
3  Constitution du dossier
4  Validation             - dossier soumis, en revue secrétariat
5  Inscription
6  Paiement
7  Cours théorique
8  Examens blancs
9  Code obtenu
10 Cours pratique
11 Évaluation continue
12 Prêt pour l'examen
13 Examen pratique
14 Permis obtenu
15 Ancien élève
```

Deux retours arrière autorisés dans le graphe (contre un seul aujourd'hui) :
- `Examen pratique → Évaluation continue` (déjà existant, inchangé).
- **Nouveau** : `Validation → Constitution du dossier`, déclenché automatiquement dès qu'un document du dossier est rejeté.

### Transitions automatiques vs manuelles

| Transition | Déclencheur | Type |
|---|---|---|
| `Prospect → Pré-inscription` | OTP email validé | Automatique |
| `Pré-inscription → Constitution du dossier` | Immédiat après validation OTP (pas d'action intermédiaire) | Automatique |
| `Constitution du dossier → Validation` | Élève clique « Soumettre mon dossier » | Automatique (déclenché par l'action élève) |
| `Validation → Constitution du dossier` | Un document est rejeté par le secrétariat | Automatique |
| `Validation → Inscription` | Tous les documents requis actifs sont `Approved` | Automatique |
| `Inscription → Paiement` et toutes les étapes suivantes | Action explicite secrétariat/moniteur | **Manuel, inchangé** |

Toute transition, automatique ou manuelle, passe exclusivement par `LifecycleService::transitionTo()` - jamais d'écriture directe de `lifecycle_stage`. Les transitions automatiques sont déclenchées par des listeners d'événements (OTP validé, dossier soumis, document rejeté/approuvé), pas par un nouveau mécanisme parallèle.

### Migration des données existantes

Le réordonnancement ne modifie pas les valeurs de chaîne stockées en base (mêmes cas d'enum) - seul le graphe de transitions *futures* change. Aucun script de rétro-migration n'est prévu pour les élèves déjà positionnés sur les anciennes étapes réordonnées : le projet est en phase de développement, les données existantes sont des données de test. Si des données de production existaient, ce point serait à retraiter séparément.

## Création de compte élève + validation OTP

### Formulaire de création de compte

Remplace `resources/views/register/student.blade.php`, même point d'entrée `register/student?token=...` (résolution du tenant par le token du lien d'établissement, inchangée - voir `StudentRegistrationLinkService::validate()`).

Champs collectés : prénom, nom, email, mot de passe + confirmation, téléphone, date de naissance, catégorie de permis visée (`LicenseCategory`), type de cours (`CourseType`). `birth_place`/`address` restent optionnels, saisissables plus tard.

À la soumission, dans une transaction unique :
1. Création du `User` (rôle `eleve` via Spatie, `structure_id` résolu depuis le token - jamais depuis la requête, `email_verified_at = null`).
2. Création du `Student` lié (`user_id`), `structure_id` du token, `lifecycle_stage = Prospect` (valeur par défaut existante, inchangée), champs saisis.
3. Génération d'un OTP à 6 chiffres, stocké **haché**, expiration 10 minutes, compteur de tentatives à 0.
4. Envoi de l'email contenant le code en clair.
5. Connexion automatique de l'élève (session créée).

### Écran de vérification OTP

- Route accessible uniquement à un utilisateur connecté avec `email_verified_at = null` et rôle `eleve`.
- Champ de saisie à 6 chiffres + bouton de validation.
- Bouton « Renvoyer le code », throttlé à 1 renvoi/minute (cohérent avec `throttle:6,1` déjà utilisé sur les routes publiques sensibles du projet) ; chaque renvoi invalide l'ancien code et réinitialise l'expiration/le compteur de tentatives.
- Maximum 5 tentatives de saisie erronée : au-delà, le code est invalidé, l'élève doit en redemander un.
- Un middleware équivalent à `verified` (mais basé sur ce mécanisme OTP plutôt que sur le lien signé Breeze standard) redirige tout accès à une autre route de l'espace élève vers cet écran tant que non vérifié.

### Nouvelle table `email_otps`

`user_id` (FK), `code_hash`, `expires_at`, `attempts` (défaut 0), `consumed_at` (nullable). Un `User` n'a jamais plus d'un OTP actif à la fois (le renvoi invalide/remplace l'existant plutôt que d'en empiler un nouveau).

### Événement déclenché

`StudentEmailVerified` (ou réutilisation d'un événement Laravel standard si suffisant), écouté par un listener qui appelle `LifecycleService::transitionTo($student, LifecycleStage::PreEnrollment)` puis, immédiatement, `LifecycleService::transitionTo($student, LifecycleStage::DossierSetup)` - les deux transitions s'enchaînent automatiquement sans état intermédiaire visible nécessitant une action.

## Documents requis personnalisables + revue par document

### Configuration côté établissement

Nouveau modèle `App\Domain\Students\Models\RequiredDocumentType`, dans le domaine `Students` (pas `Settings`) puisqu'il porte une règle métier spécifique au dossier élève, pas une préférence générale d'établissement :

- `structure_id`, `label` (texte libre), `position` (int, ordre d'affichage), `is_active` (bool, défaut `true`).
- CRUD complet réservé au rôle `admin`, écran `settings/documents-requis` (ou équivalent), ajouté au bloc Administration de la navigation.
- Désactiver une entrée (`is_active = false`) ne supprime pas l'historique des documents déjà liés - elle disparaît simplement de la liste des pièces à fournir pour les nouveaux dossiers.

### Extension du modèle `Document` existant

Pas de nouveau système de documents parallèle - extension du modèle `Document` déjà versionné (`is_current`, `version`) :

- `required_document_type_id` (FK nullable vers `RequiredDocumentType` - rempli uniquement pour les documents de dossier élève, `null` pour tout document hors de ce contexte, ex. documents véhicule).
- `review_status` enum (`Pending` par défaut, `Approved`, `Rejected`).
- `rejection_reason` (text, nullable).
- `reviewed_by_id` (FK `users`, nullable).
- `reviewed_at` (timestamp, nullable).

### Flux de dépôt (élève)

Écran « Constitution du dossier » (`eleve/dossier`) listant les `RequiredDocumentType` actifs de l'établissement, avec pour chacun l'état courant (rien déposé / en attente de revue / rejeté + motif / approuvé) et une zone de dépôt/redépôt.

Chaque dépôt crée une **nouvelle version** du `Document` correspondant (`is_current = true`, la précédente passe à `false`), `review_status` remis à `Pending`. L'historique complet - y compris les versions rejetées et leur motif - reste consultable, jamais supprimé.

Bouton « Soumettre mon dossier » : actif seulement quand chaque `RequiredDocumentType` actif a au moins une version de document déposée (peu importe son `review_status`). Si la liste des pièces requises est vide, le bouton est immédiatement actif. Le clic déclenche `LifecycleService::transitionTo($student, LifecycleStage::Validation)`.

Si l'élève redépose un document déjà `Approved` avant que le dossier entier soit passé à `Inscription`, la nouvelle version repasse en `Pending` - l'UI avertit explicitement avant confirmation de cette conséquence.

### Flux de revue (secrétariat)

Écran de file d'attente « Dossiers en attente de revue » (élèves à l'étape `Validation`), plus un accès depuis chaque fiche élève individuelle. Pour chaque document du dossier : action Approuver / Rejeter (motif obligatoire si rejet).

Cette action n'est disponible/acceptée côté serveur que si `student.lifecycle_stage === Validation` - refusée sinon, pour empêcher tout rejet/approbation hors-contexte.

- Un document rejeté → `LifecycleService::transitionTo($student, LifecycleStage::DossierSetup)` immédiat (peu importe l'état des autres documents). Le motif reste affiché côté élève jusqu'à ce qu'il redépose une nouvelle version de *ce* document précis.
- Tous les documents requis actifs passent à `Approved` → `LifecycleService::transitionTo($student, LifecycleStage::Enrollment)` automatique.

### Cas limite : ajout d'une pièce requise après soumission

Si l'admin ajoute un nouveau `RequiredDocumentType` après qu'un élève a déjà soumis son dossier (élève à l'étape `Validation` ou au-delà), cet élève **n'est pas** rétroactivement obligé de fournir la nouvelle pièce - seuls les dossiers non encore soumis au moment de l'ajout sont concernés.

## Écrans impactés (résumé)

| Écran | Route (indicative) | Rôle |
|---|---|---|
| Création de compte | `register/student?token=...` (remplace l'existant) | Public (tokenisé) |
| Vérification OTP | `eleve/verification-otp` | Élève non vérifié |
| Constitution du dossier | `eleve/dossier` | Élève |
| Configuration des pièces requises | `settings/documents-requis` | Admin |
| Revue de dossiers | file d'attente + section sur `students/{id}` | Admin |

Ce design **remplace** l'implémentation prévue par `Promptset/03-revue-dossier-admin.md` et la section « Mon Dossier » de `Promptset/04-espace-eleve-progression-paiements-dossier.md` (dossier global) par cette version plus fine (par document, avec configuration par tenant). Ces deux fichiers du Promptset seront à réviser en conséquence avant exécution.

## Gestion des erreurs et cas limites

- **Email déjà utilisé** : rejet avec message clair, sans révéler à quel établissement ce compte est déjà rattaché.
- **OTP expiré** : message dédié + proposition immédiate de renvoi (pas une erreur de validation générique).
- **OTP épuisé (5 tentatives)** : code invalidé, renvoi obligatoire.
- **Lien d'inscription révoqué/expiré entre affichage et soumission du formulaire** : comportement actuel conservé (`InvalidRegistrationLink`), aucune régression.
- **Rejet hors contexte** (tentative de rejeter/approuver un document alors que l'élève n'est plus à l'étape `Validation`) : refusé côté serveur (403 ou erreur de validation explicite), pas seulement caché côté UI.
- **Isolation tenant** : `RequiredDocumentType`, la revue de documents, l'écran OTP et toutes les nouvelles routes doivent être couvertes par des tests d'isolation tenant explicites (classe de faille la plus récurrente du projet, cf. `docs/audit/multi-tenancy-audit.md`).

## Tests à couvrir

- Scénario nominal complet : création de compte → OTP validé → documents déposés → tous approuvés → `Inscription` atteinte automatiquement, avec les bonnes transitions `lifecycle_stage` à chaque étape.
- Rejet d'un document → retour automatique à `Constitution du dossier` ; dossier de nouveau soumissible après correction de la seule pièce fautive (les autres pièces déjà approuvées ne sont pas affectées).
- Anti-brute-force OTP : tentatives épuisées, expiration, renvoi throttlé.
- Refus serveur d'une action de revue hors étape `Validation`.
- Isolation tenant sur chaque nouvelle route/modèle (`RequiredDocumentType`, revue de documents, OTP).
- Non-régression : les tests existants sur `PublicStudentRegistrationController` sont adaptés ou remplacés - aucun test rouge après ce changement. Vérifier aussi `docs/features/student-public-registration.md` (documentation existante) à mettre à jour en conséquence.

## Hors périmètre de ce design

- Envoi de l'OTP par SMS/WhatsApp (le canal reste l'email pour cette première version - cohérent avec `Promptset/11-notifications-sms.md`/`12-rappels-whatsapp-seances.md`, qui restent des sujets séparés).
- Réservation de créneau en libre-service, paiement mobile money (traités par ailleurs dans le Promptset).
- Migration rétroactive des élèves existants vers le nouveau graphe de transitions (voir section dédiée ci-dessus).
