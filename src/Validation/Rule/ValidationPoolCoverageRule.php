<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\ContentLevel;
use CertPath\Domain\Pool;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * ADR-0006: every item whose minimum evidence requires exam mode must have an
 * exam-mode question to sit.
 *
 * The matrix states, for each STANDARD and DEEP item, that the evidence is
 * "2 questions uniques minimum, reussies sur 2 sessions distinctes, dont une en
 * mode examen". Exam Mode serves the VALIDATION pool. An item at those levels
 * with no VALIDATION question therefore declares evidence that cannot be
 * produced — a claim the project has no way of meeting.
 *
 * MINIMAL items are exempt: their stated evidence does not require exam mode.
 */
final class ValidationPoolCoverageRule implements Rule
{
    public function id(): string
    {
        return 'POOL-002';
    }

    public function description(): string
    {
        return 'Every EXAM_READY item requiring exam-mode evidence has a VALIDATION question.';
    }

    public function check(ContentSet $content): array
    {
        $validationByItem = [];
        foreach ($content->questions as $question) {
            if (Pool::Validation === $question->pool) {
                $validationByItem[$question->officialItemId] = true;
            }
        }

        $violations = [];
        foreach ($content->matrix->officialItems() as $item) {
            if (!$item->examReady) {
                continue;
            }

            if (ContentLevel::Standard !== $item->contentLevel && ContentLevel::Deep !== $item->contentLevel) {
                continue;
            }

            if (!isset($validationByItem[$item->id->value])) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf(
                        'Item is %s and EXAM_READY but has no VALIDATION question, so its stated '
                        .'exam-mode evidence cannot be produced (ADR-0006).',
                        $item->contentLevel->value,
                    ),
                    $item->id->value,
                );
            }
        }

        return $violations;
    }
}
