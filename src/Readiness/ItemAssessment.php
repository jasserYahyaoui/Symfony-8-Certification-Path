<?php

declare(strict_types=1);

namespace CertPath\Readiness;

/**
 * One item's readiness, with the criteria that produced it.
 *
 * The criteria are carried rather than summarised, because a percentage nobody
 * can take apart is a percentage nobody can audit.
 */
final readonly class ItemAssessment
{
    /**
     * @param array<string, bool> $criteria criterion id => met, required ones only
     */
    public function __construct(
        public string $itemId,
        public string $lot,
        public string $officialItem,
        public string $contentLevel,
        public array $criteria,
        public ItemReadiness $readiness,
        public bool $lotAudited,
    ) {
    }

    /** @return list<string> */
    public function unmetCriteria(): array
    {
        return array_keys(array_filter($this->criteria, static fn (bool $met): bool => !$met));
    }

    public function meetsEveryCriterion(): bool
    {
        return [] === $this->unmetCriteria();
    }
}
