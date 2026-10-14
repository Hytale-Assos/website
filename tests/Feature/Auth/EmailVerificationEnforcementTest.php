<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('registration sends an email verification notification to the new member', function () {
    Notification::fake();

    config(['members.school_email_domains' => ['ecole.fr']]);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'member@ecole.fr',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $user = User::query()->latest('id')->first();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('changing the email address clears the verified status and sends a new verification notification', function () {
    Notification::fake();

    config(['members.school_email_domains' => ['ecole.fr']]);

    $user = User::factory()->create(['email' => 'verified@ecole.fr']);

    $response = $this->actingAs($user)->patch('/settings/profile', [
        'name' => $user->name,
        'email' => 'new-address@ecole.fr',
        'current_password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user->fresh(), VerifyEmail::class);
});

test('verified members can access the dashboard without being redirected to the verification notice', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
});
