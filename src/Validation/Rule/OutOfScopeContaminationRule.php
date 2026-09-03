<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\Classification;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "question depending on OUT_OF_SCOPE content"; §1.5 prohibited expansion.
 *
 * §1.5 allows an excluded term to appear inside a clearly labelled explanation
 * of an exclusion, so a question may mention one only when it is explicitly
 * tagged `exclusion-note` — and never when points depend on it.
 *
 * Matching is word-boundary aware rather than a raw substring search. Short
 * exclusion terms are otherwise unusable: `esi` matched inside `SameSite`,
 * failing two legitimate cookie questions. Boundaries keep the rule sharp —
 * `ESI` as a word still trips it — without punishing words that merely contain
 * the letters.
 */
final class OutOfScopeContaminationRule implements Rule
{
    private const string EXCLUSION_NOTE_TAG = 'exclusion-note';

    public function id(): string
    {
        return 'SCOPE-001';
    }

    public function description(): string
    {
        return 'No scored content depends on out-of-scope knowledge.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->questions as $question) {
            $subject = $question->id->value;

            if (!$question->classification->isScorable()) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf('Scored question is classified %s.', $question->classification->value),
                    $subject,
                );
            }

            $item = $content->matrix->findById($question->officialItemId);
            if (null !== $item && Classification::OutOfScope === $item->classification) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Question maps to an OUT_OF_SCOPE official item.',
                    $subject,
                );
            }

            if (\in_array(self::EXCLUSION_NOTE_TAG, $question->tags, true)) {
                continue;
            }

            $haystack = mb_strtolower($question->question.' '.$question->explanation);
            foreach ($question->choices as $choice) {
                $haystack .= ' '.mb_strtolower($choice->text);
                // A distractor explanation is content the learner reads, and
                // §7.1 makes it a required field. Leaving it out of the
                // haystack let an excluded term sit in a scored question
                // without any rule being able to see it.
                $haystack .= ' '.mb_strtolower($choice->explanation ?? '');
            }

            foreach ($content->excludedTerms as $term) {
                $needle = mb_strtolower(trim($term));
                if ('' === $needle) {
                    continue;
                }

                if (self::mentionsTerm($haystack, $needle)) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf(
                            'Scored question references excluded topic "%s" without the `%s` tag (§1.5).',
                            $term,
                            self::EXCLUSION_NOTE_TAG,
                        ),
                        $subject,
                    );
                }
            }
        }

        return $violations;
    }

    /**
     * True when the term appears as a whole word (or whole phrase), rather
     * than merely as a run of characters inside a longer word.
     */
    public static function mentionsTerm(string $haystack, string $needle): bool
    {
        $pattern = '/(?<![\p{L}\p{N}])'.preg_quote($needle, '/').'(?![\p{L}\p{N}])/u';

        return 1 === preg_match($pattern, $haystack);
    }
}
