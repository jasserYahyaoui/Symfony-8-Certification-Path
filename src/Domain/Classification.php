<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * Master Plan §3.3 `classification`.
 */
enum Classification: string
{
    case Official = 'OFFICIAL';
    case Prerequisite = 'PREREQUISITE';
    case Enrichment = 'ENRICHMENT';
    case OutOfScope = 'OUT_OF_SCOPE';

    /**
     * Only OFFICIAL items form the coverage denominator (§3.5).
     */
    public function countsTowardCoverage(): bool
    {
        return self::Official === $this;
    }

    /**
     * §1.2: enrichment is excluded from scoring; out-of-scope may never be scored.
     */
    public function isScorable(): bool
    {
        return self::Official === $this || self::Prerequisite === $this;
    }
}
