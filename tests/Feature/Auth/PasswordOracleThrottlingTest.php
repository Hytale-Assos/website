<?php

use App\Models\User;

test('password confirmation endpoint throttles repeated wrong password attempts', function () {
    $this->freezeTime();

    $user = User::factory()->create();

    $this->actingAs($user);

    for ($attempt = 1; $attempt <= 6; $attempt++) {
        $response = $this->post(route('password.confirm.store'), ['password' => 'wrong-password']);

        expect($response->status())->not->toBe(429);
    }

    $this->post(route('password.confirm.store'), ['password' => 'wrong-password'])
        ->assertTooManyRequests();
});

test('account deletion endpoint throttles repeated wrong password attempts', function () {
    $this->freezeTime();

    $user = User::factory()->create();

    $this->actingAs($user);

    for ($attempt = 1; $attempt <= 6; $attempt++) {
        $response = $this->delete(route('profile.destroy'), ['password' => 'wrong-password']);

        expect($response->status())->not->toBe(429);
    }

    $this->delete(route('profile.destroy'), ['password' => 'wrong-password'])
        ->assertTooManyRequests();
});

test('two-factor confirmation endpoint throttles repeated attempts', function () {
    $this->freezeTime();

    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user);

    for ($attempt = 1; $attempt <= 6; $attempt++) {
        $response = $this->post(route('two-factor.confirm'), ['code' => '123123']);

        expect($response->status())->not->toBe(429);
    }

    $this->post(route('two-factor.confirm'), ['code' => '123123'])
        ->assertTooManyRequests();
});

test('correct password passes confirmation without throttling and marks the session confirmed', function () {
    $this->freezeTime();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('password.confirm.store'), ['password' => 'password']);

    expect($response->status())->not->toBe(429);

    $this->get(route('password.confirmation'))
        ->assertOk()
        ->assertJson(['confirmed' => true]);
});
