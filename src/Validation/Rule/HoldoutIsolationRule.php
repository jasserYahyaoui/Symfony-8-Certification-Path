<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\Pool;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "holdout question exposed in Practice Mode"; §7.3 and §17 (holdout
 * leakage is a critical blocker).
 *
 * This rule guards the *data*. The build additionally guarantees that holdout
 * questions are never written into the Practice Mode payload at all, so a
 * client-side mistake cannot leak them (ADR-0001).
 */
final class HoldoutIsolationRule implements Rule
{
    public function id(): string
    {
        return 'POOL-001';
    }

    public function description(): string
    {
        return 'Holdout questions are never reachable from learning content.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        $holdoutIds = [];
        foreach ($content->questions as $question) {
            if (Pool::Holdout === $question->pool) {
                $holdoutIds[$question->id->value] = true;
            }
        }

        if ([] === $holdoutIds) {
            return [];
        }

        // A holdout question referenced from the matrix as ordinary practice
        // material would surface it in learning flows.
        foreach ($content->matrix->items as $item) {
            foreach ($item->questionRefs as $ref) {
                if (isset($holdoutIds[$ref])) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf('Holdout question "%s" is referenced as learning material.', $ref),
                        $item->id->value,
                    );
                }
            }
        }

        return $violations;
    }
}
