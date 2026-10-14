<?php

use App\Models\User;
use Illuminate\Testing\TestResponse;

function prometheusMetricNames(TestResponse $response): array
{
    $names = [];

    foreach (preg_split('/\r\n|\r|\n/', $response->getContent()) as $line) {
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (preg_match('/^([a-zA-Z_:][a-zA-Z0-9_:]*)(\{[^}]*\})?[ \t]/', $line, $matches)) {
            $names[] = $matches[1];
        }
    }

    return array_values(array_unique($names));
}

function prometheusMetricValue(TestResponse $response, string $metric): string
{
    preg_match('/^'.preg_quote($metric, '/').'(?:\{[^}]*\})?[ \t]+([^ \t]+)$/m', $response->getContent(), $matches);

    expect($matches)->not->toBeEmpty();

    return $matches[1];
}

it('serves the prometheus metrics endpoint without authentication', function () {
    $this->get(route('prometheus.default'))->assertOk();
});

it('exposes exactly the application metrics in the prometheus text exposition format', function () {
    $response = $this->get(route('prometheus.default'));

    $expected = [
        'app_users_total',
        'app_users_verified',
        'app_users_internal',
        'app_users_external',
        'app_users_discord_linked',
        'app_users_hytale_linked',
        'app_users_2fa_enabled',
        'app_invitations_pending',
        'app_invitations_accepted',
    ];

    $actual = prometheusMetricNames($response);

    sort($expected);
    sort($actual);

    expect($actual)->toBe($expected);
});

it('computes the user metrics from the database at scrape time', function () {
    User::factory()->create();

    $response = $this->get(route('prometheus.default'));

    expect((float) prometheusMetricValue($response, 'app_users_total'))->toBe(1.0);
});

it('omits app_sessions_active when the session driver is not the database', function () {
    config(['session.driver' => 'array']);

    $response = $this->get(route('prometheus.default'));

    expect(prometheusMetricNames($response))->not->toContain('app_sessions_active');
});

it('rejects requests from IPs that are not on the allowlist', function () {
    config(['prometheus.allowed_ips' => ['203.0.113.10']]);

    $this->get(route('prometheus.default'))->assertForbidden();
});

it('allows requests from IPs that are on the allowlist', function () {
    config(['prometheus.allowed_ips' => ['127.0.0.1']]);

    $this->get(route('prometheus.default'))->assertOk();
});

it('allows all IPs when the allowlist is empty', function () {
    config(['prometheus.allowed_ips' => []]);

    $this->get(route('prometheus.default'))->assertOk();
});
