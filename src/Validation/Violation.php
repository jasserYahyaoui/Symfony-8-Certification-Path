<?php

declare(strict_types=1);

namespace CertPath\Validation;

final readonly class Violation
{
    public function __construct(
        public string $ruleId,
        public Severity $severity,
        public string $message,
        public ?string $subject = null,
    ) {
    }

    public function format(): string
    {
        return \sprintf(
            '[%s] %s: %s%s',
            $this->severity->value,
            $this->ruleId,
            $this->message,
            null !== $this->subject ? ' ('.$this->subject.')' : '',
        );
    }
}
