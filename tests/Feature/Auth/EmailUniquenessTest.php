<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('registration rejects an email that already belongs to an account', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    User::factory()->create(['email' => 'taken@example.com']);

    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'Second User',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(User::count())->toBe(1);
});

test('registration treats a differently cased and whitespace padded email as the same email', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    User::factory()->create(['email' => 'taken@example.com']);

    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'Second User',
            'email' => '  TAKEN@Example.COM  ',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(User::count())->toBe(1);
});

test('profile update rejects an email that belongs to another user', function () {
    $user = User::factory()->create(['email' => 'first@example.com']);
    User::factory()->create(['email' => 'second@example.com']);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'First User',
            'email' => 'second@example.com',
        ])
        ->assertSessionHasErrors('email');

    expect($user->refresh()->email)->toBe('first@example.com');
});

test('profile update accepts keeping the same email with different casing and padding', function () {
    $user = User::factory()->create(['email' => 'keep@example.com']);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Renamed User',
            'email' => '  KEEP@Example.COM  ',
        ])
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Renamed User');

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'keep@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
});
