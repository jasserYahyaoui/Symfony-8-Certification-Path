<?php

declare(strict_types=1);

namespace CertPath\Review;

/**
 * Master Plan §6 review outcomes.
 */
enum ReviewOutcome: string
{
    case Again = 'AGAIN';
    case Hard = 'HARD';
    case Good = 'GOOD';
    case Easy = 'EASY';
}
