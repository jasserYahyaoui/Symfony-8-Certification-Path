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
 * Two checks, because they fail differently.
 *
 * An identical normalized prompt is always a duplicate. That half was the whole
 * rule until 2026-09-03, and it only ever caught an exact repeat: two prompts
 * differing by a single word passed. Audit item P2.2 is what that cost — a
 * HOLDOUT question measured 0.88 similar to its VALIDATION counterpart, found
 * by a manual audit because no rule could see it.
 *
 * Near-duplication therefore also compares prompts by similarity. It does not
 * fire on similarity alone: this corpus deliberately reuses a stem across
 * different subjects — "provided by FrameworkBundle rather than by X" is asked
 * of HttpKernel and of Routing — and those reach 0.92 while testing different
 * facts. What separates them is the answer. Measured over the six pairs above
 * 0.75 prompt similarity in the bank at the time of writing, answer similarity
 * never exceeded 0.52, so a question only counts as a near-duplicate when its
 * prompt *and* its correct answers are both close.
 */
final class DuplicateQuestionRule implements Rule
{
    public function id(): string
    {
        return 'DUP-001';
    }

    public function description(): string
    {
        return 'No two questions share a normalized prompt, or a near-identical prompt and answer.';
    }

    /**
     * Both thresholds are percentages, and both must be met.
     */
    private const float PROMPT_SIMILARITY = 75.0;
    private const float ANSWER_SIMILARITY = 75.0;

    public function check(ContentSet $content): array
    {
        $violations = [];
        $seen = [];

        /** @var list<array{id: string, prompt: string, answers: string, tokens: array<string, true>}> $indexed */
        $indexed = [];

        foreach ($content->questions as $question) {
            $key = self::normalize($question->question);
            if ('' === $key) {
                continue;
            }

            if (isset($seen[$key])) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf('Duplicate of question "%s": the prompts are identical once normalized.', $seen[$key]),
                    $question->id->value,
                );
                continue;
            }

            $seen[$key] = $question->id->value;

            $answers = [];
            foreach ($question->choices as $choice) {
                if ($choice->correct) {
                    $answers[] = self::normalize($choice->text);
                }
            }
            sort($answers);

            $indexed[] = [
                'id' => $question->id->value,
                'prompt' => $key,
                'answers' => implode(' | ', $answers),
                'tokens' => array_fill_keys(explode(' ', $key), true),
            ];
        }

        $count = \count($indexed);
        for ($i = 0; $i < $count; ++$i) {
            for ($j = $i + 1; $j < $count; ++$j) {
                // Cheap gate first: two prompts sharing fewer than half their
                // words cannot reach the similarity threshold, and skipping them
                // keeps this quadratic pass fast on a bank of this size.
                if (self::tokenOverlap($indexed[$i]['tokens'], $indexed[$j]['tokens']) < 0.5) {
                    continue;
                }

                similar_text($indexed[$i]['prompt'], $indexed[$j]['prompt'], $promptPercent);
                if ($promptPercent < self::PROMPT_SIMILARITY) {
                    continue;
                }

                similar_text($indexed[$i]['answers'], $indexed[$j]['answers'], $answerPercent);
                if ($answerPercent < self::ANSWER_SIMILARITY) {
                    continue;
                }

                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf(
                        'Near-duplicate of question "%s": prompts %d%% similar and answers %d%% similar.',
                        $indexed[$i]['id'],
                        (int) round($promptPercent),
                        (int) round($answerPercent),
                    ),
                    $indexed[$j]['id'],
                );
            }
        }

        return $violations;
    }

    /**
     * @param array<string, true> $a
     * @param array<string, true> $b
     */
    private static function tokenOverlap(array $a, array $b): float
    {
        $smaller = min(\count($a), \count($b));
        if (0 === $smaller) {
            return 0.0;
        }

        return \count(array_intersect_key($a, $b)) / $smaller;
    }

    public static function normalize(string $text): string
    {
        $lowered = mb_strtolower(trim($text));
        $stripped = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $lowered) ?? $lowered;
        $collapsed = preg_replace('/\s+/u', ' ', $stripped) ?? $stripped;

        return trim($collapsed);
    }
}
