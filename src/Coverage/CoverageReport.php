<?php

declare(strict_types=1);

namespace CertPath\Coverage;

/**
 * Master Plan §3.5:
 *   coverage = EXAM_READY atomic official items / total atomic official items * 100
 *
 * Nothing else — not lots, pages, chapters, flashcards, questions or files —
 * may be used to compute it.
 */
final readonly class CoverageReport
{
    /**
     * @param array<string, array{total: int, ready: int}> $byLot
     * @param array<string, array{total: int, ready: int}> $byTopic
     * @param list<string>                                 $blockedItemIds
     */
    public function __construct(
        public int $totalOfficialItems,
        public int $examReadyItems,
        public array $byLot,
        public array $byTopic,
        public array $blockedItemIds,
    ) {
    }

    public function percentage(): float
    {
        if (0 === $this->totalOfficialItems) {
            return 0.0;
        }

        return round($this->examReadyItems / $this->totalOfficialItems * 100, 2);
    }

    /**
     * The denominator is unknown until the official syllabus has been imported
     * verbatim (§3.1). Reporting "0%" and reporting "undefined" are different
     * claims, and conflating them would be a false completeness signal.
     */
    public function isDenominatorEstablished(): bool
    {
        return $this->totalOfficialItems > 0;
    }
}
