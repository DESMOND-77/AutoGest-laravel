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
