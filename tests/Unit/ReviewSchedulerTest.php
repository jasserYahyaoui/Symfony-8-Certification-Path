<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Review\ReviewOutcome;
use CertPath\Review\ReviewScheduler;
use CertPath\Review\ReviewState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Master Plan §6 requires the review algorithm to be specified and tested in
 * Lot 0. These tests pin the exact behaviour documented in
 * docs/policy/review-algorithm.md.
 */
#[CoversClass(ReviewScheduler::class)]
#[CoversClass(ReviewState::class)]
final class ReviewSchedulerTest extends TestCase
{
    private ReviewScheduler $scheduler;

    protected function setUp(): void
    {
        $this->scheduler = new ReviewScheduler();
    }

    public function testFirstGoodReviewSchedulesOneDay(): void
    {
        $next = $this->scheduler->next(ReviewState::fresh(), ReviewOutcome::Good);

        self::assertSame(1, $next->intervalDays);
        self::assertSame(1, $next->repetitions);
        self::assertFalse($next->dueInSession);
    }

    public function testSecondGoodReviewSchedulesThreeDays(): void
    {
        $state = $this->scheduler->next(ReviewState::fresh(), ReviewOutcome::Good);
        $next = $this->scheduler->next($state, ReviewOutcome::Good);

        self::assertSame(3, $next->intervalDays);
        self::assertSame(2, $next->repetitions);
    }

    public function testThirdGoodReviewMultipliesByEase(): void
    {
        $state = new ReviewState(intervalDays: 3, ease: 2.5, repetitions: 2);
        $next = $this->scheduler->next($state, ReviewOutcome::Good);

        self::assertSame(8, $next->intervalDays, '3 * 2.5 = 7.5, rounded to 8');
    }

    /**
     * §6: "AGAIN: repeat in current session, then J1".
     */
    public function testAgainRepeatsInSessionAndResetsToOneDay(): void
    {
        $state = new ReviewState(intervalDays: 21, ease: 2.5, repetitions: 5);
        $next = $this->scheduler->next($state, ReviewOutcome::Again);

        self::assertTrue($next->dueInSession);
        self::assertSame(1, $next->intervalDays);
        self::assertSame(0, $next->repetitions);
        self::assertSame(1, $next->lapses);
        self::assertSame(2.3, $next->ease);
    }

    public function testHardGrowsMoreSlowlyThanGoodAndGoodMoreSlowlyThanEasy(): void
    {
        $state = new ReviewState(intervalDays: 10, ease: 2.5, repetitions: 3);

        $hard = $this->scheduler->next($state, ReviewOutcome::Hard)->intervalDays;
        $good = $this->scheduler->next($state, ReviewOutcome::Good)->intervalDays;
        $easy = $this->scheduler->next($state, ReviewOutcome::Easy)->intervalDays;

        self::assertLessThan($good, $hard);
        self::assertLessThan($easy, $good);
    }

    public function testIntervalAlwaysGrowsOnSuccess(): void
    {
        $state = new ReviewState(intervalDays: 1, ease: 1.3, repetitions: 4);
        $next = $this->scheduler->next($state, ReviewOutcome::Hard);

        self::assertGreaterThan($state->intervalDays, $next->intervalDays);
    }

    public function testEaseIsClampedToTheDocumentedBounds(): void
    {
        $low = new ReviewState(intervalDays: 5, ease: ReviewScheduler::MIN_EASE, repetitions: 3);
        self::assertSame(ReviewScheduler::MIN_EASE, $this->scheduler->next($low, ReviewOutcome::Hard)->ease);

        $high = new ReviewState(intervalDays: 5, ease: ReviewScheduler::MAX_EASE, repetitions: 3);
        self::assertSame(ReviewScheduler::MAX_EASE, $this->scheduler->next($high, ReviewOutcome::Easy)->ease);
    }

    public function testIntervalIsCappedAtTheHorizon(): void
    {
        $state = new ReviewState(intervalDays: 170, ease: 3.0, repetitions: 9);
        $next = $this->scheduler->next($state, ReviewOutcome::Easy);

        self::assertSame(ReviewScheduler::MAX_INTERVAL_DAYS, $next->intervalDays);
    }

    public function testSchedulerIsDeterministic(): void
    {
        $state = new ReviewState(intervalDays: 7, ease: 2.35, repetitions: 3);

        $first = $this->scheduler->next($state, ReviewOutcome::Good);
        $second = $this->scheduler->next($state, ReviewOutcome::Good);

        self::assertEquals($first, $second);
    }
}
