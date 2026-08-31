<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\VerificationStatus;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "question without official-item mapping", "invalid answers or answer
 * counts"; §7.2 quality rules.
 */
final class QuestionIntegrityRule implements Rule
{
    public function id(): string
    {
        return 'QST-001';
    }

    public function description(): string
    {
        return 'Questions map to a real official item and declare a consistent, explained answer set.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->questions as $question) {
            $subject = $question->id->value;

            if (null === $content->matrix->findById($question->officialItemId)) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf('Question maps to unknown official item "%s".', $question->officialItemId),
                    $subject,
                );
            }

            if ([] === $question->choices) {
                $violations[] = new Violation($this->id(), Severity::Error, 'Question has no choices.', $subject);
                continue;
            }

            if (0 === $question->correctAnswerCount()) {
                $violations[] = new Violation($this->id(), Severity::Error, 'Question has no correct answer.', $subject);
            }

            if (!$question->hasConsistentAnswerCount()) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf(
                        'Declared required_answer_count=%d and answer_mode=%s disagree with the %d correct choice(s).',
                        $question->requiredAnswerCount,
                        $question->answerMode->value,
                        $question->correctAnswerCount(),
                    ),
                    $subject,
                );
            }

            if (!$question->hasDistractorExplanations()) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'At least one distractor has no explanation (§7.2).',
                    $subject,
                );
            }

            if ('' === trim($question->explanation)) {
                $violations[] = new Violation($this->id(), Severity::Error, 'Question has no explanation.', $subject);
            }

            if ([] === $question->officialSources) {
                $violations[] = new Violation($this->id(), Severity::Error, 'Question cites no official source.', $subject);
            }

            if (VerificationStatus::UnknownNeedsVerification === $question->verificationStatus) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'UNKNOWN_NEEDS_VERIFICATION content must never be scored (§2.5).',
                    $subject,
                );
            }
        }

        return $violations;
    }
}
