<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\Pool;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * ADR-0010: a comprehension question is not an exam question reworded.
 *
 * The owner required the comprehension checks to be « indépendants des examens
 * et qcm déjà en place ». Testing the same fact is unavoidable — both come from
 * the same course — so the rule compares the questions, not the facts: a prompt
 * identical to, or at least 60 % similar to, any question of any pool is
 * refused. DUP-001 needs 75 % on the prompt AND on the answer; independence is
 * a stronger promise than "not a duplicate", so the bar is lower and the
 * prompt alone decides.
 *
 * Prompts are normalized by DuplicateQuestionRule::normalize(), so both rules
 * compare the same text. The holdout is compared like every other pool,
 * automatically, and a match against it is reported without its id: the check
 * must not become a way of locating a holdout question.
 */
final class ComprehensionIndependenceRule implements Rule
{
    /** A percentage, as `similar_text()` reports it. */
    public const float PROMPT_SIMILARITY = 60.0;

    /**
     * Cheap gate before `similar_text()`, as in DUP-001: two prompts sharing
     * fewer than 40 % of the shorter one's words are skipped. Lower than
     * DUP-001's 50 % because the threshold it guards is lower too. Without it
     * the full corpus is some 500 000 cubic-time comparisons per run.
     */
    private const float TOKEN_GATE = 0.4;

    public function id(): string
    {
        return 'CMP-002';
    }

    public function description(): string
    {
        return 'No comprehension question repeats or rewords a question of the exam banks, holdout included.';
    }

    public function check(ContentSet $content): array
    {
        $existing = [];
        foreach ($content->questions as $question) {
            $prompt = DuplicateQuestionRule::normalize($question->question);
            if ('' !== $prompt) {
                $existing[] = [$question->id->value, Pool::Holdout === $question->pool, $prompt, self::tokens($prompt)];
            }
        }

        $violations = [];

        foreach ($content->comprehension as $question) {
            $prompt = DuplicateQuestionRule::normalize($question->question);
            if ('' === $prompt) {
                continue;
            }

            $tokens = self::tokens($prompt);

            foreach ($existing as [$id, $holdout, $other, $otherTokens]) {
                $smaller = min(\count($tokens), \count($otherTokens));
                if (0 === $smaller || \count(array_intersect_key($tokens, $otherTokens)) / $smaller < self::TOKEN_GATE) {
                    continue;
                }

                similar_text($prompt, $other, $percent);
                if ($percent < self::PROMPT_SIMILARITY) {
                    continue;
                }

                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf(
                        'Prompt %d%% similar to %s; a comprehension question must be written apart from the exam banks.',
                        (int) round($percent),
                        $holdout ? 'a HOLDOUT question (id withheld)' : \sprintf('question "%s"', $id),
                    ),
                    $question->id->value,
                );
                break;
            }
        }

        return $violations;
    }

    /**
     * @return array<string, true>
     */
    private static function tokens(string $normalized): array
    {
        return array_fill_keys(explode(' ', $normalized), true);
    }
}
