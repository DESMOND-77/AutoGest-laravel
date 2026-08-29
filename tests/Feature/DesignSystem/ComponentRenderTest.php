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
