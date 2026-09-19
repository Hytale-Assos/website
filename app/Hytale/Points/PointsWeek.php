<?php

namespace App\Hytale\Points;

use Carbon\CarbonImmutable;

/**
 * One calendar week in the points timeline.
 */
final readonly class PointsWeek
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public float $hours,
        public bool $rewarded,
        public float $carryIn,
    ) {}
}
