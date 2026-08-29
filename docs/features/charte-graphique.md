Après analyse des **deux archives réelles** — `../../brand-images` et `../../` — je pense qu'il faut maintenant arrêter les choix graphiques et établir une **charte graphique officielle Auto-GestBoard**, qui deviendra la référence obligatoire pour toutes les futures évolutions.

L'analyse du SaaS montre d'ailleurs un point important : **l'interface actuelle utilise encore un violet/indigo comme couleur primaire** (`#4F46E5` en clair et `#6C6FF6` en sombre), alors que les éléments de marque fournis sont clairement construits autour du **bleu nuit + vert/turquoise**.  

Les assets de marque fournissent justement plusieurs variantes exploitables : logo horizontal, icône seule, variantes pour fonds clairs/sombres et variantes monochromes. 

# 🎨 CHARTE GRAPHIQUE OFFICIELLE — AUTO-GESTBOARD

**Version 1.0 — SaaS de gestion d'auto-écoles**

> **Positionnement visuel :**
> SaaS professionnel + mobilité + performance + simplicité + confiance.
>
> **Style UI :**
> **Soft Neumorphism maîtrisé + SaaS moderne + interface professionnelle.**

Le but n'est surtout pas de transformer Auto-GestBoard en interface « gadget neumorphique ». Le neumorphisme sera utilisé comme **langage de profondeur et de surfaces**, tandis que l'architecture, la navigation et la lisibilité resteront celles d'un SaaS professionnel.

---

# 1. Identité visuelle

## Logo officiel

Le logo principal retenu est celui-ci :

**Auto-GestBoard**

avec :

* Auto-Gest en bleu nuit
* Board en vert
* slogan :
  **GÉREZ. SUIVEZ. FAITES RÉUSSIR.**

La version horizontale constitue le **logo principal**.

### Assets

| Asset         | Utilisation                    |
| ------------- | ------------------------------ |
| `brand 1.png` | Logo principal sur fond clair  |
| `brand 2.png` | Icône seule                    |
| `brand 3.png` | Icône carrée / application     |
| `brand 4.png` | Icône circulaire               |
| `brand 5.png` | Version monochrome bleue/grise |
| `brand 6.png` | Version monochrome noire       |
| `brand 7.png` | Logo sur fond bleu nuit        |
| `brand 8.png` | Logo sur fond vert             |

Les variantes ne doivent **pas être mélangées arbitrairement**.

---

# 2. Couleurs de marque

Les deux couleurs fondamentales identifiables dans les assets sont un **bleu nuit très profond**, autour de `#082543`, et un **vert/turquoise**, autour de `#0FAF81`.

Ce sont ces couleurs qui doivent remplacer l'indigo actuellement utilisé par l'application.

## Primary — Bleu Auto-Gest

```text
#082543
```

RGB :

```text
8 / 37 / 67
```

Utilisation :

* navigation ;
* titres importants ;
* logo ;
* boutons secondaires ;
* icônes ;
* éléments de confiance ;
* fonds sombres ;
* éléments actifs selon contexte.

---

## Brand Green — Vert Auto-Gest

```text
#0FAF81
```

RGB :

```text
15 / 175 / 129
```

Utilisation :

* CTA principal ;
* progression ;
* indicateurs positifs ;
* éléments actifs ;
* statistiques ;
* graphiques ;
* accents ;
* validation.

**C'est la couleur d'action de la marque.**

---

# 3. Palette complète recommandée

Je propose cette palette officielle :

| Token             | Hex       | Rôle               |
| ----------------- | --------- | ------------------ |
| `brand-navy-950`  | `#061C30` | Très sombre        |
| `brand-navy-900`  | `#082543` | Bleu principal     |
| `brand-navy-800`  | `#0D3557` | Bleu secondaire    |
| `brand-navy-700`  | `#16466D` | Hover / accent     |
| `brand-green-700` | `#08785C` | Vert sombre        |
| `brand-green-600` | `#0A966F` | Hover              |
| `brand-green-500` | `#0FAF81` | **Vert principal** |
| `brand-green-400` | `#31C49B` | Accent             |
| `brand-green-100` | `#DDF7EF` | Fond léger         |
| `white`           | `#FFFFFF` | Surface            |
| `gray-50`         | `#F7F9FB` | Fond               |
| `gray-100`        | `#EEF2F5` | Surface secondaire |
| `gray-200`        | `#DCE3E9` | Bordure            |
| `gray-500`        | `#718096` | Texte secondaire   |
| `gray-700`        | `#344054` | Texte              |
| `gray-900`        | `#17202B` | Texte principal    |

---

# 4. Couleurs sémantiques

Il ne faut pas utiliser le vert de marque pour **toutes** les informations positives.

Créer une distinction entre **brand green** et **semantic success**.

### Success

```text
#159A6C
```

### Warning

```text
#D98B18
```

### Danger

```text
#D64545
```

### Info

```text
#2388B5
```

### Neutral

```text
#718096
```

Ainsi :

```text
Vert Auto-Gest
      ↓
Brand / CTA / progression

Vert Success
      ↓
Paiement réussi
Dossier validé
Examen réussi
```

---

# 5. Thème clair

Le thème clair doit devenir :

```text
Background
#F4F7FA

Surface
#FFFFFF

Surface elevated
#F9FBFC

Surface inset
#E7EDF2

Text
#17202B

Text secondary
#64748B

Border
#DCE3E9
```

L'application actuelle possède déjà une architecture de tokens CSS avec `--color-background`, `--color-surface`, `--color-content`, etc. Il faut donc **conserver cette architecture mais remplacer les valeurs**, plutôt que reconstruire tout le système. 

---

# 6. Thème sombre

Le thème sombre doit être beaucoup plus proche de l'identité Auto-GestBoard.

```text
Background
#061522

Surface
#082543

Surface elevated
#0D304D

Surface inset
#04101C

Text
#F1F5F9

Text secondary
#A8B6C5

Text muted
#71869A

Border
#183A55
```

Et :

```text
Primary
#0FAF81

Primary hover
#31C49B
```

### Important

Le mode sombre ne doit **pas simplement inverser les couleurs**.

Il doit conserver :

> **Bleu nuit comme environnement + vert comme accent.**

La marque fournit d'ailleurs explicitement une variante du logo sur fond bleu nuit, ce qui confirme que cette combinaison doit faire partie de l'identité sombre. 

---

# 7. Neumorphism

C'est ici qu'il faut être particulièrement discipliné.

## Principe

Le neumorphisme doit donner l'impression :

> **« surfaces physiques légères »**

et non :

> **« tous les éléments flottent »**.

### Cartes

Utiliser :

```text
border-radius: 16px
```

avec une ombre douce.

### KPI

Les cartes statistiques peuvent être légèrement plus prononcées.

### Boutons

Le bouton principal peut avoir :

```text
vert Auto-Gest
+
ombre légère
```

### Champs

Les champs peuvent utiliser une légère impression **inset** lorsqu'ils sont actifs.

---

# 8. Ce qu'il faut absolument éviter

❌ Neumorphism excessif

❌ Ombres énormes

❌ 5 niveaux de profondeur sur une même page

❌ Texte gris trop clair

❌ boutons sans contraste

❌ cartes imbriquées dans des cartes imbriquées

❌ gradients partout

❌ couleurs aléatoires

❌ violet/indigo comme couleur primaire

❌ emoji comme icônes d'interface

❌ interface ressemblant à un template générique

---

# 9. Rayons

Je propose :

```text
radius-xs   6px
radius-sm   10px
radius-md   14px
radius-lg   18px
radius-xl   24px
radius-pill 9999px
```

### Usage

```text
Input             10px
Button            10px
Badge             pill
Card              16px
KPI               18px
Modal             20px
Sidebar           20px
```

L'application actuelle utilise déjà plusieurs niveaux de radius, notamment `0.625rem`, `0.875rem`, `1.25rem` et `1.75rem`. 

On peut donc conserver cette logique mais la normaliser.

---

# 10. Typographie

## Police principale

Je recommande :

> **Inter**

pour l'application.

Alternative :

> **Plus Jakarta Sans**

si nous voulons un rendu légèrement plus premium.

### Hiérarchie

```text
H1       30–36 px / 700
H2       24–28 px / 700
H3       18–20 px / 650
Body     14–15 px / 400
Small    12–13 px / 400
Caption  11–12 px / 500
```

Le SaaS actuel utilise déjà des tailles assez compactes et une hiérarchie de titres relativement petite. 

Je conserverais cette philosophie : **Auto-GestBoard doit être dense mais respirable**, particulièrement pour les secrétaires et gestionnaires qui travaillent toute la journée dans l'application.

---

# 11. Sidebar

C'est l'un des éléments qui doit être complètement repensé.

La sidebar actuelle est déjà une sidebar flottante avec largeur `lg:w-64`, coins arrondis et ombre soft, ce qui constitue une bonne base. 

Mais elle doit devenir plus cohérente avec la marque.

## Desktop

```text
┌──────────────────────────┐
│ 🚗 Auto-GestBoard        │
│                          │
│ PRINCIPAL                │
│                          │
│ ▣ Tableau de bord        │
│                          │
│ GESTION                  │
│                          │
│ ○ Élèves                 │
│ ○ Prospects              │
│ ○ Dossiers               │
│ ○ Moniteurs              │
│                          │
│ FORMATION                │
│                          │
│ ○ Planning               │
│ ○ Compétences            │
│ ○ Examens                │
│                          │
│ FINANCES                 │
│                          │
│ ○ Factures               │
│ ○ Paiements              │
│ ○ Journal                │
│                          │
│ FLOTTE                   │
│ ○ Véhicules              │
│                          │
│ ...                      │
│                          │
│ ⚙ Paramètres             │
└──────────────────────────┘
```

### Actif

Je recommande :

```text
background: #0FAF81
text: white
```

et non l'actuel :

```text
bg-primary
```

qui correspond actuellement à l'indigo. 

---

# 12. Navigation

La navigation doit être organisée selon le **travail réel d'une auto-école**, et non selon la structure technique du code.

Je propose :

### PRINCIPAL

* Tableau de bord

### GESTION

* Élèves
* Prospects
* Dossiers
* Moniteurs

### FORMATION

* Planning
* Compétences
* Code
* Conduite
* Examens

### FINANCES

* Factures
* Paiements
* Journal

### FLOTTE

* Véhicules
* Maintenance
* Carburant

### RELATION CLIENT

* CRM
* Notifications

### BOUTIQUE

* Produits
* Ventes
* Stock

### ADMINISTRATION

* Utilisateurs
* Paramètres
* Documents
* Audit

Cette organisation correspond beaucoup mieux à la logique métier déjà présente dans AutoGest.

Le code actuel possède déjà ces domaines, mais ils sont répartis en groupes comme « Gestion », « Formation », « Finances », « Flotte & Boutique » et « Administration ».  

---

# 13. Dashboard

Le dashboard doit devenir **la vitrine du produit**.

Il doit immédiatement répondre à :

> **« Comment va mon auto-école aujourd'hui ? »**

### Première ligne

```text
Élèves actifs
248

Séances aujourd'hui
18

Paiements du mois
1 850 000 FCFA

Examens à venir
12
```

### Deuxième zone

```text
┌───────────────────────────────┐
│ Progression des élèves        │
│                               │
│           graphique           │
│                               │
└───────────────────────────────┘
```

*

```text
┌──────────────────────┐
│ Planning aujourd'hui │
│                      │
│ 08:00 Jean           │
│ 09:00 Patrick        │
│ 10:00 Marie          │
└──────────────────────┘
```

---

# 14. KPI Cards

Les KPI doivent utiliser une structure uniforme :

```text
┌──────────────────────────────┐
│  Élèves actifs          👤   │
│                              │
│  248                         │
│  ↑ 12% ce mois               │
└──────────────────────────────┘
```

### Couleur

L'icône peut utiliser :

* bleu pour les informations ;
* vert pour progression ;
* orange pour attention ;
* rouge pour problème.

Ne pas colorer toute la carte.

---

# 15. Boutons

### Primary

```text
background: #0FAF81
color: #FFFFFF
```

Exemple :

```text
+ Ajouter un élève
```

### Secondary

```text
background: transparent
border: #082543
color: #082543
```

### Ghost

```text
background: transparent
color: #64748B
```

### Danger

```text
background: #D64545
color: white
```

---

# 16. Tables

Le SaaS actuel possède énormément de tableaux.

Il faut donc établir une vraie norme.

### Desktop

```text
┌─────────────────────────────────────────────────────┐
│ Élève       Téléphone     Statut      Actions       │
├─────────────────────────────────────────────────────┤
│ Jean Dupont +241...      Actif       •••            │
│ Marie X     +241...      Formation   •••            │
└─────────────────────────────────────────────────────┘
```

### Règles

* hauteur de ligne confortable ;
* hover léger ;
* colonnes importantes toujours visibles ;
* actions regroupées ;
* badges cohérents ;
* pagination claire ;
* filtres au-dessus.

Le code existant possède déjà des composants et wrappers pour les tableaux, mais l'objectif doit être de standardiser leur comportement dans tout le SaaS. 

---

# 17. Planning

**Le planning doit être une pièce maîtresse du design.**

L'audit existant confirme que la reconstruction en grille est nécessaire : l'ancien système avait une véritable grille **jour × heure**, alors que la version Laravel avait régressé vers un tableau plat. 

Je recommande :

```text
             LUN     MAR     MER     JEU     VEN
07:00
        ┌────────┬────────┬────────┬────────┬────────┐
08:00   │ Jean   │        │ Marie  │        │ Paul   │
        │ Conduite│       │ Code   │        │ Conduite│
09:00   ├────────┼────────┼────────┼────────┼────────┤
        │        │ Marc   │        │ Sarah  │        │
10:00   │        │ Conduite│       │ Code   │        │
        └────────┴────────┴────────┴────────┴────────┘
```

Avec :

* vert = conduite ;
* bleu = code ;
* orange = examen ;
* gris = indisponibilité ;
* rouge = conflit.

---

# 18. Badges

Standardiser :

### Actif

🟢

```text
Actif
```

### En attente

🟠

```text
En attente
```

### Suspendu

🔴

```text
Suspendu
```

### Terminé

🔵/gris

```text
Terminé
```

Les badges existants utilisent déjà des formes arrondies et des couleurs sémantiques ; cette logique peut être conservée en la raccordant aux nouveaux tokens. 

---

# 19. Formulaires

Les formulaires doivent être beaucoup plus propres.

```text
Nom
┌─────────────────────────────────────┐
│ Jean                                │
└─────────────────────────────────────┘

Prénom
┌─────────────────────────────────────┐
│ Dupont                              │
└─────────────────────────────────────┘

Téléphone
┌─────────────────────────────────────┐
│ +241 XX XX XX XX                    │
└─────────────────────────────────────┘
```

Focus :

```text
border green
+
ring léger
```

Erreur :

```text
border red
+
message sous le champ
```

---

# 20. Messages système

L'un des objectifs est de corriger le problème que tu avais identifié concernant :

> `auth.failed`, `password.sent`, etc.

On établit donc quatre niveaux :

### Success

```text
✓ Élève ajouté avec succès.
```

### Info

```text
ⓘ Une nouvelle version du dossier est disponible.
```

### Warning

```text
⚠ Le dossier de Jean Dupont est incomplet.
```

### Error

```text
✕ Impossible d'enregistrer le paiement.
```

Le système actuel utilise déjà `session('status')` et `$errors` Laravel avec des bannières ; l'audit recommande justement de généraliser les quatre niveaux. 

---

# 21. Icônes

Utiliser **une seule bibliothèque d'icônes** dans toute l'application.

Je recommande :

> **Heroicons**

Style :

```text
outline
stroke-width: 1.8–2
```

Ne pas mélanger :

```text
Font Awesome
Heroicons
emoji
SVG maison
Bootstrap Icons
```

sans raison.

---

# 22. Illustrations

Les illustrations doivent être rares.

Auto-GestBoard est un **outil de travail**, pas une application de divertissement.

Utiliser les illustrations principalement pour :

* empty states ;
* onboarding ;
* erreurs ;
* landing page ;
* pages de bienvenue.

---

# 23. Empty states

Exemple :

```text
             🚗

        Aucun véhicule

Votre flotte ne contient encore
aucun véhicule.

        [+ Ajouter un véhicule]
```

Le système possède déjà un composant `x-empty-table-row`, ce qui est une bonne base à généraliser. 

---

# 24. Responsive

Trois expériences doivent être conçues :

### Desktop

≥ 1280 px

### Tablet

768–1279 px

### Mobile

< 768 px

Sur mobile :

**ne pas simplement réduire le desktop.**

Exemple :

```text
Tableau desktop
       ↓
Cartes mobiles
```

et pour le planning :

```text
Grille desktop
       ↓
Timeline / journée mobile
```

---

# 25. Animation

Très sobre :

```text
150–200ms
ease-out
```

Animations autorisées :

* hover ;
* ouverture menu ;
* modal ;
* changement d'état ;
* apparition d'une notification ;
* chargement.

Pas de :

❌ animations permanentes
❌ éléments qui sautent
❌ effets flashy.

---

# 26. Logo dans l'application

### Sidebar développée

Utiliser :

**logo horizontal adapté**

### Sidebar réduite

Utiliser :

**`brand 2.png` ou `brand 3.png`**

### Login

Utiliser :

**icône + logo horizontal**

### Favicon

Utiliser :

**icône seule**

### Mode sombre

Utiliser la variante adaptée au fond sombre.

---

# 27. Design system technique

Je recommande que la charte soit traduite en tokens.

Par exemple :

```css
:root {
    --ag-navy-950: 6 28 48;
    --ag-navy-900: 8 37 67;
    --ag-navy-800: 13 53 87;

    --ag-green-700: 8 120 92;
    --ag-green-600: 10 150 111;
    --ag-green-500: 15 175 129;
    --ag-green-400: 49 196 155;

    --ag-bg: 244 247 250;
    --ag-surface: 255 255 255;
    --ag-surface-elevated: 249 251 252;
    --ag-surface-inset: 231 237 242;

    --ag-text: 23 32 43;
    --ag-text-secondary: 100 116 139;
    --ag-text-muted: 148 163 184;

    --ag-border: 220 227 233;

    --ag-success: 21 154 108;
    --ag-warning: 217 139 24;
    --ag-danger: 214 69 69;
    --ag-info: 35 136 181;
}
```

Puis Tailwind doit consommer **ces tokens**.

---

# 28. Remplacement du système actuel

C'est un point important pour l'agent de code.

Le projet possède actuellement :

```css
--color-primary: 79 70 229;
```

soit :

```text
#4F46E5
```

et en sombre :

```css
--color-primary: 108 111 246;
```

soit environ :

```text
#6C6FF6
```

Cela doit disparaître comme **couleur de marque principale**. 

On ne doit pas simplement faire :

```text
purple → green
```

Il faut **refactorer les tokens** afin que :

```text
bg-primary
text-primary
border-primary
ring-primary
shadow-primary
```

aient tous une définition cohérente.

---

# 29. Ce que nous conservons du SaaS actuel

Il ne faut surtout pas tout jeter.

L'audit et le code montrent plusieurs bonnes fondations :

* architecture de tokens CSS ;
* thème clair/sombre ;
* sidebar flottante ;
* composants Blade réutilisables ;
* `x-card` ;
* `x-kpi-card` ;
* `x-badge` ;
* `x-empty-table-row` ;
* `x-planning-grid` ;
* système de notifications ;
* responsive drawer ;
* composants de formulaires. 

**On améliore l'UI, on ne reconstruit pas inutilement l'architecture.**

---

# 30. Ce qui doit être supprimé/refactorisé

### ❌ À supprimer

```text
Indigo #4F46E5 comme couleur primaire
```

### ❌ À éviter

```text
gray-800 / gray-700 / gray-600
```

utilisés directement dans toutes les vues.

À la place :

```text
text-content
text-content-secondary
text-content-muted
bg-surface
bg-surface-elevated
border-border
```

### ❌ À éviter

Classes Tailwind extrêmement longues répétées partout.

Créer des composants.

---

# 31. Composants UI officiels

À terme, le design system doit fournir :

```text
x-button
x-icon-button
x-card
x-kpi-card
x-badge
x-alert
x-input
x-select
x-textarea
x-modal
x-dropdown
x-tabs
x-table
x-pagination
x-empty-state
x-loading-state
x-error-state
x-page-header
x-breadcrumb
x-filter-bar
x-stat-card
x-planning-grid
x-avatar
x-tooltip
```

Cela permettra d'éviter les incohérences entre les dizaines de pages du SaaS.

---

# 32. Principe majeur : hiérarchie visuelle

Chaque page doit respecter :

```text
PAGE
 ↓
HEADER
 ↓
CONTEXT / FILTRES
 ↓
PRIMARY ACTION
 ↓
MAIN CONTENT
 ↓
SECONDARY CONTENT
```

et non :

```text
titre
bouton
card
card
bouton
table
card
texte
bouton
```

sans hiérarchie.

---

# 33. Dashboard par rôle

La charte doit être identique, mais l'expérience doit être différente.

### Gérant/Admin

Focus :

```text
CA
élèves
planning
paiements
examens
flotte
alertes
```

### Moniteur

Focus :

```text
Aujourd'hui
Prochain cours
Élèves
Progression
Planning
```

### Élève

Focus :

```text
Prochaine séance
Progression
Code
Paiements
Dossier
Examens
```

La marque reste la même.

---

# 34. Slogan

Le slogan officiel retenu :

> **GÉREZ. SUIVEZ. FAITES RÉUSSIR.**

Il doit être utilisé principalement :

* landing page ;
* écran de connexion ;
* supports marketing ;
* documents commerciaux.

Pas besoin de le répéter dans le dashboard.

---

# 35. Philosophie de marque

La personnalité visuelle d'Auto-GestBoard doit être :

| Qualité       | Niveau |
| ------------- | ------ |
| Professionnel | ★★★★★  |
| Simple        | ★★★★★  |
| Moderne       | ★★★★☆  |
| Technologique | ★★★★☆  |
| Accessible    | ★★★★★  |
| Dynamique     | ★★★★☆  |
| Ludique       | ★★☆☆☆  |
| Premium       | ★★★★☆  |

Le produit doit donner l'impression :

> **« C'est un logiciel professionnel conçu spécifiquement pour mon auto-école. »**

et non :

> « C'est un template Tailwind adapté. »

---

# 36. Architecture visuelle finale

Je vois donc Auto-GestBoard comme ceci :

```text
                    AUTO-GESTBOARD
                          │
             ┌────────────┴────────────┐
             │                         │
          BLEU NUIT                  VERT
        Confiance                  Action
        Gestion                   Progression
        Stabilité                 Réussite
             │                         │
             └────────────┬────────────┘
                          │
                 SOFT NEUMORPHISM
                          │
              ┌───────────┴───────────┐
              │                       │
         LIGHT MODE               DARK MODE
              │                       │
         Blanc / gris            Bleu nuit
         + bleu/vert             + vert
              │                       │
              └───────────┬───────────┘
                          │
                    SAAS MODERNE
                          │
          ┌───────────────┼───────────────┐
          │               │               │
       Dashboard       Planning         Finance
          │               │               │
       Students        Formation         CRM
          │               │               │
        Fleet          Exams           Reports
```

---

# 37. Directive à donner à l'agent de code

Une fois cette charte validée, je te conseille de donner **un prompt séparé** à l'agent, plutôt que de mélanger charte graphique et développement fonctionnel.

Voici la directive principale :

> **À partir de maintenant, cette charte graphique constitue la source de vérité visuelle d'Auto-GestBoard.**
>
> Analyse les assets présents dans `brand-images` et utilise-les comme identité officielle de la marque. Le logo principal, les icônes, les variantes pour fonds clairs/sombres et les versions monochromes doivent être utilisés selon leur destination.
>
> Refactorise le design system existant d'Auto-GestBoard afin de remplacer l'actuelle identité indigo/violette par l'identité officielle **bleu nuit `#082543` + vert `#0FAF81`**.
>
> Ne crée pas un nouveau système UI parallèle. Fais évoluer les tokens CSS/Tailwind et les composants Blade existants.
>
> Le style cible est :
>
> **Soft Neumorphism maîtrisé + SaaS moderne + identité Auto-GestBoard.**
>
> Le neumorphisme doit servir uniquement à créer une hiérarchie de surfaces, avec des ombres légères et des contrastes accessibles. Aucun écran ne doit devenir excessivement neumorphique.
>
> Implémente obligatoirement :
>
> * palette officielle ;
> * tokens light/dark ;
> * typographie uniforme ;
> * boutons ;
> * badges ;
> * cartes ;
> * KPI ;
> * formulaires ;
> * tableaux ;
> * alertes ;
> * modales ;
> * dropdowns ;
> * navigation ;
> * sidebar ;
> * topbar ;
> * pagination ;
> * empty states ;
> * loading states ;
> * error states ;
> * planning ;
> * responsive mobile/tablette/desktop.
>
> Toutes les pages existantes doivent consommer les composants et tokens du design system plutôt que définir leurs propres couleurs.
>
> **Ne modifie pas la logique métier, les routes, les Policies, le multi-tenancy ou les workflows fonctionnels sauf nécessité absolue liée à l'UI.**
>
> La landing page existante doit  recevoir les ajustements nécessaires pour utiliser correctement les assets officiels.
>
> L'objectif final est que l'utilisateur puisse passer de `Dashboard → Élèves → Planning → Finance → Flotte → CRM → Paramètres` sans avoir l'impression de changer d'application.

---
**toute nouvelle page respec`te automatiquement la même identité visuelle**.
