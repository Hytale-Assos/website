<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());
});

test('reset link request does not reveal whether the email belongs to a member', function () {
    Notification::fake();

    $user = User::factory()->create();

    $knownEmail = $this->post(route('password.email'), ['email' => $user->email]);

    $knownEmail
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $knownStatus = session('status');
    $knownTarget = $knownEmail->getTargetUrl();

    $unknownEmail = $this->post(route('password.email'), ['email' => 'unknown.'.uniqid().'@example.test']);

    $unknownEmail->assertSessionHasNoErrors();

    expect($unknownEmail->status())
        ->toBe($knownEmail->status())
        ->and(session('status'))->toBe($knownStatus)
        ->and($unknownEmail->getTargetUrl())->toBe($knownTarget);
});

test('reset with a token issued for another email reports the same error as an invalid token', function () {
    Notification::fake();

    $member = User::factory()->create();
    $other = User::factory()->create();

    $token = null;

    $this->post(route('password.email'), ['email' => $other->email]);

    Notification::assertSentTo($other, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $crossEmail = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $member->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $crossEmail->assertSessionHasErrors('email');

    $crossEmailError = session('errors')->get('email');

    $invalidToken = $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $member->email,
        'password' => 'another-password',
        'password_confirmation' => 'another-password',
    ]);

    $invalidToken->assertSessionHasErrors('email');

    expect(session('errors')->get('email'))->toBe($crossEmailError);
});

test('7th reset link request for the same email within a minute is rejected with 429', function () {
    Notification::fake();

    $this->freezeTime();

    $user = User::factory()->create();

    for ($attempt = 1; $attempt <= 6; $attempt++) {
        $response = $this->post(route('password.email'), ['email' => $user->email]);

        expect($response->status())->not->toBe(429);
    }

    $this->post(route('password.email'), ['email' => $user->email])->assertTooManyRequests();
});

test('reset link requests for more than 20 different emails within a minute are rejected with 429', function () {
    Notification::fake();

    $this->freezeTime();

    for ($attempt = 1; $attempt <= 20; $attempt++) {
        $response = $this->post(route('password.email'), ['email' => "member{$attempt}@example.test"]);

        expect($response->status())->not->toBe(429);
    }

    $this->post(route('password.email'), ['email' => 'member1@example.test'])->assertTooManyRequests();
});

test('7th password reset submission within a minute is rejected with 429', function () {
    Notification::fake();

    $this->freezeTime();

    $user = User::factory()->create();

    for ($attempt = 1; $attempt <= 6; $attempt++) {
        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        expect($response->status())->not->toBe(429);
    }

    $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertTooManyRequests();
});

test('member can still reset their password with a valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});
