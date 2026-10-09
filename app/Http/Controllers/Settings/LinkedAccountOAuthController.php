<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\IdHasher;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider as SocialiteProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class LinkedAccountOAuthController extends Controller
{
    /**
     * Providers that can be linked to a user account, mapped to the user
     * columns their OAuth identity is stored in. When the provider id column
     * is encrypted, a deterministic hash column carries its uniqueness.
     *
     * `scopes` lists the provider-specific scopes the link flow requests on
     * top of the driver defaults. `verified_column` names the column that
     * marks accounts confirmed by the provider rather than self-declared;
     * it is deliberately not fillable, so it is force-filled on link and
     * cleared on unlink.
     *
     * @var array<string, array{id_column: string, hash_column: string|null, nickname_column: string, verified_column: string|null, scopes: list<string>}>
     */
    private const PROVIDERS = [
        'discord' => [
            'id_column' => 'discord_id',
            'hash_column' => 'discord_id_hash',
            'nickname_column' => 'discord_nickname',
            'verified_column' => null,
            'scopes' => [],
        ],
        'hytale' => [
            'id_column' => 'hytale_id',
            'hash_column' => 'hytale_id_hash',
            'nickname_column' => 'hytale_nickname',
            'verified_column' => 'hytale_account_verified_at',
            'scopes' => ['hytale:profile'],
        ],
    ];

    /**
     * Redirect the user to the provider's OAuth authorization page.
     */
    public function redirect(Request $request, string $provider): SymfonyRedirectResponse
    {
        $this->ensureProviderIsSupported($provider);

        $driver = Socialite::driver($provider);

        $scopes = self::PROVIDERS[$provider]['scopes'];

        if ($scopes !== [] && $driver instanceof SocialiteProvider) {
            $driver->scopes($scopes);
        }

        return $driver->redirect();
    }

    /**
     * Handle the OAuth callback and link the provider account to the current user.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProviderIsSupported($provider);

        // Authorization failures are delivered back on the redirect URI as
        // error query parameters (a declined consent, an invalid scope),
        // before any code can be exchanged.
        if ($request->query('error') !== null) {
            Log::warning('OAuth authorization error on the link callback.', [
                'provider' => $provider,
                'error' => $request->query('error'),
                'error_description' => $request->query('error_description'),
            ]);

            Toast::error(__('The :provider account could not be linked. Please try again.', ['provider' => $provider]));

            return to_route('accounts.edit');
        }

        try {
            $oauthUser = Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            report($e);

            Toast::error(__('The :provider account could not be linked. Please try again.', ['provider' => $provider]));

            return to_route('accounts.edit');
        }

        $providerId = $this->providerIdentity($oauthUser, $provider);

        if ($providerId === null) {
            Log::warning('OAuth user carries no linkable identity.', [
                'provider' => $provider,
                'claims' => method_exists($oauthUser, 'getRaw') ? array_keys($oauthUser->getRaw() ?? []) : null,
            ]);

            Toast::error(__('The :provider account could not be linked. Please try again.', ['provider' => $provider]));

            return to_route('accounts.edit');
        }

        [$idColumn, $hashColumn, $nicknameColumn] = $this->providerColumns($provider);

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

        $user = $request->user()->fill($attributes);

        $verifiedColumn = self::PROVIDERS[$provider]['verified_column'];

        if ($verifiedColumn !== null) {
            $user->forceFill([$verifiedColumn => now()]);
        }

        $user->save();

        Toast::success(__('Your :provider account has been linked.', ['provider' => Str::ucfirst($provider)]));

        return to_route('accounts.edit');
    }

    /**
     * Unlink the provider account from the current user.
     */
    public function destroy(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProviderIsSupported($provider);

        [$idColumn, , $nicknameColumn] = $this->providerColumns($provider);

        $attributes = [
            $idColumn => null,
            $nicknameColumn => null,
        ];

        $user = $request->user()->fill($attributes);

        $verifiedColumn = self::PROVIDERS[$provider]['verified_column'];

        if ($verifiedColumn !== null) {
            $user->forceFill([$verifiedColumn => null]);
        }

        $user->save();

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
     * The identity a provider contributes to the linked account. Discord
     * contributes its OAuth id. Hytale contributes the game profile uuid
     * picked at sign-in: its `sub` claim is an anonymous per-application
     * identifier that would not match the game identity the site keys on.
     */
    private function providerIdentity(mixed $oauthUser, string $provider): ?string
    {
        return $provider === 'hytale' ? $oauthUser->uuid : $oauthUser->getId();
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
