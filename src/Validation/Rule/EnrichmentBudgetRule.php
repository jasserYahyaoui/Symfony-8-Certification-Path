<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\Classification;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "unexplained enrichment excess"; §1.2 caps enrichment below 10% of
 * learning content.
 */
final class EnrichmentBudgetRule implements Rule
{
    private const float MAX_ENRICHMENT_RATIO = 0.10;

    public function id(): string
    {
        return 'SCOPE-002';
    }

    public function description(): string
    {
        return 'Enrichment stays below 10% of learning content (§1.2).';
    }

    public function check(ContentSet $content): array
    {
        $total = \count($content->matrix->items);
        if (0 === $total) {
            return [];
        }

        $enrichment = 0;
        foreach ($content->matrix->items as $item) {
            if (Classification::Enrichment === $item->classification) {
                ++$enrichment;
            }
        }

        $ratio = $enrichment / $total;
        if ($ratio <= self::MAX_ENRICHMENT_RATIO) {
            return [];
        }

        return [new Violation(
            $this->id(),
            Severity::Error,
            \sprintf(
                'Enrichment is %.1f%% of learning content, above the %.0f%% ceiling (§1.2).',
                $ratio * 100,
                self::MAX_ENRICHMENT_RATIO * 100,
            ),
        )];
    }
}
