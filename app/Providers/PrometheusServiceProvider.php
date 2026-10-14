<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Register the application's Prometheus collectors. Metric values are
     * computed when the metrics endpoint is scraped, not on boot.
     */
    public function register(): void
    {
        //
    }
}
