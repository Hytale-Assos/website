<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registration with a school email cannot be forged into an external account with numeric flags', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);

    $this->post(route('register.store'), [
        'name' => 'Forged Student',
        'email' => 'student@ecole.fr',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_internal' => 0,
        'is_external' => 1,
    ]);

    $user = User::all()->firstWhere('email', 'student@ecole.fr');

    expect($user)->not->toBeNull()
        ->and($user->is_internal)->toBeTrue()
        ->and($user->is_external)->toBeFalse();
});

test('registration with a school email cannot be forged into an external account with string flags', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);

    $this->post(route('register.store'), [
        'name' => 'Forged Student',
        'email' => 'student@ecole.fr',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_internal' => 'false',
        'is_external' => 'true',
    ]);

    $user = User::all()->firstWhere('email', 'student@ecole.fr');

    expect($user)->not->toBeNull()
        ->and($user->is_internal)->toBeTrue()
        ->and($user->is_external)->toBeFalse();
});

test('an internal member cannot change their member status via profile update', function () {
    $user = User::factory()->create([
        'is_internal' => true,
        'is_external' => false,
    ]);

    $response = $this->actingAs($user)->patch('/settings/profile', [
        'name' => 'Still Internal',
        'email' => $user->email,
        'is_public' => true,
        'is_internal' => 0,
        'is_external' => 1,
    ]);

    $response->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Still Internal')
        ->and($user->is_internal)->toBeTrue()
        ->and($user->is_external)->toBeFalse();
});

test('an external member cannot promote themselves to internal via profile update', function () {
    $user = User::factory()->create([
        'is_internal' => false,
        'is_external' => true,
    ]);

    $response = $this->actingAs($user)->patch('/settings/profile', [
        'name' => 'Still External',
        'email' => $user->email,
        'is_public' => false,
        'is_internal' => 1,
        'is_external' => 0,
    ]);

    $response->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Still External')
        ->and($user->is_internal)->toBeFalse()
        ->and($user->is_external)->toBeTrue();
});
