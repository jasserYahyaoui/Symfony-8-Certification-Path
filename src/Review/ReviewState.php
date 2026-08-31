<?php

declare(strict_types=1);

namespace CertPath\Review;

/**
 * The scheduling state of one flashcard for one learner.
 */
final readonly class ReviewState
{
    public function __construct(
        public int $intervalDays,
        public float $ease,
        public int $repetitions,
        public int $lapses = 0,
        public bool $dueInSession = false,
    ) {
    }

    public static function fresh(): self
    {
        return new self(intervalDays: 0, ease: ReviewScheduler::INITIAL_EASE, repetitions: 0);
    }
}
