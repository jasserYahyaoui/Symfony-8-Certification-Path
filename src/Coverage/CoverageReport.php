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
        public bool $syllabusComplete = false,
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
     * A usable denominator requires the syllabus to be BOTH imported and
     * complete (§3.1, §3.5).
     *
     * A partial import is the more dangerous of the two failure modes: an
     * empty matrix is obviously empty, whereas a partial one yields a
     * percentage that looks entirely credible while measuring against the
     * wrong total — and it always reads higher than the truth.
     */
    public function isDenominatorEstablished(): bool
    {
        return $this->totalOfficialItems > 0 && $this->syllabusComplete;
    }

    /** Items are present, but the syllabus itself is known to be truncated. */
    public function isPartialImport(): bool
    {
        return $this->totalOfficialItems > 0 && !$this->syllabusComplete;
    }
}
