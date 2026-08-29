# Charte Graphique — Design System Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. Also load `frontend-design` for visual judgement calls within a task.

**Goal:** Replace Auto-GestBoard's indigo/violet visual identity with the official **navy `#082543` + green `#0FAF81`** brand identity by evolving the existing CSS-token / Tailwind / Blade-component design system in place — no parallel UI system.

**Architecture:** The authenticated app already has a token layer (`--color-*` CSS custom properties in `resources/css/app.css`, consumed by `tailwind.config.js` as `rgb(var(--x) / <alpha-value>)` colours) and a set of reusable Blade components. This refactor: (1) rewrites the token **values** and adds brand + semantic scales, keeping every token **name**; (2) recolours/consolidates the existing components; (3) reworks the app shell (sidebar, topbar, drawer, layouts) for brand fit and the §12 navigation grouping; (4) adds the missing design-system components; (5) lightly repoints the landing page to the official logo assets. A grep-based Pest guard test locks out re-introduction of legacy styles.

**Tech Stack:** Laravel 12, Blade, Tailwind CSS v3 (`darkMode: 'class'`), Alpine.js v3, `@tailwindcss/forms`, Vite, Pest v3. Fonts via `fonts.bunny.net` (Inter).

**Spec:** `docs/features/charte-graphique.md` — the plan argues from this document; executors read both. Section numbers below (`§7`, `§12`, …) refer to it.

## Global Constraints

- **Brand primary = green `#0FAF81`** (`--color-primary`). Navy `#082543` is `--color-secondary` (navigation, secondary buttons, trust elements, dark surfaces). Indigo `#4F46E5` / `#6C6FF6` must not survive anywhere as a brand colour.
- **Keep every existing token name** (`--color-background`, `--color-surface`, `--color-surface-elevated`, `--color-surface-inset`, `--color-content`, `--color-content-secondary`, `--color-content-muted`, `--color-border`, `--color-primary`, `--color-primary-content`, `--color-success`, `--color-warning`, `--color-danger`, `--color-info`, `--radius-*`, `--shadow-*`). Add new names, never rename. Do **not** introduce the `--ag-*` prefix from spec §27 (illustrative only) — it would break `tailwind.config.js` and every component.
- **Semantic ≠ brand** (§4): `--color-success` (`#159A6C`) is distinct from brand green `#0FAF81`. Use brand green for CTA / progression / brand accents; use `success` for "payment succeeded / dossier validated / exam passed".
- **Dark mode is navy, not inverted** (§6): navy environment + green accent. Toggled by `.dark` class on `<html>` (see `resources/views/components/theme-init-script.blade.php`). Domain Blade views must **not** use `dark:` variants — the token swap handles it. `dark:` is allowed only in `resources/views/welcome.blade.php`, `resources/views/components/landing/**`, and `resources/views/components/brand/**`.
- **No raw palette classes in domain views** (§30): no `text-gray-700`, `bg-white`, `border-gray-200`, no hex colours in `class="…"`, no `indigo/violet/purple`. Use `text-content`/`text-content-secondary`/`text-content-muted`, `bg-surface`/`bg-surface-elevated`, `border-border`.
- **One icon set**: the `<x-icon>` Heroicons-outline set (24×24, stroke 1.5). No emoji as UI icons (§8, §21). Emoji already present as decorative glyphs in empty-state copy may stay only if replaced by `<x-icon>` per §8 — prefer `<x-icon>`.
- **No arrow glyphs** anywhere (`←`, `→`, `&larr;`, `&rarr;`): use `<x-icon name="chevron-left|chevron-right">`.
- **Do not touch** business logic, routes, Policies, multi-tenancy, workflows (spec §37) except where a component's PHP signature must change.
- **Brand asset reality:** the 8 files in `brand-images/` are **JPEG** (opaque, no transparency) despite `.png` names — `brand 1` 950×256 (horizontal, light bg), `brand 7/8` on navy/green bg, `brand 2–6` small icon crops. Use each variant only on its matching background. Note for the user: a transparent SVG/PNG master would remove the theme-switching workaround in Task 4.
- After any PHP change run `vendor/bin/pint --dirty --format agent`. Tests target the separate MySQL test DB (`phpunit.xml`) — never migrate the dev DB.
- `npm run build` after CSS/Tailwind/asset changes so the reviewer can see the result.

---

## File Structure

**Token layer**
- `resources/css/app.css` — MODIFY: rewrite `:root` and `:root.dark` blocks; add `--brand-navy-*`, `--brand-green-*` scales, `--color-secondary*`, `--radius-xs`, `--radius-pill`; retune shadows (green focus ring).
- `tailwind.config.js` — MODIFY: add `brand-navy`, `brand-green`, `secondary` colours; add `ui-xs`, `ui-pill` radii. Landing palette block untouched.

**Brand**
- `public/images/brand/*.jpg` — CREATE (copied from `brand-images/`).
- `resources/views/components/brand/logo.blade.php` — CREATE: context + theme aware `<img>` lockup.
- `resources/views/components/application-logo.blade.php` — MODIFY: keep as the generic inline SVG mark (icon-only fallback), recolour usages via token.
- `resources/views/layouts/guest.blade.php`, `resources/views/layouts/app.blade.php`, `resources/views/layouts/partials/sidebar.blade.php`, `resources/views/layouts/partials/mobile-drawer.blade.php`, `resources/views/components/error-page.blade.php` — MODIFY: swap logo, favicon.
- `resources/views/welcome.blade.php` — MODIFY: repoint `images/logo.png` references to official asset.

**Components — recolour / consolidate**
- CREATE: `button.blade.php`, `icon-button.blade.php`, `page-header.blade.php`, `filter-bar.blade.php`, `empty-state.blade.php`, `loading-state.blade.php`, `error-state.blade.php`, `avatar.blade.php`, `tooltip.blade.php`, `select.blade.php`, `textarea.blade.php`, `pagination.blade.php` (all under `resources/views/components/`).
- MODIFY: `card`, `kpi-card`, `badge`, `alert`, `sidebar-link`, `tabs`, `text-input`, `input-label`, `input-error`, `modal`, `dialog`, `empty-table-row`, `planning-grid`, `planning-session-card`, `nav-link`, `responsive-nav-link`, `theme-toggle`, `primary-button`, `secondary-button`, `danger-button`.

**Shell**
- `resources/views/layouts/partials/sidebar-nav.blade.php`, `topbar.blade.php` — MODIFY: §12 grouping, green active state, tokens.

**i18n / system messages**
- `lang/en/auth.php`, `lang/en/passwords.php`, `lang/en/validation.php`, `lang/en/pagination.php` + `lang/fr/*` — CREATE via `php artisan lang:publish` then translate `fr`.
- `config/app.php` + `.env` — MODIFY: `APP_LOCALE=fr`.

**Tests**
- `tests/Feature/DesignSystem/NoLegacyStylesTest.php` — CREATE (grep guard).
- `tests/Feature/DesignSystem/DesignTokensTest.php` — CREATE (token presence).
- `tests/Feature/DesignSystem/ComponentRenderTest.php` — CREATE (`Blade::render` assertions).
- `tests/Feature/DesignSystem/PublicPagesRenderTest.php` — CREATE (welcome + login smoke).

---

## Task 1: CSS design tokens

**Files:**
- Modify: `resources/css/app.css`
- Test: `tests/Feature/DesignSystem/DesignTokensTest.php`

**Interfaces:**
- Produces: CSS custom properties consumed by Task 2. Names unchanged; new names: `--brand-navy-950|900|800|700`, `--brand-green-700|600|500|400|100`, `--color-secondary`, `--color-secondary-content`, `--radius-xs`, `--radius-pill`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/DesignSystem/DesignTokensTest.php`:

```php
<?php

use Illuminate\Support\Facades\File;

$css = fn () => File::get(resource_path('css/app.css'));

it('defines the official brand navy + green scales', function () use ($css) {
    expect($css())
        ->toContain('--brand-navy-900: 8 37 67')      // #082543
        ->toContain('--brand-green-500: 15 175 129')  // #0FAF81
        ->toContain('--brand-green-100: 221 247 239'); // #DDF7EF
});

it('maps primary to brand green and secondary to brand navy in light mode', function () use ($css) {
    expect($css())
        ->toContain('--color-primary: 15 175 129')
        ->toContain('--color-secondary: 8 37 67');
});

it('uses a navy background in dark mode, not an inverted grey', function () use ($css) {
    // #061522
    expect($css())->toContain('--color-background: 6 21 34');
});

it('keeps semantic success distinct from brand green', function () use ($css) {
    // #159A6C, not 15 175 129
    expect($css())->toContain('--color-success: 21 154 108');
});

it('no longer contains the legacy indigo primary', function () use ($css) {
    expect($css())
        ->not->toContain('79 70 229')   // #4F46E5
        ->not->toContain('108 111 246'); // #6C6FF6
});

it('exposes the normalised radius scale', function () use ($css) {
    expect($css())
        ->toContain('--radius-xs:')
        ->toContain('--radius-pill: 9999px');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DesignTokensTest`
Expected: FAIL — assertions on `--brand-navy-900` / `--color-secondary` etc. not found.

- [ ] **Step 3: Rewrite the token blocks**

Replace the entire `:root { … }` and `:root.dark { … }` blocks in `resources/css/app.css` (keep the file's leading `@tailwind` lines, `[x-cloak]` rule, and the explanatory comment block — update the comment's "indigo" wording to "brand navy + green") with:

```css
:root {
    /* Official brand scales (spec §3) — RGB triplets for Tailwind alpha */
    --brand-navy-950: 6 28 48;
    --brand-navy-900: 8 37 67;
    --brand-navy-800: 13 53 87;
    --brand-navy-700: 22 70 109;

    --brand-green-700: 8 120 92;
    --brand-green-600: 10 150 111;
    --brand-green-500: 15 175 129;
    --brand-green-400: 49 196 155;
    --brand-green-100: 221 247 239;

    /* Light theme (spec §5) */
    --color-background: 244 247 250;
    --color-surface: 255 255 255;
    --color-surface-elevated: 249 251 252;
    --color-surface-inset: 231 237 242;

    --color-content: 23 32 43;
    --color-content-secondary: 100 116 139;
    --color-content-muted: 148 163 184;

    --color-border: 220 227 233;

    --color-primary: 15 175 129;          /* brand green — CTA / active */
    --color-primary-content: 255 255 255;
    --color-secondary: 8 37 67;           /* brand navy — nav / secondary btn */
    --color-secondary-content: 255 255 255;

    /* Semantic — deliberately NOT the brand green (spec §4) */
    --color-success: 21 154 108;          /* #159A6C */
    --color-warning: 217 139 24;          /* #D98B18 */
    --color-danger: 214 69 69;            /* #D64545 */
    --color-info: 35 136 181;             /* #2388B5 */

    /* Radius scale (spec §9) */
    --radius-xs: 0.375rem;   /* 6px  */
    --radius-sm: 0.625rem;   /* 10px */
    --radius-md: 0.875rem;   /* 14px */
    --radius-lg: 1.125rem;   /* 18px */
    --radius-xl: 1.5rem;     /* 24px */
    --radius-pill: 9999px;

    /* Soft neumorphism — lighter than before (spec §7, §8) */
    --shadow-soft-sm: -2px -2px 6px rgba(255, 255, 255, 0.9), 3px 3px 8px rgba(15, 35, 60, 0.10);
    --shadow-soft: -4px -4px 10px rgba(255, 255, 255, 0.85), 6px 6px 14px rgba(15, 35, 60, 0.12);
    --shadow-soft-hover: -5px -5px 12px rgba(255, 255, 255, 0.9), 8px 8px 18px rgba(15, 35, 60, 0.14);
    --shadow-inset: inset 2px 2px 5px rgba(15, 35, 60, 0.10), inset -2px -2px 5px rgba(255, 255, 255, 0.8);
    --shadow-inset-focus:
        inset 1px 1px 3px rgba(15, 35, 60, 0.08),
        0 0 0 3px rgba(15, 175, 129, 0.35);
}

:root.dark {
    /* Dark theme = navy environment + green accent (spec §6) */
    --color-background: 6 21 34;          /* #061522 */
    --color-surface: 8 37 67;             /* #082543 */
    --color-surface-elevated: 13 48 77;  /* #0D304D */
    --color-surface-inset: 4 16 28;      /* #04101C */

    --color-content: 241 245 249;
    --color-content-secondary: 168 182 197;
    --color-content-muted: 113 134 154;

    --color-border: 24 58 85;            /* #183A55 */

    --color-primary: 15 175 129;
    --color-primary-content: 255 255 255;
    --color-secondary: 226 232 240;      /* light — navy outline is invisible on navy */
    --color-secondary-content: 8 37 67;

    --color-success: 45 191 130;
    --color-warning: 224 160 45;
    --color-danger: 240 105 105;
    --color-info: 66 170 209;

    --shadow-soft-sm: -3px -3px 8px rgba(255, 255, 255, 0.02), 3px 3px 8px rgba(0, 0, 0, 0.45);
    --shadow-soft: -4px -4px 12px rgba(255, 255, 255, 0.025), 6px 6px 16px rgba(0, 0, 0, 0.5);
    --shadow-soft-hover: -5px -5px 14px rgba(255, 255, 255, 0.03), 8px 8px 20px rgba(0, 0, 0, 0.55);
    --shadow-inset: inset 2px 2px 6px rgba(0, 0, 0, 0.5), inset -2px -2px 6px rgba(255, 255, 255, 0.015);
    --shadow-inset-focus:
        inset 1px 1px 3px rgba(0, 0, 0, 0.4),
        0 0 0 3px rgba(15, 175, 129, 0.4);
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=DesignTokensTest`
Expected: PASS (all 6).

- [ ] **Step 5: Build & commit**

```bash
npm run build
git add resources/css/app.css tests/Feature/DesignSystem/DesignTokensTest.php
git commit -m "feat(design-system): official navy + green token values"
```

---

## Task 2: Tailwind config — brand scales & radii

**Files:**
- Modify: `tailwind.config.js`
- Test: `tests/Feature/DesignSystem/DesignTokensTest.php` (extend)

**Interfaces:**
- Consumes: Task 1 CSS vars.
- Produces: Tailwind classes `bg-brand-navy-900`, `text-brand-green-500`, `bg-brand-green-100`, `bg-secondary`, `text-secondary-content`, `rounded-ui-xs`, `rounded-ui-pill`. `primary`, `content`, `surface*`, `border`, `success|warning|danger|info` unchanged.

- [ ] **Step 1: Add the failing assertion**

Append to `tests/Feature/DesignSystem/DesignTokensTest.php`:

```php
it('registers brand + secondary colours and the full radius scale in tailwind config', function () {
    $config = File::get(base_path('tailwind.config.js'));

    expect($config)
        ->toContain("'brand-navy'")
        ->toContain("'brand-green'")
        ->toContain('secondary:')
        ->toContain("'ui-xs'")
        ->toContain("'ui-pill'");
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=DesignTokensTest`
Expected: FAIL on the new case.

- [ ] **Step 3: Edit `tailwind.config.js`**

Inside `theme.extend.colors`, **after** the existing `info: …` line and before the closing `}` of `colors`, add:

```js
            'brand-navy': {
                950: 'rgb(var(--brand-navy-950) / <alpha-value>)',
                900: 'rgb(var(--brand-navy-900) / <alpha-value>)',
                800: 'rgb(var(--brand-navy-800) / <alpha-value>)',
                700: 'rgb(var(--brand-navy-700) / <alpha-value>)',
            },
            'brand-green': {
                100: 'rgb(var(--brand-green-100) / <alpha-value>)',
                400: 'rgb(var(--brand-green-400) / <alpha-value>)',
                500: 'rgb(var(--brand-green-500) / <alpha-value>)',
                600: 'rgb(var(--brand-green-600) / <alpha-value>)',
                700: 'rgb(var(--brand-green-700) / <alpha-value>)',
            },
            secondary: {
                DEFAULT: 'rgb(var(--color-secondary) / <alpha-value>)',
                content: 'rgb(var(--color-secondary-content) / <alpha-value>)',
            },
```

Replace the whole `borderRadius` block with:

```js
            borderRadius: {
                'ui-xs': 'var(--radius-xs)',
                'ui-sm': 'var(--radius-sm)',
                'ui-md': 'var(--radius-md)',
                'ui-lg': 'var(--radius-lg)',
                'ui-xl': 'var(--radius-xl)',
                'ui-pill': 'var(--radius-pill)',
            },
```

Leave the landing palette (`ink`, `route`, `signal`, `cream`, `paper`, `slate`, `line`, `asphalt`) and `fontFamily` / `boxShadow` / `keyframes` / `animation` exactly as they are.

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test --compact --filter=DesignTokensTest`
Expected: PASS.

- [ ] **Step 5: Build & commit**

```bash
npm run build
git add tailwind.config.js tests/Feature/DesignSystem/DesignTokensTest.php
git commit -m "feat(design-system): brand-navy/brand-green Tailwind scales + radius scale"
```

---

## Task 3: Legacy-style guard test

**Files:**
- Create: `tests/Feature/DesignSystem/NoLegacyStylesTest.php`

**Interfaces:**
- Produces: a red test listing every Blade view still using forbidden styles. Tasks 4–11 drive it green; Task 12 mops up stragglers.

- [ ] **Step 1: Write the test**

```php
<?php

use Illuminate\Support\Facades\File;

/**
 * @return \Illuminate\Support\Collection<int, \SplFileInfo>
 */
function domainBladeViews(): \Illuminate\Support\Collection
{
    // The landing page and its components keep their own separate palette
    // (route/signal/asphalt) on purpose — see resources/css/app.css header.
    $allowed = ['welcome.blade.php'];

    return collect(File::allFiles(resource_path('views')))
        ->filter(fn ($f) => str_ends_with($f->getFilename(), '.blade.php'))
        ->reject(fn ($f) => str_contains($f->getPathname(), '/components/landing/'))
        ->reject(fn ($f) => str_contains($f->getPathname(), '/components/brand/'))
        ->reject(fn ($f) => in_array($f->getFilename(), $allowed, true));
}

function offenders(string $pattern): \Illuminate\Support\Collection
{
    return domainBladeViews()
        ->filter(fn ($f) => preg_match($pattern, File::get($f->getPathname())))
        ->map(fn ($f) => str_replace(resource_path('views').'/', '', $f->getPathname()))
        ->values();
}

it('has no indigo / violet / purple utility classes', function () {
    $found = offenders('/\b(indigo|violet|purple)-\d{2,3}\b/');
    expect($found)->toBeEmpty($found->implode("\n"));
});

it('has no raw Tailwind grey palette classes', function () {
    $found = offenders('/\b(?:bg|text|border|ring|divide|from|to|via)-(?:gray|slate|zinc|neutral|stone)-\d{2,3}\b/');
    expect($found)->toBeEmpty($found->implode("\n"));
});

it('has no bg-white / text-black / bg-black literals', function () {
    $found = offenders('/\b(?:bg-white|text-black|bg-black)\b/');
    expect($found)->toBeEmpty($found->implode("\n"));
});

it('has no dark: variants (the token swap handles dark mode)', function () {
    $found = offenders('/\bdark:[a-z-]/');
    expect($found)->toBeEmpty($found->implode("\n"));
});

it('has no hex colours inside class attributes', function () {
    $found = offenders('/class="[^"]*#[0-9a-fA-F]{3,6}[^"]*"/');
    expect($found)->toBeEmpty($found->implode("\n"));
});

it('has no arrow glyphs', function () {
    $found = offenders('/\x{2190}|\x{2192}|&larr;|&rarr;/u');
    expect($found)->toBeEmpty($found->implode("\n"));
});
```

- [ ] **Step 2: Run — expect failure, capture the list**

Run: `php artisan test --compact --filter=NoLegacyStylesTest`
Expected: FAIL. Known offenders at this point: `components/nav-link.blade.php`, `components/responsive-nav-link.blade.php`, `components/modal.blade.php`, `components/theme-toggle.blade.php`, `profile/partials/*.blade.php`, and any straggler domain views. Record the full list in the commit body.

- [ ] **Step 3: Commit the (failing) guard**

```bash
git add tests/Feature/DesignSystem/NoLegacyStylesTest.php
git commit -m "test(design-system): guard against legacy indigo/grey/dark: styles

Currently RED — driven green by the component + shell + sweep tasks."
```

---

## Task 4: Brand assets & `<x-brand-logo>`

**Files:**
- Create: `public/images/brand/logo-horizontal-light.jpg`, `icon.jpg`, `icon-square.jpg`, `icon-circle.jpg`, `logo-mono-blue.jpg`, `logo-mono-black.jpg`, `logo-horizontal-on-navy.jpg`, `logo-horizontal-on-green.jpg`, `og.jpg`
- Create: `resources/views/components/brand/logo.blade.php`
- Modify: `resources/views/layouts/guest.blade.php`, `resources/views/layouts/partials/sidebar.blade.php`, `resources/views/layouts/partials/mobile-drawer.blade.php`, `resources/views/components/error-page.blade.php`
- Test: `tests/Feature/DesignSystem/ComponentRenderTest.php`

**Interfaces:**
- Produces: `<x-brand-logo variant="full|icon|mono" on="light|navy|green" class="…" />` → renders `<img>` with `alt` = app name. `variant="full"` auto-swaps light/navy image by theme via `hidden`/`dark:block` (allow-listed path).

- [ ] **Step 1: Copy the raster assets**

```bash
mkdir -p public/images/brand
cp "brand-images/brand 1.png" public/images/brand/logo-horizontal-light.jpg
cp "brand-images/brand 2.png" public/images/brand/icon.jpg
cp "brand-images/brand 3.png" public/images/brand/icon-square.jpg
cp "brand-images/brand 4.png" public/images/brand/icon-circle.jpg
cp "brand-images/brand 5.png" public/images/brand/logo-mono-blue.jpg
cp "brand-images/brand 6.png" public/images/brand/logo-mono-black.jpg
cp "brand-images/brand 7.png" public/images/brand/logo-horizontal-on-navy.jpg
cp "brand-images/brand 8.png" public/images/brand/logo-horizontal-on-green.jpg
cp "brand-images/brand.png"   public/images/brand/og.jpg
```

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/DesignSystem/ComponentRenderTest.php`:

```php
<?php

use Illuminate\Support\Facades\Blade;

it('renders the horizontal brand lockup for light and dark surfaces', function () {
    $html = Blade::render('<x-brand-logo variant="full" class="h-8" />');

    expect($html)
        ->toContain('images/brand/logo-horizontal-light.jpg')
        ->toContain('images/brand/logo-horizontal-on-navy.jpg')
        ->toContain('alt="'.config('app.name').'"');
});

it('renders the icon-only brand mark', function () {
    $html = Blade::render('<x-brand-logo variant="icon" class="h-8 w-8" />');

    expect($html)
        ->toContain('images/brand/icon.jpg')
        ->toContain('h-8 w-8');
});
```

- [ ] **Step 3: Run — expect failure**

Run: `php artisan test --compact --filter=ComponentRenderTest`
Expected: FAIL — component `brand.logo` not found.

- [ ] **Step 4: Create `resources/views/components/brand/logo.blade.php`**

```blade
@props([
    'variant' => 'full',       // full | icon | mono
    'on' => 'light',           // light | navy | green  (only used for variant="full")
])

@php
    $name = config('app.name', 'Auto-GestBoard');

    // JPEG assets are opaque: each variant only reads well on its own
    // background. For variant="full" on a themeable surface we ship both
    // the light-bg and navy-bg lockups and let the .dark class pick.
    $icon = 'images/brand/icon.jpg';
    $mono = 'images/brand/logo-mono-blue.jpg';
    $onGreen = 'images/brand/logo-horizontal-on-green.jpg';
    $light = 'images/brand/logo-horizontal-light.jpg';
    $navy = 'images/brand/logo-horizontal-on-navy.jpg';
@endphp

@if ($variant === 'icon')
    <img src="{{ asset($icon) }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => 'h-8 w-8 rounded-ui-sm object-contain']) }} />
@elseif ($variant === 'mono')
    <img src="{{ asset($mono) }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => 'h-8 w-auto object-contain']) }} />
@elseif ($on === 'green')
    <img src="{{ asset($onGreen) }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => 'h-8 w-auto object-contain']) }} />
@elseif ($on === 'navy')
    <img src="{{ asset($navy) }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => 'h-8 w-auto object-contain']) }} />
@else
    {{-- Themed surface: light-bg lockup in light mode, navy-bg lockup in dark mode --}}
    <img src="{{ asset($light) }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => 'h-8 w-auto object-contain block dark:hidden']) }} />
    <img src="{{ asset($navy) }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => 'h-8 w-auto object-contain hidden dark:block']) }} />
@endif
```

- [ ] **Step 5: Swap logo usages in the shell**

In `resources/views/layouts/partials/sidebar.blade.php` and `resources/views/layouts/partials/mobile-drawer.blade.php`, replace the `<x-application-logo … />` + adjacent `<span … >AutoGest</span>` with:

```blade
<x-brand-logo variant="full" x-show="!collapsed" x-cloak class="h-7 w-auto" />
<x-brand-logo variant="icon" x-show="collapsed" x-cloak class="h-8 w-8" />
```

(In `mobile-drawer.blade.php` there is no `collapsed` state for the header — just use `<x-brand-logo variant="full" class="h-7 w-auto" />`.)

In `resources/views/layouts/guest.blade.php` replace the logo `<a>` block's inner markup with:

```blade
<x-brand-logo variant="full" class="h-9 w-auto" />
```

In `resources/views/components/error-page.blade.php` replace `<x-application-logo … />` with `<x-brand-logo variant="icon" class="h-10 w-10" />`.

- [ ] **Step 6: Favicon**

In `resources/views/layouts/app.blade.php` and `resources/views/layouts/guest.blade.php`, add inside `<head>` (after `<title>`):

```blade
<link rel="icon" type="image/jpeg" href="{{ asset('images/brand/icon.jpg') }}">
```

- [ ] **Step 7: Run tests**

Run: `php artisan test --compact --filter="ComponentRenderTest|NoLegacyStylesTest"`
Expected: `ComponentRenderTest` PASS. `NoLegacyStylesTest` still red (other files) — fine.

- [ ] **Step 8: Pint, build, commit**

```bash
vendor/bin/pint --dirty --format agent
npm run build
git add public/images/brand resources/views/components/brand tests/Feature/DesignSystem/ComponentRenderTest.php \
        resources/views/layouts resources/views/components/error-page.blade.php
git commit -m "feat(brand): official Auto-GestBoard logo assets + x-brand-logo"
```

---

## Task 5: Unified `<x-button>` + retire the three button files

**Files:**
- Create: `resources/views/components/button.blade.php`, `resources/views/components/icon-button.blade.php`
- Modify: `resources/views/components/primary-button.blade.php`, `resources/views/components/secondary-button.blade.php`, `resources/views/components/danger-button.blade.php`
- Test: `tests/Feature/DesignSystem/ComponentRenderTest.php` (extend)

**Interfaces:**
- Consumes: `primary`/`secondary` tokens (Tasks 1–2).
- Produces:
  - `<x-button variant="primary|secondary|ghost|danger" type="submit|button" :href="null">` — renders `<button>`, or `<a>` when `href` given.
  - `<x-icon-button icon="plus" label="Ajouter" variant="primary|secondary|ghost|danger" :href="null" />` — square icon-only button with `aria-label`.
  - `x-primary-button` / `x-secondary-button` / `x-danger-button` remain as 1-line aliases delegating to `<x-button>` (no call-site churn).

- [ ] **Step 1: Extend the test**

Append to `ComponentRenderTest.php`:

```php
it('renders the primary button with the brand-green primary token', function () {
    $html = Blade::render('<x-button variant="primary">Ajouter un élève</x-button>');
    expect($html)
        ->toContain('bg-primary')
        ->toContain('text-primary-content')
        ->toContain('<button');
});

it('renders the secondary button as a navy outline', function () {
    $html = Blade::render('<x-button variant="secondary">Annuler</x-button>');
    expect($html)
        ->toContain('border-secondary')
        ->toContain('text-secondary');
});

it('renders as an anchor when href is provided', function () {
    $html = Blade::render('<x-button variant="primary" href="/students/create">Nouveau</x-button>');
    expect($html)->toContain('<a')->toContain('href="/students/create"');
});

it('keeps the legacy x-primary-button alias working', function () {
    $html = Blade::render('<x-primary-button>Enregistrer</x-primary-button>');
    expect($html)->toContain('bg-primary')->toContain('text-primary-content');
});

it('renders an icon-button with an accessible label', function () {
    $html = Blade::render('<x-icon-button icon="plus" label="Ajouter" variant="primary" />');
    expect($html)->toContain('aria-label="Ajouter"')->toContain('bg-primary');
});
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --compact --filter=ComponentRenderTest`
Expected: FAIL on the new cases.

- [ ] **Step 3: Create `resources/views/components/button.blade.php`**

```blade
@props([
    'variant' => 'primary',   // primary | secondary | ghost | danger
    'href' => null,
    'type' => 'submit',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-ui-sm text-sm font-semibold '
        .'focus:outline-none focus-visible:shadow-inset-focus disabled:opacity-50 disabled:pointer-events-none transition';

    $variants = [
        'primary' => 'bg-primary text-primary-content shadow-soft-sm hover:shadow-soft active:shadow-inset',
        'secondary' => 'bg-transparent border border-secondary text-secondary hover:bg-secondary/5 active:shadow-inset',
        'ghost' => 'bg-transparent font-medium text-content-secondary hover:text-content hover:bg-surface-elevated',
        'danger' => 'bg-danger text-white shadow-soft-sm hover:shadow-soft active:shadow-inset',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</{{ $tag }}>
```

- [ ] **Step 4: Create `resources/views/components/icon-button.blade.php`**

```blade
@props([
    'icon',
    'label',
    'variant' => 'ghost',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex h-10 w-10 items-center justify-center rounded-ui-sm '
        .'focus:outline-none focus-visible:shadow-inset-focus disabled:opacity-50 disabled:pointer-events-none transition';

    $variants = [
        'primary' => 'bg-primary text-primary-content shadow-soft-sm hover:shadow-soft',
        'secondary' => 'bg-surface text-content-secondary shadow-soft-sm hover:text-content hover:shadow-soft',
        'ghost' => 'bg-transparent text-content-secondary hover:text-content hover:bg-surface-elevated',
        'danger' => 'bg-danger text-white shadow-soft-sm hover:shadow-soft',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['ghost']);
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    aria-label="{{ $label }}"
    {{ $attributes->merge(['class' => $classes]) }}
>
    <x-icon :name="$icon" class="w-5 h-5" />
</{{ $tag }}>
```

- [ ] **Step 5: Convert the three legacy button files to aliases**

`resources/views/components/primary-button.blade.php`:

```blade
<x-button variant="primary" {{ $attributes }}>{{ $slot }}</x-button>
```

`resources/views/components/secondary-button.blade.php`:

```blade
<x-button variant="secondary" type="button" {{ $attributes }}>{{ $slot }}</x-button>
```

`resources/views/components/danger-button.blade.php`:

```blade
<x-button variant="danger" {{ $attributes }}>{{ $slot }}</x-button>
```

- [ ] **Step 6: Run tests**

Run: `php artisan test --compact --filter=ComponentRenderTest`
Expected: PASS.

- [ ] **Step 7: Pint & commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/components/button.blade.php resources/views/components/icon-button.blade.php \
        resources/views/components/primary-button.blade.php resources/views/components/secondary-button.blade.php \
        resources/views/components/danger-button.blade.php tests/Feature/DesignSystem/ComponentRenderTest.php
git commit -m "feat(design-system): unified x-button + x-icon-button (primary=green, secondary=navy)"
```

---

## Task 6: `<x-alert>` four levels + French system messages

**Files:**
- Modify: `resources/views/components/alert.blade.php`
- Create: `lang/en/*.php` + `lang/fr/*.php` via `php artisan lang:publish`
- Modify: `config/app.php` (`'locale' => env('APP_LOCALE', 'fr')`), `.env` (`APP_LOCALE=fr`), `.env.example`
- Test: `tests/Feature/DesignSystem/ComponentRenderTest.php` (extend), `tests/Feature/DesignSystem/PublicPagesRenderTest.php`

**Interfaces:**
- Consumes: `success`/`warning`/`danger`/`info` tokens.
- Produces: `<x-alert variant="success|info|warning|error" :title="null" :dismissible="false">` — icon + optional bold title + slot body. `error` is an alias of `danger`.

- [ ] **Step 1: Extend `ComponentRenderTest.php`**

```php
it('renders each of the four alert levels with a matching icon', function () {
    foreach ([
        'success' => 'document-check',
        'info' => 'bell',
        'warning' => 'exclamation-triangle',
        'error' => 'x-mark',
    ] as $variant => $icon) {
        $html = Blade::render('<x-alert variant="'.$variant.'" title="T">Message</x-alert>');
        expect($html)->toContain($icon)->toContain('T');
    }
});
```

- [ ] **Step 2: Create `PublicPagesRenderTest.php`**

```php
<?php

it('renders the landing page', function () {
    $this->get('/')->assertOk()->assertSee('Auto-GestBoard', escape: false);
});

it('renders the login page with a wrong-credentials message in French', function () {
    $this->get('/login')->assertOk();

    $this->post('/login', ['email' => 'nobody@example.test', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    expect(trans('auth.failed'))
        ->not->toBe('auth.failed')
        ->toContain('identifiants');
});
```

- [ ] **Step 3: Run — expect failure**

Run: `php artisan test --compact --filter="ComponentRenderTest|PublicPagesRenderTest"`
Expected: FAIL — alert icons/title missing; `trans('auth.failed')` returns the key.

- [ ] **Step 4: Publish & translate lang files**

```bash
php artisan lang:publish
```

Then create `lang/fr/auth.php`:

```php
<?php

return [
    'failed' => 'Ces identifiants ne correspondent à aucun compte.',
    'password' => 'Le mot de passe est incorrect.',
    'throttle' => 'Trop de tentatives de connexion. Veuillez réessayer dans :seconds secondes.',
];
```

`lang/fr/passwords.php`:

```php
<?php

return [
    'reset' => 'Votre mot de passe a été réinitialisé.',
    'sent' => 'Un lien de réinitialisation vient de vous être envoyé par e-mail.',
    'throttled' => 'Veuillez patienter avant de réessayer.',
    'token' => 'Ce jeton de réinitialisation est invalide.',
    'user' => "Aucun compte ne correspond à cette adresse e-mail.",
];
```

`lang/fr/pagination.php`:

```php
<?php

return [
    'previous' => 'Précédent',
    'next' => 'Suivant',
];
```

For `lang/fr/validation.php`: copy `lang/en/validation.php`, then translate the top-level rule messages that actually surface in the UI (`required`, `email`, `unique`, `min`, `max`, `confirmed`, `numeric`, `date`, `exists`, `in`, `image`, `mimes`, `file`) to French. Leave the `attributes` and `custom` arrays as `[]` (project convention — validation messages elsewhere are inline). Keep `lang/en/*` as the published English originals.

- [ ] **Step 5: Set the locale**

`config/app.php`: change `'locale' => env('APP_LOCALE', 'en'),` to `'locale' => env('APP_LOCALE', 'fr'),`.
`.env` and `.env.example`: set `APP_LOCALE=fr` (add the line if absent). Run `php artisan config:clear`.

- [ ] **Step 6: Rewrite `resources/views/components/alert.blade.php`**

```blade
@props([
    'variant' => 'info',       // success | info | warning | error
    'title' => null,
    'dismissible' => false,
])

@php
    $variant = $variant === 'danger' ? 'error' : $variant;

    $map = [
        'success' => ['classes' => 'bg-success/10 text-success', 'icon' => 'document-check'],
        'info' => ['classes' => 'bg-info/10 text-info', 'icon' => 'bell'],
        'warning' => ['classes' => 'bg-warning/10 text-warning', 'icon' => 'exclamation-triangle'],
        'error' => ['classes' => 'bg-danger/10 text-danger', 'icon' => 'x-mark'],
    ];

    $config = $map[$variant] ?? $map['info'];
@endphp

<div
    @if ($dismissible) x-data="{ shown: true }" x-show="shown" x-cloak @endif
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-ui-md p-4 text-sm '.$config['classes']]) }}
    role="alert"
>
    <x-icon :name="$config['icon']" class="w-5 h-5 shrink-0 mt-0.5" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['mt-0.5' => $title])>{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" @click="shown = false" class="shrink-0 opacity-70 hover:opacity-100" aria-label="Fermer">
            <x-icon name="x-mark" class="w-4 h-4" />
        </button>
    @endif
</div>
```

- [ ] **Step 7: Run tests**

Run: `php artisan test --compact --filter="ComponentRenderTest|PublicPagesRenderTest"`
Expected: PASS.

- [ ] **Step 8: Pint & commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/components/alert.blade.php lang config/app.php .env.example \
        tests/Feature/DesignSystem/ComponentRenderTest.php tests/Feature/DesignSystem/PublicPagesRenderTest.php
git commit -m "feat(design-system): 4-level x-alert + French auth/password/validation strings"
```

> Note: also set `APP_LOCALE=fr` in your local `.env` (not committed).

---

## Task 7: Recolour the remaining shared components

**Files (Modify):** `card.blade.php`, `kpi-card.blade.php`, `badge.blade.php`, `sidebar-link.blade.php`, `tabs.blade.php`, `text-input.blade.php`, `input-label.blade.php`, `input-error.blade.php`, `modal.blade.php`, `dialog.blade.php`, `empty-table-row.blade.php`, `nav-link.blade.php`, `responsive-nav-link.blade.php`, `theme-toggle.blade.php`
**Test:** `tests/Feature/DesignSystem/NoLegacyStylesTest.php`, `ComponentRenderTest.php`

**Interfaces:** No signature changes. Class-string edits only, per this mapping:

| Legacy class | Replacement |
| --- | --- |
| `bg-white` / `dark:bg-gray-800` | `bg-surface` |
| `bg-gray-500` / `bg-gray-900` (overlay) | `bg-content/60` |
| `text-gray-900` / `dark:text-gray-100` | `text-content` |
| `text-gray-700` / `text-gray-600` / `dark:text-gray-300` | `text-content-secondary` |
| `text-gray-500` / `text-gray-400` | `text-content-muted` |
| `border-gray-200` / `border-gray-300` | `border-border` |
| `border-indigo-400` / `focus:border-indigo-700` | `border-primary` |
| `text-indigo-700` / `dark:text-indigo-300` | `text-primary` |
| `bg-indigo-50` / `dark:bg-indigo-900/50` | `bg-primary/10` |
| `focus:ring-indigo-500` | `focus-visible:shadow-inset-focus` (drop the ring utilities) |
| `rounded-lg` (modal) | `rounded-ui-lg` |
| `shadow-xl` (modal) | `shadow-soft-hover` |

- [ ] **Step 1: Add component assertions to `ComponentRenderTest.php`**

```php
it('renders an active sidebar link as solid green (spec §11)', function () {
    $html = Blade::render(
        '<x-sidebar-link href="/x" :active="true" icon="users">Élèves</x-sidebar-link>'
    );
    expect($html)->toContain('bg-primary')->toContain('text-primary-content');
});

it('renders the KPI card without indigo', function () {
    $html = Blade::render(
        '<x-kpi-card icon="users" label="Élèves actifs" value="248" trend="+12%" />'
    );
    expect($html)->not->toContain('indigo')->toContain('text-primary');
});

it('renders the modal shell on brand surfaces', function () {
    $html = Blade::render('<x-modal name="m">Body</x-modal>');
    expect($html)
        ->not->toContain('bg-gray-800')
        ->not->toContain('bg-white')
        ->toContain('bg-surface');
});
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --compact --filter=ComponentRenderTest`
Expected: FAIL (sidebar-link active still `text-primary`+`shadow-inset`; modal still `bg-white`).

- [ ] **Step 3: Edit `sidebar-link.blade.php`**

Change the active branch of the `@class([...])` from
`'bg-surface shadow-inset font-medium text-primary' => $active,`
to
`'bg-primary text-primary-content font-medium shadow-soft-sm' => $active,`.
Leave the inactive branch and the collapsed-tooltip markup unchanged.

- [ ] **Step 4: Edit `modal.blade.php`**

- Overlay `<div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>` → `<div class="absolute inset-0 bg-content/60"></div>`.
- Panel `class="mb-6 bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl …"` → `class="mb-6 bg-surface rounded-ui-lg overflow-hidden shadow-soft-hover …"`.

- [ ] **Step 5: Edit `nav-link.blade.php` and `responsive-nav-link.blade.php`**

Rewrite each using the mapping table. Final `nav-link.blade.php`:

```blade
@props(['active'])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center px-1 pt-1 border-b-2 border-primary text-sm font-medium leading-5 text-content focus:outline-none transition duration-150 ease-in-out'
    : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-content-muted hover:text-content-secondary hover:border-border focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
```

Final `responsive-nav-link.blade.php`:

```blade
@props(['active'])

@php
$classes = ($active ?? false)
    ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-primary text-start text-base font-medium text-primary bg-primary/10 focus:outline-none transition duration-150 ease-in-out'
    : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-content-secondary hover:text-content hover:bg-surface-elevated hover:border-border focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
```

- [ ] **Step 6: Edit `theme-toggle.blade.php`**

Change the `<button class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">` to `<button class="p-2 rounded-ui-sm text-content-secondary hover:text-content hover:bg-surface-elevated transition">`.

- [ ] **Step 7: Edit `card`, `kpi-card`, `badge`, `tabs`, `text-input`, `input-label`, `input-error`, `dialog`, `empty-table-row`**

Scan each for any table-listed legacy class and apply the mapping. Expected touches:
- `dialog.blade.php`: `bg-slate-900/50` → `bg-content/60`.
- `card`, `kpi-card`, `badge`, `tabs`, `text-input`, `input-label`, `input-error`, `empty-table-row`: already token-based — confirm no `gray`/`indigo`/`dark:` remains; adjust `rounded-ui-lg` on `card`/`kpi-card` stays (now 18px). No change likely needed beyond verification.
- `kpi-card.blade.php`: keep the icon chip `bg-primary/10 text-primary` (green = progression, spec §14). Leave `text-success`/`text-danger` trend colours.

- [ ] **Step 8: Run the guard + component tests**

Run: `php artisan test --compact --filter="ComponentRenderTest|NoLegacyStylesTest"`
Expected: `ComponentRenderTest` PASS. `NoLegacyStylesTest` — `components/*` no longer listed; only `profile/partials/*` and stragglers remain.

- [ ] **Step 9: Pint & commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/components
git commit -m "refactor(design-system): recolour shared components to brand tokens (green active state)"
```

---

## Task 8: Planning grid colour mapping

**Files:**
- Modify: `resources/views/components/planning-grid.blade.php`, `resources/views/components/planning-session-card.blade.php`
- Test: `tests/Feature/DesignSystem/ComponentRenderTest.php` (extend)

**Interfaces:**
- Produces: session-type → colour mapping per spec §17: `practical` (conduite) = green (`primary`), `theoretical`/`code` = blue (`info`), `mock_exam` (examen) = orange (`warning`), cancelled = `content-muted`, conflict = `danger`.

- [ ] **Step 1: Extend the test**

```php
it('colours the planning legend per spec §17 (conduite green, code blue, exam orange)', function () {
    $sessions = collect();
    $html = Blade::render('<x-planning-grid :sessions="$sessions" :week="$week" />', [
        'sessions' => $sessions,
        'week' => \Illuminate\Support\Carbon::parse('2026-08-31'),
    ]);

    expect($html)
        ->toContain('bg-primary')   // conduite / practical
        ->toContain('bg-info')      // code
        ->toContain('bg-warning');  // mock exam
});
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --compact --filter=ComponentRenderTest`
Expected: FAIL — current legend maps `code` → `bg-warning`, `mock_exam` → `bg-danger`.

- [ ] **Step 3: Edit the `$dotClasses` map in `planning-grid.blade.php`**

```php
$dotClasses = [
    'theoretical' => 'bg-info',
    'practical' => 'bg-primary',
    'code' => 'bg-info',
    'mock_exam' => 'bg-warning',
][$case->value];
```

- [ ] **Step 4: Align `planning-session-card.blade.php`**

Read the file; wherever it derives a per-type accent/background/border class, apply the same mapping (practical→`primary`, theoretical/code→`info`, mock_exam→`warning`, cancelled→`content-muted`). Keep a `danger` accent for a conflict state if the card renders one.

- [ ] **Step 5: Run tests**

Run: `php artisan test --compact --filter="ComponentRenderTest|Scheduling"`
Expected: PASS (design assertion + existing scheduling tests unbroken).

- [ ] **Step 6: Pint & commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/components/planning-grid.blade.php resources/views/components/planning-session-card.blade.php \
        tests/Feature/DesignSystem/ComponentRenderTest.php
git commit -m "refactor(planning): session-type colours per charte §17"
```

---

## Task 9: App shell — sidebar, nav grouping, topbar

**Files:**
- Modify: `resources/views/layouts/partials/sidebar-nav.blade.php`, `resources/views/layouts/partials/topbar.blade.php`, `resources/views/layouts/partials/sidebar.blade.php`, `resources/views/layouts/app.blade.php`
- Test: `tests/Feature/DesignSystem/NoLegacyStylesTest.php`, plus existing per-domain dashboard tests as regression.

**Interfaces:**
- Produces: §12 navigation grouping (only routes that already exist — see table), green active states, brand logo in header, unchanged Alpine `collapsed` / `mobileMenuOpen` contract.

**Navigation grouping (charte §12 → existing routes; omit rows with no route):**

| Group | Item | Route | Roles |
| --- | --- | --- | --- |
| PRINCIPAL | Tableau de bord | `dashboard` | all |
| GESTION | Élèves | `students.index` | admin, moniteur |
| GESTION | Moniteurs | `instructors.index` | admin |
| GESTION | Dossiers en attente | `dossiers.index` | admin |
| GESTION | Recyclage & Tests | `recyclage.index` | admin |
| FORMATION | Planning | `scheduling.index` / `moniteur.agenda` / `eleve.planning` | per role |
| FORMATION | Compétences | `training.skills.index` (admin) / `eleve.progression` | per role |
| FORMATION | Examens | `training.exams.index` | admin |
| FORMATION | Entraînement au code | `quiz.play` | eleve |
| FINANCES | Factures | `finance.invoices.index` | admin |
| FINANCES | Forfaits | `finance.packages.index` | admin |
| FINANCES | Journal | `finance.ledger.index` | admin |
| FINANCES | Mes paiements | `eleve.paiements` | eleve |
| FLOTTE | Véhicules | `fleet.index` | admin |
| RELATION CLIENT | Prospects | `crm.leads.index` | admin |
| BOUTIQUE | Boutique | `store.index` | admin |
| ADMINISTRATION | Comptes utilisateurs | `settings.users.index` | admin |
| ADMINISTRATION | Pièces requises | `settings.document-types.index` | admin |
| ADMINISTRATION | Inscription publique | `settings.student-registration.show` | admin |
| ADMINISTRATION | Paramètres | `settings.show` | admin |
| ADMINISTRATION | Audit | `audit.index` | admin, superadmin |
| ADMINISTRATION | Établissements | `superadmin.structures.index` | superadmin |

- [ ] **Step 1: Regression baseline**

Run: `php artisan test --compact --filter="Dashboard"` and note the currently-passing set (admin/moniteur/eleve dashboard render tests). These must stay green.

- [ ] **Step 2: Rework `sidebar-nav.blade.php`**

- Move `crm.leads.index` out of GESTION into a new `Relation client` group (label `text-[11px] font-semibold uppercase tracking-wider text-content-muted`).
- Split the current `Flotte & Boutique` group into `Flotte` (Véhicules) and `Boutique` (Boutique).
- Keep all `@can` / `hasRole` guards exactly as they are — only the grouping/labels/order change.
- Dashboard top link: it already uses `bg-primary text-primary-content shadow-soft-sm` when active — now correct (green). Leave it.
- Verify no `gray`/`indigo`/`dark:` classes are introduced.

- [ ] **Step 3: `topbar.blade.php`**

- Avatar circle `bg-primary text-primary-content` — keep (green initial badge is on-brand).
- No `gray`/`dark:` — confirm the notification + profile dropdown panels use `bg-surface` / `border-border/60` (they already do).

- [ ] **Step 4: `app.blade.php` fonts**

Leave the Inter `<link>` as-is (spec §10 recommends Inter). No change unless the reviewer opts into Plus Jakarta Sans — if so, add `family=plus-jakarta-sans:400,500,600,700` to the bunny URL and prepend `'"Plus Jakarta Sans"'` to `fontFamily.sans` in `tailwind.config.js`. Default: **skip**.

- [ ] **Step 5: Run regression + guard**

Run: `php artisan test --compact --filter="Dashboard|NoLegacyStylesTest"`
Expected: dashboard tests PASS unchanged; guard shows only `profile/partials/*` + any not-yet-swept domain views.

- [ ] **Step 6: Pint, build, commit**

```bash
vendor/bin/pint --dirty --format agent
npm run build
git add resources/views/layouts
git commit -m "feat(shell): charte §12 navigation grouping + green active states"
```

---

## Task 10: Missing design-system components

**Files (Create):** `page-header.blade.php`, `filter-bar.blade.php`, `empty-state.blade.php`, `loading-state.blade.php`, `error-state.blade.php`, `avatar.blade.php`, `tooltip.blade.php`, `select.blade.php`, `textarea.blade.php`, `pagination.blade.php` (all in `resources/views/components/`)
**Test:** `tests/Feature/DesignSystem/ComponentRenderTest.php` (extend)

**Interfaces (Produces):**
- `<x-page-header title :subtitle>` with an `actions` slot — the §32 hierarchy (title left, primary action right).
- `<x-filter-bar>` — `bg-surface rounded-ui-lg shadow-soft-sm p-4` row wrapper; slot holds inputs.
- `<x-empty-state icon title :message :action :actionLabel>` — full-panel empty state (generalises `x-empty-table-row`, spec §23).
- `<x-loading-state :label>` — centred spinner (`animate-spin` via inline SVG, no new lib).
- `<x-error-state :title :message :retry>` — error panel with `x-icon name="exclamation-triangle"` in `text-danger`.
- `<x-avatar :name :src :size="sm|md|lg">` — initial-or-image circle, `bg-primary text-primary-content` fallback.
- `<x-tooltip text>` — Alpine hover bubble wrapping its slot.
- `<x-select>` / `<x-textarea>` — token-styled `@tailwindcss/forms` controls matching `x-text-input` (`bg-surface shadow-inset rounded-ui-sm`).
- `<x-pagination :paginator>` — wraps `$paginator->links()` but forces the project's French + token styling via a published vendor view OR a thin Tailwind wrapper.

- [ ] **Step 1: Extend `ComponentRenderTest.php`**

```php
it('renders a page header with title and actions slot', function () {
    $html = Blade::render(
        '<x-page-header title="Élèves" subtitle="248 actifs"><x-slot:actions>BTN</x-slot:actions></x-page-header>'
    );
    expect($html)->toContain('Élèves')->toContain('248 actifs')->toContain('BTN');
});

it('renders an empty state with an icon and a call to action', function () {
    $html = Blade::render(
        '<x-empty-state icon="truck" title="Aucun véhicule" message="Votre flotte est vide." action="/fleet/create" actionLabel="Ajouter un véhicule" />'
    );
    expect($html)->toContain('Aucun véhicule')->toContain('/fleet/create')->toContain('Ajouter un véhicule');
});

it('renders an avatar fallback initial on the brand primary', function () {
    $html = Blade::render('<x-avatar name="Jean Dupont" size="md" />');
    expect($html)->toContain('J')->toContain('bg-primary');
});

it('renders loading and error states', function () {
    expect(Blade::render('<x-loading-state label="Chargement…" />'))->toContain('Chargement…');
    expect(Blade::render('<x-error-state title="Oups" message="Échec" />'))
        ->toContain('Oups')->toContain('text-danger');
});
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --compact --filter=ComponentRenderTest`
Expected: FAIL — components not found.

- [ ] **Step 3: Create the components**

`page-header.blade.php`:

```blade
@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-start justify-between gap-4 mb-6']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-content truncate">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-content-secondary">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>
    @endisset
</div>
```

`filter-bar.blade.php`:

```blade
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end gap-3 bg-surface rounded-ui-lg shadow-soft-sm p-4 mb-5']) }}>
    {{ $slot }}
</div>
```

`empty-state.blade.php`:

```blade
@props(['icon' => 'archive-box', 'title', 'message' => null, 'action' => null, 'actionLabel' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center px-6 py-14']) }}>
    <span class="flex h-14 w-14 items-center justify-center rounded-ui-lg bg-primary/10 text-primary">
        <x-icon :name="$icon" class="w-7 h-7" />
    </span>
    <p class="mt-4 text-sm font-semibold text-content">{{ $title }}</p>
    @if ($message)
        <p class="mt-1 text-sm text-content-muted max-w-sm">{{ $message }}</p>
    @endif
    @if ($action && $actionLabel)
        <x-button variant="primary" :href="$action" class="mt-5">
            <x-icon name="plus" class="w-4 h-4" /> {{ $actionLabel }}
        </x-button>
    @endif
</div>
```

`loading-state.blade.php`:

```blade
@props(['label' => 'Chargement…'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-14 text-content-muted']) }}>
    <svg class="w-8 h-8 animate-spin text-primary" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
    </svg>
    <p class="mt-3 text-sm">{{ $label }}</p>
</div>
```

`error-state.blade.php`:

```blade
@props(['title' => 'Une erreur est survenue', 'message' => null, 'retry' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center px-6 py-14']) }}>
    <span class="flex h-14 w-14 items-center justify-center rounded-ui-lg bg-danger/10 text-danger">
        <x-icon name="exclamation-triangle" class="w-7 h-7" />
    </span>
    <p class="mt-4 text-sm font-semibold text-content">{{ $title }}</p>
    @if ($message)
        <p class="mt-1 text-sm text-content-muted max-w-sm">{{ $message }}</p>
    @endif
    @if ($retry)
        <x-button variant="secondary" :href="$retry" class="mt-5">Réessayer</x-button>
    @endif
</div>
```

`avatar.blade.php`:

```blade
@props(['name' => '', 'src' => null, 'size' => 'md'])

@php
    $sizes = [
        'sm' => 'h-7 w-7 text-xs',
        'md' => 'h-9 w-9 text-sm',
        'lg' => 'h-12 w-12 text-base',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $initial = mb_strtoupper(mb_substr(trim($name), 0, 1));
@endphp

@if ($src)
    <img src="{{ $src }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => 'rounded-full object-cover '.$sizeClass]) }} />
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full bg-primary font-semibold text-primary-content '.$sizeClass]) }}>
        {{ $initial }}
    </span>
@endif
```

`tooltip.blade.php`:

```blade
@props(['text'])

<span x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false"
      {{ $attributes->merge(['class' => 'relative inline-flex']) }}>
    {{ $slot }}
    <span x-show="open" x-cloak
          class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 whitespace-nowrap rounded-ui-sm bg-content px-2 py-1 text-xs font-medium text-background">
        {{ $text }}
    </span>
</span>
```

`select.blade.php`:

```blade
@props(['disabled' => false])

<select
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'w-full rounded-ui-sm border-0 bg-surface px-3.5 py-2.5 text-sm text-content shadow-inset focus:outline-none focus:shadow-inset-focus disabled:opacity-50 transition']) }}
>
    {{ $slot }}
</select>
```

`textarea.blade.php`:

```blade
@props(['disabled' => false])

<textarea
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'w-full rounded-ui-sm border-0 bg-surface px-3.5 py-2.5 text-sm text-content shadow-inset placeholder:text-content-muted focus:outline-none focus:shadow-inset-focus disabled:opacity-50 transition']) }}
>{{ $slot }}</textarea>
```

`pagination.blade.php`:

```blade
@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-2 mt-4 text-sm" role="navigation">
        <div>
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1 rounded-ui-sm px-3 py-1.5 text-content-muted">
                    <x-icon name="chevron-left" class="w-4 h-4" /> {{ __('pagination.previous') }}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center gap-1 rounded-ui-sm bg-surface px-3 py-1.5 text-content-secondary shadow-soft-sm hover:text-content transition">
                    <x-icon name="chevron-left" class="w-4 h-4" /> {{ __('pagination.previous') }}
                </a>
            @endif
        </div>
        <span class="text-content-muted">
            {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </span>
        <div>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center gap-1 rounded-ui-sm bg-surface px-3 py-1.5 text-content-secondary shadow-soft-sm hover:text-content transition">
                    {{ __('pagination.next') }} <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
            @else
                <span class="inline-flex items-center gap-1 rounded-ui-sm px-3 py-1.5 text-content-muted">
                    {{ __('pagination.next') }} <x-icon name="chevron-right" class="w-4 h-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact --filter=ComponentRenderTest`
Expected: PASS.

- [ ] **Step 5: Pint & commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/components tests/Feature/DesignSystem/ComponentRenderTest.php
git commit -m "feat(design-system): page-header, filter-bar, empty/loading/error states, avatar, tooltip, select, textarea, pagination"
```

---

## Task 11: Landing page — official logo assets

**Files:**
- Modify: `resources/views/welcome.blade.php`
- Test: `tests/Feature/DesignSystem/PublicPagesRenderTest.php` (extend)

**Interfaces:**
- Landing keeps its own palette (`route`/`signal`/`asphalt`/`cream`) — spec §37 only asks it to use the official assets correctly. Scope: logo image + favicon + og:image references, nothing else.

- [ ] **Step 1: Extend the test**

```php
it('serves the landing page with the official brand logo asset', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('images/brand/', escape: false);
});
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --compact --filter=PublicPagesRenderTest`
Expected: FAIL — `images/logo.png` still referenced.

- [ ] **Step 3: Edit `welcome.blade.php`**

- `<link rel="icon" … href="{{ asset('images/logo.png') }}">` → `href="{{ asset('images/brand/icon.jpg') }}"`, `type="image/jpeg"`.
- `<meta property="og:image" content="{{ asset('images/logo.png') }}">` → `{{ asset('images/brand/og.jpg') }}`.
- Nav `<img src="{{ asset('images/logo.png') }}" alt="Auto-GestBoard" class="h-9 w-auto">` (on the dark asphalt bar) → `<img src="{{ asset('images/brand/logo-horizontal-on-navy.jpg') }}" alt="Auto-GestBoard" class="h-9 w-auto">`; if an adjacent `<span>…Auto-GestBoard</span>` duplicates the wordmark now baked into the image, remove that span.
- Any footer logo instance: use `images/brand/logo-horizontal-on-navy.jpg` on dark sections, `images/brand/logo-horizontal-light.jpg` on cream/white sections.
- Do **not** change `route`/`signal`/`asphalt` colours or `font-display`.

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact --filter=PublicPagesRenderTest`
Expected: PASS.

- [ ] **Step 5: Build & commit**

```bash
npm run build
git add resources/views/welcome.blade.php tests/Feature/DesignSystem/PublicPagesRenderTest.php
git commit -m "feat(landing): use official Auto-GestBoard logo assets"
```

---

## Task 12: Straggler sweep + full verification

**Files:**
- Modify: `resources/views/profile/partials/update-profile-information-form.blade.php`, `update-password-form.blade.php`, `delete-user-form.blade.php`, plus any file still listed by `NoLegacyStylesTest`.
- Test: whole suite.

- [ ] **Step 1: List remaining offenders**

Run: `php artisan test --compact --filter=NoLegacyStylesTest`
Record every path in each failure message.

- [ ] **Step 2: Fix each file with the Task 7 mapping table**

For the profile partials: replace `text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100` → `text-content-secondary hover:text-content`; drop `focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800` → `focus-visible:shadow-inset-focus`. Repeat the pattern for any other flagged domain view; where a bespoke colour block existed, prefer the nearest `x-*` component instead of re-styling inline (spec §30).

- [ ] **Step 3: Guard green**

Run: `php artisan test --compact --filter=NoLegacyStylesTest`
Expected: PASS (all 6 cases).

- [ ] **Step 4: Full suite**

Run: `php artisan test --compact`
Expected: PASS. Investigate any failure — a broken snapshot/DOM assertion in a domain test usually means a class string it asserted on changed; update the assertion to the token class, do not revert the component.

- [ ] **Step 5: Pint + build**

```bash
vendor/bin/pint --dirty --format agent
npm run build
```

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "refactor(design-system): sweep remaining views onto brand tokens; guard green"
```

- [ ] **Step 7: Manual visual pass (spec §29 checklist)**

With `composer run dev` running, in both light and dark theme walk: `Dashboard → Élèves → Planning → Finance → Flotte → CRM → Paramètres` + `/login` + `/`. Confirm: no indigo anywhere; sidebar active item solid green; KPI icons green; planning conduite green / code blue / exam orange; alerts show the four levels; login shows the French "identifiants" message; brand logo correct on light and dark. Note anything off for a follow-up task — do not fix ad hoc here.

---

## Self-Review

**1. Spec coverage**

| Spec § | Covered by |
| --- | --- |
| §1 identity / logo assets | Task 4, Task 11 |
| §2–3 brand + palette | Task 1, Task 2 |
| §4 semantic ≠ brand | Task 1 (`--color-success` `#159A6C`), Task 6 |
| §5 light theme | Task 1 |
| §6 dark theme (navy) | Task 1 |
| §7 neumorphism (light shadows) | Task 1 shadow retune; components keep `shadow-soft*` |
| §8 things to avoid | Task 3 guard (no indigo/hex/dark:), Task 1 (shadow depth), §21 icon rule |
| §9 radius scale | Task 1 (`--radius-*`), Task 2 (`ui-*`) |
| §10 typography | Task 9 Step 4 (Inter kept; Jakarta optional) |
| §11 sidebar active = green | Task 7 Step 3 |
| §12 navigation grouping | Task 9 |
| §13 dashboard hierarchy | `x-page-header` + `x-kpi-card` (Task 10, Task 7) — layout stays; role dashboards already exist |
| §14 KPI cards | Task 7 Step 7 (icon chip green, card not fully coloured) |
| §15 buttons | Task 5 |
| §16 tables | `x-filter-bar` + existing table wrappers; `x-pagination` Task 10 |
| §17 planning | Task 8 |
| §18 badges | `x-badge` already dot+tint; Task 7 verifies tokens |
| §19 forms | `x-text-input`/`x-select`/`x-textarea`/`x-input-error` (Task 7, Task 10); focus ring now green (Task 1) |
| §20 system messages (4 levels) | Task 6 |
| §21 icons (one set) | `x-icon` set unchanged; guard forbids emoji-as-icon via review; §8 |
| §22–23 illustrations / empty states | `x-empty-state` Task 10 |
| §24 responsive | existing `md:` breakpoints in `planning-grid` etc.; no regression — components are responsive; **note:** no new mobile-card table transform is built here (out of scope, flag as follow-up) |
| §25 animation | `x-loading-state` spin; transitions kept at existing durations |
| §26 logo in app | Task 4 (sidebar expanded/collapsed, login, favicon, dark variant) |
| §27 tokens as CSS vars | Task 1 (kept `--color-*`, added `--brand-*`; `--ag-*` deliberately not used — documented) |
| §28 replace `--color-primary` | Task 1 |
| §29 keep good foundations | plan evolves tokens/components, no rebuild |
| §30 remove indigo / raw greys / long class repeats | Task 3 guard + Task 7 + Task 12 |
| §31 official component list | Task 5 + Task 10 (x-button, x-icon-button, x-card, x-kpi-card/x-stat-card, x-badge, x-alert, x-input, x-select, x-textarea, x-modal, x-dropdown, x-tabs, x-pagination, x-empty-state, x-loading-state, x-error-state, x-page-header, x-filter-bar, x-planning-grid, x-avatar, x-tooltip). **Gap:** `x-table` and `x-breadcrumb` and `x-stat-card` alias not built — added as Task 10 note below. |
| §32 visual hierarchy | `x-page-header` + `x-filter-bar` order |
| §33 dashboard by role | role dashboards already exist; charte identical — no work beyond tokens |
| §34 slogan | in logo assets (baked into `brand 1/7/8`); nothing to build |
| §35 brand personality | qualitative — guides review, no task |
| §37 directive (no parallel system, don't touch logic) | Global Constraints + every task scoped to views/tokens/components |

**Gaps found & resolved:** §31 lists `x-table`, `x-breadcrumb`, and a `x-stat-card` name. Add to **Task 10 Step 3**:
- `stat-card.blade.php`: `<x-stat-card … />` → one line delegating to `<x-kpi-card>` (alias, so both names work).
- `breadcrumb.blade.php`: `@props(['items' => []])` → `<nav>` with `/` separators, `text-content-muted` links, last item `text-content`, uses `<x-icon name="chevron-right">` between items (no arrow glyphs).
- `table.blade.php`: `@props(['headers' => []])` → `bg-surface rounded-ui-lg shadow-soft overflow-hidden` wrapper + `<table class="w-full text-sm">` + `<thead>` row `text-content-secondary border-b border-border` + `<tbody class="divide-y divide-border/60">`; slot for rows. Add a `ComponentRenderTest` case asserting `x-table` renders its headers and `x-breadcrumb` renders the last crumb as `text-content`.
- §24 responsive: building per-page mobile-card transforms for every data table is genuinely out of scope for a design-system pass — **documented as a follow-up plan**, not silently dropped.

**2. Placeholder scan:** No `TBD`/`handle edge cases`/`similar to Task N` — each component task carries full file bodies; recolour tasks carry an explicit class-mapping table (concrete, not a placeholder).

**3. Type consistency:** `<x-button>` prop `variant` values `primary|secondary|ghost|danger` used identically in Task 5, Task 10 (`x-empty-state`, `x-error-state`). `x-brand-logo` props `variant`/`on` consistent between Task 4 definition and Task 9/11 call sites. `x-alert` `variant` values `success|info|warning|error` consistent Task 6 ↔ guard. Token names identical across Task 1 (CSS) and Task 2 (Tailwind consumer). `x-kpi-card` signature unchanged (`icon,label,value,href,trend,trendUp`) — `x-stat-card` alias forwards `{{ $attributes }}` + slot.

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-08-29-charte-graphique-design-system.md`. Two execution options:

**1. Subagent-Driven (recommended)** — fresh subagent per task, review between tasks, fast iteration.

**2. Inline Execution** — execute tasks in this session using executing-plans, batch execution with checkpoints.

Which approach?
