<?php

declare(strict_types=1);

namespace CertPath\Validation;

/**
 * Master Plan §12: "CI fails on mandatory-rule violations."
 */
enum Severity: string
{
    case Error = 'ERROR';
    case Warning = 'WARNING';

    public function failsBuild(): bool
    {
        return self::Error === $this;
    }
}
