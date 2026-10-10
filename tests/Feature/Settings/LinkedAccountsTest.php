<?php

use App\Models\User;
use App\Support\IdHasher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

test('hytale account fields can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put('/settings/accounts', [
            'hytale_nickname' => 'HytaleFan42',
            'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/accounts');

    $user->refresh();

    expect($user->hytale_nickname)->toBe('HytaleFan42');
    expect($user->hytale_id)->toBe('123e4567-e89b-42d3-a456-426614174000');
    expect($user->hytale_id_hash)->toBe(IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'));
});

test('both hytale account fields can be null', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put('/settings/accounts', [
            'hytale_nickname' => null,
            'hytale_id' => null,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/accounts');

    $user->refresh();

    expect($user->hytale_nickname)->toBeNull();
    expect($user->hytale_id)->toBeNull();
    expect($user->hytale_id_hash)->toBeNull();

    $row = rawLinkedAccountsUserRow($user);

    expect($row->hytale_id_hash)->toBeNull();
});

test('existing hytale account fields can be cleared while the account is not verified', function () {
    $user = User::factory()->create([
        'hytale_nickname' => 'OldNick',
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        'hytale_id_hash' => IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'),
    ]);

    $response = $this
        ->actingAs($user)
        ->put('/settings/accounts', [
            'hytale_nickname' => null,
            'hytale_id' => null,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/accounts');

    $user->refresh();

    expect($user->hytale_nickname)->toBeNull();
    expect($user->hytale_id)->toBeNull();
    expect($user->hytale_id_hash)->toBeNull();

    $row = rawLinkedAccountsUserRow($user);

    expect($row->hytale_id_hash)->toBeNull();
});

test('existing hytale account fields can be changed while the account is not verified', function () {
    $user = User::factory()->create([
        'hytale_nickname' => 'OldNick',
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        'hytale_id_hash' => IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'),
    ]);

    $response = $this
        ->actingAs($user)
        ->put('/settings/accounts', [
            'hytale_nickname' => 'NewNick',
            'hytale_id' => '987fcdeb-51a2-43d7-91c2-4a6b7bd8c9f0',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/accounts');

    $user->refresh();

    expect($user->hytale_nickname)->toBe('NewNick');
    expect($user->hytale_id)->toBe('987fcdeb-51a2-43d7-91c2-4a6b7bd8c9f0');
    expect($user->hytale_id_hash)->toBe(IdHasher::hash('987fcdeb-51a2-43d7-91c2-4a6b7bd8c9f0'));
});

test('user can keep their own hytale id without conflict', function () {
    $user = User::factory()->create([
        'hytale_nickname' => 'SomeNick',
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        'hytale_id_hash' => IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'),
    ]);

    $response = $this
        ->actingAs($user)
        ->put('/settings/accounts', [
            'hytale_nickname' => 'UpdatedNick',
            'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/accounts');

    $user->refresh();

    expect($user->hytale_nickname)->toBe('UpdatedNick');
    expect($user->hytale_id)->toBe('123e4567-e89b-42d3-a456-426614174000');
    expect($user->hytale_id_hash)->toBe(IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'));
});

test('hytale nickname longer than 255 characters is rejected', function () {
    $user = User::factory()->create([
        'hytale_nickname' => 'OldNick',
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        'hytale_id_hash' => IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'),
    ]);

    $response = $this
        ->actingAs($user)
        ->put('/settings/accounts', [
            'hytale_nickname' => str_repeat('a', 256),
            'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        ]);

    $response->assertSessionHasErrors('hytale_nickname');

    $user->refresh();

    expect($user->hytale_nickname)->toBe('OldNick');
    expect($user->hytale_id)->toBe('123e4567-e89b-42d3-a456-426614174000');
    expect($user->hytale_id_hash)->toBe(IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'));
});

test('hytale id that is not a valid uuid is rejected', function () {
    $user = User::factory()->create([
        'hytale_nickname' => 'OldNick',
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        'hytale_id_hash' => IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'),
    ]);

    $response = $this
        ->actingAs($user)
        ->put('/settings/accounts', [
            'hytale_nickname' => 'NewNick',
            'hytale_id' => 'not-a-uuid',
        ]);

    $response->assertSessionHasErrors('hytale_id');

    $user->refresh();

    expect($user->hytale_nickname)->toBe('OldNick');
    expect($user->hytale_id)->toBe('123e4567-e89b-42d3-a456-426614174000');
    expect($user->hytale_id_hash)->toBe(IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'));
});

test('hytale id already used by another user is rejected', function () {
    // The one-hytale-account-per-user constraint lives on hytale_id_hash,
    // so the other user must carry the deterministic hash of their hytale id.
    User::factory()->create([
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        'hytale_id_hash' => IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'),
    ]);

    $user = User::factory()->create([
        'hytale_nickname' => 'OldNick',
        'hytale_id' => null,
    ]);

    $response = $this
        ->actingAs($user)
        ->put('/settings/accounts', [
            'hytale_nickname' => 'NewNick',
            'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        ]);

    $response->assertSessionHasErrors('hytale_id');

    $user->refresh();

    expect($user->hytale_nickname)->toBe('OldNick');
    expect($user->hytale_id)->toBeNull();
    expect($user->hytale_id_hash)->toBeNull();
});

test('hytale account verification status cannot be set through the update payload', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put('/settings/accounts', [
            'hytale_nickname' => 'HytaleFan42',
            'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
            'hytale_account_verified_at' => '2026-10-04 10:00:00',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/accounts');

    $user->refresh();

    expect($user->hytale_nickname)->toBe('HytaleFan42');
    expect($user->hytale_id)->toBe('123e4567-e89b-42d3-a456-426614174000');
    expect($user->hytale_id_hash)->toBe(IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'));
    expect($user->hytale_account_verified_at)->toBeNull();
});

test('hytale account fields cannot be modified once the account is verified', function () {
    $user = User::factory()->create([
        'hytale_nickname' => 'VerifiedNick',
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        'hytale_id_hash' => IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'),
    ]);
    $user->forceFill(['hytale_account_verified_at' => '2026-10-01 10:00:00'])->save();

    $response = $this
        ->actingAs($user)
        ->put('/settings/accounts', [
            'hytale_nickname' => 'NewNick',
            'hytale_id' => '987fcdeb-51a2-43d7-91c2-4a6b7bd8c9f0',
        ]);

    $response->assertSessionHasErrors();

    $user->refresh();

    expect($user->hytale_nickname)->toBe('VerifiedNick');
    expect($user->hytale_id)->toBe('123e4567-e89b-42d3-a456-426614174000');
    expect($user->hytale_id_hash)->toBe(IdHasher::hash('123e4567-e89b-42d3-a456-426614174000'));
    expect($user->hytale_account_verified_at)->not->toBeNull();
});

test('hytale data is never stored in plaintext in the database', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->put('/settings/accounts', [
            'hytale_nickname' => 'HytaleFan42',
            'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        ]);

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

test('hytale id hash is the keyed blind index of the hytale id after submission', function () {
    $user = User::factory()->create();

    expect($user->hytale_id_hash)->toBeNull();

    $this
        ->actingAs($user)
        ->put('/settings/accounts', [
            'hytale_nickname' => 'HytaleFan42',
            'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
        ]);

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
