<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

test('a registered account keeps working while its personal data is not stored in clear', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'Confidential User',
        'email' => 'confidential@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $raw = DB::table('users')->where('id', auth()->id())->sole();

    expect($raw->email)->not->toBe('confidential@example.com')
        ->not->toContain('confidential@example.com');
    expect($raw->name)->not->toBe('Confidential User')
        ->not->toContain('Confidential User');

    $user = User::findOrFail(auth()->id());

    expect($user->name)->toBe('Confidential User');
    expect($user->email)->toBe('confidential@example.com');

    $this->post(route('logout'));

    $this->assertGuest();

    $this->post(route('login.store'), [
        'email' => 'confidential@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
});

test('the password reset flow stores no email in clear and still works end to end', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->skipUnlessFortifyHas(Features::resetPasswords());

    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Reset Me',
        'email' => 'reset-me@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $user = User::findOrFail(auth()->id());

    $this->post(route('logout'));

    $this->assertGuest();

    $this->post(route('password.email'), ['email' => 'reset-me@example.com'])
        ->assertSessionHasNoErrors();

    $token = null;

    Notification::assertSentTo(
        $user,
        ResetPassword::class,
        function ($notification, $channels, $notifiable) use (&$token) {
            $token = $notification->token;

            return $notifiable->email === 'reset-me@example.com';
        }
    );

    expect($token)->not->toBeNull();

    $resetRow = DB::table('password_reset_tokens')->sole();

    expect($resetRow->email)->not->toBe('reset-me@example.com')
        ->not->toContain('reset-me@example.com');

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => 'reset-me@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors();

    $this->post(route('login.store'), [
        'email' => 'reset-me@example.com',
        'password' => 'new-password',
    ]);

    $this->assertAuthenticated();
});

test('requesting a password reset for an unknown email sends no notification and does not crash', function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());

    Notification::fake();

    $response = $this->post(route('password.email'), ['email' => 'nobody-here@example.com']);

    expect($response->isServerError())->toBeFalse();

    Notification::assertNothingSent();
    expect(DB::table('password_reset_tokens')->count())->toBe(0);
});

test('school member flags are not stored as plain booleans while the public flag stays plain', function () {
    $user = User::factory()->create([
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
        'grade_level' => '10th grade',
        'is_internal' => true,
        'is_external' => false,
        'is_public' => false,
    ]);

    $raw = DB::table('users')->where('id', $user->id)->sole();

    foreach (['is_internal', 'is_external'] as $column) {
        expect($raw->{$column})->not->toBeIn([true, false, 1, 0, '1', '0', 'true', 'false', 't', 'f', 'on', 'off', 'yes', 'no', '']);
    }

    // Raw boolean columns come back as 0/1 on SQLite and as bool on Postgres.
    expect((bool) $raw->is_public)->toBe(false);

    expect($raw->firstname)->not->toBe('Grace')
        ->not->toContain('Grace');
    expect($raw->lastname)->not->toBe('Hopper')
        ->not->toContain('Hopper');
    expect($raw->grade_level)->not->toBe('10th grade')
        ->not->toContain('10th grade');

    $user->refresh();

    expect($user->firstname)->toBe('Grace');
    expect($user->lastname)->toBe('Hopper');
    expect($user->grade_level)->toBe('10th grade');
    expect($user->is_internal)->toBe(true);
    expect($user->is_external)->toBe(false);
    expect($user->is_public)->toBe(false);
});
