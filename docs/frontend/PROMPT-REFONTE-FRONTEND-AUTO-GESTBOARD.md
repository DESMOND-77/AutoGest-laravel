# PROMPT MAÎTRE — Refonte complète du frontend UI/UX d'Auto-GestBoard

> **Style** : glassmorphism maîtrisé, 6 thèmes + couleur d'accent au choix, mode léger pour appareils modestes.
> **Stack imposée** : Blade (rendu serveur) + Alpine.js 3 + Tailwind CSS v4 + Vite 7, sans SPA.
> **Version** : 2026-10-22 · Langue de l'interface : français (Gabon) · Fuseau : configurable dans les paramtres(default:`Africa/Libreville`) · Monnaie : configurable dans les paramtres(default:FCFA (XAF, sans décimales))
>
> Ce document est le **seul** contexte dont tu as besoin pour la partie frontend. Il a été produit après une recherche sur les bonnes pratiques de glassmorphisme (accessibilité, performance), sur les patterns UX des SaaS de gestion et des logiciels d'auto-école, et sur le contexte d'usage local. Le fichier `auto-gestboard-prototype-reference.html` livré avec ce prompt est l'**implémentation visuelle de référence** (tokens, matériaux, shell, dashboard, planning, tableau, palette de commandes, sélecteur d'apparence). Ouvre-le dans Chromium avant d'écrire la moindre ligne.

---

## 0. Comment utiliser ce document

### 0.1 Rôles

- **Orchestrateur** (un seul agent) : exécute le lot WP0, crée les fichiers de coordination, distribue les lots (§12), arbitre les conflits, fusionne, lance la QA finale. Il ne code pas les pages de domaine.
- **Agents de lot** (un par lot WP1 à WP11) : chacun possède un périmètre de fichiers **exclusif** (voir §12). Un agent ne modifie jamais les fichiers d'un autre lot ; il dépose une demande dans `docs/frontend/component-requests.md`.
- **Agent QA** (lot WP12) : valide, mesure, nettoie, supprime l'ancien frontend.

### 0.2 Fichiers de coordination (créés par l'orchestrateur en WP0)

| Fichier | Rôle |
|---|---|
| `docs/frontend/inventory.md` | Inventaire des routes par rôle, des vues, des variables passées aux vues, des composants Blade existants. Généré depuis `php artisan route:list --json` et le code, jamais de mémoire. |
| `docs/frontend/backend-needs.md` | Besoins backend **additifs** détectés (filtre de liste manquant, endpoint de recherche, etc.). Voir §1.3. |
| `docs/frontend/component-requests.md` | File d'attente de demandes de composants ou de variantes adressées à WP2. |
| `docs/frontend/decisions.md` | Journal des décisions (une ligne datée par décision non triviale). |
| `docs/frontend/reference/` | Copie du prototype de référence et des captures de la matrice visuelle. |
| `docs/frontend/PROMPT-MAITRE.md` | Ce document, copié dans le dépôt pour que tous les agents lisent la même version. |

### 0.3 Règles de travail communes à tous les agents

1. Travaille sur la branche `feat/frontend-redesign`. Un commit par unité cohérente ; message en français à l'impératif ; respecte les lignes d'attribution demandées par l'environnement.
2. **Avant chaque commit** : `npm run build` doit passer, `php artisan test --compact` doit passer (dont `DomainBoundariesTest` et le garde-fou design system mis à jour en WP12), `vendor/bin/pint --dirty --format agent` si du PHP a été touché.
3. **Jamais** de `migrate:fresh`, `migrate:refresh`, `db:wipe` sur la base principale. Toute migration est **additive**. Les tests tournent sur `autoecole_jh_laravel_test`.
4. Ne copie aucune ligne de l'ancien frontend (« Soft Neumorphism », landing « route/signal »). Tu peux lire les anciennes vues **uniquement** pour comprendre quelles données elles reçoivent et quelles actions elles exposent.
5. Tout texte visible est en français, orthographe et accents corrects. Aucun texte en dur dans un composant générique : il vient d'un slot, d'une prop ou d'un fichier `lang/fr`.
6. Aucune couleur en dur dans une vue (§4.3). Aucun glyphe fléché `←` `→` ni emoji comme icône : toujours `<x-ui.icon>`.
7. Quand une décision n'est pas couverte ici : choisis l'option la plus accessible et la plus légère, consigne-la dans `decisions.md`, continue. Ne bloque pas.
8. Avant de déclarer un lot terminé, passe la **Définition de terminé** du §13 sur chaque page produite.

---

## 1. Mission, périmètre, contrat backend

### 1.1 Mission

Reconstruire **de zéro** tout le frontend de l'application (pages publiques, authentification, application connectée pour les 4 rôles, pages d'erreur, e-mails, PDF) avec un nouveau design system, une nouvelle bibliothèque de composants et une nouvelle expérience, inspirés des meilleures applications de gestion (planning, CRM, facturation, flotte, stock, suivi pédagogique), tout en conservant 100 % des fonctionnalités et des règles métier existantes.

L'ancien frontend est **ignoré** : ni réutilisé, ni migré progressivement, ni imité. Il est supprimé en WP12.

### 1.2 Dans le périmètre

Landing publique · inscription d'un établissement · connexion / mot de passe oublié · inscription publique d'un élève + OTP e-mail · application connectée (superadmin, admin, moniteur, élève) · profil et apparence · notifications · pages d'erreur (403, 404, 419, 422, 429, 500, 503) · gabarits e-mail · gabarits PDF (factures, feuille de route, rapports boutique) · feuilles de style d'impression.

### 1.3 Contrat backend (gelé)

- **Intouchables** : routes (URL et noms), contrôleurs, FormRequests, Policies, Services, Repositories, Events, migrations existantes, middlewares, permissions, noms des variables passées aux vues.
- **Autorisé, uniquement de façon additive et testée** (Pest) :
  1. Paramètres de requête optionnels de filtrage/tri sur des listes existantes (ex. `?statut=`, `?q=`, `?moniteur=`, `?du=&au=`), implémentés dans le Service/Repository du domaine concerné, jamais dans le contrôleur seul.
  2. Un endpoint `PUT /profile/appearance` (auth, `throttle`) et une colonne JSON nullable `users.ui_preferences` (migration additive) pour synchroniser l'apparence entre appareils (WP1, recommandé).
  3. Un endpoint de recherche globale `GET /search` pour la palette de commandes (WP3, phase 2) : résultats filtrés par Policy et par tenant, ne franchissant pas les frontières de domaines (vérifie `DomainBoundariesTest`).
  4. Données supplémentaires passées à une vue (ex. compteurs pour un onglet) via le contrôleur existant, sans changer les noms déjà passés.
- Tout besoin hors de cette liste est écrit dans `backend-needs.md` et **n'est pas implémenté** sans validation humaine.
- Le frontend ne doit **jamais** promettre ce que le backend ne fait pas : le SMS n'est pas branché (`LogSmsGateway`), il n'y a pas d'API publique, les paiements Airtel Money / Moov Money sont **enregistrés manuellement** (aucune intégration de paiement). Aucun logo d'opérateur : libellés texte uniquement.

---

## 2. Contexte produit (condensé de l'analyse)

**Auto-GestBoard** est un SaaS multi-établissements (row-level tenancy) pour auto-écoles gabonaises : élèves, moniteurs, planning, facturation, flotte, examens, boutique, CRM, documents, audit. Quatre rôles : `superadmin` (plateforme, hors tenant), `admin` (direction de l'auto-école), `moniteur`, `eleve`.

**Domaines** : Tenancy, Users, Students, Instructors, Training, Scheduling, Fleet, Finance, Store, CRM, Documents, Notifications, Audit, Recyclage, Reports, Settings.

**Parcours élève (15 étapes, `LifecycleStage`)** : Prospect → Pré-inscription → Constitution du dossier → Validation → Inscription → Paiement → Cours théorique → Examens blancs → Code obtenu → Cours pratique → Évaluation continue → Prêt examen → Examen pratique → Permis obtenu → Ancien élève. Retours autorisés : Validation → Constitution du dossier ; Examen pratique → Évaluation continue.

**Regroupement visuel imposé en 5 phases** (15 points sur une ligne sont illisibles, notamment sur mobile) :

| Phase | Étapes |
|---|---|
| 1. Admission | Prospect, Pré-inscription, Constitution du dossier, Validation |
| 2. Paiement | Inscription, Paiement |
| 3. Code | Cours théorique, Examens blancs, Code obtenu |
| 4. Conduite | Cours pratique, Évaluation continue, Prêt examen |
| 5. Permis | Examen pratique, Permis obtenu, Ancien élève |

**Flux à fort enjeu UX** : inscription publique par lien tokenisé + OTP e-mail ; revue de dossier (approuver / rejeter avec motif) ; planning avec détection de conflit moniteur **et** véhicule ; paiement (refus si > solde, annulation possible) ; alertes flotte (contrôle technique et assurance ≤ 30 jours) ; conversion lead → élève.

**Contexte d'usage local** (statistiques Statcounter, septembre 2026) : au Gabon, Chrome pèse 83 % des navigateurs mobiles et tablettes, Safari 8,8 %, Opera 2,4 %, Samsung Internet 2,2 % ; Android 11 à 16 couvre environ 84 % des appareils Android. Conséquences : **mobile-first**, appareils parfois modestes, données mobiles coûteuses. Le moniteur et l'élève utilisent surtout leur téléphone ; la direction alterne bureau et mobile.

**Formats locaux** : montants `25 000 FCFA` (espace fine insécable, aucune décimale, `Intl.NumberFormat('fr-GA', {style:'currency', currency:'XAF'})` rend `1 250 000 FCFA`, vérifié) ; dates `jeu. 22 oct. 2026`, heures 24 h `14:30`, fuseau `Africa/Libreville` ; téléphones `+241` acceptés avec ou sans indicatif, sans sur-validation côté front.

---

## 3. Principes de conception (issus de la recherche)

Chaque principe est **normatif**. La justification est donnée pour que tu puisses trancher les cas limites.

**P1 — Le verre est le « châssis », jamais le contenu.** Sidebar, topbar, dock mobile, dialogs, tiroirs et palette de commandes sont en verre. Tableaux, formulaires, graphiques, grille de planning et visionneuse de documents sont sur surface **pleine**. Apple réserve Liquid Glass à la couche de navigation et de contrôle et interdit son usage dans la couche de contenu ; Setproduct recommande de garder les données denses sur surfaces pleines à fort contraste.

**P2 — Un seul niveau de verre.** Jamais de verre sur du verre. Chaque couche de flou coûte du GPU et dégrade la lisibilité. Budget : **3 surfaces `backdrop-filter` simultanées au maximum à l'écran** (mobile : topbar + dock + un overlay). Les mobiles gèrent en général 3 à 5 effets simultanés.

**P3 — Le « verre des cartes » est simulé sans flou.** Le canevas derrière est un dégradé doux et statique ; une carte translucide sans `backdrop-filter` produit visuellement la même chose pour zéro coût GPU. Seules les couches qui **recouvrent du contenu qui défile** ont un vrai flou.

**P4 — Le flou ne remplace jamais le contraste.** Axess Lab : ne jamais s'appuyer sur le flou seul, éviter `blur(20px)` et plus, combiner transparence + bordure + ombre. Chaque texte atteint 4,5:1 (3:1 pour les composants d'interface) **dans le pire cas de fond**, ce qui est vérifié par script (Annexe D : 1 068 vérifications, 0 échec).

**P5 — Observation faite sur le prototype (à respecter)** : (a) une barre sticky en verre à 62 % d'opacité laisse lire le texte qui défile dessous : **opacité minimale 78 %** pour les barres sticky ; (b) un menu en verre placé **à l'intérieur** d'un parent en verre ne floute plus rien (le parent est le « backdrop root ») : **menus, popovers, dropdowns et tooltips sont opaques** (`--surface-solid`), seuls les dialogs et tiroirs téléportés à la racine de `<body>` peuvent être en verre.

**P6 — Repli solide d'abord.** Toute surface de verre a une version pleine conçue en premier, activée par : `@supports not (backdrop-filter)`, `prefers-reduced-transparency: reduce` (Chrome et Edge 118+, Firefox derrière un flag, **non supporté par Safari**), `prefers-contrast: more`, `forced-colors: active`, et le **mode léger** (`data-perf="lite"`).

**P7 — Opérationnel avant décoratif.** Un dashboard doit proposer la « prochaine meilleure action », pas seulement des chiffres (SaaSFrame) : bloc « À traiter » en tête, métrique principale en haut à gauche, jamais de graphique vide (un état vide explique comment l'alimenter). Si survoler un point est nécessaire pour comprendre un graphique, il a échoué.

**P8 — Navigation par tâche, pas par schéma de données.** Groupes par fréquence d'usage, 2 à 3 destinations quotidiennes en haut, administration en bas, pas plus de 3 niveaux, état actif visible sans lire, repli en rail d'icônes avec infobulles, état de repli mémorisé, palette de commandes `Ctrl/Cmd+K` visible (pas seulement un raccourci caché).

**P9 — Listes = filtres visibles + états explicites.** Recherche libre combinée à des filtres structurés ; filtres actifs affichés en **chips** supprimables avec « Tout effacer » ; tri par défaut délibéré avec indicateur de sens ; **vues enregistrées** sous forme d'onglets (« Tous », « Impayés », …) ; état dans l'**URL** (partageable, survit au rechargement) ; « aucun résultat » est un moment, pas une erreur : afficher les filtres actifs et proposer de les relâcher.

**P10 — Les ressources se planifient en colonnes.** Le planning est une grille par ressource (moniteurs ou véhicules en colonnes, heures en lignes), avec détails dans un popover ou tiroir, couleur **toujours doublée d'un texte** (type de séance), conflits visibles par icône + libellé + contour pointillé, et **alternative au glisser-déposer** (WCAG 2.5.7). Dans les logiciels d'auto-école de référence, la vue multi-moniteurs sur une journée, la réaffectation d'une séance et le planning imprimable sont des fonctions centrales ; le moniteur doit voir sa journée complète dès la connexion.

**P11 — Mobile-first réel.** Cibles tactiles ≥ 44 px pour les actions principales (minimum absolu 24 px, WCAG 2.5.8). Navigation basse (dock) pour moniteur et élève ; tableaux qui deviennent des listes de cartes sous 768 px ; formulaires en une colonne ; actions principales à portée de pouce.

**P12 — Sobriété de poids.** Données mobiles coûteuses : une seule police variable auto-hébergée, JS initial ≤ 70 Ko gzip, graphiques chargés à la demande, illustrations en SVG/CSS, aucune image décorative lourde. Voir §11.

---

## 4. Système visuel

### 4.1 Les quatre matériaux

| Matériau | Classe utilitaire | Fond | `backdrop-filter` | Où l'utiliser |
|---|---|---|---|---|
| **Canevas** | `.app-canvas` | Base + 3 dégradés radiaux **statiques** (une seule couche `position: fixed; inset: 0; contain: strict`) | non | Fond de page. Jamais animé, jamais `background-attachment: fixed`. |
| **Panneau** | `material-panel` | `--surface-panel` (72-76 % d'opacité) + bordure 1 px + reflet haut `--glass-edge` + `--elev-1` | **non** | Cartes de dashboard, KPI, blocs de page qui ne recouvrent pas de contenu qui défile. |
| **Verre** | `material-glass` | `--surface-glass` (78-80 %) | oui : `blur(16px) saturate(1.35)` (8 px sous 768 px) | Sidebar, topbar sticky, dock mobile. |
| **Verre overlay** | `material-overlay` | `--surface-overlay` (86-90 %) + `--elev-3` | oui, **téléporté dans `#overlays`** | Dialogs, tiroirs latéraux, palette de commandes. |
| **Plein** | `material-solid` | `--surface-solid` opaque | non | Tableaux, formulaires, graphiques, grille de planning, documents, **menus, popovers, dropdowns, tooltips**. |

Règles de verre (rappel P1 à P6) :

1. Jamais de `backdrop-filter` sur un élément répété (ligne de tableau, carte de liste, séance de planning).
2. Jamais de verre imbriqué dans du verre. Un popover ouvert depuis la topbar est **opaque** (le prototype le démontre : en verre, il ne floute rien).
3. Animer uniquement `opacity` et `transform` ; jamais l'intensité du flou ; `will-change` posé seulement pendant l'animation.
4. Préfixe `-webkit-backdrop-filter` toujours présent (Safari ≤ 16).
5. Les replis sont obligatoires et testés (§10) : `@supports not`, `prefers-reduced-transparency`, `prefers-contrast: more`, `forced-colors`, `[data-perf="lite"]`.

### 4.2 Thèmes (6) + accent (8) + densité (2) + performance (3)

Quatre préférences indépendantes, portées par des attributs sur `<html>` :

| Attribut | Valeurs | Défaut |
|---|---|---|
| `data-theme` | `aurore`, `ocean`, `sable`, `foret`, `crepuscule`, `graphite` | `aurore` |
| `data-mode` | `light` \| `dark` (**déduit du thème**, toujours posé par le serveur) | `light` |
| `data-accent` | `indigo`, `violet`, `rose`, `orange`, `amber`, `emerald`, `teal`, `sky` ; absent = accent par défaut du thème | absent |
| `data-density` | `comfortable` (ligne 52 px, contrôle 44 px), `compact` (ligne 40 px, contrôle 36 px) | `comfortable` |
| `data-perf` | absent = complet ; `lite` = sans flou, sans dégradés, ombres simples | auto (voir 5.4) |

| Thème | Mode | Ambiance | Accent par défaut |
|---|---|---|---|
| **Aurore** | clair | Lavande, rose poudré, menthe. Le thème « vitrine ». | indigo |
| **Océan** | clair | Bleus glacés, cyan. Sérieux, finance. | sky |
| **Sable** | clair | Crème chaud, ambre, corail. Chaleureux, lumière de plein jour. | orange |
| **Forêt** | sombre | Vert profond. Rappelle la marque et l'équatorial gabonais. | emerald |
| **Crépuscule** | sombre | Indigo nuit, violet. Le thème « premium ». | violet |
| **Graphite** | sombre | Neutre quasi noir. Contraste maximal, le plus économe. | sky |

**Option « Auto »** : suit `prefers-color-scheme` et résout en `aurore` (clair) ou `crepuscule` (sombre). Le serveur ne connaît pas la préférence système : le script anti-flash (Annexe C) la résout avant le premier rendu.

**Valeurs exactes** : Annexe A (`tokens.css`, source de vérité). **Ne modifie aucune valeur sans relancer** `check-contrast.py` (Annexe D) **et mettre à jour les deux fichiers ensemble**.

### 4.3 Règles d'usage des tokens

- Dans les vues Blade, on n'utilise **que** les utilitaires sémantiques exposés par `@theme inline` (Annexe B) : `bg-canvas`, `bg-solid`, `bg-panel`, `bg-glass`, `bg-inset`, `bg-hover`, `border-line`, `border-control`, `text-ink`, `text-ink-2`, `text-ink-3`, `bg-accent`, `text-on-accent`, `bg-accent-soft`, `text-accent`, `text-success` / `bg-success-soft` (idem `warning`, `danger`, `info`), `bg-success-solid`, etc.
- **Interdits** (le garde-fou Pest les détecte) : `bg-white`, `bg-black`, `text-gray-*`, `text-slate-*`, tout `#hex` ou `rgb()` dans un attribut `class`/`style`, `bg-[#…]`, `dark:` pour recolorer (les tokens basculent seuls ; `dark:` n'est autorisé que pour un cas structurel non colorimétrique).
- Texte : `text-ink` (corps), `text-ink-2` (secondaire), `text-ink-3` (légendes, métadonnées). **Les trois atteignent 4,5:1** sur panneau et verre. Aucune autre nuance de texte.
- Texte sur fond d'accent : toujours `text-on-accent` (blanc en clair, `#0B1020` en sombre). **Ne jamais** forcer `text-white` sur un fond d'accent.
- Accent comme **texte** (liens, onglet actif) : `text-accent` (4,5:1 vérifié sur panneau et verre pour les 8 accents dans les 6 thèmes).
- Statut : jamais la couleur seule. Toujours **pastille ou icône + libellé** (`x-ui.status`).
- Séries de graphiques : `--chart-1` (= accent) à `--chart-6`, vérifiées ≥ 3:1 sur surface pleine ; doublées de libellés directs, de valeurs ou de motifs, jamais d'une légende colorée seule.

### 4.4 Typographie

- **Famille** : Inter Variable, **auto-hébergée** (`@fontsource-variable/inter`, sous-ensembles `latin` et `latin-ext` seulement, `font-display: swap`, préchargement du fichier `latin`). Aucune ressource externe. Pile de repli : `ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial`.
- **Chiffres** : `font-variant-numeric: tabular-nums` sur **tout le corps** (montants, heures, compteurs alignés) ; montants alignés à droite dans les tableaux.
- **Échelle** (px, desktop → mobile) : titre de page 28/24 (poids 750, interlettrage −0,025 em) · titre de section 17-20 (700) · corps 15 (400) · petit 14 · légende 12-13 · sur-titre de groupe 11 en capitales (700, +0,07 em).
- **Champs de saisie : 16 px minimum sur mobile** (évite le zoom automatique d'iOS).
- Longueur de ligne ≤ 70 caractères pour les textes d'aide ; pas de texte justifié ; pas de capitales sur plus de 3 mots.

### 4.5 Espacement, rayons, élévation

- Grille de 4 px. Gouttières de page 16 px (mobile) / 24 px (≥ 1024 px). Espacement entre cartes 16 px. Padding de carte 20 px (16 px mobile).
- Rayons : sidebar, topbar, dialogs `--r-xl` (28) ; cartes `--r-lg` (20) ; champs `--r-md` (14) ; boutons et chips **pilule** ; tableau dans une carte `--r-md`.
- Bordures : 1 px `--line-subtle` pour les surfaces ; **`--line-control`** (≥ 3:1) pour les champs et contrôles, car une bordure à 10 % d'opacité ne suffit pas à délimiter un contrôle (WCAG 1.4.11).
- Ombres teintées par thème (`--shadow-rgb`), jamais noires pures en mode clair.

### 4.6 Mouvement

Durées `--dur-1/2/3` = 120/200/320 ms, courbe `--ease-ui`. Transitions d'apparition : opacité + translation ≤ 8 px. **Pas** de parallaxe, pas de fond animé, pas de rebond. Squelettes de chargement : un léger balayage, désactivé sous `prefers-reduced-motion`. Règle globale `@media (prefers-reduced-motion: reduce) { *, ::before, ::after { animation: none !important; transition: none !important } }`.

### 4.7 Iconographie

Jeu **Lucide** (trait 1,75 px, 20 px par défaut) via un unique composant `<x-ui.icon name="calendar" />`. Aucune icône décorative n'est annoncée (`aria-hidden="true"`) ; une icône seule dans un bouton exige `aria-label` sur le bouton. Pas de glyphes Unicode comme icônes. Pas de logos d'opérateurs de paiement.

---

## 5. Architecture technique du frontend

### 5.1 Arborescence cible

```
resources/
  css/        app.css · tokens.css · materials.css · print.css
  js/         app.js · alpine/{prefs.js, palette.js, toasts.js, planning.js, …} · lib/{format.js, charts.js}
  views/
    layouts/      app · guest · public · print · pdf
    components/
      ui/         primitives (button, badge, status, avatar, icon, tooltip, kbd, progress, skeleton, money, date…)
      form/       input, textarea, select, combobox, checkbox, radio, switch, segmented, file-drop, otp, money-input, section…
      layout/     sidebar, topbar, dock, page-header, breadcrumbs, tabs, toasts, overlays…
      data/       table, filter-bar, kpi, stat, sparkline, ring, bar, chart-card, timeline, empty-state, error-state…
      domain/     stage-stepper, planning-grid, session-card, vehicle-card, invoice-summary, doc-list, kanban…
    <domaine>/…   (students, finance, scheduling, training, fleet, store, crm, documents, instructors, recyclage, settings, audit, tenancy, users, register, emails, profile, admin, moniteur, eleve)
    errors/       403 404 419 422 429 500 503
    design-system/index.blade.php    (guide vivant, environnement local + superadmin uniquement)
```

Les **chemins et noms des vues** appelés par les contrôleurs ne changent pas : on réécrit les fichiers en place. Les nouveaux composants vivent sous les namespaces `ui`, `form`, `layout`, `data`, `domain`. Les anciens composants (`<x-card>`, `<x-button>`, `<x-kpi-card>`, …) sont **supprimés en WP12** ; un test interdit leur usage dès WP2.

### 5.2 Dépendances

**npm — ajouter** : `tailwindcss@^4`, `@tailwindcss/vite`, `alpinejs`, `@alpinejs/focus`, `@alpinejs/collapse`, `@alpinejs/anchor`, `@alpinejs/persist`, `@alpinejs/mask`, `@alpinejs/intersect`, `@alpinejs/sort` (planning / kanban, optionnel), `@fontsource-variable/inter`, `chart.js` (chargé à la demande). **Retirer** : `tailwindcss@3`, `@tailwindcss/forms`, `postcss`, `autoprefixer`, `axios` (remplacé par `fetch` + jeton CSRF en `<meta>`, **après** avoir vérifié par `grep` qu'aucun script ni aucune vue n'en dépend). **Supprimer** : `tailwind.config.js`, `postcss.config.js`.

**composer — ajouter** : `mallardduck/blade-lucide-icons` (licence MIT, repose sur Blade Icons ; la compatibilité Laravel 12 n'est pas confirmée par la page du paquet : **teste-la**, sinon utilise `blade-ui-kit/blade-icons` avec les SVG Lucide enregistrés comme jeu personnalisé). Activer le cache : `php artisan icons:cache` (à ajouter à `docs/deployment.md`).

**Navigateurs** : Tailwind v4 exige Chrome 111, Safari 16.4, Firefox 128 (`color-mix`, variables natives). C'est cohérent avec le trafic local (Chrome 83 % ; Android 11+ reçoit Chrome à jour via le Play Store). Ajoute dans le layout un test de fonctionnalité : si `!CSS.supports('color', 'color-mix(in srgb, red, blue)')`, afficher une bannière sobre « Votre navigateur est trop ancien, mettez-le à jour » (le contenu reste lisible). **Pas de polyfills.** Opera Mini (mode extrême) n'est pas supporté ; le documenter.

### 5.3 Tailwind v4 + Vite

- `vite.config.js` : `laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true })` + `tailwindcss()` (plugin `@tailwindcss/vite`).
- `app.css` : voir l'Annexe B (squelette complet). Points critiques :
  - `@source` explicites pour `resources/**/*.blade.php`, `resources/**/*.js`, `storage/framework/views/*.php` et les vues de pagination de Laravel ;
  - `@theme inline` pour **tout** ce qui référence un token runtime (`--color-accent: var(--accent)`), sans quoi la valeur serait figée sur `:root` et ne suivrait pas `data-theme` ;
  - **ne jamais nommer un token runtime comme une variable de thème Tailwind** (piège de référence circulaire) : la police s'appelle `--font-ui` dans `tokens.css` et `@theme inline { --font-sans: var(--font-ui) }` ;
  - variantes personnalisées : `dark` (basée sur `data-mode`), `lite`, `compact`.

### 5.4 Système de préférences (WP1)

1. **Stockage** : cookie `ui_prefs` (JSON : `{"theme":"auto|aurore|…","accent":null|"rose","density":"comfortable|compact","perf":"auto|lite|full"}`), 1 an, `SameSite=Lax`, `Path=/`, **doublé** dans `localStorage` (même clé).
2. **Cookie non chiffré** : Laravel chiffre les cookies par défaut ; un cookie écrit par JavaScript serait rejeté. Ajouter `ui_prefs` à `$middleware->encryptCookies(except: ['ui_prefs'])` dans `bootstrap/app.php`.
3. **Lecture côté serveur (anti-flash)** : un View Composer (ou composant de classe `Layout\HtmlAttributes`) lit le cookie, **valide chaque valeur contre une liste blanche** (enum PHP), ignore le reste, et pose `data-theme`, `data-mode`, `data-accent`, `data-density`, `data-perf` sur `<html>`. **Ne jamais écho une valeur de cookie brute** dans le HTML (risque d'injection) : toujours passer par la liste blanche.
4. **Script inline dans `<head>`** (Annexe C, < 1 Ko) : résout `auto` via `matchMedia('(prefers-color-scheme: dark)')`, active `data-perf="lite"` automatiquement quand `perf` vaut `auto` **et** que l'une des conditions est vraie : `navigator.connection?.saveData`, `navigator.deviceMemory <= 2`, `navigator.hardwareConcurrency <= 2` (ces API n'existent que sur Chromium : à utiliser en amélioration, jamais en dépendance). Si une CSP est ajoutée, prévoir un nonce.
5. **Store Alpine** `Alpine.store('prefs')` : `set(clé, valeur)` met à jour les attributs de `<html>`, le cookie, le `localStorage`, émet l'événement `prefs:changed` (les graphiques se redessinent), et, si l'utilisateur est connecté et que l'endpoint existe, envoie `PUT /profile/appearance` (debounce 600 ms). À la connexion, la valeur de la base **prime** sur le cookie.
6. **Interface** : popover « Apparence » (topbar) et section « Apparence » du profil : 6 vignettes de thème + « Auto », 8 pastilles d'accent (+ « Par défaut »), segmenté de densité, interrupteur « Mode léger » avec sa description (« Sans flou ni dégradés : pour les appareils modestes »). Les pastilles d'accent montrent la couleur **du mode courant**.

### 5.5 Layouts

- **`layouts.app`** : `<html lang="fr" data-…>` · head (meta viewport, `color-scheme`, script anti-flash, `@vite`, préchargement police) · `<div class="app-canvas" aria-hidden="true">` · lien d'évitement « Aller au contenu » · shell (sidebar verre, colonne principale : topbar verre + `<main id="main" tabindex="-1">`) · dock mobile · `<div id="overlays">` (cible des téléportations) · zone de toasts (`aria-live`).
- **`layouts.guest`** : carte centrée en verre sur canevas, logo, sélecteur de thème discret (coin), pied de page de confiance.
- **`layouts.public`** : landing et pages publiques (en-tête verre sticky, sections pleine largeur).
- **`layouts.print`** : fond blanc, texte noir, chrome masqué (`print.css`).
- **`layouts.pdf`** : gabarit **dompdf** : CSS 2.1 simple, `table` pour la mise en page, **aucune variable CSS, aucun flex/grid, aucun backdrop-filter** ; couleurs en hexadécimal statique tirées de la marque (thème Aurore, accent indigo).

Les **dialogs et tiroirs** utilisent `x-teleport="#overlays"`, `x-trap.inert.noscroll` (plugin Focus : piège le focus, rend le reste inerte, bloque le défilement, restitue le focus à la fermeture), `role="dialog"`, `aria-modal="true"`, `aria-labelledby`, fermeture par `Échap` et clic sur le voile (`--scrim`).

### 5.6 Messages flash et validation

- Flashes de session (`status`, `success`, `error`, `warning`) → **toasts** : coin supérieur droit (bas sur mobile, au-dessus du dock), `role="status"` ; les erreurs `role="alert"` ne disparaissent pas seules ; les autres disparaissent après 6 s (pause au survol et au focus).
- Erreurs de validation : message **sous chaque champ** (`aria-describedby`, `aria-invalid="true"`) + **résumé en tête de formulaire** avec liens vers les champs, focus placé sur le résumé après soumission. `old()` toujours restitué.
- Actions destructives : dialog de confirmation avec verbe explicite (« Supprimer le véhicule »), conséquence rappelée, bouton danger ; pour les suppressions irréversibles majeures (établissement, élève), **saisie du nom** pour confirmer. Jamais d'action destructive déclenchée par `Entrée` seule.

### 5.7 Formats et présentation des données (composants de classe dans `app/View/Components/Ui`)

- `<x-ui.money :amount="25000" />` → `25 000 FCFA` (espaces insécables, tabulaire, `aria-label` complet). Vérifie la sortie PHP (`Illuminate\Support\Number::currency` ou `NumberFormatter`) : elle doit être **identique** au rendu JavaScript `Intl`.
- `<x-ui.date :value="$d" format="short|long|time|relative" />` → locale `fr`, fuseau `Africa/Libreville` ; le relatif (« il y a 2 jours ») porte la date absolue en `title` et `<time datetime>`.
- `<x-ui.status :value="$enum" />` : **un seul fichier de configuration** `config/ui-status.php` associe chaque enum métier (`LifecycleStage`, statuts de facture, `LeadStatus`, statuts de structure, statuts de document, présence…) à `{libellé, ton, icône}`. Aucun mapping enum → couleur dans les vues.
- `<x-ui.phone>` et `<x-ui.avatar>` (initiales, couleur dérivée stable du nom parmi 8 teintes **de texte accessible**).

### 5.8 Graphiques

- **Par défaut : SVG/CSS rendu côté serveur** (sparklines, barres, anneaux de progression, entonnoir) : zéro JavaScript.
- **Séries temporelles et comparaisons riches** : `chart.js` (≈ 67 Ko gzip pour la build complète, moteur canvas, modulaire : n'enregistre que les types utilisés), **chargé par `import()` dynamique** uniquement sur les pages qui en ont besoin. Couleurs lues sur `getComputedStyle(documentElement)` (`--chart-*`, `--line-subtle`, `--text-3`), redessin sur `prefs:changed`. Chaque graphique a un nom accessible, un résumé textuel et une **alternative tabulaire** accessible.
- Règles de choix : courbe = tendance, barres = comparaison de catégories, anneau seulement pour des parties d'un tout (≤ 5 parts), jamais de camembert pour comparer.

### 5.9 Planning

Composant maison en **CSS Grid** + Alpine (pas de FullCalendar : ses vues de ressources sont des plugins Premium sous licence, et la version d'évaluation interdit l'usage commercial en production). Détails au §8 (archétype F). La règle métier (`ConflictRule`) reste **côté serveur** ; l'interface affiche l'erreur renvoyée et, quand c'est possible, prévient avant la soumission.

### 5.10 Impression et PDF

`print.css` : masque sidebar/topbar/dock/boutons, fond blanc, texte noir, tableaux en `break-inside: avoid`, liens avec URL en pied pour les documents. Les PDF (factures, feuille de route, rapports boutique) passent par `layouts.pdf` (contraintes dompdf ci-dessus) : vérifier leur rendu réel après refonte, pas seulement la page HTML.

---

## 6. Bibliothèque de composants (WP2)

Tous les composants sont des **composants Blade anonymes** (sauf `ui.money`, `ui.date`, `ui.status`, `ui.icon` qui ont une classe) construits avec `@props`, `$attributes->merge()` et des slots nommés. Chacun gère **tous ses états** : repos, survol, focus visible, actif, désactivé, chargement, erreur, vide (selon pertinence). Chacun apparaît dans le guide vivant `/design-system` dans les 6 thèmes × 2 densités.

### 6.1 Primitives (`ui.*`)

| Composant | API et variantes | Exigences |
|---|---|---|
| `button` | `variant` primary · secondary · ghost · danger · link ; `size` sm · md · lg ; `icon` ; `loading` ; `href` (rend un `<a>`) ; `type` | Hauteur `--control-h`. `loading` : spinner, `aria-busy`, désactive le double envoi. Bouton icône seule : prop `label` **obligatoire** (→ `aria-label`). Focus : anneau double `--focus-ring`. |
| `badge` / `status` | `tone` neutral · accent · success · warning · danger · info ; pastille ou icône | Toujours libellé + pastille/icône. `status` prend une valeur d'enum et lit `config/ui-status.php`. |
| `avatar`, `avatar-group` | `name`, `src`, `size`, `status` | Initiales si pas d'image ; `alt` vide si le nom est affiché à côté. |
| `tooltip` | `text`, `placement` | **Opaque.** Apparaît au survol **et au focus**, `role="tooltip"`, jamais pour une information essentielle. |
| `kbd` | touches | Affiche `⌘` sur macOS, `Ctrl` ailleurs. |
| `progress` | `value`, `max`, `label`, `variant` bar · ring | `role="progressbar"` + valeur textuelle. |
| `skeleton`, `spinner` | formes | Squelette = forme de la donnée finale (pas de rectangle générique). |
| `alert` | `tone`, `title`, `dismissible`, `action` | `role="alert"` pour erreur, `role="status"` sinon. |
| `card` | `variant` panel · solid · flat ; slots `header` (titre, description, actions), `footer` | `panel` par défaut ; `solid` si contient un tableau, un formulaire, un graphique. |
| `dropdown`, `popover` | `x-anchor` pour le positionnement | **Opaques.** `role="menu"`, flèches, `Home/End`, saisie rapide, `Échap` rend le focus au déclencheur. |
| `dialog`, `drawer`, `confirm` | tailles sm · md · lg ; `drawer` à droite (plein écran sur mobile) ; `confirm` : `title`, `consequence`, `confirmLabel`, `typedConfirmation` | Téléportés dans `#overlays`, piège de focus, `Échap`, titre obligatoire. |
| `tabs`, `segmented` | `variant` underline · pill ; mode **lien** (navigation serveur) ou **panneau** | `role="tablist"`, flèches, compteurs, onglet actif aussi marqué par autre chose que la couleur (soulignement). |
| `pagination` | pages serveur, sélecteur de taille | « Affichage 1-25 sur 148 », boutons précédent/suivant avec icône et libellé. |
| `empty-state`, `error-state`, `loading-state` | `icon`/illustration SVG légère, `title`, `description`, `primary`, `secondary` | Un état vide **explique et propose l'action** (« Aucun véhicule. Ajoutez le premier pour suivre contrôle technique et assurance. »). |
| `icon`, `money`, `date`, `phone`, `link`, `separator` | voir §5.7 | |

### 6.2 Formulaires (`form.*`)

| Composant | Particularités |
|---|---|
| `field` | Enveloppe : `label` visible (jamais un simple placeholder), `hint`, `error`, mention « (obligatoire) » **en texte** (pas seulement `*`), compteur de caractères si limite. Génère les `id` et `aria-describedby`. |
| `input`, `textarea` | Fond `--surface-inset`, bordure `--line-control`, rayon `--r-md`, 16 px mobile, préfixe/suffixe/icône, `inputmode` et `autocomplete` corrects. |
| `select` | Natif stylé pour ≤ 15 options (meilleur sur mobile). `combobox` consultable (listbox ARIA) pour élèves, moniteurs, véhicules. |
| `checkbox`, `radio`, `switch` | Cible ≥ 44 px (zone cliquable étendue au libellé). `radio-card` pour les choix riches (mode de paiement). |
| `date`, `time` | `<input type="date">` et `<input type="time">` natifs stylés (léger, clavier mobile adapté). Pas de bibliothèque de calendrier lourde. |
| `money` | Masque `@alpinejs/mask` ou dérivé, séparateur de milliers, suffixe `FCFA`, **envoie un entier brut** au serveur. Affiche le solde restant comme aide. |
| `file-drop` | Glisser-déposer + clic + `capture` caméra sur mobile ; types/tailles annoncés **avant** l'envoi ; progression ; erreurs claires ; liste de fichiers avec retrait. |
| `otp` | 6 cases, avance automatique, collage d'un code complet, `autocomplete="one-time-code"`, `inputmode="numeric"`, décompte du renvoi (le backend limite à 1 par minute), compteur de tentatives restantes. |
| `section` | Mise en page de paramètres : titre et description à gauche, champs à droite (empilé sur mobile). |
| `actions` | Barre d'actions de formulaire : Annuler (ghost) + Enregistrer (primary) ; **sticky en bas** sur les longs formulaires (barre en verre, opacité ≥ 78 %, `scroll-padding-bottom` pour ne pas masquer le focus). |
| `stepper` | Assistant multi-étapes : progression, liste des étapes, retour sans perte de données, validation serveur par étape. |

### 6.3 Données (`data.*`)

| Composant | Particularités |
|---|---|
| `table` | `<caption class="sr-only">`, en-têtes `scope="col"`, `aria-sort`, en-tête sticky, tri cliquable avec indicateur de sens, sélection par lignes (case + « tout sélectionner »), menu d'actions de ligne, densité héritée de `data-density`, **sous 768 px : liste de cartes** (chaque cellule affiche son libellé via `data-label`), ombres de défilement horizontal. Surface **pleine**. |
| `filter-bar` | Recherche + chips de filtres actifs (retrait unitaire + « Tout effacer ») + menu « Ajouter un filtre » + **onglets de vues** (liens avec compteurs) + nombre de résultats + sélecteur de tri. Soumet en **GET**, état dans l'URL. |
| `bulk-bar` | Barre flottante opaque en bas quand ≥ 1 ligne est sélectionnée : « 3 sélectionnés · Exporter · Relancer · Annuler la sélection ». N'exposer que les actions **réellement supportées** par le backend. |
| `kpi` | `label`, `value`, `delta` (icône de direction **et** texte « +6 ce mois »), `sparkline`, `link`. La métrique principale est en **haut à gauche** de la page. |
| `sparkline`, `ring`, `bar` | SVG serveur, `role="img"` + `aria-label` décrivant la valeur. |
| `chart-card` | Titre, plage de dates, légende **avec valeurs**, alternative tabulaire, état vide explicatif. |
| `timeline` | Fil d'activité groupé par jour (Aujourd'hui, Hier, date), icône par type, acteur, heure, détail extensible. Pour l'historique d'un élève et l'audit. |
| `description-list` | Paires libellé/valeur, 2 colonnes ≥ 768 px. |

### 6.4 Domaine (`domain.*`)

Le propriétaire de chaque composant est indiqué au §12.2 : WP2 construit les composants génériques (`stage-stepper`, `expiry-chip`), chaque lot de domaine construit les siens à partir des primitives de WP2.

| Composant | Contenu |
|---|---|
| `stage-stepper` | Les **5 phases** (§2) avec l'étape courante nommée ; variantes `full` (page élève), `compact` (en-tête de fiche), `ring` (carte). Phase passée = coche, courante = anneau, future = numéro. Sur mobile : phase courante + « Étape X sur 15 ». `aria-current="step"`. |
| `planning-grid` | Voir archétype F. |
| `session-card` | Heure, type (texte + couleur), élève, moniteur, véhicule, lieu, présence, conflit. Variantes `block` (grille) et `row` (liste mobile). |
| `presence-toggle` | Segmenté à 3 états (valeurs **lues dans l'enum du code**) : cible ≥ 44 px, grande version pour le moniteur. |
| `vehicle-card`, `expiry-chip` | Plaque, modèle, statut, chips d'échéance contrôle technique et assurance : > 30 j neutre, ≤ 30 j avertissement, ≤ 7 j ou expiré danger ; texte « dans 12 jours » / « expiré depuis 3 jours ». |
| `invoice-summary`, `payment-method-picker` | Total / payé / solde / statut / barre ; picker en `radio-card` : **Espèces · Airtel Money · Moov Money · Virement · Chèque** (libellés texte, icône neutre). |
| `doc-requirement`, `doc-list`, `doc-viewer` | Type requis, statut (à fournir · soumis · approuvé · rejeté + **motif visible**), action de dépôt, aperçu PDF/image, repli téléchargement. |
| `skill-matrix`, `skill-level` | Compétences × niveaux (`SkillLevel`), puces + barre ; saisie rapide par segmenté. |
| `quiz-question`, `quiz-result` | Options en grandes cartes radio, progression, états juste/faux **non colorimétriques seuls**, revue question par question. |
| `kanban` | Colonnes par `LeadStatus` (compteur), cartes, **menu « Déplacer vers… » toujours disponible** (alternative au glisser-déposer) + glisser-déposer via `@alpinejs/sort`. |
| `stock-bar`, `order-flow` | Niveau vs seuil ; chaîne de statuts de commande. |
| `availability-editor` | 7 jours × plages horaires ; ajout par dialog (alternative à la saisie par glissement). |
| `notification-item` | Icône par type, texte, temps relatif, non-lu (pastille **et** libellé SR « non lu »). |

### 6.5 Mise en page (`layout.*`)

`sidebar`, `topbar`, `dock`, `page-header` (fil d'Ariane, titre, description, badge de statut, actions primaire/secondaires, slot d'onglets), `breadcrumbs`, `toasts`, `command-palette`, `appearance-popover`, `notifications-popover`, `user-menu`, `split-view` (liste + détail), `settings-shell` (navigation d'ancres à gauche + sections).

### 6.6 Guide vivant `/design-system`

Route disponible **uniquement** si `app()->environment('local')` **et** utilisateur superadmin. Une page par famille ; sélecteur de thème/accent/densité/léger en tête ; chaque composant dans tous ses états. C'est la documentation ET le banc de test visuel de WP12.

---

## 7. Shell, navigation et recherche (WP3)

### 7.1 Sidebar (≥ 1024 px)

- Verre, flottante à 12 px des bords, rayon 28. **264 px** étendue, **rail de 76 px** replié (icônes + infobulles opaques ; l'état actif reste visible). Repli mémorisé (`$persist`).
- En-tête : marque + **nom de l'établissement** (superadmin : « Plateforme »). Corps : groupes à intitulés en capitales 11 px. Pied : « Paramètres » + carte utilisateur (avatar, nom, **rôle**).
- Élément actif : fond `--accent-soft`, texte `--accent`, barre gauche 3 px, `aria-current="page"`. Survol distinct de l'actif.
- Pastilles de comptage (ton avertissement/danger) alimentées par le serveur, **mises en cache brièvement** : dossiers à valider, alertes flotte, séances du jour.
- Au plus 7-8 éléments par groupe ; pas plus de 2 niveaux visibles.

### 7.2 Navigation par rôle (à confirmer par WP0 d'après `route:list`)

**Admin**

| Groupe | Éléments |
|---|---|
| Pilotage | Tableau de bord · Planning · Rapports |
| Élèves et formation | Élèves · Dossiers à valider · Formation (compétences, examens) · Prospects (CRM) |
| Équipe et ressources | Moniteurs · Flotte |
| Finance et boutique | Finance (factures, paiements, forfaits, journal) · Recyclage · Boutique (produits, stock, commandes, fournisseurs, bons de commande) |
| Administration | Utilisateurs · Paramètres · Journal d'audit |

**Superadmin** : Plateforme (Tableau de bord · Établissements) · Supervision (Journal d'audit · Notifications) · Compte.
**Moniteur** : Aujourd'hui (tableau de bord) · Agenda · Mes élèves · Flotte (lecture) · Disponibilités · Résultats de quiz.
**Élève** : Accueil · Planning · Progression · Paiements · Mon dossier · Quiz.

### 7.3 Dock mobile (< 1024 px)

Verre flottant en bas (hauteur 68 px, marge `env(safe-area-inset-bottom)`), **4 destinations + action centrale** (accent). Admin : Accueil · Planning · **+ Nouveau** · Élèves · Finance ; le reste dans une feuille « Plus » (tiroir plein écran = navigation complète). Moniteur : Aujourd'hui · Agenda · Élèves · Plus. Élève : Accueil · Planning · Progression · Plus. Le contenu a un `padding-bottom` ≥ hauteur du dock + 32 px.

### 7.4 Topbar

Verre sticky (opacité ≥ 78 %). Gauche : bouton menu (mobile) + fil d'Ariane (desktop) ou titre court (mobile). Centre : **déclencheur de recherche** en pilule avec `Ctrl K` (icône seule sur mobile). Droite : **« Nouveau »** (menu des créations autorisées au rôle : élève, séance, facture, paiement, prospect…), **notifications** (point rouge + popover des 5 dernières + « Tout voir » → `/notifications`), **apparence** (popover opaque), **menu utilisateur** (Profil, Apparence, Raccourcis clavier, Déconnexion). Le contenu qui défile sous la barre ne doit jamais rester lisible (P5).

### 7.5 Palette de commandes

`Ctrl/Cmd+K` ou clic sur le déclencheur ; dialog en verre téléporté ; champ focalisé à l'ouverture. Sections : **Actions rapides**, **Pages**, **Récents** (5, `localStorage`), puis (phase 2) **résultats serveur** groupés (Élèves, Factures, Véhicules, Prospects) via `GET /search?q=` (debounce 200 ms, filtré par Policy et tenant). `↑ ↓` naviguer, `Entrée` valider, `Échap` fermer, indices de raccourcis. **Aucune action destructive** accessible par `Entrée` seule. État vide : proposer une étape suivante. Les entrées sont filtrées par rôle (`@can` / rôle) avant rendu.

Raccourcis optionnels : `G` puis `D/P/E/F` (aller à), `N` puis `E/S/P` (nouveau), `/` (recherche), `?` (aide) ; désactivés dans les champs de saisie ; documentés dans le menu utilisateur.

---

## 8. Archétypes de pages

Chaque page de l'application appartient à **un** archétype. Un écran qui ne rentre dans aucun archétype est un signal d'alerte : consigne-le dans `decisions.md`.

**A. Tableau de bord** — titre personnalisé + date + phrase de synthèse ; **« À traiter »** en premier (3 à 6 lignes actionnables avec bouton direct : valider dossiers, relancer impayés, planifier un contrôle technique…) ; 4 KPI (métrique principale en haut à gauche) ; 1-2 blocs analytiques ; aperçu du planning du jour ; fil d'activité récente. Chaque bloc a son état vide explicatif.

**B. Liste (index de ressources)** — `page-header` (titre, compteur, action primaire « Nouveau… », secondaires Exporter/Importer) → `filter-bar` (recherche, chips, vues) → `table` (ou cartes sur mobile) → `pagination`. État vide initial : illustration + explication + action. État « aucun résultat » : rappelle les filtres actifs et propose de les relâcher. Clic sur une ligne = fiche détail (ou tiroir d'aperçu rapide pour les listes à forte volumétrie).

**C. Fiche détail** — en-tête de fiche (avatar/icône, nom, statut, métadonnées clés, actions rapides, menu « … ») ; **onglets** (liens) ; colonne principale + **colonne latérale** « Résumé » (≥ 1280 px) ; historique en `timeline`. Les actions dangereuses sont dans le menu « … », avec confirmation.

**D. Formulaire** — une colonne, sections `form.section`, hints, `actions` sticky, résumé d'erreurs, confirmation de sortie si modifications non enregistrées, enregistrement avec retour explicite (toast + redirection vers la fiche).

**E. Assistant** — `form.stepper` ; une décision par étape ; récapitulatif final avant validation ; reprise possible.

**F. Planning** — barre d'outils (Aujourd'hui, précédent/suivant, sélecteur de date, vues Jour/Semaine/Mois, bascule **Moniteurs/Véhicules**, filtres, légende, bouton « Nouvelle séance », export CSV). Grille : **heures en lignes, ressources en colonnes** (jour) ou jours en colonnes (semaine, une ressource choisie) ; créneau de 30 min minimum ; bloc de séance : heure + type (texte) + élève ; **ligne « maintenant »** ; cellules vides cliquables (création pré-remplie) ; clic sur un bloc = **popover/tiroir de détails** (présence, modifier, dupliquer, supprimer) ; conflit : icône + « Conflit véhicule/moniteur » + contour pointillé ; **alternative au glisser-déposer** : menu « Déplacer… » (dialog date/heure/ressource) ; navigation clavier (flèches entre créneaux, `Entrée` ouvre). Surface **pleine**. Mobile : liste verticale du jour par ressource (`session-card row`), sélecteur de ressource en haut.

**G. Tableau Kanban** — colonnes par statut avec compteur, cartes compactes, ajout rapide en tête de colonne, **menu « Déplacer vers… »** sur chaque carte, glisser-déposer en plus, alternative tableau (bascule Liste/Tableau). Mobile : une colonne à la fois (onglets de statut).

**H. Paramètres** — `settings-shell` : navigation d'ancres à gauche (menu déroulant sur mobile), sections `form.section`, enregistrement par section avec confirmation visible.

**I. Document (facture, feuille de route, rapport)** — mise en page « papier » sur surface pleine, en-tête établissement, tableau de lignes, totaux en FCFA, historique des paiements, actions (Télécharger PDF, Imprimer, Enregistrer un paiement) ; `print.css` fidèle.

**J. Authentification et pages publiques** — `layouts.guest` : carte en verre centrée (largeur ≤ 440 px), un objectif par écran, champs 16 px, messages d'erreur explicites, aide cohérente en pied (WCAG 3.2.6), **aucun test cognitif** (WCAG 3.3.8) : coller un mot de passe et un code OTP doit fonctionner ; ne pas redemander une information déjà saisie dans le même parcours (3.3.7).

**K. États système** — vide, chargement (squelettes), erreur serveur, accès refusé, session expirée (419 → « Votre session a expiré, reconnectez-vous » avec action), trop de requêtes (429 → message + décompte si `Retry-After`), maintenance (503). Ton humain, action de sortie claire, jamais de trace technique.

**L. « Ma journée » (mobile-first)** — pour moniteur et élève : une colonne, cartes larges, **une action principale par carte** (Marquer présent / Voir la feuille de route / Voir mon dossier), compte à rebours de la prochaine séance, dock en bas.

---

## 9. Spécifications par écran

> WP0 confirme chaque écran contre `php artisan route:list` et le code. Si un écran listé ici n'existe pas côté backend, **ne l'invente pas** : consigne-le dans `backend-needs.md`. Si un écran existe côté backend et n'est pas listé ici, applique l'archétype le plus proche.

### 9.1 Tableaux de bord par rôle (archétypes A et L)

| Rôle | Contenu, dans l'ordre |
|---|---|
| **Admin** | Salutation + date + phrase de synthèse (« 23 séances aujourd'hui, 3 dossiers attendent votre validation ») · **À traiter** (dossiers à valider avec ancienneté du plus ancien ; factures en retard avec total ; alertes flotte ≤ 30 j ; séances sans présence marquée ; nouvelles inscriptions publiques) · 4 KPI : élèves actifs, séances du jour (avancement), encaissements du mois, impayés · **Parcours des élèves** : entonnoir des 5 phases avec effectifs · Encaissements sur 30 jours (courbe) · Planning du jour (aperçu par moniteur) · Fil d'activité récente. |
| **Superadmin** | KPI : établissements actifs / en attente / suspendus · **Inscriptions à traiter** (liste avec Activer / Refuser) · Activité plateforme récente (audit) · accès rapide aux établissements. |
| **Moniteur** (L) | **Prochaine séance** (compte à rebours, élève, lieu, véhicule, bouton « Feuille de route ») · **Séances du jour** en cartes avec `presence-toggle` large · Élèves à évaluer · Alertes du véhicule assigné · Raccourcis Agenda / Disponibilités. Doit voir **toute sa journée dès l'ouverture**. |
| **Élève** (L) | **Mon parcours** : `stage-stepper` des 5 phases + description de l'étape courante + « ce qu'il me reste à faire » · **Prochaine séance** (date, moniteur, lieu, véhicule) · **Paiements** : payé / total avec barre et solde restant (lecture seule) · **Mon dossier** : checklist des pièces et statuts · Quiz : dernier score + bouton « S'entraîner » · Notifications. |

### 9.2 Plateforme et comptes

| Écran | Arch. | Points clés |
|---|---|---|
| Établissements (`/superadmin/structures`) | B | Onglets par statut avec compteurs : En attente · Actifs · Suspendus · Désactivés. Actions Activer / Suspendre / Désactiver / Supprimer ; suppression par **saisie du nom**. Tiroir de détail (infos d'inscription). |
| Inscription d'un établissement (`/register`) | J + D | Sections Établissement puis Administrateur ; écran de succès « En attente de validation » expliquant la suite. |
| Utilisateurs | B + D | Rôle en badge, statut actif en interrupteur **avec confirmation**, réinitialisation du mot de passe (dialog + retour clair), garde-fou contre l'auto-désactivation. |
| Compte désactivé | K | Message dédié (middleware `EnsureUserIsActive`), contact de l'établissement, déconnexion. |

### 9.3 Élèves, dossiers, documents

| Écran | Arch. | Points clés |
|---|---|---|
| Liste des élèves | B | Vues : Tous · Admission · Paiement · Code · Conduite · Permis · Anciens élèves (compteurs). Filtres : moniteur, solde (impayé), étape, date d'inscription. Colonnes : élève (avatar, nom, téléphone), étape (`status`), progression (n/15), solde, moniteur, prochaine séance. |
| Fiche élève | C | En-tête + `stage-stepper compact` + actions rapides (Changer d'étape · Nouvelle facture · Planifier une séance · Télécharger le dossier ZIP). Onglets : Résumé · Dossier · Planning · Finance · Formation · Historique (`timeline` : changements d'étape + audit). |
| Changement d'étape | dialog | Liste **uniquement** les transitions autorisées par `LifecycleService` ; retours en arrière signalés comme tels ; note facultative ; texte explicite : « Le changement d'étape est un repère de suivi, il ne crée ni facture ni séance. » |
| Création / modification | D | Sections : identité, contact, établissement/moniteur, notes. |
| Lien d'inscription publique (Paramètres) | H | Lien à copier, jours restants avant expiration, **Régénérer** et **Révoquer** (confirmation), état « aucun lien actif ». |
| Inscription publique `/register/student` | J + E | Mobile-first, 2-3 étapes courtes ; écrans dédiés **lien invalide / expiré / révoqué** ; limiteur de débit : message 429 clair. |
| Vérification OTP `/eleve/verification-otp` | J | `form.otp`, expiration visible, tentatives restantes, renvoi avec décompte, « Modifier mon e-mail » si le backend le permet. |
| **Dossiers à valider** `/dossiers` | B en `split-view` | Liste à gauche (élève, pièce, ancienneté, **plus ancien d'abord**) ; visionneuse à droite ; **Approuver** / **Rejeter** (motif **obligatoire**, motifs rapides + texte libre) ; passage automatique au suivant ; raccourcis `A` / `R` / `J` / `K` ; compteur restant ; « Télécharger tout le dossier (ZIP) ». |
| Mon dossier (élève) | L | Checklist des types requis avec statut ; **pièces rejetées en premier** avec le motif et « Remplacer » ; dépôt (`file-drop`, caméra) ; « Soumettre mon dossier » actif seulement quand tout est déposé. |
| Documents requis (Paramètres) | H | Liste réordonnable + création/édition/suppression. |
| GED véhicule / élève | composant | `doc-list` réutilisable sur fiches élève et véhicule. |

### 9.4 Moniteurs, planning, formation

| Écran | Arch. | Points clés |
|---|---|---|
| Moniteurs (liste, fiche) | B, C | Charge de la semaine, disponibilités (`availability-editor`), prochaines séances, élèves suivis. |
| **Planning admin** | F | Voir archétype F. Création/édition en **tiroir** (élève en combobox, moniteur, véhicule, type, date, début/fin, lieu, notes). **Conflit** moniteur ou véhicule : message du serveur affiché dans le tiroir avec lien vers la séance en conflit. Duplication (dialog de dates cibles), suppression (confirmation), export CSV. |
| Présence | F/L | Liste du jour, `presence-toggle` large, enregistrement par séance, état « marquée / à marquer ». |
| Agenda moniteur `/moniteur/agenda` | L + F | Jour (mobile) / semaine (desktop) ; **feuille de route élève** (archétype I, imprimable). |
| Planning élève `/eleve/planning` | L | Prochaines séances en cartes, passées en liste, mini-calendrier mensuel avec indicateurs. |
| Compétences (admin) | B + D | Liste groupée, création/édition, réordonnable. |
| Évaluation d'un élève | D | `skill-matrix` : une ligne par compétence, niveau en segmenté, commentaire, enregistrement groupé, historique. Accessible admin et moniteur. |
| Progression (élève) | L | Barres par compétence, niveau global, étape courante, prochains objectifs. |
| Examens | B + D | Saisie du résultat (réussi/échec, date, observations), historique par élève ; après un échec à l'examen pratique, proposer « Revenir à Évaluation continue » (retour autorisé). |
| Quiz (élève) | L | Accueil (dernier score, « S'entraîner »), **une question par écran** avec progression et grandes options, résultat avec corrigé détaillé, historique. Résultats par élève côté moniteur/admin (tableau + détail des tentatives). |

### 9.5 Flotte, finance, boutique

| Écran | Arch. | Points clés |
|---|---|---|
| Véhicules | B (cartes) | `vehicle-card` + `expiry-chip` ; **bandeau d'alertes** (≤ 30 j) en tête ; création/suppression réservées à l'admin ; moniteur en lecture + entretien + carburant. |
| Fiche véhicule | C | Onglets Aperçu · Entretiens · Carburant · Documents. Formulaires en tiroir (montant FCFA, km, date) avec la mention « Cette dépense est enregistrée automatiquement au journal comptable ». |
| Finance — synthèse | A | Encaissements du mois, impayés, **âge des créances** (0-30 / 31-60 / 60+ jours), derniers paiements, courbe. |
| Forfaits | B + D | Cartes (nom, prix en FCFA), création/édition. |
| Factures | B | Vues : Toutes · Impayées · Partielles · Payées · En retard. Colonnes : n°, élève (**ou « Hors élève »** : `student_id` est nullable), émission, échéance, total, payé, solde avec barre, statut. |
| Facture | I | Lignes, totaux, historique des paiements (**annulés barrés** avec date et motif), actions : **Enregistrer un paiement** (tiroir : `payment-method-picker`, montant par défaut = solde, **plafonné au solde** avec message clair si dépassement, date, référence), Annuler un paiement (confirmation), Télécharger le PDF. |
| Journal comptable | B | Débit / crédit / solde cumulé ; filtres période, type, source (paiement, dépense véhicule, recyclage, manuel) ; saisie manuelle en tiroir ; export. |
| Recyclage | B + D | Liste (motif, montant, date, lien vers l'écriture) et formulaire. |
| Boutique : produits | B | Vignette, catégorie, prix, `stock-bar` avec seuil d'alerte. |
| Boutique : stock, fournisseurs | B, B + D | Mouvements filtrables ; fournisseurs CRUD. |
| Boutique : commandes | B + C | `order-flow` ; erreur **« stock insuffisant »** affichée sur la ligne concernée avec le stock disponible ; annulation avec confirmation. |
| Boutique : bons de commande / réception | E | Quantités attendues vs reçues par ligne, récapitulatif avant validation. |
| Boutique : rapports | A | Top produits (graphique + tableau), exports CSV et PDF. |

### 9.6 CRM, rapports, réglages, supervision

| Écran | Arch. | Points clés |
|---|---|---|
| Prospects | G + B | Kanban par `LeadStatus` (compteurs), bascule Liste/Tableau, ajout rapide, tiroir de fiche. **« Convertir en élève »** : confirmation précisant que l'élève est créé à l'étape Prospect. |
| Rapports | A | **Filtre de période global** qui met à jour tous les blocs : tendance des encaissements, entonnoir des 5 phases, taux de remplissage des séances, taux de réussite aux examens ; boutons d'export CSV nommés comme leur fichier. |
| Paramètres | H | Établissement · Inscription des élèves · Documents requis · autres paramètres découverts par WP0. SMS : si le pilote est `log`, afficher « Envoi de SMS non configuré » (jamais « SMS activés »). |
| Journal d'audit | B | Filtres utilisateur / action / entité / période ; **détail en tiroir avec comparaison avant/après** (éléments modifiés marqués par un texte « modifié », pas seulement une couleur) ; lecture seule. |
| Notifications | B + popover | Groupées par jour, Toutes / Non lues, « Tout marquer comme lu », icône par type (paiement reçu, inscription publique, alerte flotte, nouvel établissement), lien direct vers l'objet. |
| Profil | D | Informations, mot de passe, **Apparence complète**, suppression de compte avec saisie de confirmation si la fonctionnalité existe. |

### 9.7 Public, erreurs, documents

| Écran | Points clés |
|---|---|
| Landing | Hero (promesse + aperçu produit stylisé en verre, SVG/CSS, pas de capture lourde) · fonctionnalités par rôle (Direction, Moniteur, Élève) · **« Le parcours en 5 phases / 15 étapes »** · modules (planning, finance, flotte, boutique, CRM, documents) · tarifs · FAQ (`<details>` accessibles) · CTA « Créer mon établissement » · pied de page. **Reprendre le contenu métier** (textes, offres, tarifs, FAQ) de l'ancienne landing en extrayant le **texte**, pas le visuel ; les valeurs vont dans `config/landing.php`. **Ne jamais inventer** chiffres, témoignages, logos de clients, prix ou intégrations ; ce qui manque est un champ de configuration vide et masqué. |
| Connexion, mot de passe oublié, réinitialisation, confirmation | Archétype J. |
| Erreurs 403 · 404 · 419 · 422 · 429 · 500 · 503 | Archétype K ; cartes en verre, illustration SVG légère, action de sortie (Retour à l'accueil, Se reconnecter, Réessayer). |
| E-mails (OTP, notifications) | Mise en page **tableau**, styles en ligne, 600 px, texte alternatif, bouton « à toute épreuve » (lien texte de secours), accent de marque indigo, pas de verre. |
| PDF (facture, feuille de route, rapports boutique) | `layouts.pdf` (contraintes dompdf, §5.5) ; **vérifier le PDF généré** réellement. |

---

## 10. Accessibilité (WCAG 2.2 AA, non négociable)

- **Contraste** : texte ≥ 4,5:1, composants d'interface et graphiques ≥ 3:1, dans **tous** les thèmes et pour les 8 accents. `check-contrast.py` doit passer à chaque modification de tokens.
- **Clavier** : tout est utilisable sans souris ; ordre logique ; focus **toujours visible** (anneau double 2 px + 2 px) et **jamais masqué** par une barre sticky (2.4.11) : `scroll-padding-top: calc(var(--topbar-h) + 24px)` et `scroll-padding-bottom` pour dock et barre d'actions.
- **Taille des cibles** : ≥ 24 × 24 px (2.5.8), ≥ 44 px pour les actions principales et tout le mobile.
- **Pas de glisser obligatoire** (2.5.7) : planning, kanban, listes réordonnables, éditeur de disponibilités ont une alternative à simple pointeur.
- **Saisie** : pas de ressaisie inutile (3.3.7), authentification sans test cognitif, collage autorisé (3.3.8), aide au même endroit sur chaque page (3.2.6).
- **Sémantique** : repères (`header`, `nav`, `main`, `aside`), un seul `h1`, hiérarchie des titres respectée, `lang="fr"`, lien d'évitement, `aria-current`, tableaux avec légende et en-têtes, dialogs conformes, toasts en régions `aria-live`, erreurs liées aux champs, noms accessibles sur toute icône cliquable.
- **Couleur** : jamais seule porteuse d'information (statut, type de séance, diff d'audit, état juste/faux d'un quiz).
- **Préférences système** : `prefers-reduced-motion`, `prefers-reduced-transparency`, `prefers-contrast: more` (surfaces pleines, bordures `--line-control`), `forced-colors: active` (bordures `CanvasText`, indicateurs de focus par contour, pas d'information portée par l'arrière-plan seul).
- **Reflow** : utilisable à 320 px de large et 400 % de zoom sans défilement horizontal de page (seuls tableaux et planning défilent dans leur conteneur, et ont une alternative en liste).
- **Tests** : `axe-core` via Playwright sur chaque page-type × 3 thèmes (Aurore, Crépuscule, Graphite) ; passage clavier complet sur les parcours clés ; vérification au lecteur d'écran (NVDA ou VoiceOver, TalkBack) sur 5 parcours : connexion, inscription élève + OTP, création de séance avec conflit, enregistrement d'un paiement, revue d'un dossier ; émulation DevTools « Rendering » des préférences ci-dessus.

---

## 11. Performance et compatibilité

### 11.1 Budgets (bloquants en WP12)

| Mesure | Budget |
|---|---|
| JavaScript initial (Alpine + plugins + code applicatif) | ≤ 70 Ko gzip ; Chart.js **hors** du lot initial |
| CSS | ≤ 60 Ko gzip |
| Police | 1 fichier variable `latin` ≤ 60 Ko, préchargé ; `latin-ext` chargé à la demande (unicode-range) |
| LCP (Lighthouse mobile, 4G lente, CPU ×4) | ≤ 2,5 s ; INP ≤ 200 ms ; CLS ≤ 0,1 |
| Surfaces `backdrop-filter` simultanées | ≤ 3 ; défilement ≥ 50 i/s avec ralentissement CPU ×4, sinon basculer en mode léger |

### 11.2 Mode léger (`data-perf="lite"`)

Supprime flou, dégradés du canevas (fond uni `--canvas-base`) et ombres lourdes ; surfaces de verre remplacées par `--surface-glass-fallback` (≥ 94 %). Activation : choix de l'utilisateur, ou **automatique** si `navigator.connection.saveData`, `deviceMemory ≤ 2` ou `hardwareConcurrency ≤ 2` (Chromium seulement). Safari ne supporte pas `prefers-reduced-transparency` : l'interrupteur « Mode léger » est donc **le** moyen de couper la transparence sur iOS.

### 11.3 Techniques

Images : `loading="lazy"`, `width`/`height` explicites. Polices : `size-adjust` sur la pile de repli pour éviter les sauts de mise en page. Aucun `box-shadow` animé. `content-visibility: auto` sur les longues listes sous la ligne de flottaison. Tableaux et listes rendus **côté serveur** : Alpine n'est pas utilisé pour la réactivité des données de tableau. Pas d'Alpine sur des centaines de nœuds : un état par composant, pas par ligne.

### 11.4 Compatibilité

Chrome 111+, Safari 16.4+ (préfixe `-webkit-backdrop-filter` requis jusqu'à 16), Firefox 128+, Samsung Internet récent. Opera Mini : non supporté. Bannière de navigateur trop ancien (§5.2).

---

## 12. Plan de travail : lots, dépendances, périmètres

### 12.1 Graphe

```
WP0 Inventaire ──► WP1 Fondations ──► WP2 Composants (vague 1) ──► [Jalon M1]
                                  └──► WP3 Shell & navigation ───► [Jalon M2]
                                                    M1 + M2 ──► WP4 · WP5 · WP6 · WP7 · WP8 · WP9 · WP10 · WP11 (en parallèle)
                                                                     └──► WP12 QA & nettoyage (dernier)
```

- **Jalon M1** : primitives `ui.*`, `form.*`, `dialog/drawer`, `table`, `filter-bar`, `page-header`, `stage-stepper`, `expiry-chip`, `status` mergés et visibles dans `/design-system`.
- **Jalon M2** : `layouts.app` + sidebar + topbar + dock + toasts + `layouts.guest` mergés ; le dashboard admin s'affiche dans le nouveau shell.
- Les lots 4 à 11 peuvent démarrer dès M1 + M2. Un composant manquant : `component-requests.md`, puis continuer avec un composant local provisoire dans le dossier du lot (`components/domain/<lot>/`), à fusionner par WP2 si générique.

### 12.2 Propriété des fichiers (un fichier = un propriétaire)

L'orchestrateur complète `inventory.md` avec une colonne **Lot propriétaire** pour **chaque** fichier de vue. Répartition par défaut :

| Lot | Périmètre exclusif |
|---|---|
| WP0 | `docs/frontend/**`, `database/seeders/FrontendDemoSeeder.php` (local uniquement, idempotent, **jamais** sur la base principale sans accord) |
| WP1 | `package.json`, `vite.config.js`, `resources/css/**`, `resources/js/app.js`, `resources/js/alpine/prefs.js`, `bootstrap/app.php` (cookie `ui_prefs`), migration + endpoint d'apparence, `frontend-qa/check-contrast.py` |
| WP2 | `resources/views/components/{ui,form,data}/**`, `components/domain/` génériques (`stage-stepper`, `expiry-chip`), `app/View/Components/Ui/**`, `config/ui-status.php`, `lang/fr/ui.php`, `resources/views/design-system/**` |
| WP3 | `resources/views/layouts/**`, `components/layout/**`, `resources/views/errors/**`, `resources/views/profile/**`, notifications (popover + page), `resources/js/alpine/{palette,toasts}.js`, endpoint `GET /search` (phase 2) |
| WP4 | `welcome.blade.php`, `components/landing/**`, `auth/**`, `register/**`, vérification OTP, `emails/**`, `config/landing.php` |
| WP5 | `dashboard.blade.php`, `admin/**`, `moniteur/dashboard*`, `eleve/dashboard*`, `reports/**` |
| WP6 | `students/**`, `documents/**`, `eleve/dossier*`, pages de réglages « lien d'inscription » et « documents requis », composants `doc-*` |
| WP7 | `scheduling/**`, `instructors/**`, `moniteur/*` (agenda, feuille de route), `eleve/planning*`, composants `planning-grid`, `session-card`, `presence-toggle`, `availability-editor` |
| WP8 | `training/**`, quiz (`/quiz/*`), `eleve/progression*`, composants `skill-*`, `quiz-*` |
| WP9 | `finance/**`, `recyclage/**`, `eleve/paiements*`, PDF de factures, composants `invoice-summary`, `payment-method-picker` |
| WP10 | `fleet/**`, `store/**`, PDF de rapports boutique, composants `vehicle-card`, `stock-bar`, `order-flow` |
| WP11 | `crm/**`, `users/**`, `tenancy/**`, `settings/**` (reste), `audit/**`, composant `kanban` |
| WP12 | `tests/Feature/DesignSystem/**`, `frontend-qa/**` (Playwright, axe, Lighthouse), suppression de l'ancien frontend, documentation, `CHANGELOG.md` |

### 12.3 Bloc de lancement commun (à coller en tête du prompt de **chaque** agent de lot)

```text
Tu es l'agent du lot {WPn} du chantier « Refonte frontend Auto-GestBoard ».
1. Lis INTÉGRALEMENT le prompt maître (docs/frontend/PROMPT-MAITRE.md) puis ouvre docs/frontend/reference/auto-gestboard-prototype-reference.html dans Chromium.
2. Lis docs/frontend/inventory.md : ne traite que les fichiers dont la colonne « Lot propriétaire » vaut {WPn}.
3. Contrat backend gelé (§1.3) : n'écris aucune ligne de PHP métier ; besoin additif => docs/frontend/backend-needs.md.
4. Utilise exclusivement les composants de WP2 et les tokens sémantiques. Composant manquant => docs/frontend/component-requests.md.
5. Pour CHAQUE page : états (chargement, vide, aucun résultat, erreur, accès refusé), responsive 360 / 768 / 1280, 6 thèmes, clavier, Définition de terminé (§13).
6. Avant chaque commit : npm run build && php artisan test --compact. Jamais de migrate:fresh sur la base principale.
7. Rends compte : pages livrées, captures (docs/frontend/reference/{WPn}/), décisions prises, écarts, besoins backend.
```

### 12.4 Lots

**WP0 — Orchestration et inventaire** *(orchestrateur)*
Livrables : branche `feat/frontend-redesign` ; `inventory.md` généré depuis `php artisan route:list --json` (route, méthode, middleware/rôle, vue retournée, variables passées, lot propriétaire) + liste de **tous** les composants Blade existants + liste des **enums métier et leurs cas** (pour `config/ui-status.php`) ; `backend-needs.md` ; `FrontendDemoSeeder` (un établissement gabonais réaliste, 4 rôles, ~40 élèves répartis sur les 5 phases, séances avec un conflit, factures impayées, véhicule à CT proche, dossiers à valider, prospects) ; copie du prototype dans `reference/` ; état de base des tests.
Critère : 100 % des routes de `route:list` sont dans l'inventaire avec un propriétaire.

**WP1 — Fondations**
Livrables : migration vers Tailwind v4 (§5.2-5.3) ; `tokens.css` (Annexe A) ; `materials.css` (canevas, `material-*`, replis §4.1) ; `print.css` ; police auto-hébergée ; Alpine + plugins ; système de préférences complet (§5.4) avec **test** (le HTML serveur contient `data-theme` quand le cookie existe ; valeurs invalides ignorées) ; script `check-contrast.py` branché sur `npm run qa:contrast` ; `/design-system` minimal (thèmes, tokens, matériaux).
Critère : `npm run build` ≤ budgets §11.1 ; changement de thème sans flash au rechargement ; contraste 0 échec.

**WP2 — Composants** (deux sous-agents : 2a `ui` + `form` ; 2b `data` + génériques `domain`)
Livrables : catalogue §6 (hors composants propres à un domaine) + `config/ui-status.php` + guide vivant complet. Tests Pest de rendu (chaque composant se rend, attributs ARIA présents, aucune couleur en dur).
Critère : chaque composant montré dans tous ses états × 6 thèmes × 2 densités ; navigation clavier validée sur dropdown, dialog, drawer, tabs, combobox.

**WP3 — Shell et navigation**
Livrables : layouts (§5.5), sidebar/topbar/dock (§7), palette de commandes (phase 1 statique ; phase 2 `GET /search` + tests de Policy et de tenant), notifications (popover + page), apparence (popover + section profil), toasts, pages d'erreur, profil.
Critère : navigation complète au clavier ; repli de sidebar mémorisé ; dock correct avec clavier virtuel ouvert ; aucun contenu lisible sous la topbar.

**WP4 à WP11 — Domaines** : appliquer §8 et §9 aux fichiers du lot. Chaque lot ajoute ses composants de domaine (§12.2) sous `components/domain/<lot>/`.
Critère commun : Définition de terminé (§13) sur chaque page ; parcours E2E du lot rejoué dans 3 thèmes.

**WP12 — QA et nettoyage**
Livrables : matrice visuelle automatisée (Playwright) rôle × page × 6 thèmes × 3 largeurs (360, 768, 1280) → captures dans `docs/frontend/reference/qa/` ; `axe-core` sur chaque page-type × 3 thèmes ; Lighthouse mobile sur 6 pages clés (budgets §11.1) ; vérification **réelle** des PDF ; garde-fou Pest mis à jour (§13.3) ; **suppression** de l'ancien frontend (anciens composants, `tailwind.config.js`, vues orphelines, classes mortes, ancienne landing) ; mise à jour de `README.md`, `docs/development.md`, `docs/deployment.md` (`icons:cache`, build), `CLAUDE.md` / `AGENTS.md` (règles frontend v2, Tailwind v4), `CHANGELOG.md`.
Critère : tous les budgets tenus, 0 violation axe de niveau sérieux/critique, 0 écran en échec de contraste, `php artisan test --compact` vert, aucune référence à l'ancien design system.

---

## 13. Définition de terminé et garde-fous

### 13.1 Par page (toutes obligatoires)

1. Archétype identifié (§8) et respecté ; `page-header` standard.
2. **Cinq états** rendus : chargement (squelette réaliste), vide initial, aucun résultat, erreur, accès refusé.
3. Rendu correct dans les **6 thèmes**, 2 densités, mode léger ; aucun texte < 4,5:1 ; capture archivée.
4. Responsive 360 / 768 / 1280 sans défilement horizontal de page ; tableaux → cartes sous 768 px.
5. Clavier complet, focus visible et non masqué ; `axe-core` sans violation sérieuse/critique.
6. Aucune couleur en dur, aucun glyphe fléché, aucune icône emoji, aucun composant hérité.
7. Textes en français correct ; montants, dates, téléphones via les composants dédiés.
8. Actions destructives confirmées ; retours utilisateur par toast ; erreurs de validation liées aux champs.
9. Toute règle métier reste **serveur** ; le front ne la duplique que comme aide (jamais comme garde).
10. Test Pest de la page existant ou étendu (au moins : statut 200 pour chaque rôle autorisé, 403 pour les autres, éléments clés présents).

### 13.2 Commandes de contrôle

```bash
npm run build
python3 frontend-qa/check-contrast.py
php artisan test --compact
php artisan test --compact --filter=DomainBoundariesTest
php artisan test --compact --filter=DesignSystem
vendor/bin/pint --dirty --format agent
php artisan route:list --json > /tmp/routes.json   # vérifier qu'aucune route n'a changé
```

### 13.3 Garde-fou Pest `tests/Feature/DesignSystem` (WP2 l'initialise, WP12 le finalise)

Il parcourt `resources/views/**` (hors `layouts/pdf`, `emails/**`) et échoue sur : (a) tout usage d'un composant hérité (liste de l'inventaire) ; (b) classes de couleur interdites (`bg-white`, `bg-black`, `text-gray-*`, `text-slate-*`, `bg-[#`, `text-[#`, `border-[#`) ; (c) `#hex` ou `rgb(` dans `class=` / `style=` ; (d) glyphes `← → ↑ ↓ ➜ ➔ ›` et emojis servant d'icône ; (e) `<img` sans `alt` ; `target="_blank"` sans `rel="noopener"` ; (f) `onclick=` et autres gestionnaires en ligne ; (g) `<svg` en ligne hors composants `ui.icon`/illustrations/graphiques. Un second test charge `tokens.css`, vérifie que les **6 thèmes déclarent exactement le même jeu de variables**, et exécute le validateur de contraste.

---

## 14. Anti-patterns interdits

1. Verre sur les tableaux, formulaires, graphiques, grille de planning, lignes de liste.
2. Verre dans du verre, menu/popover en verre sous une barre en verre.
3. Barres sticky à moins de 78 % d'opacité ; flou ≥ 20 px ; fond animé ; `background-attachment: fixed`.
4. Texte gris clair décoratif sous 4,5:1 ; placeholder utilisé comme libellé ; couleur seule pour un statut.
5. Hover comme seul moyen d'accéder à une action ou une information.
6. Glisser-déposer sans alternative ; modale sans titre ni piège de focus ; action destructive sans confirmation.
7. Tableau large qui force un défilement horizontal de la page sur mobile.
8. Dashboard de chiffres sans action ; graphique vide ; camembert pour comparer ; plus de 5 parts dans un anneau.
9. Menu de 15 éléments sans groupes ; navigation à 3 niveaux pour un écran quotidien.
10. Message d'erreur technique affiché à l'utilisateur ; « Une erreur est survenue » sans issue.
11. Témoignages, logos clients, chiffres d'usage, prix ou intégrations **inventés** ; promesse d'envoi de SMS ou de paiement mobile automatique.
12. Bibliothèque de calendrier ou de composants lourde pour un besoin simple ; polyfills ; Alpine sur chaque ligne d'un grand tableau.
13. Valeur de cookie ou de requête écrite telle quelle dans le HTML.
14. Modification de routes, contrôleurs, Policies, Services ou migrations existantes hors §1.3.

---

## 15. Risques et points à valider

| Sujet | Risque | Décision par défaut |
|---|---|---|
| Plancher navigateurs de Tailwind v4 | Chrome < 111, Safari < 16.4, Firefox < 128 mal rendus | Accepté (trafic local dominé par Chrome récent) ; bannière d'avertissement ; pas de polyfill |
| Paquet d'icônes | Compatibilité Laravel 12 de `mallardduck/blade-lucide-icons` non confirmée par sa documentation | Tester en WP1 ; repli sur `blade-ui-kit/blade-icons` + SVG Lucide |
| `prefers-reduced-transparency` | Absent de Safari, derrière un flag dans Firefox | Interrupteur « Mode léger » in-app (§11.2) |
| `deviceMemory`, `connection.saveData` | Chromium uniquement | Amélioration progressive, jamais une dépendance |
| dompdf | Pas de variables CSS, pas de flex/grid | `layouts.pdf` en CSS 2.1 + tables, vérification réelle |
| Recherche globale | Fuite inter-tenant ou inter-rôle si mal filtrée | Policy + tenant + tests ; phase 2 seulement |
| Vues enregistrées | Persistance par utilisateur nécessiterait du backend | v1 : onglets de vues prédéfinis + état en URL ; v2 éventuelle : `localStorage` puis base (hors périmètre) |
| Landing | Contenu commercial (prix, offres) à reprendre fidèlement | Extraire le texte de l'ancienne landing, le mettre en configuration, ne rien inventer |
| SMS | Non branché | Interface honnête (« non configuré ») |

---

# ANNEXES

## Annexe A — `resources/css/tokens.css` (source de vérité des tokens)

Contient les 6 thèmes, les statuts, les 8 accents, les séries de graphiques, les dérivés et le mode léger. Toute modification doit être reportée dans `check-contrast.py` (Annexe D) et validée **avant** commit. Conventions de nommage volontaires, pour éviter toute collision avec les espaces de noms Tailwind v4 : rayons `--r-*` (pas `--radius-*`), élévations `--elev-*` (pas `--shadow-*`), courbe `--ease-ui` (pas `--ease-out`), polices `--font-ui` / `--font-code` (pas `--font-sans`).

```css
/* =========================================================================
   AUTO-GESTBOARD — TOKENS DE DESIGN (source de vérité)
   Tous les ratios WCAG ci-dessous ont été calculés (composition alpha incluse,
   pire cas = bulle de couleur la plus saturée du canevas sous le panneau).
   ========================================================================= */

/* ---------- 1. Échelles invariantes (indépendantes du thème) ---------- */
:root {
  --font-ui: "Inter Variable", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  --font-code: ui-monospace, "SF Mono", "Cascadia Mono", Menlo, Consolas, monospace;

  --r-xs: 6px;  --r-sm: 10px; --r-md: 14px;
  --r-lg: 20px; --r-xl: 28px; --r-pill: 999px;

  --blur-glass: 16px;           /* 8px sous 768px (voir §3) */
  --glass-saturate: 1.35;

  --ease-ui: cubic-bezier(.2, .8, .2, 1);
  --dur-1: 120ms; --dur-2: 200ms; --dur-3: 320ms;

  --topbar-h: 64px;
  --sidebar-w: 264px;
  --sidebar-w-collapsed: 76px;
  --dock-h: 68px;

  --row-h: 52px;                /* densité confortable ; 40px en compact */
  --control-h: 44px;            /* cible tactile ; 36px en compact */
}
:root[data-density="compact"] { --row-h: 40px; --control-h: 36px; }

/* ---------- 2. Thèmes ---------- */

/* ===== LUMINEUX ===== */
:root,
:root[data-theme="aurore"] {
  color-scheme: light;
  --canvas-base: #EEF0FF;
  --canvas-a: #C7D2FE;  --canvas-b: #FBCFE8;  --canvas-c: #99F6E4;
  --surface-solid: #FFFFFF;
  --surface-panel: rgb(255 255 255 / .72);
  --surface-glass: rgb(255 255 255 / .78);
  --surface-glass-fallback: rgb(255 255 255 / .94);
  --surface-overlay: rgb(255 255 255 / .86);
  --surface-inset: rgb(255 255 255 / .92);
  --surface-hover: rgb(15 23 42 / .05);
  --line-subtle: rgb(15 23 42 / .10);
  --line-control: #6B7385;                 /* ≥ 4.0:1 sur panneau */
  --glass-edge: inset 0 1px 0 rgb(255 255 255 / .75);
  --shadow-rgb: 49 46 129;                 /* ombre teintée */
  --text-1: #0F172A;  --text-2: #475569;  --text-3: #5B6577;
  --accent: #4F46E5;  --on-accent: #FFFFFF;
  --hover-mix: black;
  --scrim: rgb(15 23 42 / .45);
}
:root[data-theme="ocean"] {
  color-scheme: light;
  --canvas-base: #E8F4FB;
  --canvas-a: #BAE6FD;  --canvas-b: #A5F3FC;  --canvas-c: #BFDBFE;
  --surface-solid: #FFFFFF;
  --surface-panel: rgb(255 255 255 / .72);
  --surface-glass: rgb(255 255 255 / .78);
  --surface-glass-fallback: rgb(255 255 255 / .94);
  --surface-overlay: rgb(255 255 255 / .86);
  --surface-inset: rgb(255 255 255 / .92);
  --surface-hover: rgb(8 47 73 / .05);
  --line-subtle: rgb(8 47 73 / .10);
  --line-control: #667A8E;
  --glass-edge: inset 0 1px 0 rgb(255 255 255 / .75);
  --shadow-rgb: 12 74 110;
  --text-1: #0B1B2B;  --text-2: #425569;  --text-3: #56677A;
  --accent: #0369A1;  --on-accent: #FFFFFF;
  --hover-mix: black;
  --scrim: rgb(8 47 73 / .45);
}
:root[data-theme="sable"] {
  color-scheme: light;
  --canvas-base: #FBF5EC;
  --canvas-a: #FDE68A;  --canvas-b: #FED7AA;  --canvas-c: #FECDD3;
  --surface-solid: #FFFFFF;
  --surface-panel: rgb(255 255 255 / .72);
  --surface-glass: rgb(255 255 255 / .78);
  --surface-glass-fallback: rgb(255 255 255 / .94);
  --surface-overlay: rgb(255 255 255 / .86);
  --surface-inset: rgb(255 255 255 / .92);
  --surface-hover: rgb(41 37 36 / .05);
  --line-subtle: rgb(41 37 36 / .10);
  --line-control: #78716C;
  --glass-edge: inset 0 1px 0 rgb(255 255 255 / .75);
  --shadow-rgb: 120 53 15;
  --text-1: #1C1917;  --text-2: #57534E;  --text-3: #6A645F;
  --accent: #B83C0A;  --on-accent: #FFFFFF;
  --hover-mix: black;
  --scrim: rgb(28 25 23 / .45);
}

/* ===== SOMBRES ===== */
:root[data-theme="foret"] {
  color-scheme: dark;
  --canvas-base: #06161A;
  --canvas-a: #0E4F45;  --canvas-b: #0B3B3B;  --canvas-c: #07201F;
  --surface-solid: #0E2426;
  --surface-panel: rgb(10 34 36 / .74);
  --surface-glass: rgb(10 34 36 / .80);
  --surface-glass-fallback: rgb(10 34 36 / .96);
  --surface-overlay: rgb(10 34 36 / .88);
  --surface-inset: rgb(4 20 22 / .60);
  --surface-hover: rgb(255 255 255 / .06);
  --line-subtle: rgb(255 255 255 / .10);
  --line-control: #7FAA9D;
  --glass-edge: inset 0 1px 0 rgb(255 255 255 / .09);
  --shadow-rgb: 0 0 0;
  --text-1: #ECFDF5;  --text-2: #B7D3CA;  --text-3: #A0C0B6;
  --accent: #34D399;  --on-accent: #04251A;
  --hover-mix: white;
  --scrim: rgb(0 0 0 / .60);
}
:root[data-theme="crepuscule"] {
  color-scheme: dark;
  --canvas-base: #0B0A22;
  --canvas-a: #3B1A8C;  --canvas-b: #1A1040;  --canvas-c: #0B1633;
  --surface-solid: #171238;
  --surface-panel: rgb(20 14 52 / .74);
  --surface-glass: rgb(20 14 52 / .80);
  --surface-glass-fallback: rgb(20 14 52 / .96);
  --surface-overlay: rgb(20 14 52 / .88);
  --surface-inset: rgb(10 7 34 / .60);
  --surface-hover: rgb(255 255 255 / .06);
  --line-subtle: rgb(255 255 255 / .11);
  --line-control: #8F86C6;
  --glass-edge: inset 0 1px 0 rgb(255 255 255 / .10);
  --shadow-rgb: 0 0 0;
  --text-1: #F1EEFF;  --text-2: #C6BFEA;  --text-3: #B0A8DB;
  --accent: #A78BFA;  --on-accent: #0B1020;
  --hover-mix: white;
  --scrim: rgb(0 0 0 / .60);
}
:root[data-theme="graphite"] {
  color-scheme: dark;
  --canvas-base: #0B0D10;
  --canvas-a: #273244;  --canvas-b: #12151A;  --canvas-c: #0F1217;
  --surface-solid: #14181E;
  --surface-panel: rgb(17 21 26 / .76);
  --surface-glass: rgb(17 21 26 / .80);
  --surface-glass-fallback: rgb(17 21 26 / .96);
  --surface-overlay: rgb(17 21 26 / .90);
  --surface-inset: rgb(8 10 13 / .60);
  --surface-hover: rgb(255 255 255 / .06);
  --line-subtle: rgb(255 255 255 / .10);
  --line-control: #8791A3;
  --glass-edge: inset 0 1px 0 rgb(255 255 255 / .08);
  --shadow-rgb: 0 0 0;
  --text-1: #F3F4F6;  --text-2: #BCC2CC;  --text-3: #A5ACB8;
  --accent: #38BDF8;  --on-accent: #0B1020;
  --hover-mix: white;
  --scrim: rgb(0 0 0 / .65);
}

/* ---------- 3. Statuts sémantiques (clair / sombre) ---------- */
:root, :root[data-mode="light"] {
  --success: #166534; --warning: #854D0E; --danger: #B91C1C; --info: #075985;           /* texte/icône */
  --success-solid: #15803D; --warning-solid: #B45309; --danger-solid: #DC2626; --info-solid: #0369A1;
  --on-solid: #FFFFFF;
  --chart-1: var(--accent); --chart-2: #0E7490; --chart-3: #B45309; --chart-4: #BE185D; --chart-5: #4D7C0F; --chart-6: #6B7280;
}
:root[data-mode="dark"] {
  --success: #86EFAC; --warning: #FCD34D; --danger: #FCA5A5; --info: #7DD3FC;
  --success-solid: #4ADE80; --warning-solid: #FBBF24; --danger-solid: #F87171; --info-solid: #38BDF8;
  --on-solid: #0B1020;
  --chart-1: var(--accent); --chart-2: #22D3EE; --chart-3: #FBBF24; --chart-4: #F472B6; --chart-5: #A3E635; --chart-6: #9CA3AF;
}

/* ---------- 4. Accent personnalisé (orthogonal au thème) ---------- */
:root[data-mode="light"][data-accent="indigo"]  { --accent: #4F46E5; }
:root[data-mode="light"][data-accent="violet"]  { --accent: #7C3AED; }
:root[data-mode="light"][data-accent="rose"]    { --accent: #BE123C; }
:root[data-mode="light"][data-accent="orange"]  { --accent: #B83C0A; }
:root[data-mode="light"][data-accent="amber"]   { --accent: #9A5006; }
:root[data-mode="light"][data-accent="emerald"] { --accent: #047857; }
:root[data-mode="light"][data-accent="teal"]    { --accent: #0F766E; }
:root[data-mode="light"][data-accent="sky"]     { --accent: #0369A1; }
:root[data-mode="dark"][data-accent="indigo"]   { --accent: #818CF8; }
:root[data-mode="dark"][data-accent="violet"]   { --accent: #A78BFA; }
:root[data-mode="dark"][data-accent="rose"]     { --accent: #FB7185; }
:root[data-mode="dark"][data-accent="orange"]   { --accent: #FB923C; }
:root[data-mode="dark"][data-accent="amber"]    { --accent: #FBBF24; }
:root[data-mode="dark"][data-accent="emerald"]  { --accent: #34D399; }
:root[data-mode="dark"][data-accent="teal"]     { --accent: #2DD4BF; }
:root[data-mode="dark"][data-accent="sky"]      { --accent: #38BDF8; }
/* --on-accent : #FFFFFF en mode clair, #0B1020 en mode sombre (déjà défini par thème) */

/* ---------- 5. Dérivés (calculés, ne pas surcharger par thème) ---------- */
:root {
  --accent-hover: color-mix(in srgb, var(--accent) 86%, var(--hover-mix));
  --accent-soft:  color-mix(in srgb, var(--accent) 14%, transparent);
  --accent-line:  color-mix(in srgb, var(--accent) 38%, transparent);
  --success-soft: color-mix(in srgb, var(--success-solid) 16%, transparent);
  --warning-soft: color-mix(in srgb, var(--warning-solid) 18%, transparent);
  --danger-soft:  color-mix(in srgb, var(--danger-solid)  16%, transparent);
  --info-soft:    color-mix(in srgb, var(--info-solid)    16%, transparent);
  --focus-ring: 0 0 0 2px var(--surface-solid), 0 0 0 4px var(--accent);

  --elev-1: 0 1px 2px rgb(var(--shadow-rgb) / .07), 0 2px 6px rgb(var(--shadow-rgb) / .06);
  --elev-2: 0 2px 4px rgb(var(--shadow-rgb) / .06), 0 8px 24px rgb(var(--shadow-rgb) / .10);
  --elev-3: 0 4px 8px rgb(var(--shadow-rgb) / .08), 0 24px 56px rgb(var(--shadow-rgb) / .22);
}

/* ---------- 6. Mode « léger » (appareils modestes / Save-Data) ---------- */
:root[data-perf="lite"] {
  --blur-glass: 0px;
  --glass-saturate: 1;
  --elev-1: 0 1px 2px rgb(var(--shadow-rgb) / .10);
  --elev-2: 0 2px 8px rgb(var(--shadow-rgb) / .12);
  --elev-3: 0 8px 24px rgb(var(--shadow-rgb) / .22);
}
```

## Annexe B — `resources/css/app.css` et `materials.css` (squelette Tailwind v4)

```css
/* resources/css/app.css */
@import "tailwindcss";
@import "./tokens.css";
@import "./materials.css";
@import "@fontsource-variable/inter/wght.css";   /* ou fichiers latin / latin-ext ciblés + @font-face maison avec size-adjust */

@source "../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php";
@source "../../storage/framework/views/*.php";
@source "../**/*.blade.php";
@source "../**/*.js";

/* Variantes pilotées par les attributs de <html> (les tokens basculent seuls ; `dark:` reste exceptionnel) */
@custom-variant dark    (&:where([data-mode="dark"], [data-mode="dark"] *));
@custom-variant lite    (&:where([data-perf="lite"], [data-perf="lite"] *));
@custom-variant compact (&:where([data-density="compact"], [data-density="compact"] *));

/* `inline` est INDISPENSABLE : sans lui, la valeur serait résolue sur :root et ne suivrait pas data-theme. */
@theme inline {
  --font-sans: var(--font-ui);
  --font-mono: var(--font-code);

  --color-canvas: var(--canvas-base);
  --color-solid: var(--surface-solid);
  --color-panel: var(--surface-panel);
  --color-glass: var(--surface-glass);
  --color-overlay: var(--surface-overlay);
  --color-inset: var(--surface-inset);
  --color-hover: var(--surface-hover);
  --color-line: var(--line-subtle);
  --color-control: var(--line-control);

  --color-ink: var(--text-1);
  --color-ink-2: var(--text-2);
  --color-ink-3: var(--text-3);

  --color-accent: var(--accent);
  --color-accent-hover: var(--accent-hover);
  --color-accent-soft: var(--accent-soft);
  --color-accent-line: var(--accent-line);
  --color-on-accent: var(--on-accent);

  --color-success: var(--success);  --color-success-soft: var(--success-soft);  --color-success-solid: var(--success-solid);
  --color-warning: var(--warning);  --color-warning-soft: var(--warning-soft);  --color-warning-solid: var(--warning-solid);
  --color-danger:  var(--danger);   --color-danger-soft:  var(--danger-soft);   --color-danger-solid:  var(--danger-solid);
  --color-info:    var(--info);     --color-info-soft:    var(--info-soft);     --color-info-solid:    var(--info-solid);
  --color-on-solid: var(--on-solid);

  --color-chart-1: var(--chart-1); --color-chart-2: var(--chart-2); --color-chart-3: var(--chart-3);
  --color-chart-4: var(--chart-4); --color-chart-5: var(--chart-5); --color-chart-6: var(--chart-6);

  --radius-xs: var(--r-xs);  --radius-sm: var(--r-sm);  --radius-md: var(--r-md);
  --radius-lg: var(--r-lg);  --radius-xl: var(--r-xl);  --radius-pill: var(--r-pill);

  --shadow-sm: var(--elev-1);  --shadow-md: var(--elev-2);  --shadow-lg: var(--elev-3);
}

@layer base {
  html {
    -webkit-text-size-adjust: 100%;
    scroll-padding-top: calc(var(--topbar-h) + 24px);      /* focus jamais masqué par la topbar sticky (WCAG 2.4.11) */
    scroll-padding-bottom: calc(var(--dock-h) + 24px);
  }
  body {
    font-family: var(--font-ui); font-size: 15px; line-height: 1.5;
    color: var(--text-1); background: var(--canvas-base);
    font-variant-numeric: tabular-nums; -webkit-font-smoothing: antialiased;
  }
  :focus-visible { outline: none; box-shadow: var(--focus-ring); }   /* chaque composant définit son propre rayon */
  @media (forced-colors: active) {
    :focus-visible { outline: 2px solid Highlight; box-shadow: none; }
    .material-panel, .material-glass, .material-overlay, .material-solid { border: 1px solid CanvasText; }
  }
  @media (prefers-reduced-motion: reduce) {
    *, ::before, ::after { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
  }
}
```

`materials.css` (extrait du prototype ; à transposer en `@layer components` en renommant `.solid` en `.material-solid`) :

```css
/* ===== Canevas statique (une seule couche fixe, aucune animation) ===== */
.app-canvas{position:fixed;inset:0;z-index:-1;contain:strict;background:
  radial-gradient(60rem 38rem at 8% -8%,var(--canvas-a),transparent 62%),
  radial-gradient(52rem 34rem at 100% 2%,var(--canvas-b),transparent 60%),
  radial-gradient(64rem 40rem at 55% 112%,var(--canvas-c),transparent 62%),
  var(--canvas-base)}
:root[data-perf="lite"] .app-canvas{background:var(--canvas-base)}

/* ===== Matériaux ===== */
.material-panel{background:var(--surface-panel);border:1px solid var(--line-subtle);box-shadow:var(--glass-edge),var(--elev-1);border-radius:var(--r-lg)}
.material-glass,.material-overlay{background:var(--surface-glass);border:1px solid var(--line-subtle);box-shadow:var(--glass-edge),var(--elev-2);
  -webkit-backdrop-filter:blur(var(--blur-glass)) saturate(var(--glass-saturate));backdrop-filter:blur(var(--blur-glass)) saturate(var(--glass-saturate))}
.material-overlay{background:var(--surface-overlay);box-shadow:var(--glass-edge),var(--elev-3)}
.solid{background:var(--surface-solid);border:1px solid var(--line-subtle);box-shadow:var(--elev-1);border-radius:var(--r-lg)}
@supports not ((backdrop-filter:blur(1px)) or (-webkit-backdrop-filter:blur(1px))){.material-glass,.material-overlay{background:var(--surface-glass-fallback)}}
@media (prefers-reduced-transparency:reduce){.material-glass,.material-overlay{-webkit-backdrop-filter:none;backdrop-filter:none;background:var(--surface-glass-fallback)}.material-panel{background:var(--surface-solid)}}
@media (prefers-contrast:more){.material-glass,.material-overlay{-webkit-backdrop-filter:none;backdrop-filter:none;background:var(--surface-solid)}.material-panel{background:var(--surface-solid)}:root{--line-subtle:var(--line-control)}}
:root[data-perf="lite"] .material-glass,:root[data-perf="lite"] .material-overlay{-webkit-backdrop-filter:none;backdrop-filter:none;background:var(--surface-glass-fallback)}
@media (max-width:767px){:root{--blur-glass:8px}}

```

## Annexe C — Système de préférences : script anti-flash, store Alpine, liste blanche serveur

### C.1 Script inline dans `<head>` (testé sur 9 scénarios : défauts clair/sombre, cookie seul, valeurs invalides, JSON corrompu, mode léger automatique, `perf: full` prioritaire)

```html
<script>
(function(){
  var d=document.documentElement, K='ui_prefs', P={theme:'auto',accent:null,density:'comfortable',perf:'auto'};
  var TH=['aurore','ocean','sable','foret','crepuscule','graphite'], DARK=['foret','crepuscule','graphite'],
      AC=['indigo','violet','rose','orange','amber','emerald','teal','sky'];
  try{
    var raw=localStorage.getItem(K);
    if(!raw){var m=document.cookie.match(new RegExp('(?:^|; )'+K+'=([^;]*)'));raw=m?decodeURIComponent(m[1]):'{}';}
    var s=JSON.parse(raw)||{}; for(var k in P){ if(s[k]!==undefined) P[k]=s[k]; }
  }catch(e){}
  var t=P.theme;
  if(t==='auto'||TH.indexOf(t)<0){ t=matchMedia('(prefers-color-scheme: dark)').matches?'crepuscule':'aurore'; }
  d.dataset.theme=t; d.dataset.mode=DARK.indexOf(t)>-1?'dark':'light';
  if(AC.indexOf(P.accent)>-1){ d.dataset.accent=P.accent; } else { delete d.dataset.accent; }
  d.dataset.density=P.density==='compact'?'compact':'comfortable';
  var c=navigator.connection||{}, lite=P.perf==='lite'||(P.perf!=='full'&&(c.saveData||(navigator.deviceMemory&&navigator.deviceMemory<=2)||(navigator.hardwareConcurrency&&navigator.hardwareConcurrency<=2)));
  if(lite){ d.dataset.perf='lite'; } else { delete d.dataset.perf; }
})();
</script>
```

### C.2 Store Alpine (`resources/js/alpine/prefs.js`, implémentation de référence à compléter et tester)

```js
const KEY = 'ui_prefs';
const THEMES = { aurore: 'light', ocean: 'light', sable: 'light', foret: 'dark', crepuscule: 'dark', graphite: 'dark' };
const ACCENTS = ['indigo', 'violet', 'rose', 'orange', 'amber', 'emerald', 'teal', 'sky'];
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

export default (Alpine) => {
  Alpine.store('prefs', {
    theme: 'auto', accent: null, density: 'comfortable', perf: 'auto', _t: null,

    init() {
      try { Object.assign(this, JSON.parse(localStorage.getItem(KEY) || '{}')); } catch (_) {}
      this.apply();
      matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => this.theme === 'auto' && this.apply());
    },

    set(key, value) {
      if (key === 'theme' && value !== 'auto' && !(value in THEMES)) return;
      if (key === 'accent' && value !== null && !ACCENTS.includes(value)) return;
      if (key === 'density' && !['comfortable', 'compact'].includes(value)) return;
      if (key === 'perf' && !['auto', 'lite', 'full'].includes(value)) return;
      this[key] = value;
      this.apply();
      this.persist();
      window.dispatchEvent(new CustomEvent('prefs:changed', { detail: this.snapshot() }));
    },

    snapshot() { return { theme: this.theme, accent: this.accent, density: this.density, perf: this.perf }; },

    resolvedTheme() {
      return this.theme === 'auto' ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'crepuscule' : 'aurore') : this.theme;
    },

    apply() {
      const d = document.documentElement, t = this.resolvedTheme();
      d.dataset.theme = t; d.dataset.mode = THEMES[t];
      this.accent ? (d.dataset.accent = this.accent) : delete d.dataset.accent;
      d.dataset.density = this.density;
      const c = navigator.connection || {};
      const lite = this.perf === 'lite' || (this.perf === 'auto' && (c.saveData || navigator.deviceMemory <= 2 || navigator.hardwareConcurrency <= 2));
      lite ? (d.dataset.perf = 'lite') : delete d.dataset.perf;
    },

    persist() {
      const json = JSON.stringify(this.snapshot());
      try { localStorage.setItem(KEY, json); } catch (_) {}
      document.cookie = `${KEY}=${encodeURIComponent(json)}; Path=/; Max-Age=31536000; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
      clearTimeout(this._t);
      this._t = setTimeout(() => {           // synchronisation serveur facultative (utilisateur connecté + endpoint présent)
        if (!document.body.dataset.auth) return;
        fetch('/profile/appearance', {
          method: 'PUT', credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
          body: json,
        }).catch(() => {});
      }, 600);
    },
  });
};
```

### C.3 Liste blanche côté serveur (jamais de valeur de cookie brute dans le HTML)

```php
// app/Support/Ui/Theme.php
enum Theme: string
{
    case Aurore = 'aurore'; case Ocean = 'ocean'; case Sable = 'sable';
    case Foret = 'foret'; case Crepuscule = 'crepuscule'; case Graphite = 'graphite';

    public function mode(): string
    {
        return match ($this) { self::Foret, self::Crepuscule, self::Graphite => 'dark', default => 'light' };
    }
}

// app/Support/Ui/Preferences.php   (use Illuminate\Http\Request;)
final readonly class Preferences
{
    public const ACCENTS = ['indigo', 'violet', 'rose', 'orange', 'amber', 'emerald', 'teal', 'sky'];

    public function __construct(
        public ?Theme $theme = null,            // null = « auto » : le script inline tranche avant le premier rendu
        public ?string $accent = null,
        public string $density = 'comfortable',
        public string $perf = 'auto',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $raw = json_decode((string) $request->cookie('ui_prefs'), true);
        $raw = is_array($raw) ? $raw : [];

        return new self(
            theme: Theme::tryFrom((string) ($raw['theme'] ?? '')),
            accent: in_array($raw['accent'] ?? null, self::ACCENTS, true) ? $raw['accent'] : null,
            density: ($raw['density'] ?? '') === 'compact' ? 'compact' : 'comfortable',
            perf: in_array($raw['perf'] ?? null, ['lite', 'full'], true) ? $raw['perf'] : 'auto',
        );
    }

    /** @return array<string, string> attributs de <html>, issus uniquement de valeurs autorisées */
    public function htmlAttributes(): array
    {
        $theme = $this->theme ?? Theme::Aurore;

        return array_filter([
            'data-theme' => $theme->value,
            'data-mode' => $theme->mode(),
            'data-accent' => $this->accent,
            'data-density' => $this->density,
            'data-perf' => $this->perf === 'lite' ? 'lite' : null,
        ]);
    }
}
```

```php
// bootstrap/app.php — dans withMiddleware()
$middleware->encryptCookies(except: ['ui_prefs']);   // cookie écrit par JavaScript : ne doit pas être chiffré
```

```blade
{{-- layouts/app.blade.php --}}
<html lang="fr" @foreach ($uiAttributes as $name => $value) {{ $name }}="{{ $value }}" @endforeach>
```

Un View Composer sur `layouts.*` fournit `$uiAttributes` via `Preferences::fromRequest(request())->htmlAttributes()`. Si l'utilisateur est connecté et que `users.ui_preferences` existe, cette valeur **prime** sur le cookie (puis réécrit le cookie).

---

## Annexe D — Validateur de contraste (`frontend-qa/check-contrast.py`)

**Résultat de référence : 1 068 vérifications, 0 échec.** Pour chaque thème, chaque couleur du canevas (base et 3 bulles, soit le pire cas) est testée **sous** le panneau et sous le verre, avec composition alpha. Sont vérifiés : les 3 niveaux de texte (4,5:1), le texte d'accent (4,5:1), les 8 accents dans chaque mode, les 4 statuts en texte sur panneau, sur verre et sur leur teinte douce (4,5:1), le texte sur fond d'accent et sur fond de statut plein (4,5:1), la bordure de contrôle (3:1), les séries de graphiques 2 à 6 sur surface pleine (3:1).

Valeurs minimales observées (ratio le plus bas du thème, texte sur panneau) : Aurore t2 6,79 · t3 5,27 ; Océan t2 6,99 · t3 5,28 ; Sable t2 6,95 · t3 5,31 ; Forêt t2 9,14 · t3 7,42 ; Crépuscule t2 9,73 · t3 7,66 ; Graphite t2 9,57 · t3 7,50.

```python
#!/usr/bin/env python3
"""Valide les contrastes WCAG 2.2 AA des tokens de themes Auto-GestBoard.
Usage : python3 check-contrast.py   (code retour 1 si un seuil n'est pas atteint)
Pire cas : chaque couleur du canevas est testee SOUS le panneau/verre (composition alpha).
A re-executer a chaque modification de tokens.css (reporter les valeurs ici)."""
import sys

def rgb(h): h = h.lstrip('#'); return tuple(int(h[i:i+2], 16) for i in (0, 2, 4))
def lin(c): c /= 255; return c / 12.92 if c <= .03928 else ((c + .055) / 1.055) ** 2.4
def lum(c): r, g, b = map(lin, c); return .2126 * r + .7152 * g + .0722 * b
def cr(a, b):
    la, lb = sorted((lum(a), lum(b)), reverse=True); return (la + .05) / (lb + .05)
def over(hexv, a, bg): f = rgb(hexv); return tuple(round(f[i] * a + bg[i] * (1 - a)) for i in range(3))

# mode, canevas (base + bulles), panneau (couleur, alpha), verre (couleur, alpha), solide, t1,t2,t3, bordure controle, accent, on-accent
T = {
 "aurore":     ("light", ["#EEF0FF", "#C7D2FE", "#FBCFE8", "#99F6E4"], ("#FFFFFF", .72), ("#FFFFFF", .78), "#FFFFFF", "#0F172A", "#475569", "#5B6577", "#6B7385", "#4F46E5", "#FFFFFF"),
 "ocean":      ("light", ["#E8F4FB", "#BAE6FD", "#A5F3FC", "#BFDBFE"], ("#FFFFFF", .72), ("#FFFFFF", .78), "#FFFFFF", "#0B1B2B", "#425569", "#56677A", "#667A8E", "#0369A1", "#FFFFFF"),
 "sable":      ("light", ["#FBF5EC", "#FDE68A", "#FED7AA", "#FECDD3"], ("#FFFFFF", .72), ("#FFFFFF", .78), "#FFFFFF", "#1C1917", "#57534E", "#6A645F", "#78716C", "#B83C0A", "#FFFFFF"),
 "foret":      ("dark",  ["#06161A", "#0E4F45", "#0B3B3B", "#07201F"], ("#0A2224", .74), ("#0A2224", .80), "#0E2426", "#ECFDF5", "#B7D3CA", "#A0C0B6", "#7FAA9D", "#34D399", "#04251A"),
 "crepuscule": ("dark",  ["#0B0A22", "#3B1A8C", "#1A1040", "#0B1633"], ("#140E34", .74), ("#140E34", .80), "#171238", "#F1EEFF", "#C6BFEA", "#B0A8DB", "#8F86C6", "#A78BFA", "#0B1020"),
 "graphite":   ("dark",  ["#0B0D10", "#273244", "#12151A", "#0F1217"], ("#11151A", .76), ("#11151A", .80), "#14181E", "#F3F4F6", "#BCC2CC", "#A5ACB8", "#8791A3", "#38BDF8", "#0B1020"),
}
ACC = {"light": {"indigo": "#4F46E5", "violet": "#7C3AED", "rose": "#BE123C", "orange": "#B83C0A", "amber": "#9A5006", "emerald": "#047857", "teal": "#0F766E", "sky": "#0369A1"},
       "dark":  {"indigo": "#818CF8", "violet": "#A78BFA", "rose": "#FB7185", "orange": "#FB923C", "amber": "#FBBF24", "emerald": "#34D399", "teal": "#2DD4BF", "sky": "#38BDF8"}}
ST  = {"light": {"success": "#166534", "warning": "#854D0E", "danger": "#B91C1C", "info": "#075985"},
       "dark":  {"success": "#86EFAC", "warning": "#FCD34D", "danger": "#FCA5A5", "info": "#7DD3FC"}}
SOLID = {"light": ({"success": "#15803D", "warning": "#B45309", "danger": "#DC2626", "info": "#0369A1"}, "#FFFFFF"),
         "dark":  ({"success": "#4ADE80", "warning": "#FBBF24", "danger": "#F87171", "info": "#38BDF8"}, "#0B1020")}
ON_ACC = {"light": "#FFFFFF", "dark": "#0B1020"}
CHART = {"light": ["#0E7490", "#B45309", "#BE185D", "#4D7C0F", "#6B7280"], "dark": ["#22D3EE", "#FBBF24", "#F472B6", "#A3E635", "#9CA3AF"]}

fails, n = [], 0
def chk(label, v, need=4.5):
    global n; n += 1
    if v < need: fails.append(f"{label}: {v:.2f} < {need}")

for name, (mode, cvs, panel, glass, solid, t1, t2, t3, ctl, acc, onacc) in T.items():
    chk(f"{name} on-accent/accent", cr(rgb(onacc), rgb(acc)))
    for c in cvs:
        base = rgb(c); P = over(*panel, base); G = over(*glass, base)
        for lab, bg in (("panel", P), ("glass", G)):
            for tn, t in (("t1", t1), ("t2", t2), ("t3", t3)): chk(f"{name}/{c} {tn} on {lab}", cr(rgb(t), bg))
            chk(f"{name}/{c} accent-text on {lab}", cr(rgb(acc), bg))
            for an, a in ACC[mode].items(): chk(f"{name}/{c} accent {an} text on {lab}", cr(rgb(a), bg))
            for sn, s in ST[mode].items():
                chk(f"{name}/{c} {sn} text on {lab}", cr(rgb(s), bg))
                chk(f"{name}/{c} {sn} text on soft/{lab}", cr(rgb(s), over(SOLID[mode][0][sn], .16, bg)))
        chk(f"{name}/{c} control-border vs panel (3:1)", cr(rgb(ctl), P), 3.0)
    for tn, t in (("t1", t1), ("t2", t2), ("t3", t3)): chk(f"{name} {tn} on solid", cr(rgb(t), rgb(solid)))
    chk(f"{name} control-border vs solid (3:1)", cr(rgb(ctl), rgb(solid)), 3.0)
    for k, s_ in enumerate(CHART[mode], 2): chk(f"{name} chart-{k} vs solid (3:1)", cr(rgb(s_), rgb(solid)), 3.0)
for mode in ("light", "dark"):
    for an, a in ACC[mode].items(): chk(f"{mode} on-accent/{an}", cr(rgb(ON_ACC[mode]), rgb(a)))
    sol, on = SOLID[mode]
    for sn, s in sol.items(): chk(f"{mode} on-solid/{sn}", cr(rgb(on), rgb(s)))

print(f"{n} verifications, {len(fails)} echec(s)")
for f in fails: print(" -", f)
sys.exit(1 if fails else 0)
```

---

## Annexe E — Prototype de référence (`auto-gestboard-prototype-reference.html`)

Fichier HTML autonome (aucune dépendance externe, ≈ 52 Ko) à copier dans `docs/frontend/reference/`. Il montre : les 6 thèmes et les 8 accents, la densité, le mode léger, la sidebar et la topbar en verre, le dock mobile, les KPI (avec sparkline SVG), le bloc « À traiter », le parcours en 5 phases, le planning par moniteur avec un conflit, le tableau avec chip de filtre, la palette de commandes et le popover d'apparence.

Paramètres d'URL utiles pour comparer vos rendus : `?theme=aurore|ocean|sable|foret|crepuscule|graphite` · `&accent=rose` · `&density=compact` · `&lite=1` · `&look=1` (popover ouvert) · `&palette=1` (palette ouverte). Largeurs de contrôle : 1440 px et 390 px.

**Ce que le prototype est** : la référence de rendu (matériaux, hiérarchie, espacements, états). **Ce qu'il n'est pas** : du code de production (icônes simplifiées en sprite, JavaScript d'illustration, pas d'accessibilité complète des menus). Reproduis son rendu avec les composants Blade et Tailwind, pas en copiant son CSS.

**Trois constats faits sur le prototype et intégrés au prompt** : (1) à 62 % d'opacité, un texte qui défile sous la topbar en verre reste lisible → minimum 78 % ; (2) un popover en verre placé dans la topbar en verre ne floute rien (backdrop root) → menus opaques, dialogs téléportés ; (3) sur des fonds pastel, des teintes de statut « classiques » échouaient à 4,5:1 sur leur teinte douce → couleurs de texte de statut plus sombres en clair et plus claires en sombre (déjà dans l'Annexe A).

---

## Annexe F — Sources de la recherche

**Glassmorphisme, accessibilité, performance**
- Axess Lab, *Glassmorphism Meets Accessibility: Can Glass Be Inclusive?* — https://axesslab.com/?p=5111
- Setproduct, *Glassmorphism, neumorphism, liquid glass: what to use in 2026* — https://www.setproduct.com/blog/liquid-glass-vs-glassmorphism
- Chrome for Developers, *CSS prefers-reduced-transparency* — https://developer.chrome.com/blog/css-prefers-reduced-transparency
- Apple, *Human Interface Guidelines : Materials* (copie consultée via https://glama.ai/mcp/servers/@tmaasen/apple-dev-mcp/blob/b82f0efe2115dc4539c83a2374a714a84aeb350a/content/universal/materials.md)
- OpenReplay, *Creating blurred backgrounds with CSS backdrop-filter* — https://blog.openreplay.com/creating-blurred-backgrounds-css-backdrop-filter/
- Can I use, *CSS Backdrop Filter* — https://caniuse.com/css-backdrop-filter
- Deque University, *WCAG 2.2 updates* — https://dequeuniversity.com/resources/wcag-2.2

**Stack**
- Tailwind CSS v4 : [compatibilité](https://tailwindcss.com/docs/compatibility) · [thème (`@theme`, `inline`)](https://tailwindcss.com/docs/theme) · [mode sombre / `@custom-variant`](https://tailwindcss.com/docs/dark-mode) · [installation Laravel + Vite](https://tailwindcss.com/docs/installation/framework-guides/laravel/vite)
- Thèmes multiples avec `@custom-variant` et `data-theme` — https://dev.to/vrauuss_softwares/-create-custom-themes-in-tailwind-css-v4-custom-variant-12-2nf0
- Alpine.js : [plugin Focus](https://alpinejs.dev/plugins/focus) · [plugin Persist](https://alpinejs.dev/plugins/persist)
- `mallardduck/blade-lucide-icons` — https://github.com/mallardduck/blade-lucide-icons
- FullCalendar, plugins Premium — https://fullcalendar.io/docs/premium
- Comparatif de bibliothèques de graphiques (**rédigé par l'éditeur d'ApexCharts : biais possible**, utilisé uniquement pour les ordres de grandeur de poids) — https://apexcharts.com/blog/state-of-javascript-charting-2026

**Patterns UX de SaaS de gestion**
- SaaSFrame, *Anatomy of high-performance SaaS dashboard design* — https://www.saasframe.io/blog/the-anatomy-of-high-performance-saas-dashboard-design-2026-trends-patterns
- SaaS UI, navigation latérale — https://www.saasui.design/blog/saas-sidebar-navigation-ux-patterns · filtrage et tri — https://www.saasui.design/blog/saas-filtering-sorting-ux-patterns · recherche et palette de commandes — https://www.saasui.design/blog/saas-search-command-palette-ux-patterns
- SAP Fiori, *Single planning calendar* — https://www.sap.com/design-system/fiori-design-web/v1-139/ui-elements/single-planning-calendar/usage
- Shopify, *Resource index pattern* — https://shopify.dev/docs/api/app-home/patterns/templates/resource-index

**Logiciels d'auto-école (fonctionnalités de référence)**
- Elgeaweb — https://www.formalive.fr/elgeaweb-logiciel-gestion-auto-ecole/
- Klaxo — https://www.capterra.fr/software/1071673/Klaxo
- Total Driving School Management — https://www.getapp.com/education-childcare-software/a/total-driving-school-management/
- CarDriveManager — https://www.capterra.com/p/10040524/CarDriveManager/
- Ornikar Pro — https://www.ornikar.com/pro-home

**Contexte local**
- Statcounter, navigateurs mobiles et tablettes au Gabon, septembre 2026 — https://gs.statcounter.com/browser-market-share/mobile-tablet-/gabon · versions d'Android — https://gs.statcounter.com/android-version-market-share/mobile-tablet/gabon

**Limites de la recherche** : la page Apple HIG et la page Polaris « index table » n'ont pas pu être ouvertes directement (accès refusé) ; leurs principes ont été recoupés avec une copie du texte d'Apple et avec le pattern « resource index » de Shopify. Les avis utilisateurs sur Klaxo et CarDriveManager sont inexistants sur les pages consultées : seules leurs listes de fonctionnalités sont utilisées. La compatibilité Laravel 12 du paquet d'icônes n'est pas confirmée. Les extraits PHP de l'Annexe C n'ont pas été exécutés : à tester en WP1.
