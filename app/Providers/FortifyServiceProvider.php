<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\NeutralFailedPasswordResetLinkResponse;
use App\Http\Responses\NeutralFailedPasswordResetResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\FailedPasswordResetResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use RuntimeException;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Reset-link responses must not depend on whether the submitted
        // email belongs to a member: a failed request is answered
        // exactly like a successful one, and a wrong (email, token)
        // pair reads as an invalid token, never as a missing user.
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, NeutralFailedPasswordResetLinkResponse::class);
        $this->app->bind(FailedPasswordResetResponse::class, NeutralFailedPasswordResetResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureRouteThrottling();
        $this->configurePasskeys();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by(
                ($request->input('credential.id') ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-email', function (Request $request) {
            $email = $request->input('email');
            $email = is_string($email) ? Str::lower($email) : '';

            return [
                // Per (email, IP): one address pair cannot be hammered
                // with reset mails.
                Limit::perMinute(6)->by(
                    Str::transliterate($email).'|'.$request->ip(),
                ),
                // Per IP: probing many distinct addresses from a single
                // IP to enumerate member emails hits this ceiling.
                Limit::perMinute(20)->by($request->ip()),
            ];
        });
    }

    /**
     * Fortify only throttles its login, two-factor, and passkeys
     * endpoints: registration, password-reset requests and the reset
     * itself would otherwise be unthrottled public endpoints (email
     * probing, mass account creation, reset-mail spam, unbounded
     * bcrypt on the reset route).
     *
     * The name lookup table is not rebuilt yet when this booted
     * callback runs, so refresh it explicitly before resolving the
     * Fortify routes by name.
     */
    private function configureRouteThrottling(): void
    {
        $this->app->booted(function (): void {
            Route::getRoutes()->refreshNameLookups();

            Route::getRoutes()->getByName('register.store')?->middleware('throttle:registration');
            Route::getRoutes()->getByName('password.email')?->middleware('throttle:password-email');
            Route::getRoutes()->getByName('password.update')?->middleware('throttle:password');

            // Password-gated endpoints: an authenticated session must not
            // be able to brute-force the member's password online (each
            // attempt costs a bcrypt; a success unlocks 2FA, passkeys and
            // password changes for an hour).
            Route::getRoutes()->getByName('password.confirm.store')?->middleware('throttle:password');
            Route::getRoutes()->getByName('two-factor.confirm')?->middleware('throttle:password');
        });
    }

    /**
     * Refuse to boot for a non-console production request without a
     * dedicated passkeys user handle secret: Fortify would otherwise
     * silently derive WebAuthn user handles from APP_KEY, coupling them
     * to the encryption key. Console commands (package:discover during
     * builds, migrations, queues) are intentionally allowed to boot so
     * the secret does not need to be present in build-time tooling.
     */
    private function configurePasskeys(): void
    {
        if ($this->app->isProduction()
            && ! $this->app->runningInConsole()
            && ! config('fortify.passkeys.user_handle_secret')) {
            throw new RuntimeException(
                'PASSKEYS_USER_HANDLE_SECRET must be set in production (generate with: php -r "echo \'base64:\'.base64_encode(random_bytes(32)).PHP_EOL;")'
            );
        }
    }
}
