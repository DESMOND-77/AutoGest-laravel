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
