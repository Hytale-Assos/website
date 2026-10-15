<?php

use App\Models\User;
use App\Notifications\EmailAddressChanged;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();

    $this->actingAs($this->user);
});

test('email change requires the current password', function () {
    $previousEmail = $this->user->email;

    $response = $this->patch('/settings/profile', [
        'name' => 'Test User',
        'email' => 'new-email@example.com',
    ]);

    $response->assertSessionHasErrors('current_password');
    expect($this->user->fresh()->email)->toBe($previousEmail);
});

test('email change is refused with a wrong current password', function () {
    $previousEmail = $this->user->email;

    Notification::fake();

    $response = $this->patch('/settings/profile', [
        'name' => 'Test User',
        'email' => 'new-email@example.com',
        'current_password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('current_password');
    expect($this->user->fresh()->email)->toBe($previousEmail);
    Notification::assertNothingSent();
});

test('email change succeeds with the correct current password', function () {
    $response = $this->patch('/settings/profile', [
        'name' => 'Test User',
        'email' => 'new-email@example.com',
        'current_password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    expect($this->user->fresh()->email)->toBe('new-email@example.com');
});

test('email change sends a security notice to the previous email address', function () {
    $previousEmail = $this->user->email;

    Notification::fake();

    $this->patch('/settings/profile', [
        'name' => 'Test User',
        'email' => 'new-email@example.com',
        'current_password' => 'password',
    ]);

    Notification::assertSentOnDemand(
        EmailAddressChanged::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $previousEmail
    );
});

test('email change sends a verification notification to the new email address', function () {
    Notification::fake();

    $this->patch('/settings/profile', [
        'name' => 'Test User',
        'email' => 'new-email@example.com',
        'current_password' => 'password',
    ]);

    Notification::assertSentTo($this->user, VerifyEmail::class);
});

test('email change removes all other sessions', function () {
    DB::table('sessions')->insert([
        'id' => 'another-device',
        'user_id' => $this->user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Agent',
        'payload' => 'test-payload',
        'last_activity' => time(),
    ]);

    $this->patch('/settings/profile', [
        'name' => 'Test User',
        'email' => 'new-email@example.com',
        'current_password' => 'password',
    ]);

    expect(DB::table('sessions')->where('user_id', $this->user->id)->count())->toBe(0);
});

test('email change rotates the remember me token', function () {
    $tokenBefore = Str::random(60);
    $this->user->forceFill(['remember_token' => $tokenBefore])->save();

    $this->patch('/settings/profile', [
        'name' => 'Test User',
        'email' => 'new-email@example.com',
        'current_password' => 'password',
    ]);

    expect($this->user->fresh()->remember_token)->not->toBe($tokenBefore);
});

test('profile update without an email change requires no password', function () {
    $previousEmail = $this->user->email;

    Notification::fake();

    DB::table('sessions')->insert([
        'id' => 'another-device',
        'user_id' => $this->user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Agent',
        'payload' => 'test-payload',
        'last_activity' => time(),
    ]);

    $response = $this->patch('/settings/profile', [
        'name' => 'New Name',
        'email' => $previousEmail,
    ]);

    $response->assertSessionHasNoErrors();
    $user = $this->user->fresh();
    expect($user->name)->toBe('New Name');
    expect($user->email)->toBe($previousEmail);
    expect(DB::table('sessions')->where('user_id', $user->id)->pluck('id')->all())->toBe(['another-device']);
    Notification::assertNothingSent();
});
