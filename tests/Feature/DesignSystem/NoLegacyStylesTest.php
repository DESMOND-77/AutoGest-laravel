<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

/**
 * @return Collection<int, SplFileInfo>
 */
function domainBladeViews(): Collection
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

function offenders(string $pattern): Collection
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
