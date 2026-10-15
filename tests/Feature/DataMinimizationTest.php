<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

test('shares exactly the allowlisted member attributes with a signed-in member', function () {
    $user = User::factory()->create([
        'firstname' => 'Ada',
        'lastname' => 'Lovelace',
        'grade_level' => '1i',
        'school_email' => 'ada@ecole.fr',
        'discord_id' => '987654321',
        'discord_nickname' => 'ada',
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        'hytale_nickname' => 'ada_hytale',
        'is_public' => true,
    ]);
    $user->forceFill(['pending_school_email' => 'new-school@ecole.fr'])->save();

    $response = $this->actingAs($user)->get('/settings/profile');

    $response->assertOk();

    $sharedUser = null;

    $response->assertInertia(function (AssertableInertia $page) use (&$sharedUser) {
        $page->has('auth.user');
        $sharedUser = $page->toArray()['props']['auth']['user'];
    });

    expect($sharedUser)->toBeArray();
    expect(array_keys($sharedUser))->toEqualCanonicalizing([
        'name',
        'email',
        'email_verified_at',
        'firstname',
        'lastname',
        'grade_level',
        'school_email',
        'is_internal',
        'is_public',
        'discord_id',
        'discord_nickname',
        'hytale_id',
        'hytale_nickname',
        'hytale_account_verified_at',
    ]);
    expect($sharedUser['email'])->toBe($user->email);
    expect($sharedUser['firstname'])->toBe('Ada');
    expect($sharedUser['hytale_id'])->toBe('123e4567-e89b-42d3-a456-426614174000');
});

test('shares no user data with a guest', function () {
    $response = $this->get('/settings/profile');

    $response->assertRedirect();

    $login = $this->get($response->headers->get('Location'));

    $login->assertOk();

    $sharedUser = 'sentinel';

    $login->assertInertia(function (AssertableInertia $page) use (&$sharedUser) {
        $sharedUser = $page->toArray()['props']['auth']['user'] ?? null;
    });

    expect($sharedUser)->toBeNull();
});

test('keeps the blind-index hash untouched when a hash column is mass-assigned', function () {
    $user = User::factory()->create([
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
    ]);

    $storedHash = DB::table('users')->where('id', $user->id)->value('hytale_id_hash');

    expect($storedHash)->toBeString()->not->toBeEmpty();

    $user->fill(['hytale_id_hash' => str_repeat('f', 64)])->save();

    $storedHashAfter = DB::table('users')->where('id', $user->id)->value('hytale_id_hash');

    expect($storedHashAfter)->toBe($storedHash);
    expect($storedHashAfter)->not->toBe(str_repeat('f', 64));
});

test('refuses an invitation for an email that already has an account', function () {
    $inviter = User::factory()->create(['is_internal' => true]);
    User::factory()->create(['email' => 'existing-member@example.com']);

    $response = $this->actingAs($inviter)->post(route('invitations.store'), [
        'email' => 'existing-member@example.com',
    ]);

    $response->assertSessionHasErrors('email');
});

test('shows the same refusal for an email invited by another member as for an email with an account', function () {
    $inviter = User::factory()->create(['is_internal' => true]);
    Invitation::factory()->create([
        'email' => 'invited-by-other@example.com',
        'inviter_user_id' => User::factory()->create()->id,
    ]);
    User::factory()->create(['email' => 'existing-member@example.com']);

    $memberResponse = $this->actingAs($inviter)->post(route('invitations.store'), [
        'email' => 'existing-member@example.com',
    ]);

    $memberResponse->assertSessionHasErrors('email');

    $messageForMember = session('errors')->first('email');

    $otherInviteeResponse = $this->actingAs($inviter)->post(route('invitations.store'), [
        'email' => 'invited-by-other@example.com',
    ]);

    $otherInviteeResponse->assertSessionHasErrors('email');

    expect($messageForMember)->toBeString()->not->toBeEmpty();
    expect(session('errors')->first('email'))->toBe($messageForMember);
});

test('shows a distinct refusal for an email the member invited themselves', function () {
    $inviter = User::factory()->create(['is_internal' => true]);
    Invitation::factory()->create([
        'email' => 'invited-by-self@example.com',
        'inviter_user_id' => $inviter->id,
    ]);
    User::factory()->create(['email' => 'existing-member@example.com']);

    $memberResponse = $this->actingAs($inviter)->post(route('invitations.store'), [
        'email' => 'existing-member@example.com',
    ]);

    $memberResponse->assertSessionHasErrors('email');

    $messageForMember = session('errors')->first('email');

    $ownInviteeResponse = $this->actingAs($inviter)->post(route('invitations.store'), [
        'email' => 'invited-by-self@example.com',
    ]);

    $ownInviteeResponse->assertSessionHasErrors('email');

    $ownMessage = session('errors')->first('email');

    expect($ownMessage)->toBeString()->not->toBeEmpty();
    expect($ownMessage)->not->toBe($messageForMember);
});

test('accepts a valid invitation for a fresh external email', function () {
    $inviter = User::factory()->create(['is_internal' => true]);

    $response = $this->actingAs($inviter)->post(route('invitations.store'), [
        'email' => 'guest@example.com',
    ]);

    $response->assertSessionHasNoErrors();
    expect(Invitation::where('inviter_user_id', $inviter->id)->count())->toBe(1);
});
