<?php

use App\Models\User;
use App\Notifications\SchoolEmailConfirmation;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

function requestSchoolEmailChangeAndCaptureUrl($testCase, User $member, string $newEmail): string
{
    Notification::fake();

    $testCase
        ->actingAs($member)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'new_school_email' => $newEmail,
            'current_password' => 'password',
        ])
        ->assertSessionHasNoErrors();

    $capturedUrl = null;

    Notification::assertSentOnDemand(SchoolEmailConfirmation::class, function ($notification, $channels, $notifiable) use (&$capturedUrl, $newEmail) {
        $capturedUrl = $notification->verificationUrl;

        return $notifiable->routes['mail'] === $newEmail;
    });

    expect($capturedUrl)->toBeString();

    return $capturedUrl;
}

test('an internal member requesting a new school email with their current password gets a confirmation email to the new address and a pending claim', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $member = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'current@ecole.fr',
    ]);

    Notification::fake();

    $response = $this
        ->actingAs($member)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'new_school_email' => 'new@ecole.fr',
            'current_password' => 'password',
        ]);

    $response->assertSessionHasNoErrors();

    Notification::assertSentOnDemand(SchoolEmailConfirmation::class, function ($notification, $channels, $notifiable) {
        expect($notification->verificationUrl)->toBeString();

        return $notifiable->routes['mail'] === 'new@ecole.fr';
    });

    $member->refresh();

    expect($member->school_email)->toBe('current@ecole.fr');
    expect($member->pending_school_email)->toBe('new@ecole.fr');
});

test('requesting a new school email without the correct current password is refused', function (array $passwordInput) {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $member = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'current@ecole.fr',
    ]);

    Notification::fake();

    $response = $this
        ->actingAs($member)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'new_school_email' => 'new@ecole.fr',
        ] + $passwordInput);

    $response->assertSessionHasErrors('current_password');

    $member->refresh();

    expect($member->school_email)->toBe('current@ecole.fr');
    expect($member->pending_school_email)->toBeNull();

    Notification::assertNothingSent();
})->with([
    'missing password' => [[]],
    'wrong password' => [['current_password' => 'wrong-password']],
]);

test('a new school email outside the configured school domains is refused', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $member = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'current@ecole.fr',
    ]);

    Notification::fake();

    $response = $this
        ->actingAs($member)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'new_school_email' => 'grace@gmail.com',
            'current_password' => 'password',
        ]);

    $response->assertSessionHasErrors('new_school_email');

    $member->refresh();

    expect($member->school_email)->toBe('current@ecole.fr');
    expect($member->pending_school_email)->toBeNull();

    Notification::assertNothingSent();
});

test('a new school email that is already another member\'s confirmed school email is refused', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $member = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'current@ecole.fr',
    ]);
    User::factory()->create([
        'is_internal' => true,
        'school_email' => 'taken@ecole.fr',
    ]);

    Notification::fake();

    $response = $this
        ->actingAs($member)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'new_school_email' => 'taken@ecole.fr',
            'current_password' => 'password',
        ]);

    $response->assertSessionHasErrors('new_school_email');

    $member->refresh();

    expect($member->school_email)->toBe('current@ecole.fr');
    expect($member->pending_school_email)->toBeNull();

    Notification::assertNothingSent();
});

test('a new school email that is already another member\'s pending claim is refused', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $member = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'current@ecole.fr',
    ]);
    $otherMember = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'other@ecole.fr',
    ]);

    requestSchoolEmailChangeAndCaptureUrl($this, $otherMember, 'pending@ecole.fr');

    expect($otherMember->refresh()->pending_school_email)->toBe('pending@ecole.fr');

    $response = $this
        ->actingAs($member)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'new_school_email' => 'pending@ecole.fr',
            'current_password' => 'password',
        ]);

    $response->assertSessionHasErrors('new_school_email');

    $member->refresh();

    expect($member->school_email)->toBe('current@ecole.fr');
    expect($member->pending_school_email)->toBeNull();
});

test('requesting the current confirmed school email as the new address sends no email and records no pending claim', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $member = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'current@ecole.fr',
    ]);

    Notification::fake();

    $response = $this
        ->actingAs($member)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'new_school_email' => 'current@ecole.fr',
            'current_password' => 'password',
        ]);

    $response->assertSessionHasNoErrors();

    $member->refresh();

    expect($member->school_email)->toBe('current@ecole.fr');
    expect($member->pending_school_email)->toBeNull();

    Notification::assertNothingSent();
});

test('a member confirming a valid link while signed in has their pending claim promoted to their confirmed school email', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $member = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'current@ecole.fr',
    ]);

    $confirmationUrl = requestSchoolEmailChangeAndCaptureUrl($this, $member, 'new@ecole.fr');

    expect($member->refresh()->pending_school_email)->toBe('new@ecole.fr');

    $this
        ->actingAs($member)
        ->get($confirmationUrl)
        ->assertSessionHasNoErrors();

    $member->refresh();

    expect($member->school_email)->toBe('new@ecole.fr');
    expect($member->pending_school_email)->toBeNull();
});

test('an expired confirmation link is refused and leaves the confirmed school email and the pending claim untouched', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $member = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'current@ecole.fr',
    ]);

    $confirmationUrl = requestSchoolEmailChangeAndCaptureUrl($this, $member, 'new@ecole.fr');

    $this->travel(25)->hours();

    $this
        ->actingAs($member)
        ->get($confirmationUrl);

    $member->refresh();

    expect($member->school_email)->toBe('current@ecole.fr');
    expect($member->pending_school_email)->toBe('new@ecole.fr');
});

test('a confirmation link whose address hash was tampered with is refused', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $member = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'current@ecole.fr',
    ]);

    $confirmationUrl = requestSchoolEmailChangeAndCaptureUrl($this, $member, 'new@ecole.fr');

    $tamperedUrl = preg_replace('/([?&]hash=)[^&]*/', '$1tampered', $confirmationUrl);

    if ($tamperedUrl === $confirmationUrl) {
        $tamperedUrl = preg_replace('~/([^/?]+)(\?|$)~', '/tampered$2', $confirmationUrl);
    }

    expect($tamperedUrl)->not->toBe($confirmationUrl);

    $this
        ->actingAs($member)
        ->get($tamperedUrl);

    $member->refresh();

    expect($member->school_email)->toBe('current@ecole.fr');
    expect($member->pending_school_email)->toBe('new@ecole.fr');
});

test('a member cannot confirm a link that was issued for another member', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $member = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'current@ecole.fr',
    ]);
    $otherMember = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'other@ecole.fr',
    ]);

    $otherMemberUrl = requestSchoolEmailChangeAndCaptureUrl($this, $otherMember, 'other-new@ecole.fr');

    $this
        ->actingAs($member)
        ->get($otherMemberUrl);

    $member->refresh();

    expect($member->school_email)->toBe('current@ecole.fr');
    expect($member->pending_school_email)->toBeNull();

    $otherMember->refresh();

    expect($otherMember->school_email)->toBe('other@ecole.fr');
    expect($otherMember->pending_school_email)->toBe('other-new@ecole.fr');
});

test('a guest hitting a confirmation link is redirected to the login page', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $member = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'current@ecole.fr',
    ]);

    $confirmationUrl = requestSchoolEmailChangeAndCaptureUrl($this, $member, 'new@ecole.fr');

    auth()->logout();

    $this->assertGuest();

    $response = $this->get($confirmationUrl);

    $response->assertRedirect(route('login'));
});

test('registration with a school email that is already another member\'s confirmed school email is refused', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    config(['members.school_email_domains' => ['ecole.fr']]);
    User::factory()->create([
        'is_internal' => true,
        'school_email' => 'taken@ecole.fr',
    ]);

    $usersCountBefore = User::count();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'taken@ecole.fr',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');

    expect(User::count())->toBe($usersCountBefore);
});

test('an internal member updating only their identity data triggers no email', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $member = User::factory()->create([
        'is_internal' => true,
        'school_email' => 'current@ecole.fr',
    ]);

    Notification::fake();

    $response = $this
        ->actingAs($member)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
        ]);

    $response->assertSessionHasNoErrors();

    Notification::assertNothingSent();
});

test('a new school email submitted by an external member is ignored', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $member = User::factory()->create(['is_external' => true]);

    Notification::fake();

    $response = $this
        ->actingAs($member)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'new_school_email' => 'new@ecole.fr',
            'current_password' => 'password',
        ]);

    $response->assertSessionHasNoErrors();

    $member->refresh();

    expect($member->school_email)->toBeNull();
    expect($member->pending_school_email)->toBeNull();

    Notification::assertNothingSent();
});
