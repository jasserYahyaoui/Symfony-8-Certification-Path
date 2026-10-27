<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\Pool;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * ADR-0012: a course's own quiz is not a bank question shown with its answer.
 *
 * Master Plan §4.3 ends: « Course pages must not reveal interactive exam
 * answers. » A quiz printed in a course, its correction one click away, would
 * do exactly that if one of its prompts repeated a question of the exam banks —
 * Exam Mode's VALIDATION questions, or the HOLDOUT reserved for the final mock —
 * or of the comprehension checks, whose value is that they are met fresh.
 *
 * The comparison is CMP-002's: the same normalisation, the same 40 % word gate,
 * the same 60 % `similar_text()` threshold, on the prompt. A course quiz is
 * written in French and the banks mostly in English, so a translated copy would
 * pass; what this rule catches is the realistic slip — a prompt, or a code
 * snippet, pasted from a bank. A match against the holdout is reported without
 * its id, so the rule cannot be used to locate one.
 */
final class CourseQuizIndependenceRule implements Rule
{
    public const float PROMPT_SIMILARITY = ComprehensionIndependenceRule::PROMPT_SIMILARITY;

    private const float TOKEN_GATE = 0.4;

    public function id(): string
    {
        return 'QCM-001';
    }

    public function description(): string
    {
        return 'No question of a course quiz repeats or rewords a question of the exam banks, holdout included, or of the comprehension checks.';
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
        foreach ($content->comprehension as $question) {
            $prompt = DuplicateQuestionRule::normalize($question->question);
            if ('' !== $prompt) {
                $existing[] = [$question->id->value, false, $prompt, self::tokens($prompt)];
            }
        }

        $violations = [];

        foreach ($content->courses as $course) {
            foreach ($course->quizQuestions() as $quiz) {
                $prompt = DuplicateQuestionRule::normalize($quiz->prompt);
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
                            'Quiz question %d: prompt %d%% similar to %s; a course quiz must be written apart from the banks.',
                            $quiz->number,
                            (int) round($percent),
                            $holdout ? 'a HOLDOUT question (id withheld)' : \sprintf('question "%s"', $id),
                        ),
                        $course->id->value,
                    );
                    break;
                }
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
