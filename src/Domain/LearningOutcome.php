<?php

declare(strict_types=1);

namespace CertPath\Domain;

use CertPath\Support\Id;

/**
 * One declared learning outcome of an atomic official item.
 *
 * `EntityType::LearningOutcome` and the prefix `OUT` were declared in §11 from
 * the start; outcomes were nevertheless stored as bare strings, so no question
 * could ever say which outcome it assesses. This object carries the minted id
 * that closes that gap (ADR-0007).
 *
 * The id is nullable during the staged rollout: an item whose lot has not yet
 * been refined legitimately carries outcomes without identity. Rule PED-003
 * requires the id as soon as a lot is recorded as refined, so the tolerance
 * cannot survive the refinement of a lot.
 */
final readonly class LearningOutcome
{
    public function __construct(
        public string $text,
        public ?Id $id = null,
    ) {
    }

    public function isIdentified(): bool
    {
        return null !== $this->id;
    }

    public function idValue(): ?string
    {
        return $this->id?->value;
    }
}
