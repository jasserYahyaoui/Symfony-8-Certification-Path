<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\ContentLevel;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "official item without learning outcome" and
 *      "missing content_level or justification".
 */
final class LearningOutcomeRule implements Rule
{
    /** A DEEP level is the one §4.1 says must never be the default. */
    private const int DEEP_JUSTIFICATION_MIN_LENGTH = 40;

    public function id(): string
    {
        return 'PED-001';
    }

    public function description(): string
    {
        return 'Every official item declares learning outcomes and a justified content level.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->matrix->officialItems() as $item) {
            if ([] === $item->learningOutcomes) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Official item has no learning outcome.',
                    $item->id->value,
                );
            }

            $justification = trim($item->contentLevelJustification);
            if ('' === $justification) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Content level has no justification.',
                    $item->id->value,
                );
            } elseif (
                ContentLevel::Deep === $item->contentLevel
                && \strlen($justification) < self::DEEP_JUSTIFICATION_MIN_LENGTH
            ) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'DEEP content level requires a substantive justification (§4.1: DEEP must never be the default).',
                    $item->id->value,
                );
            }
        }

        return $violations;
    }
}
