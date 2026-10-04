<?php

namespace App\Providers;

use App\Hytale\Clients\CachingHytaleApiClient;
use App\Hytale\Clients\FakeHytaleApiClient;
use App\Hytale\Clients\HttpHytaleApiClient;
use App\Hytale\Contracts\HytaleApiClient;
use App\Hytale\Points\PointsCalculator;
use App\Hytale\Support\ApiSigner;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class HytaleServiceProvider extends ServiceProvider
{
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
