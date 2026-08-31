<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * Master Plan §5. The official exam is in English.
 */
enum Language: string
{
    case French = 'fr';
    case English = 'en';
}
