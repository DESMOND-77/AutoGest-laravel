<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;

it('renders the horizontal brand lockup for light and dark surfaces', function () {
    $html = Blade::render('<x-brand-logo variant="full" class="h-8" />');

    expect($html)
        ->toContain('images/brand/logo-horizontal-light.jpg')
        ->toContain('images/brand/logo-horizontal-on-navy.jpg')
        ->toContain('alt="'.config('app.name').'"');
});

it('renders the icon-only brand mark', function () {
    $html = Blade::render('<x-brand-logo variant="icon" class="h-8 w-8" />');

    expect($html)->toContain('images/brand/icon.jpg')->toContain('h-8 w-8');
});

it('renders the primary button with the accent token', function () {
    $html = Blade::render('<x-ui.button variant="primary">Ajouter un élève</x-ui.button>');

    expect($html)->toContain('bg-accent')->toContain('text-on-accent')->toContain('<button')->toContain('Ajouter un élève');
});

it('renders the secondary button on a solid surface with a control border', function () {
    $html = Blade::render('<x-ui.button variant="secondary">Annuler</x-ui.button>');

    expect($html)->toContain('bg-solid')->toContain('border-control');
});

it('renders as an anchor when href is provided', function () {
    $html = Blade::render('<x-ui.button href="/students/create">Nouveau</x-ui.button>');

    expect($html)->toContain('<a')->toContain('href="/students/create"');
});

it('renders an icon-only button with an accessible label', function () {
    $html = Blade::render('<x-ui.button icon="plus" label="Ajouter" />');

    expect($html)->toContain('aria-label="Ajouter"');
});

it('renders each alert tone with its soft background', function () {
    foreach (['success', 'info', 'warning', 'danger'] as $tone) {
        $html = Blade::render('<x-ui.alert tone="'.$tone.'" title="T">Message</x-ui.alert>');

        expect($html)->toContain('bg-'.$tone.'-soft')->toContain('Message')->toContain('T');
    }
});

it('renders a KPI card with label, value and no indigo', function () {
    $html = Blade::render('<x-data.kpi icon="users" label="Élèves actifs" value="248" />');

    expect($html)->toContain('Élèves actifs')->toContain('248')->not->toContain('indigo');
});

it('renders a page header with title, description and actions slot', function () {
    $html = Blade::render(
        '<x-layout.page-header title="Élèves" description="248 actifs"><x-slot:actions>BTN</x-slot:actions></x-layout.page-header>'
    );

    expect($html)->toContain('Élèves')->toContain('248 actifs')->toContain('BTN');
});

it('renders an empty state with an icon and a call to action', function () {
    $html = Blade::render(
        '<x-ui.empty-state icon="truck" title="Aucun véhicule" description="Votre flotte est vide."><x-slot:actions>Ajouter un véhicule</x-slot:actions></x-ui.empty-state>'
    );

    expect($html)->toContain('Aucun véhicule')->toContain('Votre flotte est vide.')->toContain('Ajouter un véhicule');
});

it('renders an avatar with the name initials on a soft tone', function () {
    $html = Blade::render('<x-ui.avatar name="Jean Dupont" size="md" />');

    expect($html)->toContain('JD')->toContain('-soft');
});

it('renders an error state as an alert and a skeleton as hidden from assistive tech', function () {
    expect(Blade::render('<x-ui.error-state title="Oups" description="Échec" />'))
        ->toContain('Oups')->toContain('role="alert"')->toContain('text-danger');
    expect(Blade::render('<x-ui.skeleton />'))->toContain('aria-hidden="true"')->toContain('animate-pulse');
});

it('renders a filter bar as a search form', function () {
    $html = Blade::render('<x-data.filter-bar search="" placeholder="Rechercher un élève" />');

    expect($html)->toContain('role="search"')->toContain('Rechercher un élève');
});

it('renders a tooltip bubble around its slot', function () {
    $html = Blade::render('<x-ui.tooltip text="Infos">HOVER</x-ui.tooltip>');

    expect($html)->toContain('HOVER')->toContain('Infos')->toContain('role="tooltip"');
});

it('renders token-styled select and textarea controls', function () {
    view()->share('errors', new ViewErrorBag);

    expect(Blade::render('<x-form.select name="s" :options="[1 => \'A\']" />'))
        ->toContain('<select')->toContain('bg-inset')->toContain('border-control');
    expect(Blade::render('<x-form.textarea name="note" placeholder="Note" />'))
        ->toContain('<textarea')->toContain('bg-inset')->toContain('border-control');
});

it('renders a paginator with its range summary', function () {
    $paginator = new LengthAwarePaginator([1, 2, 3], 30, 10, 2);
    $html = Blade::render('<x-ui.pagination :paginator="$paginator" />', ['paginator' => $paginator]);

    expect($html)->toContain('Affichage 11-13 sur 30')->toContain('aria-label="Pagination"');
});

it('renders a data table with caption and slot', function () {
    $html = Blade::render('<x-data.table caption="Liste"><thead><tr><th>Élève</th></tr></thead><tbody><tr><td>x</td></tr></tbody></x-data.table>');

    expect($html)->toContain('Liste')->toContain('Élève')->toContain('data-table');
});

it('renders breadcrumbs with the last crumb marked current', function () {
    $html = Blade::render('<x-ui.breadcrumbs :items="[[\'label\' => \'Élèves\', \'href\' => \'/students\'], [\'label\' => \'Jean\']]" />');

    expect($html)->toContain('Jean')->toContain('aria-current')->toContain('/students');
});
