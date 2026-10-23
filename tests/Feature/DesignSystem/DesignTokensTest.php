<?php

use Illuminate\Support\Facades\File;

$tokens = fn () => File::get(resource_path('css/tokens.css'));
$app = fn () => File::get(resource_path('css/app.css'));

it('defines the six selectable themes', function () use ($tokens) {
    foreach (['aurore', 'ocean', 'sable', 'foret', 'crepuscule', 'graphite'] as $theme) {
        expect($tokens())->toContain(':root[data-theme="'.$theme.'"]');
    }
});

it('supports light and dark modes and accent overrides', function () use ($tokens) {
    expect($tokens())
        ->toContain(':root[data-mode="dark"]')
        ->toContain('[data-accent="indigo"]')
        ->toContain('--accent:');
});

it('exposes the normalised radius scale', function () use ($tokens) {
    expect($tokens())
        ->toContain('--r-xs:')
        ->toContain('--r-md:')
        ->toContain('--r-pill: 999px');
});

it('exposes density and touch-target tokens', function () use ($tokens) {
    expect($tokens())
        ->toContain('--control-h: 44px')
        ->toContain(':root[data-density="compact"]');
});

it('maps the design tokens to Tailwind theme colors and radii', function () use ($app) {
    expect($app())
        ->toContain('@theme inline')
        ->toContain('--color-accent: var(--accent)')
        ->toContain('--color-ink: var(--text-1)')
        ->toContain('--radius-pill: var(--r-pill)');
});

it('no longer contains the legacy brand-green scale', function () use ($tokens, $app) {
    expect($tokens().$app())
        ->not->toContain('--brand-navy-900')
        ->not->toContain('--color-primary:');
});
