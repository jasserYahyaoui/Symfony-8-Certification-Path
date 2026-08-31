<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * Master Plan §4.1. DEEP must never be the default.
 */
enum ContentLevel: string
{
    case Minimal = 'MINIMAL';
    case Standard = 'STANDARD';
    case Deep = 'DEEP';

    /**
     * Expected capabilities per §4.1.
     *
     * @return list<string>
     */
    public function expectedCapabilities(): array
    {
        return match ($this) {
            self::Minimal => ['KNOW', 'RECOGNIZE'],
            self::Standard => ['KNOW', 'RECOGNIZE', 'DISTINGUISH', 'APPLY'],
            self::Deep => ['KNOW', 'RECOGNIZE', 'DISTINGUISH', 'APPLY', 'DIAGNOSE'],
        };
    }

    /**
     * §4.1 requires an explicit justification for every level; DEEP is the
     * one that must survive the strictest challenge.
     */
    public function requiresStrongJustification(): bool
    {
        return self::Deep === $this;
    }
}
