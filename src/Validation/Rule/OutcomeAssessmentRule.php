<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\ItemStatus;
use CertPath\Domain\Pool;
use CertPath\Domain\Question;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * ADR-0007: every declared learning outcome of a refined item is assessed by
 * at least one question that names it.
 *
 * PED-002 already asks whether an item has *an* assessment. That question is
 * answered by a single question against five declared outcomes, which is how
 * 73 of the 163 items came to carry fewer questions than outcomes with every
 * gate green. This rule asks the harder question: is each outcome assessed?
 *
 * The link is a minted OUT id (ADR-0002), never an index into the outcome
 * list: an index would silently remap the moment somebody reorders the list,
 * and a link that can be wrong without anybody noticing is worse than none.
 *
 * Scope was at first the refined lots, ARC-001's staging, so that a build was
 * not failed over content whose refinement pass had not happened yet.
 *
 * ADR-0007's staging was removed on 2026-09-15: the twenty-six lots that
 * carry atomic official items are all recorded at framework version 2, so the
 * tolerance covered nobody. It is gone rather than dormant, because a tolerance
 * that covers nothing still tells the next reader the bar is optional.
 *
 * The aggregate arithmetic WARNING below is NOT part of that staging and stays:
 * it counts items carrying fewer questions than outcomes, which proves nothing
 * on its own (one question can assess two) but keeps a real figure visible.
 */
final class OutcomeAssessmentRule implements Rule
{
    public function id(): string
    {
        return 'PED-003';
    }

    public function description(): string
    {
        return 'Every learning outcome of a refined item is assessed by a question that names it.';
    }

    public function check(ContentSet $content): array
    {
        /** @var array<string, list<Question>> $byItem */
        $byItem = [];
        foreach ($content->questions as $question) {
            $byItem[$question->officialItemId][] = $question;
        }

        $violations = [];

        foreach ($content->matrix->officialItems() as $item) {
            if (!$item->status->isAtLeast(ItemStatus::Implemented)) {
                continue;
            }

            $questions = $byItem[$item->id->value] ?? [];

            $declaredIds = [];
            foreach ($item->learningOutcomes as $index => $outcome) {
                if (null === $outcome->id) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf(
                            'Item in lot "%s" has an outcome without a minted id (outcome #%d).',
                            $item->lot,
                            $index,
                        ),
                        $item->id->value,
                    );

                    continue;
                }

                $id = $outcome->id->value;

                if (isset($declaredIds[$id])) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf('Outcome id %s is declared twice on the same item.', $id),
                        $item->id->value,
                    );

                    continue;
                }

                $declaredIds[$id] = true;

                // A HOLDOUT question does not discharge an outcome. It reaches
                // exactly one payload, mock-4, sat once and unseen (ADR-0005,
                // ADR-0006), so an outcome assessed only there is one the
                // learner can never practise — and the item's own
                // minimum_evidence, which asks for a success in exam mode,
                // could not be produced for it. Found while linking Lot 01:
                // "Déclarer un attribut avec ses cibles et IS_REPEATABLE" was
                // named by a holdout question and by nothing else.
                $assessed = false;
                foreach ($questions as $question) {
                    if (Pool::Holdout !== $question->pool && $question->assessesOutcome($id)) {
                        $assessed = true;

                        break;
                    }
                }

                if (!$assessed) {
                    $onlyHoldout = $this->any(
                        $questions,
                        static fn (Question $q): bool => Pool::Holdout === $q->pool && $q->assessesOutcome($id),
                    );

                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        $onlyHoldout
                            ? \sprintf(
                                'Learning outcome %s is named only by a HOLDOUT question, which the learner '
                                .'meets once and unseen; it is not assessable during study.',
                                $id,
                            )
                            : \sprintf('Learning outcome %s is assessed by no question.', $id),
                        $item->id->value,
                    );
                }
            }

            // A question may only claim an outcome of the item it belongs to.
            // Without this, a stale id would keep an outcome looking assessed
            // after the outcome it referred to had been rewritten or moved.
            foreach ($questions as $question) {
                foreach ($question->assessesOutcomes as $ref) {
                    if (!isset($declaredIds[$ref])) {
                        $violations[] = new Violation(
                            $this->id(),
                            Severity::Error,
                            \sprintf(
                                'Question claims to assess outcome %s, which its item %s does not declare.',
                                $ref,
                                $item->id->value,
                            ),
                            $question->id->value,
                        );
                    }
                }
            }
        }

        $violations = array_merge($violations, $this->necessaryConditionSummary($content, $byItem));

        return $violations;
    }

    /**
     * @param list<Question>           $questions
     * @param callable(Question): bool $predicate
     */
    private function any(array $questions, callable $predicate): bool
    {
        foreach ($questions as $question) {
            if ($predicate($question)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The shortfall that is measurable before any id exists.
     *
     * One question can assess two outcomes, so `questions < outcomes` does not
     * prove an outcome is unassessed. The converse does hold: below that line,
     * one-to-one outcome coverage is arithmetically impossible. Reporting it
     * as a single aggregate WARNING keeps the figure in front of whoever runs
     * the validator without burying the output in one line per item, and it
     * keeps this rule from being silent — and therefore untested — on a corpus
     * where no outcome has been given an id yet.
     *
     * @param array<string, list<Question>> $byItem
     *
     * @return list<Violation>
     */
    private function necessaryConditionSummary(ContentSet $content, array $byItem): array
    {
        $short = 0;
        $total = 0;

        foreach ($content->matrix->officialItems() as $item) {
            if (!$item->status->isAtLeast(ItemStatus::Implemented)) {
                continue;
            }

            ++$total;

            if (\count($byItem[$item->id->value] ?? []) < \count($item->learningOutcomes)) {
                ++$short;
            }
        }

        if (0 === $short) {
            return [];
        }

        return [new Violation(
            $this->id(),
            Severity::Warning,
            \sprintf(
                '%d of %d implemented items carry fewer questions than declared learning outcomes, '
                .'so one-to-one outcome coverage is arithmetically impossible for them.',
                $short,
                $total,
            ),
        )];
    }
}
