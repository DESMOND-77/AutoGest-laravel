<?php

/**
 * Enforces the one-directional dependency rules from the domain diagram
 * (proposal §5): e.g. Finance may read a Student, but Students must never
 * depend on Finance. This is what stops the kind of coupling found in the
 * legacy app, where the vehicle maintenance page wrote directly into the
 * financial transactions table.
 */
arch('Students domain does not depend on Finance, Fleet or Store')
    ->expect('App\Domain\Students')
    ->not->toUse([
        'App\Domain\Finance',
        'App\Domain\Fleet',
        'App\Domain\Store',
    ]);

arch('Fleet domain does not depend on Students, Scheduling or CRM')
    ->expect('App\Domain\Fleet')
    ->not->toUse([
        'App\Domain\Students',
        'App\Domain\Scheduling',
        'App\Domain\CRM',
    ]);

arch('Instructors domain does not depend on Finance or Store')
    ->expect('App\Domain\Instructors')
    ->not->toUse([
        'App\Domain\Finance',
        'App\Domain\Store',
    ]);

arch('domain Models stay free of HTTP concerns')
    ->expect('App\Domain\*\Models')
    ->not->toUse([
        'Illuminate\Http\Request',
        'Illuminate\Routing\Controller',
    ]);

arch('domain code has no debug leftovers')
    ->expect('App\Domain')
    ->not->toUse(['dd', 'dump', 'ray']);
