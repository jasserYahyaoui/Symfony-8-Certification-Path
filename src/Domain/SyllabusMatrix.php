<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * The full set of atomic official items — the coverage denominator (§3.5).
 */
final readonly class SyllabusMatrix
{
    /**
     * @param list<OfficialItem> $items
     */
    public function __construct(
        public array $items,
        public ?string $syllabusRevision = null,
    ) {
    }

    /**
     * @return list<OfficialItem>
     */
    public function officialItems(): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (OfficialItem $i): bool => $i->countsTowardDenominator(),
        ));
    }

    public function findById(string $id): ?OfficialItem
    {
        foreach ($this->items as $item) {
            if ($item->id->value === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return list<OfficialItem>
     */
    public function forLot(string $lot): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (OfficialItem $i): bool => $i->lot === $lot,
        ));
    }

    public function isEmpty(): bool
    {
        return [] === $this->items;
    }
}
