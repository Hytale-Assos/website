<?php

use App\Hytale\Contracts\HytaleApiClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('a member with a linked hytale account can delete their own whitelist entry', function () {
    $hytaleId = Str::uuid()->toString();
    $member = User::factory()->withHytaleAccount($hytaleId, verified: true)->create();

    $client = app(HytaleApiClient::class);
    $serverId = $client->servers()->items[0]->id;
    $client->addToWhitelist($serverId, $hytaleId);

    $entryId = $client->playerWhitelists($hytaleId)->items[0]->id;

    $response = $this
        ->actingAs($member)
        ->delete(route('whitelist.destroy', $entryId));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $whitelists = $client->playerWhitelists($hytaleId);

    expect($whitelists->total)->toBe(0);
    expect($whitelists->items)->toHaveCount(0);
});

test("a member cannot delete another member's whitelist entry", function () {
    $ownerHytaleId = Str::uuid()->toString();
    User::factory()->withHytaleAccount($ownerHytaleId, verified: true)->create();
    $intruder = User::factory()->withHytaleAccount(verified: true)->create();

    $client = app(HytaleApiClient::class);
    $serverId = $client->servers()->items[0]->id;
    $client->addToWhitelist($serverId, $ownerHytaleId);

    $entryId = $client->playerWhitelists($ownerHytaleId)->items[0]->id;

    $response = $this
        ->actingAs($intruder)
        ->delete(route('whitelist.destroy', $entryId));

    $response->assertForbidden();

    $whitelists = $client->playerWhitelists($ownerHytaleId);

    expect($whitelists->total)->toBe(1);
    expect($whitelists->items)->toHaveCount(1);
    expect($whitelists->items[0]->id)->toBe($entryId);
});

test('a member without a linked hytale account cannot delete a whitelist entry', function () {
    $ownerHytaleId = Str::uuid()->toString();
    User::factory()->withHytaleAccount($ownerHytaleId, verified: true)->create();
    $unlinked = User::factory()->create();

    $client = app(HytaleApiClient::class);
    $serverId = $client->servers()->items[0]->id;
    $client->addToWhitelist($serverId, $ownerHytaleId);

    $entryId = $client->playerWhitelists($ownerHytaleId)->items[0]->id;

    $response = $this
        ->actingAs($unlinked)
        ->delete(route('whitelist.destroy', $entryId));

    $response->assertForbidden();

    $whitelists = $client->playerWhitelists($ownerHytaleId);

    expect($whitelists->total)->toBe(1);
    expect($whitelists->items)->toHaveCount(1);
    expect($whitelists->items[0]->id)->toBe($entryId);
});

test('deleting an owned whitelist entry fails with a user-facing error when the hytale core is unreachable', function () {
    $hytaleId = Str::uuid()->toString();
    $member = User::factory()->withHytaleAccount($hytaleId, verified: true)->create();

    $client = app(HytaleApiClient::class);
    $serverId = $client->servers()->items[0]->id;
    $client->addToWhitelist($serverId, $hytaleId);

    $entryId = $client->playerWhitelists($hytaleId)->items[0]->id;

    config([
        'hytale.mock' => false,
        'hytale.base_url' => 'http://core.test',
        'hytale.api_key' => 'test-key',
        'hytale.hmac_secret' => 'test-secret',
        'hytale.cache.enabled' => false,
    ]);
    app()->forgetInstance(HytaleApiClient::class);
    Http::fake(fn () => throw new ConnectionException('cURL error 7'));

    $response = $this
        ->actingAs($member)
        ->delete(route('whitelist.destroy', $entryId));

    $response
        ->assertRedirect()
        ->assertSessionHasErrors();
});

test('guests are redirected to the login page when deleting a whitelist entry', function () {
    $this->delete(route('whitelist.destroy', Str::uuid()->toString()))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
