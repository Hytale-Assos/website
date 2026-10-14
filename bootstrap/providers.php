<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HytaleServiceProvider;
use App\Providers\PrometheusServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    HytaleServiceProvider::class,
    PrometheusServiceProvider::class,
];
