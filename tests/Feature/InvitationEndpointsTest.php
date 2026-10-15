<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['members.invitation_limit' => 100]);
});

test('the invitations page is displayed for internal members', function () {
    $user = User::factory()->create(['is_internal' => true]);

    $response = $this
        ->actingAs($user)
        ->get(route('invitations.edit'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('invitations'));
});

test('guests are redirected to the login page on every invitation endpoint', function () {
    $invitation = Invitation::create([
        'inviter_user_id' => User::factory()->create(['is_internal' => true])->id,
        'email' => 'guest@example.com',
        'expires_at' => now()->addDays(7),
    ]);

    $this->get(route('invitations.edit'))->assertRedirect(route('login'));
    $this->post(route('invitations.store'), ['email' => 'new@example.com'])->assertRedirect(route('login'));
    $this->delete(route('invitations.destroy', $invitation))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('external members receive 403 on every invitation endpoint', function () {
    $inviter = User::factory()->create(['is_internal' => true]);
    $external = User::factory()->create(['is_external' => true]);

    $invitation = Invitation::create([
        'inviter_user_id' => $inviter->id,
        'email' => 'guest@example.com',
        'expires_at' => now()->addDays(7),
    ]);

    $this->actingAs($external)->get(route('invitations.edit'))->assertForbidden();
    $this->actingAs($external)->post(route('invitations.store'), ['email' => 'new@example.com'])->assertForbidden();
    $this->actingAs($external)->delete(route('invitations.destroy', $invitation))->assertForbidden();

    expect(Invitation::count())->toBe(1);
});

test('an internal member can create an invitation that expires in exactly seven days', function () {
    $inviter = User::factory()->create(['is_internal' => true]);

    $this->travelTo(now());

    $response = $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), ['email' => 'grace@example.com']);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('invitations.edit'));

    $invitation = Invitation::where('inviter_user_id', $inviter->id)->sole();

    expect($invitation->email)->toBe('grace@example.com');
    expect($invitation->inviter_user_id)->toBe($inviter->id);

    $expectedExpiry = now()->addDays(7);
    $actualExpiry = Carbon::parse($invitation->expires_at);

    expect(abs($actualExpiry->getTimestamp() - $expectedExpiry->getTimestamp()))->toBeLessThanOrEqual(5);
});

test('the email field is required and must be a valid email address', function (?string $email) {
    $inviter = User::factory()->create(['is_internal' => true]);

    $payload = ['email' => $email];
    if ($email === null) {
        unset($payload['email']);
    }

    $response = $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), $payload);

    $response->assertSessionHasErrors('email');

    expect(Invitation::count())->toBe(0);
})->with([
    'missing' => [null],
    'empty string' => [''],
    'not an email address' => ['grace-at-example-dot-com'],
]);

test('a school email address cannot be invited', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);

    $inviter = User::factory()->create(['is_internal' => true]);

    $response = $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), ['email' => 'student@ecole.fr']);

    $response->assertSessionHasErrors('email');

    expect(Invitation::count())->toBe(0);
});

test('an email that already belongs to an account cannot be invited', function () {
    $inviter = User::factory()->create(['is_internal' => true]);
    User::factory()->create(['email' => 'registered@example.com']);

    $response = $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), ['email' => 'registered@example.com']);

    $response->assertSessionHasErrors('email');

    expect(Invitation::count())->toBe(0);
});

test('an email with a usable invitation cannot be invited again', function () {
    $inviter = User::factory()->create(['is_internal' => true]);

    Invitation::create([
        'inviter_user_id' => $inviter->id,
        'email' => 'invited@example.com',
        'expires_at' => now()->addDays(7),
    ]);

    $response = $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), ['email' => 'invited@example.com']);

    $response->assertSessionHasErrors('email');

    expect(Invitation::count())->toBe(1);
});

test('an email can be invited again once its invitation is no longer usable', function (string $state) {
    $inviter = User::factory()->create(['is_internal' => true]);

    Invitation::factory()->{$state}()->create([
        'inviter_user_id' => $inviter->id,
        'email' => 'invited@example.com',
    ]);

    $response = $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), ['email' => 'invited@example.com']);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('invitations.edit'));

    $invitations = Invitation::where('inviter_user_id', $inviter->id)
        ->get()
        ->filter(fn (Invitation $invitation) => $invitation->email === 'invited@example.com');

    $usable = $invitations->filter(fn (Invitation $invitation) => $invitation->revoked_at === null
        && $invitation->accepted_at === null
        && Carbon::parse($invitation->expires_at)->isFuture());

    expect($usable)->toHaveCount(1);

    $invitation = $usable->first();

    expect($invitation->revoked_at)->toBeNull();
    expect($invitation->accepted_at)->toBeNull();
    expect(Carbon::parse($invitation->expires_at)->isFuture())->toBeTrue();
})->with([
    'revoked invitation' => ['revoked'],
    'accepted invitation' => ['accepted'],
    'expired invitation' => ['expired'],
]);

test('the invitation limit counts accepted and pending invitations', function () {
    config(['members.invitation_limit' => 5]);

    $inviter = User::factory()->create(['is_internal' => true]);

    Invitation::factory()->accepted()->create(['inviter_user_id' => $inviter->id]);
    Invitation::factory()->accepted()->create(['inviter_user_id' => $inviter->id]);

    foreach (['third@example.com', 'fourth@example.com', 'fifth@example.com'] as $email) {
        $this
            ->actingAs($inviter)
            ->post(route('invitations.store'), ['email' => $email])
            ->assertSessionHasNoErrors();
    }

    $response = $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), ['email' => 'overflow@example.com']);

    $response->assertSessionHasErrors('email');

    expect(Invitation::count())->toBe(5);
});

test('revoking an invitation frees a quota slot', function () {
    config(['members.invitation_limit' => 1]);

    $inviter = User::factory()->create(['is_internal' => true]);

    $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), ['email' => 'first@example.com'])
        ->assertSessionHasNoErrors();

    $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), ['email' => 'blocked@example.com'])
        ->assertSessionHasErrors('email');

    $invitation = Invitation::where('inviter_user_id', $inviter->id)->sole();

    $this
        ->actingAs($inviter)
        ->delete(route('invitations.destroy', $invitation))
        ->assertRedirect(route('invitations.edit'));

    $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), ['email' => 'second@example.com'])
        ->assertSessionHasNoErrors();

    expect(Invitation::count())->toBe(2);
});

test('an expired invitation does not consume a quota slot', function () {
    config(['members.invitation_limit' => 1]);

    $inviter = User::factory()->create(['is_internal' => true]);

    $this->travelTo(now()->subDays(10));
    Invitation::factory()->expired()->create(['inviter_user_id' => $inviter->id]);
    $this->travelTo(now());

    $response = $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), ['email' => 'fresh@example.com']);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('invitations.edit'));

    expect(Invitation::count())->toBe(2);
});

test("the invitations page lists only the inviter's own invitations newest first with a derived status", function () {
    $inviter = User::factory()->create(['is_internal' => true]);
    $now = now();

    $this->travelTo($now->copy()->subDays(10));
    Invitation::create([
        'inviter_user_id' => $inviter->id,
        'email' => 'expired@example.com',
        'expires_at' => now()->subDays(3),
    ]);

    $this->travelTo($now->copy()->subDays(5));
    Invitation::factory()->accepted()->create([
        'inviter_user_id' => $inviter->id,
        'email' => 'accepted@example.com',
        'expires_at' => $now->copy()->addDays(7),
    ]);

    $this->travelTo($now->copy()->subDays(2));
    Invitation::factory()->revoked()->create([
        'inviter_user_id' => $inviter->id,
        'email' => 'revoked@example.com',
        'expires_at' => $now->copy()->addDays(7),
    ]);

    $this->travelTo($now->copy()->subDays(1));
    Invitation::create([
        'inviter_user_id' => $inviter->id,
        'email' => 'pending@example.com',
        'expires_at' => now()->addDays(7),
    ]);

    Invitation::create([
        'inviter_user_id' => User::factory()->create(['is_internal' => true])->id,
        'email' => 'someone-elses@example.com',
        'expires_at' => now()->addDays(7),
    ]);

    $this->travelTo($now);

    $response = $this
        ->actingAs($inviter)
        ->get(route('invitations.edit'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('invitations', 4)
        ->where('invitations.0.email', 'pending@example.com')
        ->where('invitations.0.status', 'pending')
        ->where('invitations.1.email', 'revoked@example.com')
        ->where('invitations.1.status', 'revoked')
        ->where('invitations.2.email', 'accepted@example.com')
        ->where('invitations.2.status', 'accepted')
        ->where('invitations.3.email', 'expired@example.com')
        ->where('invitations.3.status', 'expired'));
});

test('the inviter can revoke a pending invitation', function () {
    $inviter = User::factory()->create(['is_internal' => true]);

    $invitation = Invitation::create([
        'inviter_user_id' => $inviter->id,
        'email' => 'invited@example.com',
        'expires_at' => now()->addDays(7),
    ]);

    $response = $this
        ->actingAs($inviter)
        ->delete(route('invitations.destroy', $invitation));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('invitations.edit'));

    expect($invitation->refresh()->revoked_at)->not->toBeNull();
});

test('revoking an already accepted invitation is rejected', function () {
    $inviter = User::factory()->create(['is_internal' => true]);

    $invitation = Invitation::factory()->accepted()->create([
        'inviter_user_id' => $inviter->id,
        'email' => 'invited@example.com',
        'expires_at' => now()->addDays(7),
    ]);

    $response = $this
        ->actingAs($inviter)
        ->delete(route('invitations.destroy', $invitation));

    $response->assertForbidden();

    expect($invitation->refresh()->revoked_at)->toBeNull();
});

test('revoking an already revoked invitation is rejected', function () {
    $inviter = User::factory()->create(['is_internal' => true]);

    $invitation = Invitation::create([
        'inviter_user_id' => $inviter->id,
        'email' => 'invited@example.com',
        'expires_at' => now()->addDays(7),
    ]);

    $this
        ->actingAs($inviter)
        ->delete(route('invitations.destroy', $invitation))
        ->assertRedirect(route('invitations.edit'));

    $response = $this
        ->actingAs($inviter)
        ->delete(route('invitations.destroy', $invitation));

    $response->assertForbidden();
});

test("revoking another member's invitation is rejected", function () {
    $inviter = User::factory()->create(['is_internal' => true]);
    $other = User::factory()->create(['is_internal' => true]);

    $invitation = Invitation::create([
        'inviter_user_id' => $other->id,
        'email' => 'invited@example.com',
        'expires_at' => now()->addDays(7),
    ]);

    $response = $this
        ->actingAs($inviter)
        ->delete(route('invitations.destroy', $invitation));

    $response->assertForbidden();

    expect($invitation->refresh()->revoked_at)->toBeNull();
});

test('the invited email is stored encrypted and the model returns the plain value', function () {
    $inviter = User::factory()->create(['is_internal' => true]);

    $this
        ->actingAs($inviter)
        ->post(route('invitations.store'), ['email' => 'secret-invite@example.com'])
        ->assertSessionHasNoErrors();

    $raw = DB::table('invitations')->sole();

    expect($raw->email)->not->toBe('secret-invite@example.com')
        ->not->toContain('secret-invite');

    $invitation = Invitation::sole();

    expect($invitation->email)->toBe('secret-invite@example.com');
});
