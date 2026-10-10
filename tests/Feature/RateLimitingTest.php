<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

test('profile, identity, and account updates share one settings rate limit bucket', function () {
    RateLimiter::for('settings', fn () => Limit::perMinute(2));

    $user = User::factory()->create();

    $first = $this->actingAs($user)->patch(route('profile.update'));
    $second = $this->actingAs($user)->put(route('identity.update'));

    expect($first->status())->not->toBe(429);
    expect($second->status())->not->toBe(429);

    $this->actingAs($user)->put(route('accounts.update'))->assertTooManyRequests();
});

test('the settings rate limit bucket is keyed to the signed-in user', function () {
    RateLimiter::for(
        'settings',
        fn (Request $request) => Limit::perMinute(1)->by($request->user()?->id ?: $request->ip())
    );

    $jane = User::factory()->create();
    $john = User::factory()->create();

    $this->actingAs($jane)->patch(route('profile.update'));
    $this->actingAs($jane)->patch(route('profile.update'))->assertTooManyRequests();

    $response = $this->actingAs($john)->patch(route('profile.update'));

    expect($response->status())->not->toBe(429);
});

test('invitation creation and revocation share one invitations rate limit bucket', function () {
    config(['members.invitation_limit' => 100]);

    RateLimiter::for('invitations', fn () => Limit::perMinute(2));

    $inviter = User::factory()->create(['is_internal' => true]);

    $invitation = Invitation::create([
        'inviter_user_id' => $inviter->id,
        'email' => 'invited@example.com',
        'expires_at' => now()->addDays(7),
    ]);

    $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), ['email' => 'one@example.com'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('invitations.edit'));

    $this
        ->actingAs($inviter)
        ->delete(route('invitations.destroy', $invitation))
        ->assertRedirect(route('invitations.edit'));

    $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), ['email' => 'two@example.com'])
        ->assertTooManyRequests();

    expect(Invitation::count())->toBe(2);
    expect(Invitation::get()->filter(fn (Invitation $persisted) => $persisted->email === 'two@example.com'))->toBeEmpty();
});

test('whitelist additions and removals share one whitelist rate limit bucket', function () {
    RateLimiter::for('whitelist', fn () => Limit::perMinute(2));

    $user = User::factory()->create(['email_verified_at' => now()]);

    $first = $this->actingAs($user)->post(route('whitelist.store'), []);
    $second = $this->actingAs($user)->post(route('whitelist.store'), []);

    expect($first->status())->not->toBe(429);
    expect($second->status())->not->toBe(429);

    $this->actingAs($user)->delete(route('whitelist.destroy', 'not-a-real-entry'))->assertTooManyRequests();
});

test('data export requests return 429 once the data-export limiter is exhausted', function () {
    RateLimiter::for('data-export', fn () => Limit::perMinute(2));

    $user = User::factory()->create();

    $first = $this->actingAs($user)->get(route('data.export'));
    $second = $this->actingAs($user)->get(route('data.export'));

    expect($first->getStatusCode())->not->toBe(429);
    expect($second->getStatusCode())->not->toBe(429);

    $this->actingAs($user)->get(route('data.export'))->assertTooManyRequests();
});

test('the oauth redirect and callback share one oauth rate limit bucket', function () {
    RateLimiter::for('oauth', fn () => Limit::perMinute(2));

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('linked.redirect', 'discord'))->assertRedirect();
    $this->actingAs($user)->get(route('linked.redirect', 'discord'))->assertRedirect();

    $this->actingAs($user)->get(route('linked.callback', 'discord'))->assertTooManyRequests();
});

test('password updates return 429 once the password limiter is exhausted without executing the update', function () {
    RateLimiter::for('password', fn () => Limit::perMinute(2));

    $user = User::factory()->create(['email_verified_at' => now()]);

    $payload = [
        'current_password' => 'not-the-current-password',
        'password' => 'n3w-S3cure-P4ssw0rd',
        'password_confirmation' => 'n3w-S3cure-P4ssw0rd',
    ];

    $first = $this->actingAs($user)->put(route('user-password.update'), $payload);
    $second = $this->actingAs($user)->put(route('user-password.update'), $payload);

    expect($first->status())->not->toBe(429);
    expect($second->status())->not->toBe(429);

    $hashBefore = $user->refresh()->password;

    $this->actingAs($user)->put(route('user-password.update'), $payload)->assertTooManyRequests();

    expect($user->refresh()->password)->toBe($hashBefore);
});

test('exhausting one rate limit bucket does not affect the others', function () {
    config(['members.invitation_limit' => 100]);

    RateLimiter::for('invitations', fn () => Limit::perMinute(1));

    $user = User::factory()->create(['is_internal' => true]);

    $this
        ->actingAs($user)
        ->post(route('invitations.store'), ['email' => 'one@example.com'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('invitations.edit'));

    $this
        ->actingAs($user)
        ->post(route('invitations.store'), ['email' => 'two@example.com'])
        ->assertTooManyRequests();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'Updated Name', 'email' => $user->email]);

    $response->assertSessionHasNoErrors();

    expect($response->status())->not->toBe(429);
});
