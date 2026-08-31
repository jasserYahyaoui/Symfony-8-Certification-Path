<?php

declare(strict_types=1);

namespace CertPath\Coverage;

use CertPath\Domain\OfficialItem;
use CertPath\Domain\SyllabusMatrix;

final class CoverageCalculator
{
    public function calculate(SyllabusMatrix $matrix): CoverageReport
    {
        $officialItems = $matrix->officialItems();

        $byLot = [];
        $byTopic = [];
        $blocked = [];
        $ready = 0;

        foreach ($officialItems as $item) {
            $isCovered = $item->isCovered();
            if ($isCovered) {
                ++$ready;
            } else {
                $blocked[] = $item->id->value;
            }

            $this->tally($byLot, $item->lot, $isCovered);
            $this->tally($byTopic, $item->officialTopic, $isCovered);
        }

        ksort($byLot);
        ksort($byTopic);

        return new CoverageReport(
            totalOfficialItems: \count($officialItems),
            examReadyItems: $ready,
            byLot: $byLot,
            byTopic: $byTopic,
            blockedItemIds: $blocked,
            syllabusComplete: $matrix->syllabusComplete,
        );
    }

    /**
     * @param array<string, array{total: int, ready: int}> $bucket
     */
    private function tally(array &$bucket, string $key, bool $isCovered): void
    {
        $bucket[$key] ??= ['total' => 0, 'ready' => 0];
        ++$bucket[$key]['total'];
        if ($isCovered) {
            ++$bucket[$key]['ready'];
        }
    }

    /**
     * Convenience accessor used by the report renderer.
     *
     * @param list<OfficialItem> $items
     *
     * @return list<OfficialItem>
     */
    public function notReady(array $items): array
    {
        return array_values(array_filter($items, static fn (OfficialItem $i): bool => !$i->isCovered()));
    }
}
