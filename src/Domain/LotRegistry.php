<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * How a delivery lot is NAMED for a learner (Master Plan §14).
 *
 * The lot id — `lot-05` — is a technical key: it names directories, URLs and
 * report rows, and it must stay stable. What a learner reads in the navigation
 * is a different thing, and this registry is the single place that decides it.
 *
 * The leading number is the **recommended revision order** and nothing else.
 * The official syllabus publishes no order, no weighting and no priority, so
 * the number must never be presented as one.
 */
final readonly class LotRegistry
{
    /**
     * @param array<string, array{order: int, display_name: string}> $lots keyed by lot id
     */
    private function __construct(private array $lots)
    {
    }

    /**
     * @param array<string, mixed> $raw the decoded lots.yml
     */
    public static function fromArray(array $raw): self
    {
        $lots = [];
        foreach ((array) ($raw['lots'] ?? []) as $entry) {
            if (!\is_array($entry) || !isset($entry['id'])) {
                continue;
            }

            $lots[(string) $entry['id']] = [
                'order' => (int) ($entry['order'] ?? 0),
                'display_name' => (string) ($entry['display_name'] ?? ''),
            ];
        }

        return new self($lots);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * The learner-facing label: "05 — Routing".
     *
     * An unregistered lot falls back to its id rather than inventing a name:
     * a missing entry is a data gap to fix in `lots.yml`, not something to
     * paper over with a guess.
     */
    public function label(string $lotId): string
    {
        $lot = $this->lots[$lotId] ?? null;
        if (null === $lot || '' === $lot['display_name']) {
            return $lotId;
        }

        return \sprintf('%02d — %s', $lot['order'], $lot['display_name']);
    }

    /** The revision order, used to sort the navigation. */
    public function order(string $lotId): int
    {
        return $this->lots[$lotId]['order'] ?? \PHP_INT_MAX;
    }

    public function has(string $lotId): bool
    {
        return isset($this->lots[$lotId]);
    }
}
