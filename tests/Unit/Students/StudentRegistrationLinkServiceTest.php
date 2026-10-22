<?php

use App\Domain\Students\Exceptions\InvalidRegistrationLink;
use App\Domain\Students\Models\StudentRegistrationLink;
use App\Domain\Students\Services\StudentRegistrationLinkService;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;

beforeEach(function () {
    $this->service = app(StudentRegistrationLinkService::class);
    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
});

it('generates a link whose plain token is never stored', function () {
    ['link' => $link, 'token' => $token] = $this->service->generate($this->structure, $this->admin);

    expect($token)->toHaveLength(64); // bin2hex(random_bytes(32))
    expect($link->token_hash)->toBe(hash('sha256', $token));
    expect($link->token_hash)->not->toBe($token);

    // The raw database row never contains the plain token under any column.
    $row = (array) StudentRegistrationLink::query()->findOrFail($link->id)->getAttributes();
    expect(collect($row)->contains($token))->toBeFalse();
});

it('generates cryptographically distinct tokens on each call', function () {
    ['token' => $tokenA] = $this->service->generate($this->structure, $this->admin);
    ['token' => $tokenB] = $this->service->generate($this->structure, $this->admin);

    expect($tokenA)->not->toBe($tokenB);
});

it('validates a freshly generated token and resolves it back to the same link', function () {
    ['link' => $link, 'token' => $token] = $this->service->generate($this->structure, $this->admin);

    $resolved = $this->service->validate($token);

    expect($resolved->id)->toBe($link->id);
    expect($resolved->structure_id)->toBe($this->structure->id);
});

it('rejects a token that was never generated', function () {
    $this->service->validate('this-token-does-not-exist');
})->throws(InvalidRegistrationLink::class);

it('rejects an expired token with a distinguishable reason', function () {
    ['token' => $token] = $this->service->generate($this->structure, $this->admin);
    StudentRegistrationLink::query()->update(['expires_at' => now()->subDay()]);

    try {
        $this->service->validate($token);
        $this->fail('Expected InvalidRegistrationLink to be thrown.');
    } catch (InvalidRegistrationLink $e) {
        expect($e->reason)->toBe('expired');
    }
});

it('rejects a revoked token', function () {
    ['link' => $link, 'token' => $token] = $this->service->generate($this->structure, $this->admin);
    $this->service->revoke($link);

    try {
        $this->service->validate($token);
        $this->fail('Expected InvalidRegistrationLink to be thrown.');
    } catch (InvalidRegistrationLink $e) {
        expect($e->reason)->toBe('invalid');
    }
});

it('rejects a token once its max_uses is reached', function () {
    ['link' => $link, 'token' => $token] = $this->service->generate($this->structure, $this->admin);
    $link->update(['max_uses' => 1, 'usage_count' => 1]);

    $this->service->validate($token);
})->throws(InvalidRegistrationLink::class);

it('rejects a token belonging to a suspended tenant', function () {
    ['token' => $token] = $this->service->generate($this->structure, $this->admin);
    $this->structure->update(['status' => StructureStatus::Suspended]);

    $this->service->validate($token);
})->throws(InvalidRegistrationLink::class);

it('revoking the active link before generating replaces it with exactly one usable link', function () {
    ['link' => $first] = $this->service->generate($this->structure, $this->admin);
    ['link' => $second, 'token' => $secondToken] = $this->service->generate($this->structure, $this->admin);

    expect($first->fresh()->isRevoked())->toBeTrue();
    expect($second->fresh()->isRevoked())->toBeFalse();

    $activeCount = StudentRegistrationLink::query()
        ->where('structure_id', $this->structure->id)
        ->active()
        ->count();

    expect($activeCount)->toBe(1);
    expect($this->service->validate($secondToken)->id)->toBe($second->id);
});

it('regenerate revokes the current link and returns a working new one', function () {
    ['link' => $original, 'token' => $originalToken] = $this->service->generate($this->structure, $this->admin);

    ['token' => $newToken] = $this->service->regenerate($this->structure, $this->admin);

    expect($original->fresh()->isRevoked())->toBeTrue();
    $this->service->validate($originalToken);
})->throws(InvalidRegistrationLink::class);

it('getActiveLink returns null when nothing has been generated', function () {
    expect($this->service->getActiveLink($this->structure))->toBeNull();
});

it('getActiveLink returns the current usable link for the tenant', function () {
    ['link' => $link] = $this->service->generate($this->structure, $this->admin);

    expect($this->service->getActiveLink($this->structure)->id)->toBe($link->id);
});

it('scopes getActiveLink strictly to the requested tenant', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->service->generate($this->structure, $this->admin);

    expect($this->service->getActiveLink($otherStructure))->toBeNull();
});
