<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Support\CourseUrl;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * Every learning outcome points at the course section that teaches it.
 *
 * The owner's request of 2026-10-14: a wrong answer in a mock must point
 * exactly at the section of the course in question. The link is built from the
 * course's `outcome_sections` and the outcomes the question assesses, so this
 * rule is what keeps it from pointing at nothing. A heading renamed, an outcome
 * added to the matrix, an outcome mapped to a section that only summarises: each
 * would ship a link that lands at the top of the page, or nowhere, and no build
 * would notice.
 *
 * What it cannot see: whether the section really teaches the outcome. That is a
 * reading, done when the map is written and again whenever a course changes.
 */
final class OutcomeSectionRule implements Rule
{
    /**
     * Headings that frame a course rather than teach it. An outcome mapped to
     * one of these would send the learner to an aim or a summary, not to the
     * explanation.
     */
    private const array NOT_TEACHING = [
        'Objectif', 'Points clés', 'Sources officielles', 'Aller lire la source', 'Prérequis', 'Périmètre',
        // ADR-0012: recaps and the quiz of a pedagogical page test or summarise;
        // they do not explain.
        "QCM d'entraînement", 'Fiche express', 'À comprendre', 'À mémoriser',
    ];

    public function id(): string
    {
        return 'SEC-001';
    }

    public function description(): string
    {
        return 'Every learning outcome of an item is mapped to a teaching section of its course that exists.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->courses as $course) {
            $item = $content->matrix->findById($course->officialItemId);
            if (null === $item) {
                continue;
            }

            $headings = $course->sectionHeadings();
            $anchors = [];
            foreach ($headings as $heading) {
                $anchor = CourseUrl::sectionAnchor($heading);
                if (isset($anchors[$anchor])) {
                    $violations[] = $this->error(\sprintf('Two sections share the anchor "%s" ("%s" and "%s"): a link to one would open the other.', $anchor, $anchors[$anchor], $heading), $course->id->value);
                }
                $anchors[$anchor] = $heading;
            }

            $outcomes = [];
            foreach ($item->learningOutcomes as $outcome) {
                $id = $outcome->idValue();
                if (null === $id) {
                    continue;
                }
                $outcomes[$id] = true;
                if (!isset($course->outcomeSections[$id])) {
                    $violations[] = $this->error(\sprintf('Outcome %s has no section: a question assessing it cannot link to where it is taught.', $id), $course->id->value);
                }
            }

            foreach ($course->outcomeSections as $id => $heading) {
                if (!isset($outcomes[$id])) {
                    $violations[] = $this->error(\sprintf('`outcome_sections` names %s, which is not an outcome of %s.', $id, $item->id->value), $course->id->value);
                }
                if (!\in_array($heading, $headings, true)) {
                    $violations[] = $this->error(\sprintf('Outcome %s is mapped to "%s", which is not a `## ` heading of the course.', $id, $heading), $course->id->value);
                } elseif (\in_array($heading, self::NOT_TEACHING, true)) {
                    $violations[] = $this->error(\sprintf('Outcome %s is mapped to "%s", a section that frames the course instead of teaching it.', $id, $heading), $course->id->value);
                }
            }
        }

        return $violations;
    }

    private function error(string $message, string $subject): Violation
    {
        return new Violation($this->id(), Severity::Error, $message, $subject);
    }
}
