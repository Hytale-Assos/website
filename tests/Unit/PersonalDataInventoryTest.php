<?php

use App\Models\User;
use App\Support\PersonalDataInventory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function inventory(User $user): array
{
    return (new PersonalDataInventory)->sections($user);
}

/**
 * Creates a user with every section filled in: identity, both linked
 * accounts, confirmed two-factor, two passkeys and two sessions. Fixed
 * times make every timestamp deterministic.
 */
function userWithEverything(Illuminate\Foundation\Testing\TestCase $test): User
{
    $test->travelTo('2026-10-01 10:00:00');

    $user = User::factory()->withTwoFactor()->create([
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
        'school_email' => 'grace@school.edu',
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
        'grade_level' => '10th grade',
        'is_internal' => true,
        'is_external' => false,
        'is_public' => true,
        'discord_id' => 'discord-123',
        'discord_nickname' => 'grace-on-discord',
        'hytale_id' => 'hytale-456',
        'hytale_nickname' => 'grace-on-hytale',
        'hytale_account_verified_at' => '2026-10-02 09:30:00',
    ]);

    $test->travelTo('2026-10-03 10:00:00');
    $olderPasskey = $user->passkeys()->create([
        'name' => 'YubiKey',
        'credential_id' => 'credential-yubikey',
        'credential' => ['publicKey' => 'older-credential-public-key'],
    ]);
    $olderPasskey->last_used_at = '2026-10-03 10:05:00';
    $olderPasskey->save();

    $test->travelTo('2026-10-04 10:00:00');
    $user->passkeys()->create([
        'name' => 'Phone',
        'credential_id' => 'credential-phone',
        'credential' => ['publicKey' => 'newer-credential-public-key'],
    ]);

    DB::table('sessions')->insert([
        [
            'id' => 'session-laptop',
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0 (laptop)',
            'payload' => 'encrypted-session-payload-laptop',
            'last_activity' => Carbon::parse('2026-10-05 10:00:00')->getTimestamp(),
        ],
        [
            'id' => 'session-phone',
            'user_id' => $user->id,
            'ip_address' => '192.168.1.5',
            'user_agent' => 'Mozilla/5.0 (phone)',
            'payload' => 'encrypted-session-payload-phone',
            'last_activity' => Carbon::parse('2026-10-06 10:00:00')->getTimestamp(),
        ],
    ]);

    $test->travelBack();

    return $user->refresh();
}

function expectIsoTimestamp(mixed $value, Carbon|string $expected): void
{
    expect($value)->toBeString()
        ->toMatch('/^2\d{3}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:?\d{2})$/');

    expect(Carbon::parse($value)->getTimestamp())->toBe(Carbon::parse($expected)->getTimestamp());
}

/**
 * @return list<string>
 */
function inventoryKeys(array $value): array
{
    $keys = [];

    foreach ($value as $key => $item) {
        $keys[] = (string) $key;

        if (is_array($item)) {
            $keys = array_merge($keys, inventoryKeys($item));
        }
    }

    return $keys;
}

/**
 * @return list<string>
 */
function inventoryStringValues(array $value): array
{
    $strings = [];

    foreach ($value as $item) {
        if (is_array($item)) {
            $strings = array_merge($strings, inventoryStringValues($item));
        } elseif (is_string($item)) {
            $strings[] = $item;
        }
    }

    return $strings;
}

it('returns the four sections with exactly the contracted keys', function () {
    $sections = inventory(userWithEverything($this));

    expect($sections)->toHaveCount(4)
        ->toHaveKeys(['account', 'identification', 'linked_accounts', 'security']);

    expect($sections['account'])->toHaveCount(4)
        ->toHaveKeys(['name', 'email', 'school_email', 'created_at']);

    expect($sections['identification'])->toHaveCount(5)
        ->toHaveKeys(['firstname', 'lastname', 'grade_level', 'status', 'is_public']);

    expect($sections['linked_accounts'])->toHaveCount(2)
        ->toHaveKeys(['discord', 'hytale']);

    expect($sections['linked_accounts']['discord'])->toHaveCount(2)
        ->toHaveKeys(['linked', 'nickname']);

    expect($sections['linked_accounts']['hytale'])->toHaveCount(3)
        ->toHaveKeys(['linked', 'nickname', 'verified_at']);

    expect($sections['security'])->toHaveCount(3)
        ->toHaveKeys(['two_factor', 'passkeys', 'sessions']);

    expect($sections['security']['two_factor'])->toHaveCount(2)
        ->toHaveKeys(['enabled', 'confirmed_at']);
});

it('returns the account data as plain values with the creation date', function () {
    $user = userWithEverything($this);

    $account = inventory($user)['account'];

    expect($account['name'])->toBe('Grace Hopper')
        ->and($account['email'])->toBe('grace@example.com')
        ->and($account['school_email'])->toBe('grace@school.edu');

    expectIsoTimestamp($account['created_at'], $user->created_at);
});

it('returns the identification data with the public profile flag', function () {
    $identification = inventory(userWithEverything($this))['identification'];

    expect($identification['firstname'])->toBe('Grace')
        ->and($identification['lastname'])->toBe('Hopper')
        ->and($identification['grade_level'])->toBe('10th grade')
        ->and($identification['is_public'])->toBeTrue();
});

it('maps the internal and external flags to the identification status', function (bool $isInternal, bool $isExternal, ?string $expectedStatus) {
    $user = User::factory()->create([
        'is_internal' => $isInternal,
        'is_external' => $isExternal,
    ]);

    expect(inventory($user)['identification']['status'])->toBe($expectedStatus);
})->with([
    'internal' => [true, false, 'internal'],
    'external' => [false, true, 'external'],
    'neither' => [false, false, null],
]);

it('reports linked discord and hytale accounts with their nicknames', function () {
    $user = userWithEverything($this);

    $linked = inventory($user)['linked_accounts'];

    expect($linked['discord']['linked'])->toBeTrue()
        ->and($linked['discord']['nickname'])->toBe('grace-on-discord');

    expect($linked['hytale']['linked'])->toBeTrue()
        ->and($linked['hytale']['nickname'])->toBe('grace-on-hytale');

    expectIsoTimestamp($linked['hytale']['verified_at'], $user->hytale_account_verified_at);
});

it('reports both accounts as unlinked when no ids are set', function () {
    $linked = inventory(User::factory()->create())['linked_accounts'];

    expect($linked['discord']['linked'])->toBeFalse()
        ->and($linked['discord']['nickname'])->toBeNull()
        ->and($linked['hytale']['linked'])->toBeFalse()
        ->and($linked['hytale']['nickname'])->toBeNull()
        ->and($linked['hytale']['verified_at'])->toBeNull();
});

it('reports two-factor as enabled once confirmed', function () {
    $user = userWithEverything($this);

    $twoFactor = inventory($user)['security']['two_factor'];

    expect($twoFactor['enabled'])->toBeTrue();

    expectIsoTimestamp($twoFactor['confirmed_at'], $user->two_factor_confirmed_at);
});

it('reports two-factor as disabled while it is not confirmed', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('configured-but-not-confirmed'),
        'two_factor_confirmed_at' => null,
    ]);

    $twoFactor = inventory($user)['security']['two_factor'];

    expect($twoFactor['enabled'])->toBeFalse()
        ->and($twoFactor['confirmed_at'])->toBeNull();
});

it('lists the passkeys newest first with their name and timestamps', function () {
    $passkeys = inventory(userWithEverything($this))['security']['passkeys'];

    expect($passkeys)->toHaveCount(2);

    expect($passkeys[0])->toHaveCount(3)
        ->toHaveKeys(['name', 'created_at', 'last_used_at']);

    expect($passkeys[0]['name'])->toBe('Phone');

    expectIsoTimestamp($passkeys[0]['created_at'], '2026-10-04 10:00:00');
    expect($passkeys[0]['last_used_at'])->toBeNull();

    expect($passkeys[1]['name'])->toBe('YubiKey');

    expectIsoTimestamp($passkeys[1]['created_at'], '2026-10-03 10:00:00');
    expectIsoTimestamp($passkeys[1]['last_used_at'], '2026-10-03 10:05:00');
});

it('lists the active sessions newest first with the last activity as an ISO-8601 string', function () {
    $sessions = inventory(userWithEverything($this))['security']['sessions'];

    expect($sessions)->toHaveCount(2);

    expect($sessions[0])->toHaveCount(3)
        ->toHaveKeys(['ip_address', 'user_agent', 'last_activity']);

    expect($sessions[0]['ip_address'])->toBe('192.168.1.5')
        ->and($sessions[0]['user_agent'])->toBe('Mozilla/5.0 (phone)');

    expectIsoTimestamp($sessions[0]['last_activity'], '2026-10-06 10:00:00');

    expect($sessions[1]['ip_address'])->toBe('10.0.0.1')
        ->and($sessions[1]['user_agent'])->toBe('Mozilla/5.0 (laptop)');

    expectIsoTimestamp($sessions[1]['last_activity'], '2026-10-05 10:00:00');
});

it('returns empty but present structures for a user with no personal data', function () {
    $sections = inventory(User::factory()->create());

    expect($sections['account']['school_email'])->toBeNull()
        ->and($sections['identification']['firstname'])->toBeNull()
        ->and($sections['identification']['lastname'])->toBeNull()
        ->and($sections['identification']['grade_level'])->toBeNull()
        ->and($sections['identification']['status'])->toBeNull()
        ->and($sections['identification']['is_public'])->toBeFalse();

    expect($sections['linked_accounts']['discord']['linked'])->toBeFalse()
        ->and($sections['linked_accounts']['hytale']['linked'])->toBeFalse();

    expect($sections['security']['two_factor']['enabled'])->toBeFalse()
        ->and($sections['security']['two_factor']['confirmed_at'])->toBeNull();

    expect($sections['security']['passkeys'])->toBe([])
        ->and($sections['security']['sessions'])->toBe([]);
});

it('never exposes confidential keys', function () {
    $keys = inventoryKeys(inventory(userWithEverything($this)));

    $forbiddenKeys = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'id',
        'payload',
        'credential_id',
        'credential',
    ];

    foreach ($forbiddenKeys as $forbiddenKey) {
        expect($keys)->not->toContain($forbiddenKey);
    }
});

it('never exposes raw encrypted database contents', function () {
    $user = userWithEverything($this);
    $sections = inventory($user);

    expect($sections['account']['name'])->toBe('Grace Hopper')
        ->and($sections['account']['email'])->toBe('grace@example.com')
        ->and($sections['account']['school_email'])->toBe('grace@school.edu')
        ->and($sections['identification']['firstname'])->toBe('Grace')
        ->and($sections['identification']['lastname'])->toBe('Hopper');

    $rawUser = DB::table('users')->where('id', $user->id)->sole();
    $rawPasskeys = DB::table('passkeys')->where('user_id', $user->id)->get();
    $rawSessions = DB::table('sessions')->where('user_id', $user->id)->get();

    $forbiddenValues = [
        $rawUser->name,
        $rawUser->email,
        $rawUser->email_hash,
        $rawUser->school_email,
        $rawUser->school_email_hash,
        $rawUser->password,
        $rawUser->remember_token,
        $rawUser->two_factor_secret,
        $rawUser->two_factor_recovery_codes,
        $rawUser->firstname,
        $rawUser->lastname,
        $rawUser->grade_level,
        $rawUser->discord_id,
        $rawUser->discord_id_hash,
        $rawUser->discord_nickname,
        $rawUser->hytale_id,
        $rawUser->hytale_id_hash,
        $rawUser->hytale_nickname,
    ];

    foreach ($rawPasskeys as $passkey) {
        $forbiddenValues[] = $passkey->credential_id;
        $forbiddenValues[] = $passkey->credential;
    }

    foreach ($rawSessions as $session) {
        $forbiddenValues[] = $session->id;
        $forbiddenValues[] = $session->payload;
    }

    $forbiddenValues = array_values(array_filter(
        $forbiddenValues,
        fn ($value) => is_string($value) && $value !== '',
    ));

    expect(inventoryStringValues($sections))
        ->each(fn ($value) => $value->not->toBeIn($forbiddenValues));
});
