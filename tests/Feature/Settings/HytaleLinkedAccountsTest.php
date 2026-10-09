<?php

use App\Hytale\Contracts\HytaleApiClient;
use App\Models\User;
use App\Support\IdHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\User as HytaleSocialiteUser;

uses(RefreshDatabase::class);

function mockHytaleOAuthUser(string $sub, ?string $uuid, ?string $nickname): void
{
    $oauthUser = (new HytaleSocialiteUser)->setRaw([
        'sub' => $sub,
        'profile' => ['uuid' => $uuid, 'username' => $nickname],
    ])->map([
        'id' => $sub,
        'nickname' => $nickname,
        'uuid' => $uuid,
    ]);

    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('user')->andReturn($oauthUser);

    Socialite::shouldReceive('driver')->with('hytale')->andReturn($provider);
}

function expectHytaleFlashedMessage(TestResponse $response, string $toastType): void
{
    $session = app('session.store');

    $toast = $session->get('inertia.flash_data.toast');

    expect($toast)->toBeArray(
        'Expected a flashed toast in session under [inertia.flash_data.toast] '
        .'but the session only contains: '.json_encode($session->all())
    );

    expect($toast)->toHaveKey('type');
    expect($toast['type'])->toBe($toastType);
    expect($toast)->toHaveKey('message');
    expect($toast['message'])->not->toBeEmpty();
}

/**
 * Raw database row, bypassing Eloquent casts (no decryption).
 */
function rawHytaleUserRow(User $user): object
{
    return DB::table('users')->where('id', $user->id)->sole();
}

test('guests are redirected to the login page when starting the hytale oauth flow', function () {
    $response = $this->get('/auth/hytale/redirect');

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

test('guests are redirected to the login page on the hytale oauth callback', function () {
    $response = $this->get('/auth/hytale/callback');

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

test('guests are redirected to the login page when unlinking their hytale account', function () {
    $response = $this->delete('/settings/accounts/hytale');

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

test('unsupported providers are rejected with a 404', function (string $httpMethod, string $url) {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->{$httpMethod}($url);

    $response->assertNotFound();
})->with([
    'redirect' => ['get', '/auth/steam/redirect'],
    'callback' => ['get', '/auth/steam/callback'],
    'unlink' => ['delete', '/settings/accounts/steam'],
]);

test('authenticated users are redirected to the hytale authorization page requesting the profile scope', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/auth/hytale/redirect');

    $response->assertRedirect();

    $location = $response->headers->get('Location');

    expect($location)->toStartWith('https://connect.accounts.hytale.com/oauth2/auth');

    parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $query);

    expect($query)->toHaveKey('scope');
    expect(explode(' ', $query['scope']))->toContain('openid')->toContain('hytale:profile');
});

test('the hytale game profile uuid and nickname are linked after the oauth callback', function () {
    $user = User::factory()->create();

    $uuid = '123e4567-e89b-42d3-a456-426614174000';

    mockHytaleOAuthUser('anonymous-sub-identifier-abc', $uuid, 'HytaleFan42');

    $response = $this
        ->actingAs($user)
        ->get('/auth/hytale/callback');

    $response->assertRedirect('/settings/accounts');

    expectHytaleFlashedMessage($response, 'success');

    $user->refresh();

    expect($user->hytale_id)->toBe($uuid);
    expect($user->hytale_nickname)->toBe('HytaleFan42');
    expect($user->hytale_id_hash)->toBe(IdHasher::hash($uuid));
});

test('the oauth callback marks the linked hytale account verified internally', function () {
    $user = User::factory()->create();

    $uuid = '123e4567-e89b-42d3-a456-426614174000';

    mockHytaleOAuthUser('anonymous-sub-identifier-abc', $uuid, 'HytaleFan42');

    $this
        ->actingAs($user)
        ->get('/auth/hytale/callback');

    $row = rawHytaleUserRow($user->refresh());

    expect($row->hytale_account_verified_at)->not->toBeNull();
    expect($user->hytale_account_verified_at)->not->toBeNull();
});

test('hytale account data is stored encrypted and never in plaintext', function () {
    $user = User::factory()->create();

    $uuid = '123e4567-e89b-42d3-a456-426614174000';

    mockHytaleOAuthUser('anonymous-sub-identifier-abc', $uuid, 'HytaleFan42');

    $this
        ->actingAs($user)
        ->get('/auth/hytale/callback');

    $row = rawHytaleUserRow($user);

    expect($row->hytale_id)->not->toBeNull();
    expect($row->hytale_id)->not->toBe($uuid);
    expect($row->hytale_nickname)->not->toBeNull();
    expect($row->hytale_nickname)->not->toBe('HytaleFan42');

    expect(json_encode((array) $row))->not->toContain($uuid);
    expect(json_encode((array) $row))->not->toContain('123e4567');
    expect(json_encode((array) $row))->not->toContain('HytaleFan42');

    expect(Crypt::decryptString($row->hytale_id))->toBe($uuid);
    expect(Crypt::decryptString($row->hytale_nickname))->toBe('HytaleFan42');

    expect($user->refresh()->hytale_id_hash)->toBe(IdHasher::hash($uuid));
    expect($user->refresh()->hytale_id_hash)->not->toBe(hash('sha256', $uuid));
});

test('the anonymous oauth sub identifier is never stored in any form', function () {
    $user = User::factory()->create();

    $uuid = '123e4567-e89b-42d3-a456-426614174000';
    $sub = 'anonymous-sub-identifier-abc';

    mockHytaleOAuthUser($sub, $uuid, 'HytaleFan42');

    $this
        ->actingAs($user)
        ->get('/auth/hytale/callback');

    $row = rawHytaleUserRow($user);

    expect(json_encode((array) $row))->not->toContain($sub);
    expect($row->hytale_id)->not->toBe($sub);
    expect($row->hytale_nickname)->not->toBe($sub);
    expect($row->hytale_id_hash)->not->toBe(IdHasher::hash($sub));
    expect(Crypt::decryptString($row->hytale_id))->not->toBe($sub);

    $user->refresh();

    expect($user->hytale_id)->not->toBe($sub);
    expect($user->hytale_id_hash)->not->toBe(IdHasher::hash($sub));
});

test('a hytale profile already linked to another user is refused', function () {
    $uuid = '123e4567-e89b-42d3-a456-426614174000';

    $otherUser = User::factory()->withHytaleAccount($uuid, verified: true)->create();
    $user = User::factory()->create();

    mockHytaleOAuthUser('anonymous-sub-identifier-abc', $uuid, 'Hijacker');

    $response = $this
        ->actingAs($user)
        ->get('/auth/hytale/callback');

    $response->assertRedirect('/settings/accounts');

    expectHytaleFlashedMessage($response, 'error');

    $user->refresh();

    expect($user->hytale_id)->toBeNull();
    expect($user->hytale_nickname)->toBeNull();
    expect($user->hytale_id_hash)->toBeNull();
    expect($user->hytale_account_verified_at)->toBeNull();

    $otherUser->refresh();

    expect($otherUser->hytale_id)->toBe($uuid);
    expect($otherUser->hytale_id_hash)->toBe(IdHasher::hash($uuid));
});

test('the same user may relink the same hytale profile and the nickname updates', function () {
    $uuid = '123e4567-e89b-42d3-a456-426614174000';

    $user = User::factory()->withHytaleAccount($uuid, verified: true)->create();

    mockHytaleOAuthUser('anonymous-sub-identifier-abc', $uuid, 'BrandNewNickname');

    $response = $this
        ->actingAs($user)
        ->get('/auth/hytale/callback');

    $response->assertRedirect('/settings/accounts');

    expectHytaleFlashedMessage($response, 'success');

    $user->refresh();

    expect($user->hytale_id)->toBe($uuid);
    expect($user->hytale_nickname)->toBe('BrandNewNickname');
    expect($user->hytale_id_hash)->toBe(IdHasher::hash($uuid));
});

test('an oauth user without a game profile uuid is not linked and flashes an error', function () {
    $user = User::factory()->create();

    mockHytaleOAuthUser('anonymous-sub-identifier-abc', null, null);

    $response = $this
        ->actingAs($user)
        ->get('/auth/hytale/callback');

    $response->assertRedirect('/settings/accounts');

    expectHytaleFlashedMessage($response, 'error');

    $user->refresh();

    expect($user->hytale_id)->toBeNull();
    expect($user->hytale_nickname)->toBeNull();
    expect($user->hytale_id_hash)->toBeNull();
    expect($user->hytale_account_verified_at)->toBeNull();
});

test('an authorization error on the callback aborts before exchanging any code', function () {
    $user = User::factory()->create();

    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('user')->never();

    Socialite::shouldReceive('driver')->with('hytale')->andReturn($provider);

    $response = $this
        ->actingAs($user)
        ->get('/auth/hytale/callback?error=access_denied&error_description=The+user+denied+the+request');

    $response->assertRedirect('/settings/accounts');

    expectHytaleFlashedMessage($response, 'error');

    $user->refresh();

    expect($user->hytale_id)->toBeNull();
    expect($user->hytale_nickname)->toBeNull();
    expect($user->hytale_id_hash)->toBeNull();
    expect($user->hytale_account_verified_at)->toBeNull();
});

test('an oauth failure leaves the user unchanged and flashes an error', function () {
    $uuid = '123e4567-e89b-42d3-a456-426614174000';

    $user = User::factory()->withHytaleAccount($uuid, verified: true)->create();

    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('user')->andThrow(new InvalidStateException);

    Socialite::shouldReceive('driver')->with('hytale')->andReturn($provider);

    $response = $this
        ->actingAs($user)
        ->get('/auth/hytale/callback');

    $response->assertRedirect('/settings/accounts');

    expectHytaleFlashedMessage($response, 'error');

    $user->refresh();

    expect($user->hytale_id)->toBe($uuid);
    expect($user->hytale_nickname)->not->toBeNull();
    expect($user->hytale_id_hash)->toBe(IdHasher::hash($uuid));
    expect($user->hytale_account_verified_at)->not->toBeNull();
});

test('an oauth-linked hytale account can join a whitelist', function () {
    $client = app(HytaleApiClient::class);

    $serverId = $client->servers()->items[0]->id;
    $uuid = '123e4567-e89b-42d3-a456-426614174000';

    $user = User::factory()->create();

    mockHytaleOAuthUser('anonymous-sub-identifier-abc', $uuid, 'HytaleFan42');

    $this
        ->actingAs($user)
        ->get('/auth/hytale/callback');

    $response = $this
        ->actingAs($user->refresh())
        ->post(route('whitelist.store'), ['hytale_server_id' => $serverId]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($client->playerWhitelists($uuid)->total)->toBe(1);
});

test('authenticated users can unlink their hytale account', function () {
    $uuid = '123e4567-e89b-42d3-a456-426614174000';

    $user = User::factory()->withHytaleAccount($uuid, verified: true)->create();

    $response = $this
        ->actingAs($user)
        ->delete('/settings/accounts/hytale');

    $response->assertRedirect('/settings/accounts');

    expectHytaleFlashedMessage($response, 'success');

    $user->refresh();

    expect($user->hytale_id)->toBeNull();
    expect($user->hytale_nickname)->toBeNull();
    expect($user->hytale_id_hash)->toBeNull();
    expect($user->hytale_account_verified_at)->toBeNull();

    $row = rawHytaleUserRow($user);

    expect($row->hytale_id)->toBeNull();
    expect($row->hytale_nickname)->toBeNull();
    expect($row->hytale_id_hash)->toBeNull();
    expect($row->hytale_account_verified_at)->toBeNull();
});
