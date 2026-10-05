<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\IdHasher;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class LinkedAccountOAuthController extends Controller
{
    /**
     * Providers that can be linked to a user account, mapped to the user
     * columns their OAuth identity is stored in. When the provider id column
     * is encrypted, a deterministic hash column carries its uniqueness.
     *
     * @var array<string, array{id_column: string, hash_column: string|null, nickname_column: string}>
     */
    private const PROVIDERS = [
        'discord' => [
            'id_column' => 'discord_id',
            'hash_column' => 'discord_id_hash',
            'nickname_column' => 'discord_nickname',
        ],
    ];

    /**
     * Redirect the user to the provider's OAuth authorization page.
     */
    public function redirect(Request $request, string $provider): SymfonyRedirectResponse
    {
        $this->ensureProviderIsSupported($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the OAuth callback and link the provider account to the current user.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProviderIsSupported($provider);

        try {
            $oauthUser = Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            report($e);

            Toast::error(__('The :provider account could not be linked. Please try again.', ['provider' => $provider]));

            return to_route('accounts.edit');
        }

        [$idColumn, $hashColumn, $nicknameColumn] = $this->providerColumns($provider);

        $providerId = $oauthUser->getId();

        $lookupColumn = $hashColumn ?? $idColumn;
        $lookupValue = $hashColumn !== null ? IdHasher::hash($providerId) : $providerId;

        $alreadyLinked = User::query()
            ->where($lookupColumn, $lookupValue)
            ->whereKeyNot($request->user()->getKey())
            ->exists();

        if ($alreadyLinked) {
            Toast::error(__('This :provider account is already linked to another user.', ['provider' => $provider]));

            return to_route('accounts.edit');
        }

        $attributes = [
            $idColumn => $providerId,
            $nicknameColumn => $oauthUser->getNickname() ?? $oauthUser->getName(),
        ];

        $request->user()->fill($attributes)->save();

        Toast::success(__('Your :provider account has been linked.', ['provider' => Str::ucfirst($provider)]));

        return to_route('accounts.edit');
    }

    /**
     * Unlink the provider account from the current user.
     */
    public function destroy(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProviderIsSupported($provider);

        [$idColumn, $hashColumn, $nicknameColumn] = $this->providerColumns($provider);

        $attributes = [
            $idColumn => null,
            $nicknameColumn => null,
        ];

        $request->user()->fill($attributes)->save();

        Toast::success(__('Your :provider account has been unlinked.', ['provider' => Str::ucfirst($provider)]));

        return to_route('accounts.edit');
    }

    /**
     * Abort the request when the provider is not linkable.
     */
    private function ensureProviderIsSupported(string $provider): void
    {
        if (! array_key_exists($provider, self::PROVIDERS)) {
            abort(404);
        }
    }

    /**
     * Get the user columns storing the given provider's identity.
     *
     * @return array{0: string, 1: string|null, 2: string}
     */
    private function providerColumns(string $provider): array
    {
        $config = self::PROVIDERS[$provider];

        return [$config['id_column'], $config['hash_column'], $config['nickname_column']];
    }
}
