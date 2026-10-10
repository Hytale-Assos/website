<?php

use App\Hytale\Contracts\HytaleApiClient;
use App\Models\User;
use Illuminate\Support\Str;

test('joining a whitelist with a server id that is not a valid uuid is rejected', function (string $hytaleServerId) {
    $member = User::factory()->create(['hytale_id' => (string) Str::uuid()]);

    $response = $this
        ->actingAs($member)
        ->post(route('whitelist.store'), ['hytale_server_id' => $hytaleServerId]);

    $response->assertSessionHasErrors('hytale_server_id');

    $client = app(HytaleApiClient::class);

    expect($client->playerWhitelists($member->hytale_id)->total)->toBe(0);
})->with([
    'script tag' => ['<script>alert(1)</script>'],
    'plain word' => ['notauuid'],
    'empty string' => [''],
]);

test('joining a whitelist with a well-formed uuid server id adds the member', function () {
    $client = app(HytaleApiClient::class);

    $serverId = $client->servers()->items[0]->id;
    $member = User::factory()->create(['hytale_id' => (string) Str::uuid()]);

    $response = $this
        ->actingAs($member)
        ->post(route('whitelist.store'), ['hytale_server_id' => $serverId]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $whitelists = $client->playerWhitelists($member->hytale_id);

    expect($whitelists->total)->toBe(1);
});

test('deleting a whitelist entry whose id is not a valid uuid returns 404 and touches nothing', function () {
    $client = app(HytaleApiClient::class);

    $serverId = $client->servers()->items[0]->id;
    $member = User::factory()->create(['hytale_id' => (string) Str::uuid()]);

    $client->addToWhitelist($serverId, $member->hytale_id);

    $response = $this
        ->actingAs($member)
        ->delete(route('whitelist.destroy', 'definitely-not-a-uuid'));

    $response->assertNotFound();

    $whitelists = $client->playerWhitelists($member->hytale_id);

    expect($whitelists->total)->toBe(1);
});

test('deleting a whitelist entry with a well-formed uuid removes the entry', function () {
    $client = app(HytaleApiClient::class);

    $serverId = $client->servers()->items[0]->id;
    $member = User::factory()->create(['hytale_id' => (string) Str::uuid()]);

    $client->addToWhitelist($serverId, $member->hytale_id);

    $entryId = $client->playerWhitelists($member->hytale_id)->items[0]->id;

    $response = $this
        ->actingAs($member)
        ->delete(route('whitelist.destroy', $entryId));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($client->playerWhitelists($member->hytale_id)->total)->toBe(0);
});
