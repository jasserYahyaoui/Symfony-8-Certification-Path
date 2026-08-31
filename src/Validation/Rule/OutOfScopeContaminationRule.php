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
            }

            foreach ($content->excludedTerms as $term) {
                $needle = mb_strtolower(trim($term));
                if ('' === $needle) {
                    continue;
                }

                if (str_contains($haystack, $needle)) {
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
}
