<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

uses(RefreshDatabase::class);

/**
 * Query /settings/profile the way an Inertia SPA client does: an outdated
 * visit answers 409 with the current asset version in the X-Inertia-Version
 * header, then the visit is replayed with that version to get the JSON
 * payload.
 *
 * @return array<string, mixed>
 */
function sharedAuthUserOfProfilePage(TestCase $test): array
{
    $user = User::factory()->create(['email_verified_at' => now()]);

    $test->actingAs($user);

    $stale = $test->get('/settings/profile', ['X-Inertia' => 'true']);

    $version = $stale->status() === 409 ? $stale->headers->get('x-inertia-version', '') : '';

    $response = $test->get('/settings/profile', [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
    ]);

    $response->assertOk();

    $authUser = $response->json('props.auth.user');

    expect($authUser)->toBeArray();

    return $authUser;
}

it('does not expose any blind index hash column in the shared auth user', function () {
    $authUser = sharedAuthUserOfProfilePage($this);

    foreach (['email_hash', 'discord_id_hash', 'hytale_id_hash', 'school_email_hash'] as $hashColumn) {
        expect($authUser)->not->toHaveKey($hashColumn);
    }

    $hashKeys = array_filter(array_keys($authUser), fn (string $key) => str_ends_with($key, '_hash'));

    expect($hashKeys)->toBeEmpty();
});

it('still exposes the legitimate fields in the shared auth user', function () {
    $authUser = sharedAuthUserOfProfilePage($this);

    expect($authUser)
        ->toHaveKey('name')
        ->toHaveKey('email')
        ->toHaveKey('is_public');
});

it('keeps existing secrets hidden from the shared auth user', function () {
    $authUser = sharedAuthUserOfProfilePage($this);

    foreach (['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'] as $secret) {
        expect($authUser)->not->toHaveKey($secret);
    }
});
