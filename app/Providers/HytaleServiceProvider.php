<?php

namespace App\Providers;

use App\Hytale\Clients\CachingHytaleApiClient;
use App\Hytale\Clients\FakeHytaleApiClient;
use App\Hytale\Clients\HttpHytaleApiClient;
use App\Hytale\Contracts\HytaleApiClient;
use App\Hytale\Points\PointsCalculator;
use App\Hytale\Support\ApiSigner;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class HytaleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->refuseMisconfiguredProduction();
    }

    /**
     * Refuse to boot for a non-console production request with a client
     * configuration that would serve fictional data: the in-memory mock
     * left enabled, or the real client with missing credentials (every
     * request would be signed with an empty secret). Console commands
     * (package discovery during builds, migrations, queue workers) are
     * intentionally allowed to boot so build-time tooling needs no
     * secrets — the same exemption as the passkeys guard in
     * FortifyServiceProvider.
     */
    private function refuseMisconfiguredProduction(): void
    {
        if (! $this->app->isProduction() || $this->app->runningInConsole()) {
            return;
        }

        $config = $this->app->make(ConfigRepository::class)->get('hytale');

        if ($config['mock']) {
            throw new RuntimeException(
                'HYTALE_API_MOCK must not be enabled in production: servers, play sessions and the whitelist would be fictional data. Set HYTALE_API_MOCK=false and provide the core credentials.'
            );
        }

        if (empty($config['api_key']) || empty($config['hmac_secret'])) {
            throw new RuntimeException(
                'HYTALE_API_KEY and HYTALE_API_HMAC_SECRET must be set in production when the mock is disabled.'
            );
        }
    }

    /**
     * Register the Hytale core API client.
     *
     * The binding is chosen from config so the rest of the application only
     * ever depends on {@see HytaleApiClient}:
     *  - `HYTALE_API_MOCK=true`  → in-memory fake, no network call;
     *  - otherwise               → signed HTTP client, optionally wrapped in a
     *                              Redis cache decorator.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/hytale.php', 'hytale');

        $this->app->singleton(HytaleApiClient::class, function ($app): HytaleApiClient {
            $config = $app['config']->get('hytale');

            $client = $config['mock']
                ? $this->makeFakeClient($app, $config)
                : $this->makeHttpClient($config);

            if (! $config['mock'] && $config['cache']['enabled']) {
                $client = new CachingHytaleApiClient(
                    client: $client,
                    cache: $app->make(CacheFactory::class)->store($config['cache']['store']),
                    ttl: $config['cache']['ttl'],
                    prefix: $config['cache']['prefix'],
                );
            }

            return $client;
        });

        $this->app->singleton(ApiSigner::class, function ($app): ApiSigner {
            $config = $app['config']->get('hytale');

            return new ApiSigner(
                apiKey: (string) $config['api_key'],
                hmacSecret: (string) $config['hmac_secret'],
            );
        });

        $this->app->singleton(PointsCalculator::class, function ($app): PointsCalculator {
            $config = $app['config']->get('hytale.points');

            return new PointsCalculator(
                hoursThreshold: (float) $config['hours_threshold'],
                pointsPerReward: (float) $config['points_per_reward'],
                weekStartsOn: (int) $config['week_starts_on'],
                maxSessionPoints: (float) $config['max_session_points'],
            );
        });
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function makeHttpClient(array $config): HttpHytaleApiClient
    {
        return new HttpHytaleApiClient(
            baseUrl: (string) $config['base_url'],
            signer: $this->app->make(ApiSigner::class),
            timeout: (int) $config['timeout'],
        );
    }

    /**
     * @param  Application  $app
     * @param  array<string, mixed>  $config
     */
    private function makeFakeClient($app, array $config): FakeHytaleApiClient
    {
        return new FakeHytaleApiClient(
            cache: $app->make(CacheFactory::class)->store(),
            moduleId: (string) $config['module_id'],
            moduleName: (string) $config['module_name'],
        );
    }
}
