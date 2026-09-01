<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * Master Plan §6 and §12 ("duplicate or near-duplicate flashcards").
 *
 * The justification field is required because §6's real constraint is not
 * formatting but restraint: a card for a fact already retained through
 * application is pure revision cost.
 */
final class FlashcardIntegrityRule implements Rule
{
    public function id(): string
    {
        return 'FLC-001';
    }

    public function description(): string
    {
        return 'Flashcards map to a real item, justify their memorization value and are not duplicated.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];
        $seen = [];

        foreach ($content->flashcards as $card) {
            $subject = $card->id->value;

            if (null === $content->matrix->findById($card->officialItemId)) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf('Flashcard maps to unknown official item "%s".', $card->officialItemId),
                    $subject,
                );
            }

            if ([] === $card->officialSources) {
                $violations[] = new Violation($this->id(), Severity::Error, 'Flashcard cites no official source.', $subject);
            }

            if (!$card->verificationStatus->mayBeScored()) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf('Flashcard is %s; unverified content must not be taught (§2.5).', $card->verificationStatus->value),
                    $subject,
                );
            }

            $key = DuplicateQuestionRule::normalize($card->front);
            if ('' !== $key && isset($seen[$key])) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf('Near-duplicate of flashcard "%s".', $seen[$key]),
                    $subject,
                );
            }
            $seen[$key] = $subject;
        }

        return $violations;
    }
}
