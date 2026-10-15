<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('deleting an account removes the member row entirely', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    expect($response->status())->toBeLessThan(500);
    expect(User::query()->where('id', $user->id)->exists())->toBeFalse();
    expect(User::withTrashed()->where('id', $user->id)->exists())->toBeFalse();
});

test('deleting an account removes every stored session of the member', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'another-device',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser',
        'payload' => base64_encode('second-device-session-payload'),
        'last_activity' => now()->timestamp,
    ]);

    expect(DB::table('sessions')->where('id', 'another-device')->exists())->toBeTrue();

    $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    expect(DB::table('sessions')->where('id', 'another-device')->exists())->toBeFalse();
    expect(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse();
});

test('deleting an account removes the member registered passkeys', function () {
    $user = User::factory()->create();

    DB::table('passkeys')->insert([
        'user_id' => $user->id,
        'name' => 'Test passkey',
        'credential_id' => base64_encode('test-credential-id'),
        'credential' => json_encode([
            'id' => base64_encode('test-credential-blob'),
            'rawId' => base64_encode('test-credential-blob'),
        ]),
        'last_used_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('passkeys')->where('user_id', $user->id)->exists())->toBeTrue();

    $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    expect(DB::table('passkeys')->where('user_id', $user->id)->exists())->toBeFalse();
});

test('deleting an account revokes pending invitations sent by the member', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);

    $inviter = User::factory()->create(['is_internal' => true]);

    $this->actingAs($inviter)->post(route('invitations.store'), [
        'email' => 'guest@example.com',
    ]);

    expect(DB::table('invitations')->count())->toBe(1);

    $this->actingAs($inviter)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    expect(User::query()->count())->toBe(0);

    $response = $this->post(route('register.store'), [
        'name' => 'Guest User',
        'email' => 'guest@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($response->status())->toBeLessThan(500);
    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

test('deleting an account keeps consumed invitation history and detaches the inviter', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);

    $inviter = User::factory()->create(['is_internal' => true]);

    $this->actingAs($inviter)->post(route('invitations.store'), [
        'email' => 'guest@example.com',
    ]);

    $invitation = DB::table('invitations')->orderByDesc('id')->first();
    expect($invitation)->not->toBeNull();
    expect($invitation->inviter_user_id)->toBe($inviter->id);

    $this->app['auth']->forgetGuards();

    $this->post(route('register.store'), [
        'name' => 'Guest User',
        'email' => 'guest@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::query()->count())->toBe(2);
    expect(DB::table('invitations')->where('id', $invitation->id)->value('accepted_at'))->not->toBeNull();

    $this->actingAs($inviter)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $row = DB::table('invitations')->where('id', $invitation->id)->first();

    expect($row)->not->toBeNull();
    expect($row->accepted_at)->not->toBeNull();
    expect($row->inviter_user_id)->toBeNull();
});

test('a member can re-register with the same email after deleting their account', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);

    $user = User::factory()->create(['email' => 'returning@ecole.fr']);

    $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    expect(User::withTrashed()->count())->toBe(0);

    $response = $this->post(route('register.store'), [
        'name' => 'Returning User',
        'email' => 'returning@ecole.fr',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($response->status())->toBeLessThan(500);
    $this->assertAuthenticated();
    expect(User::query()->count())->toBe(1);
});

test('a hytale identifier claimed by a deleted account can be claimed by another member', function () {
    $memberA = User::factory()->create();
    $memberB = User::factory()->create();

    $uuid = '123e4567-e89b-42d3-a456-426614174000';

    $this->actingAs($memberA)->put(route('accounts.update'), [
        'hytale_id' => $uuid,
    ]);

    expect($memberA->refresh()->hytale_id)->toBe($uuid);

    $this->actingAs($memberA)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $response = $this->actingAs($memberB)->put(route('accounts.update'), [
        'hytale_id' => $uuid,
    ]);

    expect($response->status())->toBeLessThan(500);
    $response->assertSessionHasNoErrors();
    expect($memberB->refresh()->hytale_id)->toBe($uuid);
});

test('deletion with a wrong password is refused and the account survives', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'wrong-password',
    ]);

    expect($response->status())->toBeLessThan(500);
    $this->assertAuthenticated();
    expect(User::query()->where('id', $user->id)->exists())->toBeTrue();
});

test('the member is logged out and redirected home after deleting their account', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('home', absolute: false));
});
