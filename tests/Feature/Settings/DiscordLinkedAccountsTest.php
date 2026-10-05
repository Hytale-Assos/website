<?php

use App\Models\User;
use App\Support\IdHasher;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

uses(RefreshDatabase::class);

function mockDiscordOAuthRedirect(): void
{
    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('redirect')->andReturn(
        new RedirectResponse('https://discord.com/api/oauth2/authorize?client_id=1234567890&scope=identify')
    );

    Socialite::shouldReceive('driver')->with('discord')->andReturn($provider);
}

function mockDiscordOAuthUser(string $id, string $nickname): void
{
    $socialiteUser = Mockery::mock();
    $socialiteUser->shouldReceive('getId')->andReturn($id);
    $socialiteUser->shouldReceive('getNickname')->andReturn($nickname);
    $socialiteUser->shouldReceive('getName')->andReturn('Discord User');

    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')->with('discord')->andReturn($provider);
}

function expectFlashedMessage(TestResponse $response, string $toastType): void
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
function rawUserRow(User $user): object
{
    return DB::table('users')->where('id', $user->id)->sole();
}

test('guests are redirected to the login page when starting the discord oauth flow', function () {
    $response = $this->get('/auth/discord/redirect');

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

test('authenticated users are redirected to the discord authorization page', function () {
    $user = User::factory()->create();

    mockDiscordOAuthRedirect();

    $response = $this
        ->actingAs($user)
        ->get('/auth/discord/redirect');

    $response->assertRedirect();

    expect($response->headers->get('Location'))->toStartWith('https://discord.com/api/oauth2/authorize');
});

test('users with an unverified email are also redirected to the discord authorization page', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    mockDiscordOAuthRedirect();

    $response = $this
        ->actingAs($user)
        ->get('/auth/discord/redirect');

    $response->assertRedirect();

    expect($response->headers->get('Location'))->toStartWith('https://discord.com/api/oauth2/authorize');
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

test('guests are redirected to the login page on the discord oauth callback', function () {
    $response = $this->get('/auth/discord/callback');

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

test('discord account is linked to the current user after the oauth callback', function () {
    $user = User::factory()->create();

    expect($user->discord_id_hash)->toBeNull();

    mockDiscordOAuthUser('1234567890123456789', 'testuser');

    $response = $this
        ->actingAs($user)
        ->get('/auth/discord/callback');

    $response->assertRedirect('/settings/accounts');

    $user->refresh();

    expect($user->discord_id)->toBe('1234567890123456789');
    expect($user->discord_nickname)->toBe('testuser');

    $expectedHash = IdHasher::hash('1234567890123456789');

    expect($user->discord_id_hash)->toBe($expectedHash);
    expect($user->discord_id_hash)->toMatch('/^[0-9a-f]{64}$/');
    expect($user->discord_id_hash)->not->toBe(hash('sha256', '1234567890123456789'));

    $row = rawUserRow($user);

    expect($row->discord_id_hash)->toBe($expectedHash);
});

test('discord snowflakes of 17 to 20 digits are accepted', function (string $snowflake) {
    $user = User::factory()->create();

    mockDiscordOAuthUser($snowflake, 'snowuser');

    $response = $this
        ->actingAs($user)
        ->get('/auth/discord/callback');

    $response->assertRedirect('/settings/accounts');

    expect($user->refresh()->discord_id)->toBe($snowflake);
    expect($user->refresh()->discord_nickname)->toBe('snowuser');
    expect($user->refresh()->discord_id_hash)->toBe(IdHasher::hash($snowflake));
})->with([
    '17 digits' => ['12345678901234567'],
    '20 digits' => ['12345678901234567890'],
]);

test('discord data is never stored in plaintext in the database', function () {
    $user = User::factory()->create();

    mockDiscordOAuthUser('1234567890123456789', 'testuser');

    $this
        ->actingAs($user)
        ->get('/auth/discord/callback');

    $row = rawUserRow($user);

    expect($row->discord_id)->not->toBeNull();
    expect($row->discord_id)->not->toBe('1234567890123456789');
    expect($row->discord_nickname)->not->toBeNull();
    expect($row->discord_nickname)->not->toBe('testuser');

    expect(json_encode((array) $row))->not->toContain('1234567890123456789');
    expect(json_encode((array) $row))->not->toContain('testuser');

    expect(Crypt::decryptString($row->discord_id))->toBe('1234567890123456789');
    expect(Crypt::decryptString($row->discord_nickname))->toBe('testuser');
});

test('discord account already linked to another user is refused', function () {
    // The one-discord-account-per-user constraint lives on discord_id_hash,
    // so a linked user must carry the deterministic hash of their discord id.
    $otherUser = User::factory()->create([
        'discord_id' => '1234567890123456789',
        'discord_id_hash' => IdHasher::hash('1234567890123456789'),
        'discord_nickname' => 'otheruser',
    ]);

    $user = User::factory()->create();

    mockDiscordOAuthUser('1234567890123456789', 'testuser');

    $response = $this
        ->actingAs($user)
        ->get('/auth/discord/callback');

    $response->assertRedirect('/settings/accounts');

    expectFlashedMessage($response, 'error');

    $user->refresh();

    expect($user->discord_id)->toBeNull();
    expect($user->discord_nickname)->toBeNull();
    expect($user->discord_id_hash)->toBeNull();

    expect($otherUser->refresh()->discord_id)->toBe('1234567890123456789');
    expect($otherUser->refresh()->discord_nickname)->toBe('otheruser');
    expect($otherUser->refresh()->discord_id_hash)->toBe(IdHasher::hash('1234567890123456789'));
});

test('discord_id_hash carries the one-discord-account-per-user constraint', function () {
    User::factory()->create([
        'discord_id' => '1234567890123456789',
        'discord_id_hash' => IdHasher::hash('1234567890123456789'),
    ]);

    expect(function () {
        DB::table('users')->insert([
            'id' => (string) Str::uuid(),
            'name' => 'Second User',
            'email' => 'second-user@example.com',
            'password' => 'irrelevant',
            'discord_id_hash' => IdHasher::hash('1234567890123456789'),
        ]);
    })->toThrow(QueryException::class);
});

test('users without a linked discord account have a null discord_id_hash', function () {
    $user = User::factory()->create();

    expect($user->refresh()->discord_id)->toBeNull();
    expect($user->refresh()->discord_nickname)->toBeNull();
    expect($user->refresh()->discord_id_hash)->toBeNull();

    $row = rawUserRow($user);

    expect($row->discord_id)->toBeNull();
    expect($row->discord_nickname)->toBeNull();
    expect($row->discord_id_hash)->toBeNull();
});

test('an oauth failure leaves the user unchanged and redirects with an error message', function () {
    $user = User::factory()->create([
        'discord_id' => '9876543210987654321',
        'discord_id_hash' => IdHasher::hash('9876543210987654321'),
        'discord_nickname' => 'alreadylinked',
    ]);

    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('user')->andThrow(new InvalidStateException);

    Socialite::shouldReceive('driver')->with('discord')->andReturn($provider);

    $response = $this
        ->actingAs($user)
        ->get('/auth/discord/callback');

    $response->assertRedirect('/settings/accounts');

    expectFlashedMessage($response, 'error');

    $user->refresh();

    expect($user->discord_id)->toBe('9876543210987654321');
    expect($user->discord_nickname)->toBe('alreadylinked');
    expect($user->discord_id_hash)->toBe(IdHasher::hash('9876543210987654321'));
});

test('authenticated users can unlink their discord account', function () {
    $user = User::factory()->create([
        'discord_id' => '1234567890123456789',
        'discord_id_hash' => IdHasher::hash('1234567890123456789'),
        'discord_nickname' => 'testuser',
    ]);

    $response = $this
        ->actingAs($user)
        ->delete('/settings/accounts/discord');

    $response->assertRedirect('/settings/accounts');

    expectFlashedMessage($response, 'success');

    $user->refresh();

    expect($user->discord_id)->toBeNull();
    expect($user->discord_nickname)->toBeNull();
    expect($user->discord_id_hash)->toBeNull();

    $row = rawUserRow($user);

    expect($row->discord_id)->toBeNull();
    expect($row->discord_nickname)->toBeNull();
    expect($row->discord_id_hash)->toBeNull();
});

test('guests are redirected to the login page when unlinking their discord account', function () {
    $response = $this->delete('/settings/accounts/discord');

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});
