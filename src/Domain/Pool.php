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
     * §7.3 / §9.1: only the learning pool may be exposed in Practice Mode.
     */
    public function isExposedInPracticeMode(): bool
    {
        return self::Learning === $this;
    }
}
