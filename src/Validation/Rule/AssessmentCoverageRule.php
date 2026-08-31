<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\ItemStatus;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "required outcome without assessment" and
 *      "missing mandatory teaching evidence".
 *
 * Only enforced once an item claims to be implemented — an item still at
 * RESEARCHED is legitimately without content.
 */
final class AssessmentCoverageRule implements Rule
{
    public function id(): string
    {
        return 'PED-002';
    }

    public function description(): string
    {
        return 'Implemented official items have teaching content and an assessment.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->matrix->officialItems() as $item) {
            if (!$item->status->isAtLeast(ItemStatus::Implemented)) {
                continue;
            }

            if ([] === $item->courseRefs) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Item is IMPLEMENTED or beyond but has no teaching content.',
                    $item->id->value,
                );
            }

            if (!$item->hasAssessment()) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Item is IMPLEMENTED or beyond but has no assessment.',
                    $item->id->value,
                );
            }
        }

        return $violations;
    }
}
