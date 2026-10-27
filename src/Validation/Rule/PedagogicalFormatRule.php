<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\Course;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * ADR-0012: a course in the pedagogical format earns its exemption from the
 * word budget.
 *
 * On 2026-10-27 the owner asked for every page to become a full revision course
 * — understand, visualise, example, rule, memorise, traps, application, quiz —
 * and to ignore the word budget. REV-001 and readiness criterion R14 therefore
 * skip a course whose front matter says `format: pedagogical`. A flag anyone can
 * set would be a way out of the budget for any page, so this rule holds the flag
 * to what the owner asked for, in the parts a script can check:
 *
 *   - an « Pièges d'examen » section;
 *   - a « Fiche express » of 5 to 10 points — « si tu dois retenir seulement
 *     X choses »;
 *   - a « QCM d'entraînement » of at least four well-formed questions, each
 *     with its correction.
 *
 * Every course that carries a quiz section has it checked, flagged or not: a
 * malformed question is a defect wherever it sits.
 *
 * What it cannot check is whether the explanation is good. That is read, like
 * every claim in a course, against the Symfony 8.0 sources.
 */
final class PedagogicalFormatRule implements Rule
{
    public const int MIN_QUESTIONS = 4;

    public const int MIN_DIGEST_POINTS = 5;

    public const int MAX_DIGEST_POINTS = 10;

    public function id(): string
    {
        return 'FMT-001';
    }

    public function description(): string
    {
        return 'A course in the pedagogical format (ADR-0012) carries its traps, a 5-to-10-point digest and a quiz of well-formed questions.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->courses as $course) {
            $id = $course->id->value;
            $questions = $course->quizQuestions();

            foreach ($questions as $question) {
                foreach ($question->problems as $problem) {
                    $violations[] = $this->error(\sprintf('Quiz question %d: %s.', $question->number, $problem), $id);
                }
            }

            if (!$course->isPedagogical()) {
                continue;
            }

            if (null === $course->section("Pièges d'examen")) {
                $violations[] = $this->error('A pedagogical course has no « Pièges d\'examen » section.', $id);
            }

            $digest = $course->section(Course::DIGEST_HEADING);
            if (null === $digest) {
                $violations[] = $this->error(\sprintf('A pedagogical course has no « %s » section.', Course::DIGEST_HEADING), $id);
            } else {
                $points = self::topLevelItems($digest);
                if ($points < self::MIN_DIGEST_POINTS || $points > self::MAX_DIGEST_POINTS) {
                    $violations[] = $this->error(\sprintf(
                        'The « %s » holds %d point(s); the format asks for %d to %d.',
                        Course::DIGEST_HEADING,
                        $points,
                        self::MIN_DIGEST_POINTS,
                        self::MAX_DIGEST_POINTS,
                    ), $id);
                }
            }

            if (null === $course->section(Course::QUIZ_HEADING)) {
                $violations[] = $this->error(\sprintf('A pedagogical course has no « %s » section.', Course::QUIZ_HEADING), $id);
            } elseif (\count($questions) < self::MIN_QUESTIONS) {
                $violations[] = $this->error(\sprintf(
                    'The quiz holds %d question(s); the format asks for at least %d.',
                    \count($questions),
                    self::MIN_QUESTIONS,
                ), $id);
            }
        }

        return $violations;
    }

    /** List items at the left margin, outside fenced code. */
    private static function topLevelItems(string $markdown): int
    {
        $count = 0;
        $inFence = false;
        foreach (explode("\n", $markdown) as $line) {
            if (str_starts_with(ltrim($line), '```')) {
                $inFence = !$inFence;

                continue;
            }
            if (!$inFence && str_starts_with($line, '- ')) {
                ++$count;
            }
        }

        return $count;
    }

    private function error(string $message, string $subject): Violation
    {
        return new Violation($this->id(), Severity::Error, $message, $subject);
    }
}
