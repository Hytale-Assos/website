<?php

use App\Hytale\Data\HytaleServer;
use App\Hytale\Data\Module;
use App\Hytale\Data\PlayerSession;
use App\Hytale\Data\WhitelistEntry;
use App\Hytale\Exceptions\HytaleApiException;
use Carbon\Carbon;

function serverPayload(array $overrides = []): array
{
    return array_merge([
        'id' => '01a0a967-f45f-77f6-a7d9-c7622bfc5e2c',
        'module_id' => '02b1b078-a660-77f7-b8e0-d7733c0df3f1',
        'name' => 'Community Server',
        'url' => 'https://servers.hytale.example.com/community',
        'created_at' => '2026-09-16T08:49:17Z',
        'updated_at' => '2026-09-16T09:15:00Z',
    ], $overrides);
}

function modulePayload(array $overrides = []): array
{
    return array_merge([
        'id' => '02b1b078-a660-77f7-b8e0-d7733c0df3f1',
        'name' => 'Whitelist',
        'description' => 'Manages the server whitelist.',
        'is_enabled' => true,
        'scopes' => ['server.read', 'player.read'],
        'created_at' => '2026-09-15T10:00:00Z',
        'updated_at' => '2026-09-15T10:30:00Z',
    ], $overrides);
}

function whitelistEntryPayload(array $overrides = []): array
{
    return array_merge([
        'id' => '03c2c189-b771-88f8-c9f1-e8844d1ea0f2',
        'hytale_server_id' => '01a0a967-f45f-77f6-a7d9-c7622bfc5e2c',
        'hytale_id' => 'hytale-player-04d3d29a',
        'created_at' => '2026-09-16T08:50:00Z',
        'updated_at' => '2026-09-16T08:50:00Z',
    ], $overrides);
}

function playerSessionPayload(array $overrides = []): array
{
    return array_merge([
        'id' => '04d3d29a-c882-99f9-da02-f9955e2fb1a3',
        'hytale_server_id' => '01a0a967-f45f-77f6-a7d9-c7622bfc5e2c',
        'hytale_id' => 'hytale-player-04d3d29a',
        'joined_at' => '2026-09-16T10:00:00Z',
        'ended_at' => '2026-09-16T11:30:00Z',
        'created_at' => '2026-09-16T08:49:17Z',
        'updated_at' => '2026-09-16T09:15:00Z',
    ], $overrides);
}

it('hydrates a server from a well-formed payload', function () {
    $server = HytaleServer::fromArray(serverPayload());

    expect($server->id)->toBe('01a0a967-f45f-77f6-a7d9-c7622bfc5e2c')
        ->and($server->moduleId)->toBe('02b1b078-a660-77f7-b8e0-d7733c0df3f1')
        ->and($server->name)->toBe('Community Server')
        ->and($server->url)->toBe('https://servers.hytale.example.com/community')
        ->and($server->createdAt)->toBeInstanceOf(Carbon::class)
        ->and($server->createdAt)->toEqual(Carbon::parse('2026-09-16T08:49:17Z'))
        ->and($server->updatedAt)->toBeInstanceOf(Carbon::class)
        ->and($server->updatedAt)->toEqual(Carbon::parse('2026-09-16T09:15:00Z'));
});

it('hydrates a module from a well-formed payload', function () {
    $module = Module::fromArray(modulePayload());

    expect($module->id)->toBe('02b1b078-a660-77f7-b8e0-d7733c0df3f1')
        ->and($module->name)->toBe('Whitelist')
        ->and($module->description)->toBe('Manages the server whitelist.')
        ->and($module->isEnabled)->toBeTrue()
        ->and($module->scopes)->toEqual(['server.read', 'player.read'])
        ->and($module->createdAt)->toBeInstanceOf(Carbon::class)
        ->and($module->createdAt)->toEqual(Carbon::parse('2026-09-15T10:00:00Z'))
        ->and($module->updatedAt)->toBeInstanceOf(Carbon::class)
        ->and($module->updatedAt)->toEqual(Carbon::parse('2026-09-15T10:30:00Z'));
});

it('hydrates a whitelist entry from a well-formed payload', function () {
    $entry = WhitelistEntry::fromArray(whitelistEntryPayload());

    expect($entry->id)->toBe('03c2c189-b771-88f8-c9f1-e8844d1ea0f2')
        ->and($entry->hytaleServerId)->toBe('01a0a967-f45f-77f6-a7d9-c7622bfc5e2c')
        ->and($entry->hytaleId)->toBe('hytale-player-04d3d29a')
        ->and($entry->createdAt)->toBeInstanceOf(Carbon::class)
        ->and($entry->createdAt)->toEqual(Carbon::parse('2026-09-16T08:50:00Z'))
        ->and($entry->updatedAt)->toBeInstanceOf(Carbon::class)
        ->and($entry->updatedAt)->toEqual(Carbon::parse('2026-09-16T08:50:00Z'));
});

it('hydrates a player session from a well-formed payload', function () {
    $session = PlayerSession::fromArray(playerSessionPayload());

    expect($session->id)->toBe('04d3d29a-c882-99f9-da02-f9955e2fb1a3')
        ->and($session->hytaleServerId)->toBe('01a0a967-f45f-77f6-a7d9-c7622bfc5e2c')
        ->and($session->hytaleId)->toBe('hytale-player-04d3d29a')
        ->and($session->joinedAt)->toBeInstanceOf(Carbon::class)
        ->and($session->joinedAt)->toEqual(Carbon::parse('2026-09-16T10:00:00Z'))
        ->and($session->endedAt)->toBeInstanceOf(Carbon::class)
        ->and($session->endedAt)->toEqual(Carbon::parse('2026-09-16T11:30:00Z'))
        ->and($session->createdAt)->toBeInstanceOf(Carbon::class)
        ->and($session->createdAt)->toEqual(Carbon::parse('2026-09-16T08:49:17Z'))
        ->and($session->updatedAt)->toBeInstanceOf(Carbon::class)
        ->and($session->updatedAt)->toEqual(Carbon::parse('2026-09-16T09:15:00Z'));
});

it('keeps building a server with null dates when a date cannot be parsed', function () {
    foreach (['not-a-date', 9876543210, null] as $badDate) {
        $server = HytaleServer::fromArray(serverPayload([
            'created_at' => $badDate,
            'updated_at' => $badDate,
        ]));

        expect($server)->toBeInstanceOf(HytaleServer::class)
            ->and($server->createdAt)->toBeNull()
            ->and($server->updatedAt)->toBeNull();
    }
});

it('keeps building a module with null dates when a date cannot be parsed', function () {
    foreach (['not-a-date', 9876543210, null] as $badDate) {
        $module = Module::fromArray(modulePayload([
            'created_at' => $badDate,
            'updated_at' => $badDate,
        ]));

        expect($module)->toBeInstanceOf(Module::class)
            ->and($module->createdAt)->toBeNull()
            ->and($module->updatedAt)->toBeNull();
    }
});

it('keeps building a whitelist entry with null dates when a date cannot be parsed', function () {
    foreach (['not-a-date', 9876543210, null] as $badDate) {
        $entry = WhitelistEntry::fromArray(whitelistEntryPayload([
            'created_at' => $badDate,
            'updated_at' => $badDate,
        ]));

        expect($entry)->toBeInstanceOf(WhitelistEntry::class)
            ->and($entry->createdAt)->toBeNull()
            ->and($entry->updatedAt)->toBeNull();
    }
});

it('keeps building a player session with null dates when a date cannot be parsed', function () {
    foreach (['not-a-date', 9876543210, null] as $badDate) {
        $session = PlayerSession::fromArray(playerSessionPayload([
            'joined_at' => $badDate,
            'ended_at' => $badDate,
            'created_at' => $badDate,
            'updated_at' => $badDate,
        ]));

        expect($session)->toBeInstanceOf(PlayerSession::class)
            ->and($session->joinedAt)->toBeNull()
            ->and($session->endedAt)->toBeNull()
            ->and($session->createdAt)->toBeNull()
            ->and($session->updatedAt)->toBeNull();
    }
});

it('rejects a server payload with a missing, empty, or non-string id', function () {
    $missingId = serverPayload();
    unset($missingId['id']);

    $payloads = [
        $missingId,
        serverPayload(['id' => '']),
        serverPayload(['id' => null]),
        serverPayload(['id' => 12345]),
    ];

    foreach ($payloads as $payload) {
        expect(fn () => HytaleServer::fromArray($payload))->toThrow(HytaleApiException::class);
    }
});

it('rejects a module payload with a missing, empty, or non-string id', function () {
    $missingId = modulePayload();
    unset($missingId['id']);

    $payloads = [
        $missingId,
        modulePayload(['id' => '']),
        modulePayload(['id' => null]),
        modulePayload(['id' => 12345]),
    ];

    foreach ($payloads as $payload) {
        expect(fn () => Module::fromArray($payload))->toThrow(HytaleApiException::class);
    }
});

it('rejects a whitelist entry payload with a missing, empty, or non-string id', function () {
    $missingId = whitelistEntryPayload();
    unset($missingId['id']);

    $payloads = [
        $missingId,
        whitelistEntryPayload(['id' => '']),
        whitelistEntryPayload(['id' => null]),
        whitelistEntryPayload(['id' => 12345]),
    ];

    foreach ($payloads as $payload) {
        expect(fn () => WhitelistEntry::fromArray($payload))->toThrow(HytaleApiException::class);
    }
});

it('rejects a player session payload with a missing, empty, or non-string id', function () {
    $missingId = playerSessionPayload();
    unset($missingId['id']);

    $payloads = [
        $missingId,
        playerSessionPayload(['id' => '']),
        playerSessionPayload(['id' => null]),
        playerSessionPayload(['id' => 12345]),
    ];

    foreach ($payloads as $payload) {
        expect(fn () => PlayerSession::fromArray($payload))->toThrow(HytaleApiException::class);
    }
});

it('defaults missing optional display fields to empty strings', function () {
    $withoutName = serverPayload();
    unset($withoutName['name']);

    $withoutModuleId = serverPayload();
    unset($withoutModuleId['module_id']);

    expect(HytaleServer::fromArray($withoutName)->name)->toBe('')
        ->and(HytaleServer::fromArray($withoutModuleId)->moduleId)->toBe('');
});
