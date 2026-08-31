<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * Master Plan §3.4 status lifecycle. The order is normative: an item may not
 * skip ahead, and only EXAM_READY counts as covered (§3.5).
 */
enum ItemStatus: string
{
    case NotStarted = 'NOT_STARTED';
    case Researched = 'RESEARCHED';
    case Specified = 'SPECIFIED';
    case Implemented = 'IMPLEMENTED';
    case SourceVerified = 'SOURCE_VERIFIED';
    case AssessmentVerified = 'ASSESSMENT_VERIFIED';
    case Tested = 'TESTED';
    case ExamReady = 'EXAM_READY';

    public function rank(): int
    {
        return match ($this) {
            self::NotStarted => 0,
            self::Researched => 1,
            self::Specified => 2,
            self::Implemented => 3,
            self::SourceVerified => 4,
            self::AssessmentVerified => 5,
            self::Tested => 6,
            self::ExamReady => 7,
        };
    }

    public function isExamReady(): bool
    {
        return self::ExamReady === $this;
    }

    public function isAtLeast(self $other): bool
    {
        return $this->rank() >= $other->rank();
    }
}
