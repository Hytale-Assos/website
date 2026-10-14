<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UnlinkAccountRequest;
use App\Models\User;
use App\Notifications\LinkedAccountUnlinked;
use App\Support\IdHasher;
use App\Support\LinkableProviders;
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

        [$idColumn, $hashColumn, $nicknameColumn] = LinkableProviders::columns($provider);

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

        // A member with a provider account already linked must swap it
        // explicitly: the callback refuses to silently replace a proven
        // identity with another one.
        $currentId = $request->user()->{$idColumn};

        if ($currentId !== null && $currentId !== $providerId) {
            Toast::error(__('Your :provider account is already linked. Unlink it first to link a different one.', ['provider' => Str::ucfirst($provider)]));

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
     *
     * The request is re-authenticated with the current password (see
     * UnlinkAccountRequest): a stolen session must not strip a proven
     * identity in one click. The member is notified afterwards.
     */
    public function destroy(UnlinkAccountRequest $request, string $provider): RedirectResponse
    {
        [$idColumn, $hashColumn, $nicknameColumn] = LinkableProviders::columns($provider);

        $attributes = [
            $idColumn => null,
            $nicknameColumn => null,
        ];

        $request->user()->fill($attributes)->save();

        $request->user()->notify(new LinkedAccountUnlinked(Str::ucfirst($provider)));

        Toast::success(__('Your :provider account has been unlinked.', ['provider' => Str::ucfirst($provider)]));

        return to_route('accounts.edit');
    }

    /**
     * Abort the request when the provider is not linkable.
     */
    private function ensureProviderIsSupported(string $provider): void
    {
        if (! LinkableProviders::exists($provider)) {
            abort(404);
        }
    }
}
