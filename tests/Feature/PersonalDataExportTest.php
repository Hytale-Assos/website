<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * @return list<string>
 */
function exportKeys(array $value): array
{
    $keys = [];

    foreach ($value as $key => $item) {
        $keys[] = (string) $key;

        if (is_array($item)) {
            $keys = array_merge($keys, exportKeys($item));
        }
    }

    return $keys;
}

test('guests are redirected to the login page', function () {
    $this->get(route('data.export'))
        ->assertRedirect(route('login'));
});

test('the export downloads as a JSON attachment', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('data.export'));

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('json');

    expect($response->headers->get('Content-Disposition'))
        ->toContain('attachment')
        ->toContain('.json');
});

test('the export body contains the four inventory sections with the user own data', function () {
    $user = User::factory()->create([
        'email' => 'grace@example.com',
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
        'grade_level' => '10th grade',
        'is_internal' => true,
        'is_external' => false,
        'is_public' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('data.export'));

    $export = json_decode($response->streamedContent(), true);

    expect($export)->toBeArray()
        ->toHaveKey('sections');

    expect($export['sections'])->toHaveCount(4)
        ->toHaveKeys(['account', 'identification', 'linked_accounts', 'security']);

    expect($export['sections']['account']['email'])->toBe('grace@example.com');

    expect($export['sections']['identification']['firstname'])->toBe('Grace')
        ->and($export['sections']['identification']['lastname'])->toBe('Hopper')
        ->and($export['sections']['identification']['grade_level'])->toBe('10th grade')
        ->and($export['sections']['identification']['status'])->toBe('internal')
        ->and($export['sections']['identification']['is_public'])->toBeTrue();
});

test('the export never contains another user data', function () {
    $grace = User::factory()->create([
        'firstname' => 'Grace',
        'email' => 'grace@example.com',
        'is_internal' => true,
        'is_external' => false,
    ]);

    User::factory()->create([
        'firstname' => 'Alan',
        'email' => 'alan@example.com',
        'is_internal' => false,
        'is_external' => true,
    ]);

    $content = $this
        ->actingAs($grace)
        ->get(route('data.export'))
        ->streamedContent();

    expect($content)
        ->toContain('grace@example.com')
        ->toContain('Grace')
        ->not->toContain('alan@example.com')
        ->not->toContain('Alan');
});

test('the export contains no bearer or secret material', function () {
    $user = User::factory()->withTwoFactor()->create();

    $user->passkeys()->create([
        'name' => 'YubiKey',
        'credential_id' => 'credential-yubikey',
        'credential' => ['publicKey' => 'credential-public-key'],
    ]);

    DB::table('sessions')->insert([
        'id' => 'session-laptop',
        'user_id' => $user->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'encrypted-session-payload',
        'last_activity' => Carbon::now()->getTimestamp(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('data.export'));

    $content = $response->streamedContent();
    $export = json_decode($content, true);

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

    $keys = exportKeys($export);

    foreach ($forbiddenKeys as $forbiddenKey) {
        expect($keys)->not->toContain($forbiddenKey);
    }

    $rawUser = DB::table('users')->where('id', $user->id)->sole();
    $rawPasskey = DB::table('passkeys')->where('user_id', $user->id)->sole();
    $rawSession = DB::table('sessions')->where('user_id', $user->id)->sole();

    $secretValues = [
        $rawUser->password,
        $rawUser->remember_token,
        $rawUser->two_factor_secret,
        $rawUser->two_factor_recovery_codes,
        $rawPasskey->credential_id,
        $rawPasskey->credential,
        $rawSession->id,
        $rawSession->payload,
    ];

    foreach ($secretValues as $secret) {
        expect($content)->not->toContain($secret);
    }
});
