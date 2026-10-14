<?php

use App\Models\User;
use App\Notifications\LinkedAccountUnlinked;
use App\Support\IdHasher;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;

function createDiscordLinkedMember(): User
{
    return User::factory()->create([
        'discord_id' => '1234567890123456789',
        'discord_id_hash' => IdHasher::hash('1234567890123456789'),
        'discord_nickname' => 'testuser',
    ]);
}

function mockDiscordCallback(string $discordId, string $nickname): void
{
    $socialiteUser = Mockery::mock();
    $socialiteUser->shouldReceive('getId')->andReturn($discordId);
    $socialiteUser->shouldReceive('getNickname')->andReturn($nickname);
    $socialiteUser->shouldReceive('getName')->andReturn('Discord User');

    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')->with('discord')->andReturn($provider);
}

test('unlinking discord without the current password is refused', function () {
    $user = createDiscordLinkedMember();
    $this->actingAs($user);

    $response = $this->delete(route('linked.unlink', 'discord'));

    $response->assertSessionHasErrors('password');
    expect($user->refresh()->discord_id)->toBe('1234567890123456789')
        ->and($user->refresh()->discord_nickname)->toBe('testuser');
});

test('unlinking discord with a wrong password is refused and sends no mail', function () {
    Notification::fake();

    $user = createDiscordLinkedMember();
    $this->actingAs($user);

    $response = $this->delete(route('linked.unlink', 'discord'), [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('password');
    expect($user->refresh()->discord_id)->toBe('1234567890123456789')
        ->and($user->refresh()->discord_nickname)->toBe('testuser');
    Notification::assertNothingSent();
});

test('unlinking discord with the correct password unlinks the account and notifies the member', function () {
    Notification::fake();

    $user = createDiscordLinkedMember();
    $this->actingAs($user);

    $response = $this->delete(route('linked.unlink', 'discord'), [
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    expect($user->refresh()->discord_id)->toBeNull()
        ->and($user->refresh()->discord_nickname)->toBeNull();
    Notification::assertSentTo($user, LinkedAccountUnlinked::class);
});

test('oauth callback for a different discord account does not replace the existing link', function () {
    $user = createDiscordLinkedMember();
    $this->actingAs($user);

    mockDiscordCallback('9876543210987654321', 'otheruser');
    $response = $this->get('/auth/discord/callback');

    expect($user->refresh()->discord_id)->toBe('1234567890123456789')
        ->and($user->refresh()->discord_nickname)->toBe('testuser');
});

test('oauth callback for the already linked discord account does not error', function () {
    $user = createDiscordLinkedMember();
    $this->actingAs($user);

    mockDiscordCallback('1234567890123456789', 'testuser');
    $response = $this->get('/auth/discord/callback');

    $response->assertSessionHasNoErrors();
    expect($user->refresh()->discord_id)->toBe('1234567890123456789')
        ->and($user->refresh()->discord_nickname)->toBe('testuser');
});

test('unlinking an unsupported provider answers 404 even without a password', function () {
    $user = createDiscordLinkedMember();
    $this->actingAs($user);

    $response = $this->delete(route('linked.unlink', 'steam'));

    $response->assertNotFound();
});

test('file serving routes under storage are not registered', function () {
    $this->get('/storage/avatars/test.png')->assertNotFound();
    $this->get('/storage/some/nested/file.txt')->assertNotFound();
});
