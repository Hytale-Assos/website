<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Toast;
use Illuminate\Http\Request;

trait SharesHytaleProps
{
    /**
     * Resolve the current member's Hytale id.
     *
     * The id is only handed to the core when the account was confirmed through
     * Hytale OAuth (`hytale_account_verified_at`). Accounts whose nickname and
     * id were entered by hand before the OAuth flow existed carry a value that
     * was never vouched for by Hytale, so it is withheld until the member
     * verifies it from the linked accounts page.
     */
    protected function hytaleId(Request $request): ?string
    {
        $user = $request->user();

        if ($user === null || $user->hytale_account_verified_at === null) {
            return null;
        }

        $id = $user->hytale_id;

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * Whether the member has a hand-entered Hytale account still waiting to be
     * confirmed through OAuth. Distinguishes the migration prompt from the
     * plain "connect your account" state.
     */
    protected function requiresHytaleVerification(Request $request): bool
    {
        $user = $request->user();

        return $user !== null
            && $user->hytale_id !== null
            && $user->hytale_account_verified_at === null;
    }

    /**
     * Props every member page shares: linked account, mock flag, API error.
     *
     * When the core is unreachable, a toast is flashed so the failure is made
     * obvious even on pages that render no inline alert.
     *
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    protected function baseProps(Request $request, array $props = []): array
    {
        $error = $props['error'] ?? null;

        if (is_string($error) && $error !== '') {
            Toast::error($error);
        }

        return [
            'hytaleId' => $this->hytaleId($request),
            'mock' => (bool) config('hytale.mock'),
            ...$props,
        ];
    }
}
