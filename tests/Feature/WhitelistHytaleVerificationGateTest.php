<?php

use App\Hytale\Contracts\HytaleApiClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('a hand-entered unverified hytale account cannot join a whitelist', function () {
    $client = app(HytaleApiClient::class);

    $serverId = $client->servers()->items[0]->id;
    $hytaleId = Str::uuid()->toString();

    $member = User::factory()->withHytaleAccount($hytaleId, verified: false)->create();

    $response = $this
        ->actingAs($member)
        ->post(route('whitelist.store'), ['hytale_server_id' => $serverId]);

    $response->assertSessionHasErrors('whitelist');

    expect($client->playerWhitelists($hytaleId)->total)->toBe(0);
});

test('an oauth-verified hytale account can join a whitelist', function () {
    $client = app(HytaleApiClient::class);

    $serverId = $client->servers()->items[0]->id;
    $hytaleId = Str::uuid()->toString();

    $member = User::factory()->withHytaleAccount($hytaleId, verified: true)->create();

    $response = $this
        ->actingAs($member)
        ->post(route('whitelist.store'), ['hytale_server_id' => $serverId]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($client->playerWhitelists($hytaleId)->total)->toBe(1);
});

test('an unverified hytale account is refused with a message different from a missing account', function () {
    $client = app(HytaleApiClient::class);

    $serverId = $client->servers()->items[0]->id;

    $missing = User::factory()->create();

    $this
        ->actingAs($missing)
        ->post(route('whitelist.store'), ['hytale_server_id' => $serverId]);

    $missingMessage = data_get(session('errors'), 'default.messages.whitelist.0');

    $this->flushSession();

    $unverified = User::factory()->withHytaleAccount(Str::uuid()->toString(), verified: false)->create();

    $this
        ->actingAs($unverified)
        ->post(route('whitelist.store'), ['hytale_server_id' => $serverId]);

    $unverifiedMessage = data_get(session('errors'), 'default.messages.whitelist.0');

    expect($unverifiedMessage)->not->toBeNull();
    expect($missingMessage)->not->toBeNull();
    expect($unverifiedMessage)->not->toBe($missingMessage);
});
