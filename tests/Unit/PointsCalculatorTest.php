<?php

use App\Hytale\Points\PointsCalculator;
use Carbon\CarbonImmutable;

function calculator(float $maxSessionPoints = 2.0): PointsCalculator
{
    return new PointsCalculator(
        hoursThreshold: 2.0,
        pointsPerReward: 0.5,
        weekStartsOn: CarbonImmutable::MONDAY,
        maxSessionPoints: $maxSessionPoints,
    );
}

function play(string $joinedAt, ?string $endedAt = null): array
{
    return [
        'hytale_server_id' => 'server-1',
        'joined_at' => $joinedAt,
        'ended_at' => $endedAt,
    ];
}

it('awards nothing below the threshold', function () {
    $now = CarbonImmutable::parse('2026-09-16T12:00:00Z'); // Wednesday

    $result = calculator()->compute([play('2026-09-15T10:00:00Z', '2026-09-15T11:00:00Z')], $now);

    expect($result->points)->toBe(0.0)
        ->and($result->hoursTotal)->toBe(1.0)
        ->and($result->hoursToNextPoint)->toBe(1.0);
});

it('awards 0.5 point at 2 hours in the same week', function () {
    $now = CarbonImmutable::parse('2026-09-16T12:00:00Z');

    $result = calculator()->compute([play('2026-09-15T10:00:00Z', '2026-09-15T12:00:00Z')], $now);

    expect($result->points)->toBe(0.5)
        ->and($result->hoursToNextPoint)->toBe(2.0);
});

it('caps at one reward per week', function () {
    $now = CarbonImmutable::parse('2026-09-16T12:00:00Z');

    $result = calculator()->compute([play('2026-09-15T08:00:00Z', '2026-09-15T14:00:00Z')], $now);

    expect($result->points)->toBe(0.5);
});

it('carries non rewarded hours to the next week', function () {
    // Week 1: 1h (not enough). Week 2: 1h more → reaches 2h → 0.5 point.
    $now = CarbonImmutable::parse('2026-09-23T12:00:00Z'); // Wednesday week 2

    $result = calculator()->compute([
        play('2026-09-15T10:00:00Z', '2026-09-15T11:00:00Z'), // Tue week 1
        play('2026-09-22T10:00:00Z', '2026-09-22T11:00:00Z'), // Tue week 2
    ], $now);

    expect($result->points)->toBe(0.5)
        ->and($result->weeks)->toHaveCount(2)
        ->and($result->weeks[0]->rewarded)->toBeFalse()
        ->and($result->weeks[1]->rewarded)->toBeTrue()
        ->and($result->weeks[1]->carryIn)->toBe(1.0);
});

it('matches the 2h over 3 weeks case', function () {
    // 2h total split across 3 weeks, 40 min each, never 2h within one week.
    $now = CarbonImmutable::parse('2026-09-30T12:00:00Z');

    $result = calculator()->compute([
        play('2026-09-08T10:00:00Z', '2026-09-08T10:40:00Z'),
        play('2026-09-15T10:00:00Z', '2026-09-15T10:40:00Z'),
        play('2026-09-22T10:00:00Z', '2026-09-22T10:40:00Z'),
    ], $now);

    expect($result->points)->toBe(0.5);
});

it('can earn again only once a new week starts after a reward', function () {
    // Week 1: 6h → 0.5. Week 2: 2h → 0.5. Total 1.0.
    $now = CarbonImmutable::parse('2026-09-23T12:00:00Z');

    $result = calculator()->compute([
        play('2026-09-15T06:00:00Z', '2026-09-15T12:00:00Z'),
        play('2026-09-22T06:00:00Z', '2026-09-22T08:00:00Z'),
    ], $now);

    expect($result->points)->toBe(1.0);
});

it('counts an open session up to now', function () {
    $now = CarbonImmutable::parse('2026-09-15T12:00:00Z');

    $result = calculator()->compute([play('2026-09-15T10:00:00Z', null)], $now);

    expect($result->points)->toBe(0.5)
        ->and($result->hoursTotal)->toBe(2.0);
});

it('exposes when the next reward unlocks', function () {
    $now = CarbonImmutable::parse('2026-09-16T12:00:00Z');

    $result = calculator()->compute([play('2026-09-15T08:00:00Z', '2026-09-15T12:00:00Z')], $now);

    expect($result->points)->toBe(0.5)
        ->and($result->nextRewardAt)->not->toBeNull();
});

it('caps session points at the configured maximum', function () {
    // 5 qualifying weeks (2h each) would give 2.5, but the play cap is 2.0.
    $now = CarbonImmutable::parse('2026-10-13T12:00:00Z');

    $sessions = [];

    foreach (['2026-09-15', '2026-09-22', '2026-09-29', '2026-10-06', '2026-10-13'] as $day) {
        $sessions[] = play($day.'T08:00:00Z', $day.'T10:00:00Z');
    }

    $result = calculator(2.0)->compute($sessions, $now);

    expect($result->points)->toBe(2.0)
        ->and($result->sessionPointsCapped)->toBeTrue();
});

it('exposes the cap metadata', function () {
    $now = CarbonImmutable::parse('2026-09-16T12:00:00Z');

    $result = calculator(2.0)->compute([
        play('2026-09-15T08:00:00Z', '2026-09-15T10:00:00Z'),
    ], $now);

    expect($result->maxSessionPoints)->toBe(2.0)
        ->and($result->sessionPointsCapped)->toBeFalse();
});

it('can disable the cap with a zero maximum', function () {
    $now = CarbonImmutable::parse('2026-10-13T12:00:00Z');

    $sessions = [
        play('2026-09-15T08:00:00Z', '2026-09-15T10:00:00Z'),
        play('2026-09-22T08:00:00Z', '2026-09-22T10:00:00Z'),
        play('2026-09-29T08:00:00Z', '2026-09-29T10:00:00Z'),
    ];

    $result = calculator(0.0)->compute($sessions, $now);

    expect($result->points)->toBe(1.5)
        ->and($result->sessionPointsCapped)->toBeFalse();
});

it('splits a session spanning two weeks', function () {
    // Sunday 22:00 → Monday 02:00 = 2h in week 1 + 2h in week 2 → 0.5 + 0.5.
    $now = CarbonImmutable::parse('2026-09-21T12:00:00Z'); // Monday week 2

    $result = calculator()->compute([
        play('2026-09-20T22:00:00Z', '2026-09-21T02:00:00Z'),
    ], $now);

    expect($result->points)->toBe(1.0)
        ->and($result->weeks)->toHaveCount(2)
        ->and($result->weeks[0]->rewarded)->toBeTrue()
        ->and($result->weeks[1]->rewarded)->toBeTrue();
});
