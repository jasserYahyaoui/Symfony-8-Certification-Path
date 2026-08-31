<?php

declare(strict_types=1);

namespace CertPath\Validation;

interface Rule
{
    /** Stable identifier, e.g. `SYL-001`. Referenced by lot reports. */
    public function id(): string;

    public function description(): string;

    /**
     * @return list<Violation>
     */
    public function check(ContentSet $content): array;
}
