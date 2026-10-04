<?php

namespace App\Hytale\Points;

/**
 * The result of evaluating a member's play time into points.
 */
final readonly class PointsResult
{
    /**
     * @param  array<int, PointsWeek>  $weeks
     */
    public function __construct(
        public float $points,
        public float $hoursTotal,
        public float $hoursSinceLastReward,
        public float $hoursToNextPoint,
        public ?string $nextRewardAt,
        public array $weeks = [],
        public float $maxSessionPoints = 2.0,
        public bool $sessionPointsCapped = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'points' => $this->points,
            'hoursTotal' => round($this->hoursTotal, 2),
            'hoursSinceLastReward' => round($this->hoursSinceLastReward, 2),
            'hoursToNextPoint' => round($this->hoursToNextPoint, 2),
            'nextRewardAt' => $this->nextRewardAt,
            'maxSessionPoints' => $this->maxSessionPoints,
            'sessionPointsCapped' => $this->sessionPointsCapped,
            'weeks' => array_map(
                static fn (PointsWeek $week): array => [
                    'start' => $week->start->toIso8601ZuluString(),
                    'end' => $week->end->toIso8601ZuluString(),
                    'hours' => round($week->hours, 2),
                    'rewarded' => $week->rewarded,
                    'carryIn' => round($week->carryIn, 2),
                ],
                $this->weeks,
            ),
        ];
    }
}
