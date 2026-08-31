<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * Master Plan §2.5. UNKNOWN_NEEDS_VERIFICATION must never appear in scored
 * content; QUARANTINED content is withheld until a contradiction is resolved.
 */
enum VerificationStatus: string
{
    case Unverified = 'UNVERIFIED';
    case UnknownNeedsVerification = 'UNKNOWN_NEEDS_VERIFICATION';
    case Quarantined = 'QUARANTINED';
    case Verified = 'VERIFIED';

    public function mayBeScored(): bool
    {
        return self::Verified === $this;
    }
}
