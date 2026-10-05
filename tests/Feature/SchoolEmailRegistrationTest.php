<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

test('registering with an email on a configured school domain creates an internal account holding the school email', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    config(['members.school_email_domains' => ['ecole.fr']]);

    $this->post(route('register.store'), [
        'name' => 'School Student',
        'email' => 'student@ecole.fr',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticated();

    $user = User::findOrFail(auth()->id());

    expect($user->is_internal)->toBe(true);
    expect($user->is_external)->toBe(false);
    expect($user->school_email)->toBe('student@ecole.fr');
});

test('a school domain match is case-insensitive on the domain part of the email', function (string $email) {
    $this->skipUnlessFortifyHas(Features::registration());

    config(['members.school_email_domains' => ['ecole.fr']]);

    $this->post(route('register.store'), [
        'name' => 'Cased Student',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticated();

    $user = User::findOrFail(auth()->id());

    expect($user->is_internal)->toBe(true);
    expect($user->is_external)->toBe(false);
})->with([
    'uppercase domain' => ['Student@ECOLE.FR'],
    'mixed case domain' => ['student@Ecole.Fr'],
]);

test('registering with an email outside the configured school domains creates an external account without a school email', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    config(['members.school_email_domains' => ['ecole.fr']]);

    $this->post(route('register.store'), [
        'name' => 'Outside Student',
        'email' => 'student@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticated();

    $user = User::findOrFail(auth()->id());

    expect($user->is_external)->toBe(true);
    expect($user->is_internal)->toBe(false);
    expect($user->school_email)->toBeNull();
});

test('an empty school domain configuration makes every registration external', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    config(['members.school_email_domains' => []]);

    $this->post(route('register.store'), [
        'name' => 'Unmatched Student',
        'email' => 'student@ecole.fr',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticated();

    $user = User::findOrFail(auth()->id());

    expect($user->is_external)->toBe(true);
    expect($user->is_internal)->toBe(false);
    expect($user->school_email)->toBeNull();
});

test('the member status is frozen at account creation and ignores later configuration changes', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    config(['members.school_email_domains' => ['ecole.fr']]);

    $this->post(route('register.store'), [
        'name' => 'Frozen Student',
        'email' => 'frozen-student@ecole.fr',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticated();

    config(['members.school_email_domains' => []]);

    $user = User::findOrFail(auth()->id())->refresh();

    expect($user->is_internal)->toBe(true);
    expect($user->is_external)->toBe(false);
    expect($user->school_email)->toBe('frozen-student@ecole.fr');
});

test('registration stores the school email and the member flags encrypted while the model returns plain values', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    config(['members.school_email_domains' => ['ecole.fr']]);

    $this->post(route('register.store'), [
        'name' => 'Confidential Student',
        'email' => 'confidential-student@ecole.fr',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $raw = DB::table('users')->where('id', auth()->id())->sole();

    expect($raw->school_email)->not->toBe('confidential-student@ecole.fr')
        ->not->toContain('confidential-student@ecole.fr');

    foreach (['is_internal', 'is_external'] as $column) {
        expect($raw->{$column})->not->toBeIn([true, false, 1, 0, '1', '0', 'true', 'false', 't', 'f', 'on', 'off', 'yes', 'no', '']);
    }

    $user = User::findOrFail(auth()->id());

    expect($user->school_email)->toBe('confidential-student@ecole.fr');
    expect($user->is_internal)->toBe(true);
    expect($user->is_external)->toBe(false);
});

test('registering twice with the same school email is rejected by the email uniqueness validation', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    config(['members.school_email_domains' => ['ecole.fr']]);

    $this->post(route('register.store'), [
        'name' => 'First Student',
        'email' => 'student@ecole.fr',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticated();

    $this->post(route('logout'));

    $this->assertGuest();

    $this->post(route('register.store'), [
        'name' => 'Second Student',
        'email' => 'student@ecole.fr',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');

    expect(DB::table('users')->count())->toBe(1);
});
