<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registrations from the same IP return 429 once the registration limiter is exhausted', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);

    foreach (['first@ecole.fr', 'second@ecole.fr', 'third@ecole.fr', 'fourth@ecole.fr', 'fifth@ecole.fr'] as $email) {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        expect($response->status())->not->toBe(429);

        $this->post(route('logout'));
    }

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'sixth@ecole.fr',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertTooManyRequests();
});

test('password reset link requests for the same email from the same IP return 429 once the limiter is exhausted', function () {
    $payload = ['email' => 'member@example.com'];

    foreach (range(1, 6) as $attempt) {
        $response = $this->post(route('password.email'), $payload);

        expect($response->status())->not->toBe(429);
    }

    $this->post(route('password.email'), $payload)->assertTooManyRequests();
});

test('exhausting the registration bucket does not affect the login screen', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);

    foreach (['first@ecole.fr', 'second@ecole.fr', 'third@ecole.fr', 'fourth@ecole.fr', 'fifth@ecole.fr', 'sixth@ecole.fr'] as $email) {
        $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->post(route('logout'));
    }

    $response = $this->get(route('login'));

    $response->assertOk();
    expect($response->status())->not->toBe(429);
});
