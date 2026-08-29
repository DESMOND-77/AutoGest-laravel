<?php

it('renders the landing page', function () {
    $this->get('/')->assertOk()->assertSee('Auto-GestBoard', escape: false);
});

it('renders the login page with a wrong-credentials message in French', function () {
    $this->get('/login')->assertOk();

    $this->post('/login', ['email' => 'nobody@example.test', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    expect(trans('auth.failed'))
        ->not->toBe('auth.failed')
        ->toContain('identifiants');
});
