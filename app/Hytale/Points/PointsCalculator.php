<?php

namespace App\Hytale\Points;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Computes "Points Open" from a member's play sessions.
 *
 * Rules:
 *  - a member earns `pointsPerReward` once they reach `hoursThreshold` hours
 *    of play within a calendar week;
 *  - hours below the threshold carry over to the following weeks;
 *  - at most one reward per week: once rewarded, the counter resets and the
 *    member must wait for that week to end before earning again;
 *  - play sessions can award at most `maxSessionPoints` points overall, while
 *    the global balance stays open to other sources.
 *
 * Only sessions with a start date are considered. An open session (no
 * `ended_at`) is counted up to `$now`.
 */
final class PointsCalculator
{
    public function __construct(
        private readonly float $hoursThreshold = 2.0,
        private readonly float $pointsPerReward = 0.5,
        private readonly int $weekStartsOn = CarbonImmutable::MONDAY,
        private readonly float $maxSessionPoints = 2.0,
    ) {}

    /**
     * @param  array<int, array{hytale_server_id?: mixed, joined_at?: mixed, ended_at?: mixed}>  $sessions
     */
    public function compute(array $sessions, ?CarbonImmutable $now = null): PointsResult
    {
        $now ??= CarbonImmutable::now();

        $secondsPerWeek = $this->secondsPerWeek($sessions, $now);

        $currentWeekStart = $now->startOfWeek($this->weekStartsOn);

        $points = 0.0;
        $carry = 0.0;
        $timeline = [];
        $lastRewardedWeekEnd = null;

        $firstWeekStart = $this->firstWeekStart($secondsPerWeek);

        if ($firstWeekStart !== null) {
            for (
                $weekStart = $firstWeekStart;
                $weekStart->lessThanOrEqualTo($currentWeekStart);
                $weekStart = $weekStart->addWeek()
            ) {
                $key = $weekStart->toIso8601ZuluString();
                $weekHours = ($secondsPerWeek[$key] ?? 0) / 3600;
                $weekEnd = $this->endOfWeek($weekStart);

                $carryIn = $carry;
                $available = $carryIn + $weekHours;

                $rewarded = false;

                if ($this->isSessionCapReached($points)) {
                    // Playing can no longer award points: stop accumulating.
                    $carry = 0.0;
                } elseif ($available >= $this->hoursThreshold) {
                    $rewarded = true;
                    $points = $this->capEnabled()
                        ? min($this->maxSessionPoints, $points + $this->pointsPerReward)
                        : $points + $this->pointsPerReward;
                    $carry = 0.0;
                    $lastRewardedWeekEnd = $weekEnd;
                } else {
                    $carry = $available;
                }

                $timeline[] = new PointsWeek(
                    start: $weekStart,
                    end: $weekEnd,
                    hours: $weekHours,
                    rewarded: $rewarded,
                    carryIn: $carryIn,
                );
            }
        }

        $capped = $this->isSessionCapReached($points);
        $hoursSinceLastReward = $capped ? 0.0 : $carry;

        $nextRewardAt = null;

        if (! $capped && $lastRewardedWeekEnd !== null) {
            $unlockAt = $lastRewardedWeekEnd->addSecond();

            $nextRewardAt = $unlockAt->greaterThan($now)
                ? $unlockAt->toIso8601ZuluString()
                : null;
        }

        return new PointsResult(
            points: $points,
            hoursTotal: array_sum($secondsPerWeek) / 3600,
            hoursSinceLastReward: $hoursSinceLastReward,
            hoursToNextPoint: $capped
                ? 0.0
                : max(0.0, $this->hoursThreshold - $hoursSinceLastReward),
            nextRewardAt: $nextRewardAt,
            weeks: $timeline,
            maxSessionPoints: $this->maxSessionPoints,
            sessionPointsCapped: $capped,
        );
    }

    private function capEnabled(): bool
    {
        return $this->maxSessionPoints > 0.0;
    }

    private function isSessionCapReached(float $points): bool
    {
        return $this->capEnabled() && $points >= $this->maxSessionPoints;
    }

    /**
     * Split sessions across calendar weeks.
     *
     * @param  array<int, array{hytale_server_id?: mixed, joined_at?: mixed, ended_at?: mixed}>  $sessions
     * @return array<string, int> start-of-week key => seconds
     */
    private function secondsPerWeek(array $sessions, CarbonImmutable $now): array
    {
        $result = [];

        foreach ($sessions as $session) {
            $start = $this->toDate($session['joined_at'] ?? null);

            if ($start === null) {
                continue;
            }

            $end = $this->toDate($session['ended_at'] ?? null) ?? $now;

            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            $cursor = $start;

            while ($cursor->lessThan($end)) {
                $weekStart = $cursor->startOfWeek($this->weekStartsOn);
                $nextWeekStart = $weekStart->addWeek();
                $sliceEnd = $nextWeekStart->lessThan($end) ? $nextWeekStart : $end;

                $key = $weekStart->toIso8601ZuluString();
                $result[$key] = ($result[$key] ?? 0) + (int) $cursor->diffInSeconds($sliceEnd);

                $cursor = $sliceEnd;
            }
        }

        ksort($result);

        return $result;
    }

    /**
     * End of the calendar week containing `$date` (last second before the next
     * week starts), derived from the start weekday to avoid Carbon's
     * `endOfWeek()` fractional-second artifacts.
     */
    private function endOfWeek(CarbonImmutable $date): CarbonImmutable
    {
        return $date->startOfWeek($this->weekStartsOn)->addWeek()->subSecond();
    }

    /**
     * @param  array<string, int>  $secondsPerWeek
     */
    private function firstWeekStart(array $secondsPerWeek): ?CarbonImmutable
    {
        $firstKey = array_key_first($secondsPerWeek);

        return $firstKey === null
            ? null
            : CarbonImmutable::parse($firstKey);
    }

    private function toDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
