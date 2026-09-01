<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\Choice;
use CertPath\Domain\Course;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * Master Plan §4.3: "Course pages must not reveal interactive exam answers."
 *
 * Checked rather than trusted. A course that reproduces a question's correct
 * option verbatim silently destroys that question's discriminating power, and
 * nobody notices until the mock-exam scores look implausibly good.
 *
 * The check deliberately ignores fenced code blocks. A course teaching
 * `use Symfony\Component\Routing\Attribute\Route;` and a question asking
 * which import is correct necessarily share that line — demanding otherwise
 * would forbid courses from showing correct code, or forbid questions from
 * testing what was taught. What §4.3 actually guards against is a course
 * giving away a question's *phrasing* in prose, so that is what is matched.
 */
final class CourseIntegrityRule implements Rule
{
    /**
     * Short options ("404", "GET") occur naturally in prose; only a
     * substantial verbatim match indicates a leaked answer.
     */
    private const int MIN_LEAK_LENGTH = 25;

    public function id(): string
    {
        return 'CRS-001';
    }

    public function description(): string
    {
        return 'Courses map to a real item, cite sources, and never reproduce a question\'s correct answer.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->courses as $course) {
            $subject = $course->id->value;

            if (null === $content->matrix->findById($course->officialItemId)) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf('Course maps to unknown official item "%s".', $course->officialItemId),
                    $subject,
                );
            }

            if ([] === $course->officialSources) {
                $violations[] = new Violation($this->id(), Severity::Error, 'Course cites no official source.', $subject);
            }

            foreach ($this->leakedAnswers($course, $content) as $leak) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf('Course reproduces the correct answer of question %s verbatim (§4.3).', $leak),
                    $subject,
                );
            }
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    private function leakedAnswers(Course $course, ContentSet $content): array
    {
        $leaks = [];

        foreach ($content->questions as $question) {
            foreach ($question->choices as $choice) {
                if (!$choice->correct) {
                    continue;
                }

                if ($this->isLeaked($choice, $course)) {
                    $leaks[] = $question->id->value;
                    break;
                }
            }
        }

        return $leaks;
    }

    private function isLeaked(Choice $choice, Course $course): bool
    {
        $text = trim($choice->text);

        if (\strlen($text) < self::MIN_LEAK_LENGTH) {
            return false;
        }

        return str_contains(mb_strtolower(self::prose($course->body)), mb_strtolower($text));
    }

    /**
     * The course body with fenced code blocks removed.
     */
    public static function prose(string $markdown): string
    {
        return preg_replace('/^```.*?^```/ms', '', $markdown) ?? $markdown;
    }
}
