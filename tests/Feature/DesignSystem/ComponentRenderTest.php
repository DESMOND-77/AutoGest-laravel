<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
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

it('lets a call site override the type on each legacy button alias', function () {
    expect(Blade::render('<x-secondary-button type="submit">S</x-secondary-button>'))->toContain('type="submit"');
    expect(Blade::render('<x-primary-button type="button">P</x-primary-button>'))->toContain('type="button"');
    expect(Blade::render('<x-danger-button type="button">D</x-danger-button>'))->toContain('type="button"');
});

it('merges a caller class through each legacy button alias', function () {
    expect(Blade::render('<x-secondary-button class="w-full">S</x-secondary-button>'))->toContain('w-full');
    expect(Blade::render('<x-primary-button class="w-full">P</x-primary-button>'))->toContain('w-full');
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

it('colours the planning legend per spec §17 (conduite green, code blue, exam orange)', function () {
    $sessions = collect();
    $html = Blade::render('<x-planning-grid :sessions="$sessions" :week="$week" />', [
        'sessions' => $sessions,
        'week' => Carbon::parse('2026-08-31'),
    ]);

    expect($html)
        ->toContain('bg-primary')   // conduite / practical
        ->toContain('bg-info')      // code
        ->toContain('bg-warning')   // mock exam
        ->not->toContain('bg-danger'); // no red in the session-type legend
});

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

it('renders a filter bar wrapper around its slot', function () {
    $html = Blade::render('<x-filter-bar><input name="q" /></x-filter-bar>');
    expect($html)->toContain('bg-surface')->toContain('<input name="q"');
});

it('renders a tooltip bubble around its slot', function () {
    $html = Blade::render('<x-tooltip text="Infos">HOVER</x-tooltip>');
    expect($html)->toContain('HOVER')->toContain('Infos')->toContain('x-data');
});

it('renders token-styled select and textarea controls', function () {
    expect(Blade::render('<x-select><option>A</option></x-select>'))
        ->toContain('<select')->toContain('bg-surface')->toContain('shadow-inset');
    expect(Blade::render('<x-textarea placeholder="Note" />'))
        ->toContain('<textarea')->toContain('bg-surface')->toContain('shadow-inset');
});

it('renders a paginator wrapper with French previous/next labels', function () {
    $paginator = new LengthAwarePaginator([1, 2, 3], 30, 10, 2);
    $html = Blade::render('<x-pagination :paginator="$paginator" />', ['paginator' => $paginator]);
    expect($html)->toContain(__('pagination.previous'))->toContain(__('pagination.next'))->toContain('2 / 3');
});

it('renders a table with its headers', function () {
    $html = Blade::render('<x-table :headers="[\'Élève\', \'Statut\']"><tr><td>x</td></tr></x-table>');
    expect($html)->toContain('Élève')->toContain('Statut')->toContain('<tbody');
});

it('renders a breadcrumb with the last crumb emphasised', function () {
    $html = Blade::render('<x-breadcrumb :items="[[\'label\' => \'Élèves\', \'url\' => \'/students\'], [\'label\' => \'Jean\']]" />');
    expect($html)->toContain('Jean')->toContain('text-content font-medium')->toContain('/students');
});

it('renders x-stat-card as a kpi-card', function () {
    $html = Blade::render('<x-stat-card icon="users" label="Actifs" value="10" />');
    expect($html)->toContain('Actifs')->toContain('10');
});
