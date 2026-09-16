<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * CRS-002 — a course's declared content level is its item's content level.
 *
 * WHY THIS EXISTS. `content_level` is written twice: on the matrix item, where
 * it is the authority, and in each course's front matter, where it is a
 * convenience for whoever opens the file. Until 2026-09-16 the second copy was
 * read by NOTHING. `Course::contentLevel` was loaded by CourseLoader and
 * consumed by zero rule — REV-001 reads the level from the matrix, and every
 * other rule that cares does the same.
 *
 * So the copy could say anything. It did: ADR-0008 promoted `HTTP request` from
 * STANDARD to DEEP in the matrix and left the course front matter reading
 * STANDARD. Every gate stayed green, the revision plan printed DEEP, the page
 * printed DEEP, and one file on disk disagreed with all of them. An independent
 * review found it by reading, which is the expensive way.
 *
 * The rule is an ERROR rather than a warning because there is no case where the
 * two may legitimately differ: one of them is simply wrong, and which one is
 * not this rule's business — the matrix is the authority (ADR-0003), so the
 * course is what moves.
 */
final class CourseLevelAgreementRule implements Rule
{
    public function id(): string
    {
        return 'CRS-002';
    }

    public function description(): string
    {
        return 'Every course declares the content level its official item declares.';
    }

    public function check(ContentSet $content): array
    {
        $levelOfItem = [];
        foreach ($content->matrix->officialItems() as $item) {
            $levelOfItem[$item->id->value] = $item->contentLevel;
        }

        $violations = [];

        foreach ($content->courses as $course) {
            // An item this course does not belong to is CRS-001's finding, not
            // this one's. Reporting it twice would make one defect look like two.
            if (!\array_key_exists($course->officialItemId, $levelOfItem)) {
                continue;
            }

            $expected = $levelOfItem[$course->officialItemId];

            if (null === $expected || $expected === $course->contentLevel) {
                continue;
            }

            $violations[] = new Violation(
                $this->id(),
                Severity::Error,
                \sprintf(
                    'Course declares content_level %s while item %s declares %s. The matrix is the '
                    .'authority, so the course is what moves.',
                    $course->contentLevel->value,
                    $course->officialItemId,
                    $expected->value,
                ),
                $course->id->value,
            );
        }

        return $violations;
    }
}
