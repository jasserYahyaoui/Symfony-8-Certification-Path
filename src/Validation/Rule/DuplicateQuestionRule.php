<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "duplicate or near-duplicate questions and flashcards"; §4.5.
 *
 * Near-duplication is detected on a normalized prompt so that punctuation and
 * whitespace differences do not hide a genuine repeat.
 */
final class DuplicateQuestionRule implements Rule
{
    public function id(): string
    {
        return 'DUP-001';
    }

    public function description(): string
    {
        return 'No two questions share a normalized prompt.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];
        $seen = [];

        foreach ($content->questions as $question) {
            $key = self::normalize($question->question);
            if ('' === $key) {
                continue;
            }

            if (isset($seen[$key])) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf('Near-duplicate of question "%s".', $seen[$key]),
                    $question->id->value,
                );
                continue;
            }

            $seen[$key] = $question->id->value;
        }

        return $violations;
    }

    public static function normalize(string $text): string
    {
        $lowered = mb_strtolower(trim($text));
        $stripped = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $lowered) ?? $lowered;
        $collapsed = preg_replace('/\s+/u', ' ', $stripped) ?? $stripped;

        return trim($collapsed);
    }
}
