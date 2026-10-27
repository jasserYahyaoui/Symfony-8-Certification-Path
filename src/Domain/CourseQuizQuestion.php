<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * One question of a course's « QCM d'entraînement » (ADR-0012).
 *
 * Parsed from the Markdown of the course, not loaded from a bank: the quiz is
 * part of the page the learner reads. That is why it carries its own list of
 * problems instead of throwing — a malformed question is reported by FMT-001
 * with the course and question number, like any other content defect.
 */
final readonly class CourseQuizQuestion
{
    /**
     * @param array<string, string> $choices  letter => text, in page order
     * @param list<string>          $answers  the letters the correction gives
     * @param list<string>          $problems what makes the question unusable
     */
    public function __construct(
        public int $number,
        public string $prompt,
        public array $choices,
        public array $answers,
        public string $correction,
        public array $problems,
    ) {
    }
}
