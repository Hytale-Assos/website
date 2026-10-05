<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Personal data the website holds about a member, grouped by section.
 *
 * This is the single source of truth for the "Data & privacy" page and the
 * JSON export: both render what this inventory returns. It is built as an
 * explicit allowlist — every field is picked by hand — so a future column
 * (secret or not) can never leak into a data disclosure by omission.
 *
 * Deliberately excluded: bearer or secret material. Session identifiers,
 * session payloads, 2FA secrets, recovery codes, passkey credential ids
 * and public keys, and password reset tokens never appear here; only the
 * state and metadata useful to the member.
 */
class PersonalDataInventory
{
    /**
     * Build the inventory of the given user's personal data.
     *
     * @return array{
     *     account: array{name: string, email: string, school_email: string|null, created_at: string|null},
     *     identification: array{firstname: string|null, lastname: string|null, grade_level: string|null, status: string|null, is_public: bool},
     *     linked_accounts: array{discord: array{linked: bool, nickname: string|null}, hytale: array{linked: bool, nickname: string|null, verified_at: string|null}},
     *     security: array{two_factor: array{enabled: bool, confirmed_at: string|null}, passkeys: array<int, array{name: string, created_at: string|null, last_used_at: string|null}>, sessions: array<int, array{ip_address: string|null, user_agent: string|null, last_activity: string|null}>}
     * }
     */
    public function sections(User $user): array
    {
        return [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'school_email' => $user->school_email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'identification' => [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'grade_level' => $user->grade_level,
                'status' => match (true) {
                    $user->is_internal => 'internal',
                    $user->is_external => 'external',
                    default => null,
                },
                'is_public' => (bool) $user->is_public,
            ],
            'linked_accounts' => [
                'discord' => [
                    'linked' => $user->discord_id !== null,
                    'nickname' => $user->discord_nickname,
                ],
                'hytale' => [
                    'linked' => $user->hytale_id !== null,
                    'nickname' => $user->hytale_nickname,
                    'verified_at' => $user->hytale_account_verified_at?->toIso8601String(),
                ],
            ],
            'security' => [
                'two_factor' => [
                    'enabled' => $user->hasEnabledTwoFactorAuthentication(),
                    'confirmed_at' => $user->two_factor_confirmed_at?->toIso8601String(),
                ],
                'passkeys' => $user->passkeys()
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn ($passkey) => [
                        'name' => $passkey->name,
                        'created_at' => $passkey->created_at?->toIso8601String(),
                        'last_used_at' => $passkey->last_used_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all(),
                'sessions' => DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->orderByDesc('last_activity')
                    ->get(['ip_address', 'user_agent', 'last_activity'])
                    ->map(fn ($session) => [
                        'ip_address' => $session->ip_address,
                        'user_agent' => $session->user_agent,
                        'last_activity' => $session->last_activity !== null
                            ? date('c', (int) $session->last_activity)
                            : null,
                    ])
                    ->all(),
            ],
        ];
    }
}
