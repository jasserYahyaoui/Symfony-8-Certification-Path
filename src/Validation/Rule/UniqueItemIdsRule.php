<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "missing or duplicate syllabus IDs".
 */
final class UniqueItemIdsRule implements Rule
{
    public function id(): string
    {
        return 'SYL-001';
    }

    public function description(): string
    {
        return 'Every atomic official item carries a unique persistent id.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];
        $seen = [];

        foreach ($content->matrix->items as $item) {
            $value = $item->id->value;
            if (isset($seen[$value])) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Duplicate syllabus item id.',
                    $value,
                );
                continue;
            }
            $seen[$value] = true;
        }

        return $violations;
    }
}
