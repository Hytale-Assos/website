<?php

use App\Models\User;
use App\Support\IdHasher;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Raw database row, bypassing Eloquent casts (no decryption).
 */
function rawLinkedAccountsUserRow(User $user): object
{
    return DB::table('users')->where('id', $user->id)->sole();
}

test('linked accounts page is displayed for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/settings/accounts');

    $response->assertOk();
});

test('linked accounts page is displayed even when email is not verified', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $response = $this
        ->actingAs($user)
        ->get('/settings/accounts');

    $response->assertOk();
});

test('guests are redirected to the login page from the linked accounts page', function () {
    $response = $this->get('/settings/accounts');

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

test('hytale data is never stored in plaintext in the database', function () {
    $user = User::factory()->create();

    $user->fill([
        'hytale_nickname' => 'HytaleFan42',
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
    ])->save();

    $row = rawLinkedAccountsUserRow($user);

    expect($row->hytale_id)->not->toBeNull();
    expect($row->hytale_id)->not->toBe('123e4567-e89b-42d3-a456-426614174000');
    expect($row->hytale_nickname)->not->toBeNull();
    expect($row->hytale_nickname)->not->toBe('HytaleFan42');

    expect(json_encode((array) $row))->not->toContain('123e4567-e89b-42d3-a456-426614174000');
    expect(json_encode((array) $row))->not->toContain('HytaleFan42');

    expect(Crypt::decryptString($row->hytale_id))->toBe('123e4567-e89b-42d3-a456-426614174000');
    expect(Crypt::decryptString($row->hytale_nickname))->toBe('HytaleFan42');
});

test('hytale id hash is the keyed blind index of the hytale id', function () {
    $user = User::factory()->create();

    expect($user->hytale_id_hash)->toBeNull();

    $user->fill([
        'hytale_nickname' => 'HytaleFan42',
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
    ])->save();

    $user->refresh();

    $expectedHash = IdHasher::hash('123e4567-e89b-42d3-a456-426614174000');

    expect($user->hytale_id_hash)->toBe($expectedHash);
    expect($user->hytale_id_hash)->toMatch('/^[0-9a-f]{64}$/');
    expect($user->hytale_id_hash)->not->toBe(hash('sha256', '123e4567-e89b-42d3-a456-426614174000'));

    $row = rawLinkedAccountsUserRow($user);

    expect($row->hytale_id_hash)->toBe($expectedHash);
});

test('users without a linked hytale account have a null hytale id hash', function () {
    $user = User::factory()->create();

    expect($user->refresh()->hytale_id)->toBeNull();
    expect($user->refresh()->hytale_nickname)->toBeNull();
    expect($user->refresh()->hytale_id_hash)->toBeNull();

    $row = rawLinkedAccountsUserRow($user);

    expect($row->hytale_id)->toBeNull();
    expect($row->hytale_nickname)->toBeNull();
    expect($row->hytale_id_hash)->toBeNull();
});

test('hytale_id_hash carries the one-hytale-account-per-user constraint', function () {
    User::factory()->create([
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        'hytale_id_hash' => IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'),
    ]);

    expect(function () {
        DB::table('users')->insert([
            'id' => (string) Str::uuid(),
            'name' => 'Second User',
            'email' => 'second-user@example.com',
            'password' => 'irrelevant',
            'hytale_id_hash' => IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'),
        ]);
    })->toThrow(QueryException::class);
});
