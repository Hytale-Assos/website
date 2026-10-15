<?php

namespace App\Support;

/**
 * Providers whose accounts can be linked to a member profile, mapped
 * to the user columns their OAuth identity is stored in. Single source
 * of truth: the OAuth controller and the unlink request both resolve
 * the whitelist here, so they can never drift apart.
 */
class LinkableProviders
{
    /**
     * @var array<string, array{id_column: string, hash_column: string|null, nickname_column: string}>
     */
    private const MAP = [
        'discord' => [
            'id_column' => 'discord_id',
            'hash_column' => 'discord_id_hash',
            'nickname_column' => 'discord_nickname',
        ],
    ];

    /**
     * Whether the provider can be linked to a member profile.
     */
    public static function exists(string $provider): bool
    {
        return array_key_exists($provider, self::MAP);
    }

    /**
     * Get the user columns storing the given provider's identity.
     *
     * @return array{0: string, 1: string|null, 2: string}
     */
    public static function columns(string $provider): array
    {
        $config = self::MAP[$provider];

        return [$config['id_column'], $config['hash_column'], $config['nickname_column']];
    }
}
