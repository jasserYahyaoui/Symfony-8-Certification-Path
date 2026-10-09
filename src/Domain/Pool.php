<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * Master Plan §7.3. HOLDOUT must never reach Practice Mode.
 */
enum Pool: string
{
    case Learning = 'LEARNING';
    case Validation = 'VALIDATION';
    case Holdout = 'HOLDOUT';

    /**
     * ADR-0010: the end-of-lot comprehension check. Its questions live in
     * `content/comprehension/`, are loaded into their own field of the content
     * set, and are served by one payload only — never by Practice Mode, Exam
     * Mode or a mock, and never counted as readiness evidence.
     */
    case Comprehension = 'COMPREHENSION';

    /**
     * §7.3 / §9.1: only the learning pool may be exposed in Practice Mode.
     */
    public function isExposedInPracticeMode(): bool
    {
        return self::Learning === $this;
    }
}
