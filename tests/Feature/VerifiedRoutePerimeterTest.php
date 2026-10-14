<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('unverified members are redirected to the verification notice', function (string $method, string $url) {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->{$method}($url);

    $response->assertRedirect(route('verification.notice', absolute: false));
})->with([
    'GET /dashboard' => ['get', '/dashboard'],
    'GET /settings/security' => ['get', '/settings/security'],
    'GET /settings/invitations' => ['get', '/settings/invitations'],
    'POST /settings/invitations' => ['post', '/settings/invitations'],
    'PUT /settings/identity' => ['put', '/settings/identity'],
    'PUT /settings/accounts' => ['put', '/settings/accounts'],
    'POST /whitelist' => ['post', '/whitelist'],
]);

test('unverified members can still view their profile settings', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/settings/profile');

    $response->assertOk();
});

test('unverified members can still update their profile settings', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->patch('/settings/profile', [
        'name' => $user->name,
        'email' => $user->email,
    ]);

    $response->assertSessionHasNoErrors();
    expect($response->headers->get('Location'))->not->toBe(route('verification.notice', absolute: false));
});

test('unverified members can still access their data settings and export', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/settings/data')->assertOk();

    $this->actingAs($user)->get('/settings/data/export')->assertOk();
});

test('unverified members can delete their own account', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->delete('/settings/profile', [
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    expect($response->headers->get('Location'))->not->toBe(route('verification.notice', absolute: false));
    expect(User::query()->find($user->id))->toBeNull();
});

test('invitation management is refused for an internal member with an unverified email', function () {
    $user = User::factory()->unverified()->create(['is_internal' => true]);

    expect(Gate::forUser($user)->allows('manage', Invitation::class))->toBeFalse();
});

test('invitation management is allowed for an internal member with a verified email', function () {
    $user = User::factory()->create(['is_internal' => true]);

    expect(Gate::forUser($user)->allows('manage', Invitation::class))->toBeTrue();
});
