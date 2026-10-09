<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * ADR-0010: a lot's comprehension check leaves no learning outcome untested.
 *
 * The owner asked for « tout ce qu'il faut pour garantir que le lot est révisé
 * en succès », with no fixed number of questions. A count would be the wrong
 * measure twice over: too low and it leaves notions untested, too high and it
 * pads. The measure is the outcomes themselves — every `OUT` id of every item
 * in the lot must be assessed by at least one comprehension question of that
 * lot, synthesis questions counting for each item they relate.
 *
 * A lot with no comprehension question is not started and is not checked; the
 * rule tightens to every lot once all of them have one, as ADR-0007 did.
 */
final class ComprehensionCoverageRule implements Rule
{
    public function id(): string
    {
        return 'CMP-001';
    }

    public function description(): string
    {
        return 'A lot with a comprehension check assesses every learning outcome of every one of its items.';
    }

    public function check(ContentSet $content): array
    {
        $assessedByLot = [];

        foreach ($content->comprehension as $question) {
            $item = $content->matrix->findById($question->officialItemId);
            if (null === $item) {
                continue;
            }

            foreach ($question->assessesOutcomes as $outcome) {
                $assessedByLot[$item->lot][$outcome] = true;
            }
            $assessedByLot[$item->lot] ??= [];
        }

        $violations = [];

        foreach ($assessedByLot as $lot => $assessed) {
            foreach ($content->matrix->forLot($lot) as $item) {
                foreach ($item->learningOutcomeIds() as $outcome) {
                    if (isset($assessed[$outcome])) {
                        continue;
                    }

                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf(
                            'Outcome %s of "%s" is assessed by no comprehension question of %s.',
                            $outcome,
                            $item->officialItem,
                            $lot,
                        ),
                        $item->id->value,
                    );
                }
            }
        }

        return $violations;
    }
}
