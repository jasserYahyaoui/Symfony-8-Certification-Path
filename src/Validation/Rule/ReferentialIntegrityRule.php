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
        return 'Prerequisites and question references resolve, each reference belongs to the item '
            .'that claims it, and no question is orphaned.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        $questionIds = [];
        foreach ($content->questions as $question) {
            $questionIds[$question->id->value] = true;
        }

        $courseIds = [];
        foreach ($content->courses as $course) {
            $courseIds[$course->id->value] = true;
        }

        $flashcardIds = [];
        foreach ($content->flashcards as $card) {
            $flashcardIds[$card->id->value] = true;
        }

        // A reference names content that belongs to the referencing item. Two items
        // claiming the same course, flashcard or question is a splice accident, and
        // it stayed invisible until Lot 25 because the wrongly credited items were
        // NOT_STARTED, which no readiness rule inspects.
        $ownerOf = [];
        foreach ($content->courses as $course) {
            $ownerOf[$course->id->value] = $course->officialItemId;
        }
        foreach ($content->flashcards as $card) {
            $ownerOf[$card->id->value] = $card->officialItemId;
        }
        foreach ($content->questions as $question) {
            $ownerOf[$question->id->value] = $question->officialItemId;
        }

        $claimedBy = [];

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

            foreach ($item->courseRefs as $ref) {
                if (!isset($courseIds[$ref])) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf('Referenced course "%s" does not exist.', $ref),
                        $item->id->value,
                    );
                }
            }

            foreach ($item->flashcardRefs as $ref) {
                if (!isset($flashcardIds[$ref])) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf('Referenced flashcard "%s" does not exist.', $ref),
                        $item->id->value,
                    );
                }
            }

            $refs = [...$item->courseRefs, ...$item->flashcardRefs, ...$item->questionRefs];

            foreach ($refs as $ref) {
                if (isset($claimedBy[$ref])) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf(
                            'Reference "%s" is also claimed by item "%s"; content belongs to one item.',
                            $ref,
                            $claimedBy[$ref],
                        ),
                        $item->id->value,
                    );
                }
                $claimedBy[$ref] = $item->id->value;

                if (isset($ownerOf[$ref]) && $ownerOf[$ref] !== $item->id->value) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf(
                            'Referenced "%s" declares item "%s" as its own, not this one.',
                            $ref,
                            $ownerOf[$ref],
                        ),
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
