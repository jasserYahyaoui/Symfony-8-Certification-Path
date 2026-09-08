<?php

declare(strict_types=1);

namespace CertPath\Readiness;

/**
 * The result of a readiness calculation.
 *
 * Every figure here is derived from the assessments it carries, so a reader who
 * distrusts the percentage can recompute it from the same object.
 */
final readonly class ReadinessReport
{
    /**
     * @param list<ItemAssessment>                                    $assessments
     * @param array<string, array{total: int, ready: int, audited: bool}> $byLot
     */
    public function __construct(
        public array $assessments,
        public array $byLot,
        public int $declaredLots = 0,
    ) {
    }

    public function totalItems(): int
    {
        return \count($this->assessments);
    }

    public function readyItems(): int
    {
        return \count(array_filter(
            $this->assessments,
            static fn (ItemAssessment $a): bool => $a->readiness->countsTowardReadiness(),
        ));
    }

    /**
     * Certification Readiness, one decimal place.
     *
     * Never rounded up to a friendlier number, and never written by hand: it is
     * the count above over the count of atomic official items.
     */
    public function percentage(): float
    {
        $total = $this->totalItems();
        if (0 === $total) {
            return 0.0;
        }

        return round($this->readyItems() / $total * 100, 1);
    }

    /** @return array<string, int> readiness status => item count */
    public function distribution(): array
    {
        $counts = [];
        foreach (ItemReadiness::cases() as $case) {
            $counts[$case->value] = 0;
        }
        foreach ($this->assessments as $assessment) {
            ++$counts[$assessment->readiness->value];
        }

        return $counts;
    }

    public function lotsRefined(): int
    {
        return \count(array_filter($this->byLot, static fn (array $l): bool => $l['audited']));
    }

    /** @return list<ItemAssessment> */
    public function itemsFallingShort(): array
    {
        return array_values(array_filter(
            $this->assessments,
            static fn (ItemAssessment $a): bool => !$a->meetsEveryCriterion(),
        ));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $lots = [];
        foreach ($this->byLot as $lot => $row) {
            $lots[$lot] = [
                'total' => $row['total'],
                'ready' => $row['ready'],
                'audited' => $row['audited'],
                'percentage' => 0 === $row['total'] ? 0.0 : round($row['ready'] / $row['total'] * 100, 1),
            ];
        }

        return [
            'total_official_items' => $this->totalItems(),
            'ready_items' => $this->readyItems(),
            'percentage' => $this->percentage(),
            'lots_refined' => $this->lotsRefined(),
            'lots_total' => max($this->declaredLots, \count($this->byLot)),
            'distribution' => $this->distribution(),
            'by_lot' => $lots,
        ];
    }
}
