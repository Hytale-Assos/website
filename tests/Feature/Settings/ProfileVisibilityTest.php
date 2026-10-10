<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('is public flag can be updated with boolean-ish values', function (bool|int|string $value, bool $expected) {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_public' => $value,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->is_public)->toBe($expected);
})->with([
    'true' => [true, true],
    'false' => [false, false],
    'integer 1' => [1, true],
    'integer 0' => [0, false],
    'string "1"' => ['1', true],
    'string "0"' => ['0', false],
]);

test('is public flag is unchanged when omitted from the request', function () {
    $user = User::factory()->create(['is_public' => true]);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->is_public)->toBeTrue();
});

test('is public flag must be a valid boolean', function (string $value) {
    $user = User::factory()->create(['is_public' => true]);

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_public' => $value,
        ]);

    $response
        ->assertSessionHasErrors('is_public')
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->is_public)->toBeTrue();
})->with([
    'banana' => ['banana'],
    'yes' => ['yes'],
]);

test('is public flag is stored in clear in the database', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_public' => true,
        ])
        ->assertSessionHasNoErrors();

    $raw = DB::table('users')->where('id', $user->id)->value('is_public');

    expect($raw)->toBe(true);
});

test('is public flag defaults to false for new users', function () {
    $user = User::factory()->create();

    expect($user->is_public)->toBeFalse();
});
