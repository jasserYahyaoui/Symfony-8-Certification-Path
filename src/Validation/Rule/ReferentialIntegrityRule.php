<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Support\Id;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "broken prerequisites and references", "orphaned content".
 */
final class ReferentialIntegrityRule implements Rule
{
    public function id(): string
    {
        return 'REF-001';
    }

    public function description(): string
    {
        return 'Prerequisites and question references resolve, and no question is orphaned.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        $questionIds = [];
        foreach ($content->questions as $question) {
            $questionIds[$question->id->value] = true;
        }

        $referencedQuestions = [];

        foreach ($content->matrix->items as $item) {
            foreach ($item->prerequisites as $prerequisite) {
                if (!Id::isValid($prerequisite)) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf('Prerequisite "%s" is not a persistent id.', $prerequisite),
                        $item->id->value,
                    );
                    continue;
                }

                if (null === $content->matrix->findById($prerequisite)) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf('Prerequisite "%s" does not resolve to a known item.', $prerequisite),
                        $item->id->value,
                    );
                }
            }

            if (\in_array($item->id->value, $item->prerequisites, true)) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Item declares itself as its own prerequisite.',
                    $item->id->value,
                );
            }

            foreach ($item->questionRefs as $ref) {
                $referencedQuestions[$ref] = true;
                if (!isset($questionIds[$ref])) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf('Referenced question "%s" does not exist.', $ref),
                        $item->id->value,
                    );
                }
            }
        }

        foreach ($content->questions as $question) {
            if (!isset($referencedQuestions[$question->id->value])
                && null === $content->matrix->findById($question->officialItemId)) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Warning,
                    'Question is orphaned: no matrix item references it and its mapping does not resolve.',
                    $question->id->value,
                );
            }
        }

        return $violations;
    }
}
