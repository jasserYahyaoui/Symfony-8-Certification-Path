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
 * The fenced-code exemption is deliberately narrow, and it is scoped to the
 * course's own official item. A course teaching
 * `use Symfony\Component\Routing\Attribute\Route;` and a question on the same
 * item asking which import is correct necessarily share that line — demanding
 * otherwise would forbid courses from showing correct code, or forbid questions
 * from testing what was taught.
 *
 * A correct answer belonging to a *different* item has no such excuse. It is a
 * leak wherever it appears, fence or not: the learner reading it sees the answer
 * either way, and the fence only hides it from this rule. Lot 05 proved the
 * point — a routing course quoted a `composer.json` excerpt that happened to
 * contain the verbatim answer to a Lot 03 deprecation question, and moving the
 * string into a fence made the violation disappear from the report while
 * leaving it fully visible on the published page.
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
            // A course may show the code its own item teaches, even when a
            // question on that item tests it. Anything from another item is
            // matched across the whole page, fenced code included.
            $sameItem = $question->officialItemId === $course->officialItemId;
            $haystack = $sameItem ? self::prose($course->body) : $course->body;

            foreach ($question->choices as $choice) {
                if (!$choice->correct) {
                    continue;
                }

                if ($this->isLeaked($choice, $haystack)) {
                    $leaks[] = $question->id->value;
                    break;
                }
            }
        }

        return $leaks;
    }

    private function isLeaked(Choice $choice, string $haystack): bool
    {
        $text = trim($choice->text);

        if (\strlen($text) < self::MIN_LEAK_LENGTH) {
            return false;
        }

        return str_contains(mb_strtolower($haystack), mb_strtolower($text));
    }

    /**
     * The course body with fenced code blocks removed.
     */
    public static function prose(string $markdown): string
    {
        return preg_replace('/^```.*?^```/ms', '', $markdown) ?? $markdown;
    }
}
