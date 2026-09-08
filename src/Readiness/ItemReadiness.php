<?php

declare(strict_types=1);

namespace CertPath\Readiness;

/**
 * How far an atomic official item has been refined.
 *
 * Coverage answers "is the item covered?". This answers a different question:
 * "has the content been refined to the point where a candidate could answer a
 * question on it they have never seen?" 100% coverage is not 100% readiness,
 * and the two must never be reported as if they were the same number.
 */
enum ItemReadiness: string
{
    /** Fails at least one criterion its content level requires. */
    case NotRefined = 'NOT_REFINED';

    /** Meets every automated criterion, but its lot has had no refinement audit. */
    case PartiallyRefined = 'PARTIALLY_REFINED';

    /** Meets every automated criterion and sits in a lot whose refinement audit is recorded. */
    case Refined = 'REFINED';

    /**
     * Refined, and assessable at the top of what its level requires: exam-mode
     * evidence, a diagnosis question and a hard question.
     */
    case MasteredReady = 'MASTERED_READY';

    /** Only these two count toward Certification Readiness. */
    public function countsTowardReadiness(): bool
    {
        return self::Refined === $this || self::MasteredReady === $this;
    }
}
