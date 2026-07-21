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

arch('Fleet domain does not depend on Students, Scheduling, Finance or CRM')
    ->expect('App\Domain\Fleet')
    ->not->toUse([
        'App\Domain\Students',
        'App\Domain\Scheduling',
        'App\Domain\Finance',
        'App\Domain\CRM',
    ]);

arch('Store domain does not depend on Fleet, Scheduling, Training or CRM')
    ->expect('App\Domain\Store')
    ->not->toUse([
        'App\Domain\Fleet',
        'App\Domain\Scheduling',
        'App\Domain\Training',
        'App\Domain\CRM',
    ]);

arch('Instructors domain does not depend on Finance or Store')
    ->expect('App\Domain\Instructors')
    ->not->toUse([
        'App\Domain\Finance',
        'App\Domain\Store',
    ]);

arch('Scheduling domain does not depend on Finance, Store or CRM')
    ->expect('App\Domain\Scheduling')
    ->not->toUse([
        'App\Domain\Finance',
        'App\Domain\Store',
        'App\Domain\CRM',
    ]);

arch('Training domain does not depend on Finance, Fleet, Store or CRM')
    ->expect('App\Domain\Training')
    ->not->toUse([
        'App\Domain\Finance',
        'App\Domain\Fleet',
        'App\Domain\Store',
        'App\Domain\CRM',
    ]);

arch('Students domain does not depend on Scheduling or Training')
    ->expect('App\Domain\Students')
    ->not->toUse([
        'App\Domain\Scheduling',
        'App\Domain\Training',
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
