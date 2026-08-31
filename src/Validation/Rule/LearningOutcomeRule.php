<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\ContentLevel;
use CertPath\Domain\ItemStatus;
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
            // An item that has only been imported or researched legitimately
            // has no outcomes yet; the requirement begins at SPECIFIED (§3.4).
            if (!$item->status->isAtLeast(ItemStatus::Specified)) {
                continue;
            }

            if (null === $item->contentLevel) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Item is SPECIFIED or beyond but declares no content level.',
                    $item->id->value,
                );
            }

            if ([] === $item->learningOutcomes) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Official item has no learning outcome.',
                    $item->id->value,
                );
            }

            $justification = trim((string) $item->contentLevelJustification);
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
