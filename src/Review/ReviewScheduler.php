<?php

declare(strict_types=1);

namespace CertPath\Review;

/**
 * Deterministic flashcard scheduler.
 *
 * Master Plan §6 requires the exact algorithm to be specified and tested in
 * Lot 0, and explicitly forbids claiming "scientifically optimized spaced
 * repetition without evidence". This is therefore a plain, auditable interval
 * schedule with no efficacy claim attached — see docs/policy/review-algorithm.md.
 *
 * The algorithm is pure: the same state and outcome always produce the same
 * next state, which is what makes it testable and portable to the browser.
 */
final class ReviewScheduler
{
    public const float INITIAL_EASE = 2.5;
    public const float MIN_EASE = 1.3;
    public const float MAX_EASE = 3.0;

    /** First two successful intervals are fixed, in days. */
    private const int FIRST_INTERVAL = 1;
    private const int SECOND_INTERVAL = 3;

    /** Never schedule beyond this horizon: the exam is a fixed-date event. */
    public const int MAX_INTERVAL_DAYS = 180;

    public function next(ReviewState $state, ReviewOutcome $outcome): ReviewState
    {
        return match ($outcome) {
            ReviewOutcome::Again => $this->lapse($state),
            ReviewOutcome::Hard => $this->advance($state, easeDelta: -0.15, multiplier: 1.2),
            ReviewOutcome::Good => $this->advance($state, easeDelta: 0.0, multiplier: $state->ease),
            ReviewOutcome::Easy => $this->advance($state, easeDelta: 0.15, multiplier: $state->ease * 1.3),
        };
    }

    /**
     * §6: "AGAIN: repeat in current session, then J1".
     */
    private function lapse(ReviewState $state): ReviewState
    {
        return new ReviewState(
            intervalDays: self::FIRST_INTERVAL,
            ease: $this->clampEase($state->ease - 0.20),
            repetitions: 0,
            lapses: $state->lapses + 1,
            dueInSession: true,
        );
    }

    private function advance(ReviewState $state, float $easeDelta, float $multiplier): ReviewState
    {
        $repetitions = $state->repetitions + 1;

        $interval = match ($repetitions) {
            1 => self::FIRST_INTERVAL,
            2 => self::SECOND_INTERVAL,
            default => (int) max(
                $state->intervalDays + 1,
                (int) round($state->intervalDays * $multiplier),
            ),
        };

        return new ReviewState(
            intervalDays: min($interval, self::MAX_INTERVAL_DAYS),
            ease: $this->clampEase($state->ease + $easeDelta),
            repetitions: $repetitions,
            lapses: $state->lapses,
            dueInSession: false,
        );
    }

    private function clampEase(float $ease): float
    {
        return round(max(self::MIN_EASE, min(self::MAX_EASE, $ease)), 2);
    }
}
