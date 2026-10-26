<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\Course;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * ADR-0011: a course's `## Objectif` states the page's goal, and only that.
 *
 * Since the owner's decision of 2026-10-24 the section is outside the revision
 * budget REV-001, like the preamble the generator writes above it. An
 * exemption that accepted any text would be a place to put the teaching the
 * budget refuses, so the section is held to what an objective is: one
 * section, short prose, without code, table, list, diagram or sub-heading.
 *
 * The ceiling is three sentences of twenty words. On 2026-10-24 the 163
 * objectives had a median of 21 words, a p95 of 39 and a maximum of 75; the
 * one above the ceiling carried a paragraph of teaching, which moved into the
 * body, where it counts.
 */
final class ObjectiveSectionRule implements Rule
{
    public const int MAX_WORDS = 60;

    public function id(): string
    {
        return 'OBJ-001';
    }

    public function description(): string
    {
        return 'A course\'s « Objectif », outside the revision budget, is one short section of prose stating the goal.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->courses as $course) {
            foreach ($this->problems($course->body) as $problem) {
                $violations[] = new Violation($this->id(), Severity::Error, $problem, $course->id->value);
            }
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    private function problems(string $body): array
    {
        $sections = Course::objectiveSections($body);
        if (\count($sections) > 1) {
            return [\sprintf(
                'The course has %d « ## Objectif » sections; one states the goal, and only one is outside the budget.',
                \count($sections),
            )];
        }

        $problems = [];
        foreach ($sections as $section) {
            $text = (string) preg_replace('/\A## Objectif[ \t]*\n/u', '', $section);

            $words = \count(preg_split('/\s+/u', trim($text), -1, \PREG_SPLIT_NO_EMPTY) ?: []);
            if ($words > self::MAX_WORDS) {
                $problems[] = \sprintf(
                    '« Objectif » holds %d words, above %d: it states the goal; what teaches belongs in the body, '
                    .'where the budget counts it.',
                    $words,
                    self::MAX_WORDS,
                );
            }

            $forms = [
                'a fenced block' => '/^[ \t]*(```|~~~)/m',
                'a table' => '/^[ \t]*\|/m',
                'a list' => '/^[ \t]*([-*+]|\d+[.)])[ \t]+/m',
                'a sub-heading' => '/^#{3,6}[ \t]/m',
            ];
            foreach ($forms as $form => $pattern) {
                if (1 === preg_match($pattern, $text)) {
                    $problems[] = \sprintf(
                        '« Objectif » carries %s: it is outside the budget, so it holds the goal in prose and nothing else.',
                        $form,
                    );
                }
            }
        }

        return $problems;
    }
}
