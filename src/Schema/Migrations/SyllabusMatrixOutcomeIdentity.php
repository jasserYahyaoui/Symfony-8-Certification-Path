<?php

declare(strict_types=1);

namespace CertPath\Schema\Migrations;

use CertPath\Schema\Migration;
use CertPath\Schema\SchemaException;
use CertPath\Schema\SchemaRegistry;
use CertPath\Support\Id;

/**
 * syllabus-matrix 1 -> 2: every learning outcome carries a minted identity.
 *
 * ADR-0007 deferred this bump for one reason: a migration able to reach
 * version 2 on its own would have to invent an identifier per outcome, either
 * random per load or derived from the outcome text, and ADR-0002 forbids both.
 * That objection is what the staged rollout existed to dissolve — each lot's
 * refinement pass minted its own ids — and it no longer applies: the canonical
 * matrix carries 603 outcomes and every one of them is identified.
 *
 * So this migration does NOT convert data. It converts the CONTRACT, and
 * refuses when the data cannot honour it. A version-1 document whose outcomes
 * are already identified is version 2 in everything but its stamp, and is
 * stamped. One that still carries a bare string is rejected with the command
 * that fixes it, because the alternative — inventing the id here — is the very
 * thing ADR-0002 forbids, and a migration that quietly dropped the outcome
 * would shrink the assessment denominator invisibly.
 */
final class SyllabusMatrixOutcomeIdentity implements Migration
{
    public function schemaName(): string
    {
        return SchemaRegistry::SYLLABUS_MATRIX;
    }

    public function fromVersion(): int
    {
        return 1;
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    public function upgrade(array $document): array
    {
        $items = $document['items'] ?? [];
        if (!\is_array($items)) {
            throw new SchemaException('syllabus-matrix 1->2: `items` must be a list.');
        }

        foreach ($items as $index => $item) {
            if (!\is_array($item)) {
                continue;
            }

            $outcomes = $item['learning_outcomes'] ?? [];
            if (!\is_array($outcomes)) {
                continue;
            }

            foreach (array_values($outcomes) as $position => $outcome) {
                if (\is_array($outcome)
                    && \is_string($outcome['id'] ?? null)
                    && Id::isValid($outcome['id'])) {
                    continue;
                }

                throw new SchemaException(\sprintf(
                    'syllabus-matrix 1->2: item #%d ("%s") has an outcome without a minted id '
                    .'(outcome #%d). Mint one with `php bin/cert id:mint LearningOutcome` and write it '
                    .'as `{id, outcome}`; this migration will not invent an identifier (ADR-0002).',
                    \is_int($index) ? $index : 0,
                    \is_scalar($item['id'] ?? null) ? (string) $item['id'] : 'unknown',
                    $position,
                ));
            }
        }

        $document['schema_version'] = 2;

        return $document;
    }
}
