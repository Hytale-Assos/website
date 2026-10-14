<?php

namespace App\Providers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Spatie\Prometheus\Facades\Prometheus;

class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Register the application's Prometheus collectors. Metric values are
     * computed when the metrics endpoint is scraped, not on boot, so an
     * idle application never touches the database for metrics.
     */
    public function register(): void
    {
        $this->registerApplicationMetrics();
        $this->registerUserMetrics();
        $this->registerInvitationMetrics();
        $this->registerSessionMetrics();
    }

    /**
     * Application state gauges. The maintenance gauge distinguishes a
     * planned `artisan down` from an outage in alerting; the metrics
     * endpoint is exempted from the maintenance mode in bootstrap/app.php
     * so it stays scrappable while the rest of the site returns 503.
     */
    protected function registerApplicationMetrics(): void
    {
        Prometheus::addGauge('Maintenance mode', fn (): int => $this->app->isDownForMaintenance() ? 1 : 0)
            ->helpText('1 when the application is in maintenance mode (artisan down), 0 otherwise');
    }

    /**
     * Membership gauges. Every count is SQL-only over plain columns or
     * blind indexes, except the internal/external split: those flags are
     * encrypted at rest (no blind index), so a single pass decrypts them
     * and the result is cached to amortize the cost between scrapes.
     */
    protected function registerUserMetrics(): void
    {
        $membershipCounts = fn (): array => Cache::remember(
            'prometheus.membership_counts',
            now()->addMinutes(5),
            fn (): array => [
                'internal' => User::query()->get(['is_internal', 'is_external'])->where('is_internal', true)->count(),
                'external' => User::query()->get(['is_internal', 'is_external'])->where('is_external', true)->count(),
            ],
        );

        Prometheus::addGauge('Users total', fn () => User::count())
            ->helpText('Number of members, soft-deleted accounts excluded');

        Prometheus::addGauge('Users verified', fn () => User::query()->whereNotNull('email_verified_at')->count())
            ->helpText('Number of members with a verified email address');

        Prometheus::addGauge('Users internal', fn (): int => $membershipCounts()['internal'])
            ->helpText('Number of internal members (school email domain registration)');

        Prometheus::addGauge('Users external', fn (): int => $membershipCounts()['external'])
            ->helpText('Number of external members (invitation-based registration)');

        Prometheus::addGauge('Users discord linked', fn () => User::query()->whereNotNull('discord_id_hash')->count())
            ->helpText('Number of members with a linked Discord account');

        Prometheus::addGauge('Users hytale linked', fn () => User::query()->whereNotNull('hytale_id_hash')->count())
            ->helpText('Number of members with a linked, verified Hytale account');

        Prometheus::addGauge('Users 2fa enabled', fn () => User::query()->whereNotNull('two_factor_confirmed_at')->count())
            ->helpText('Number of members with confirmed two-factor authentication');
    }

    /**
     * Invitation gauges. Timestamps are stored in plain columns, so both
     * counts are plain SQL. The pending scope includes expired invitations
     * that were never consumed nor revoked.
     */
    protected function registerInvitationMetrics(): void
    {
        Prometheus::addGauge('Invitations pending', fn () => Invitation::pending()->count())
            ->helpText('Invitations neither accepted nor revoked, including expired ones');

        Prometheus::addGauge('Invitations accepted', fn () => Invitation::query()->whereNotNull('accepted_at')->count())
            ->helpText('Invitations that registered an account');
    }

    /**
     * Online-activity gauge, only meaningful when sessions live in the
     * database. A session counts as active when it was seen in the last
     * five minutes.
     */
    protected function registerSessionMetrics(): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        Prometheus::addGauge('Sessions active', fn (): int => DB::table('sessions')
            ->where('last_activity', '>', now()->subMinutes(5)->getTimestamp())
            ->count())
            ->helpText('Sessions seen in the last five minutes');
    }
}
