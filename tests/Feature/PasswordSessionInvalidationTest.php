<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

function createSessionForAnotherDevice(User $user): void
{
    DB::table('sessions')->insert([
        'id' => 'another-device',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => 'test',
        'last_activity' => time(),
    ]);
}

function passwordChangePayload(string $currentPassword, string $newPassword): array
{
    return [
        'current_password' => $currentPassword,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ];
}

test('a member stays signed in on the browser that changed the password', function () {
    $user = User::factory()->create(['password' => Hash::make('current-password')]);

    $response = $this
        ->actingAs($user)
        ->put(route('user-password.update'), passwordChangePayload('current-password', 'new-password-123'));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($response->headers->get('Location'))->not()->toBe(route('login'));

    $this->assertAuthenticatedAs($user);
});

test('changing the password removes the sessions of other devices', function () {
    $user = User::factory()->create(['password' => Hash::make('current-password')]);
    createSessionForAnotherDevice($user);

    $this
        ->actingAs($user)
        ->put(route('user-password.update'), passwordChangePayload('current-password', 'new-password-123'))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(DB::table('sessions')->where('id', 'another-device')->exists())->toBeFalse();
});

test('changing the password rotates the remember-me token', function () {
    $user = User::factory()->create(['password' => Hash::make('current-password')]);
    $user->forceFill(['remember_token' => 'original-remember-token'])->save();

    $this
        ->actingAs($user)
        ->put(route('user-password.update'), passwordChangePayload('current-password', 'new-password-123'))
        ->assertSessionHasNoErrors();

    expect($user->refresh()->remember_token)
        ->not->toBeNull()
        ->not->toBe('original-remember-token');
});

test('a forgotten-password reset removes all stored sessions and rotates the remember-me token', function () {
    $user = User::factory()->create();
    $user->forceFill(['remember_token' => 'original-remember-token'])->save();

    DB::table('sessions')->insert([
        [
            'id' => 'another-device',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'test',
            'last_activity' => time(),
        ],
        [
            'id' => 'shared-tablet',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'test',
            'last_activity' => time(),
        ],
    ]);

    $token = Password::createToken($user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
    expect($user->refresh()->remember_token)
        ->not->toBeNull()
        ->not->toBe('original-remember-token');
});

test('a password change with a wrong current password changes nothing', function () {
    $user = User::factory()->create(['password' => Hash::make('current-password')]);
    $user->forceFill(['remember_token' => 'original-remember-token'])->save();
    createSessionForAnotherDevice($user);

    $response = $this
        ->actingAs($user)
        ->put(route('user-password.update'), passwordChangePayload('wrong-password', 'new-password-123'));

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect();

    expect(DB::table('sessions')->where('id', 'another-device')->exists())->toBeTrue();
    expect($user->refresh()->remember_token)->toBe('original-remember-token');
});
