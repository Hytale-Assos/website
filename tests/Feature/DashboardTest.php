<?php

use App\Hytale\Contracts\HytaleApiClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('shows the dashboard to authenticated verified users', function () {
    $user = User::factory()->create(['hytale_id' => null]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('counts')
        ->has('recentSessions')
        ->has('servers')
        ->where('hytaleId', null)
        ->where('mock', true)
    );
});

it('returns the seeded servers from the fake api', function () {
    $user = User::factory()->create(['hytale_id' => '11111111-1111-7111-8111-111111111111']);

    $response = $this->actingAs($user)->get(route('servers.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Servers')
        ->has('servers', 2)
        ->has('whitelists')
        ->where('hytaleId', '11111111-1111-7111-8111-111111111111')
    );
});

it('shows the whitelist page', function () {
    $user = User::factory()->create(['hytale_id' => '44444444-4444-7444-8444-444444444444']);

    $this->actingAs($user)
        ->get(route('whitelist.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Whitelist')
            ->has('whitelists')
            ->has('servers')
        );
});

it('shows the sessions page', function () {
    $user = User::factory()->create(['hytale_id' => '55555555-5555-7555-8555-555555555555']);

    $this->actingAs($user)
        ->get(route('sessions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Sessions')
            ->has('sessions')
            ->has('servers')
        );
});

it('prevents joining when no hytale account is linked', function () {
    $user = User::factory()->create(['hytale_id' => null]);

    $response = $this->actingAs($user)
        ->from(route('servers.index'))
        ->post(route('whitelist.store'), [
            'hytale_server_id' => '01a0a967-cbaa-7746-b9e8-b16daee6d50b',
        ]);

    $response->assertRedirect(route('servers.index'));
    $response->assertSessionHasErrors('whitelist');
});

it('adds the member to a whitelist when hytale account is linked', function () {
    $user = User::factory()->create(['hytale_id' => '22222222-2222-7222-8222-222222222222']);
    $hytale = app(HytaleApiClient::class);
    $server = $hytale->servers()->items[0];

    $response = $this->actingAs($user)
        ->from(route('servers.index'))
        ->post(route('whitelist.store'), [
            'hytale_server_id' => $server->id,
        ]);

    $response->assertRedirect(route('servers.index'));
    $response->assertSessionHasNoErrors();

    expect($hytale->playerWhitelists($user->hytale_id)->total)->toBe(1);
});

it('removes a whitelist entry', function () {
    $user = User::factory()->create(['hytale_id' => '33333333-3333-7333-8333-333333333333']);
    $hytale = app(HytaleApiClient::class);
    $server = $hytale->servers()->items[0];
    $entry = $hytale->addToWhitelist($server->id, $user->hytale_id);

    $response = $this->actingAs($user)
        ->from(route('whitelist.index'))
        ->delete(route('whitelist.destroy', $entry->id));

    $response->assertRedirect(route('whitelist.index'));
    $response->assertSessionHasNoErrors();

    expect($hytale->playerWhitelists($user->hytale_id)->total)->toBe(0);
});
