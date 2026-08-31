<?php

declare(strict_types=1);

namespace CertPath\Domain;

use CertPath\Support\Id;

final readonly class Choice
{
    public function __construct(
        public Id $id,
        public string $text,
        public bool $correct,
        public ?string $explanation = null,
    ) {
    }
}
