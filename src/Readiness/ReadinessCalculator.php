<?php

declare(strict_types=1);

namespace CertPath\Readiness;

use CertPath\Domain\ContentLevel;
use CertPath\Domain\OfficialItem;
use CertPath\Domain\Pool;
use CertPath\Domain\Question;
use CertPath\Domain\SourceRef;
use CertPath\Validation\ContentSet;

/**
 * Certification Readiness — the share of atomic official items refined to the
 * point where a candidate could answer an unseen question on them.
 *
 * The formula is documented in docs/policy/readiness-formula.md. Two rules
 * govern it and neither may be relaxed to make a number look better:
 *
 *   The criteria are CONCEPT-AWARE. A MINIMAL item is not penalised for
 *   lacking a diagnosis question it has no need of; a DEEP item is not called
 *   ready on a definition alone. Each level is asked only for what it requires.
 *
 *   The criteria are EVIDENCE-BASED. Every one is computed from canonical data.
 *   None counts files, lines or questions, because those measure volume and
 *   this measures whether the content can be answered from.
 *
 * The automated criteria alone do not discriminate: the corpus already clears
 * all of them, which is precisely why they cannot be the whole measure. What
 * moves Readiness is the per-lot refinement audit, recorded in
 * docs/progress/refinement-log.yml, in which every taught trap is checked
 * against an answer key BY READING IT. That check is deliberately not
 * automated here: a keyword heuristic for it produced four false positives out
 * of nine when it was tried, so it is a human audit or it is nothing.
 */
final class ReadinessCalculator
{
    /**
     * @param list<string> $auditedLots lot ids whose refinement audit is recorded
     */
    /**
     * @param list<string> $auditedLots  lot ids whose refinement audit is recorded
     * @param int          $declaredLots how many lots the canonical registry declares;
     *                                   Lot 27 carries no atomic item, so counting lots
     *                                   from the matrix alone would report 26 and quietly
     *                                   drop the final-review lot from the denominator
     */
    public function __construct(
        private array $auditedLots = [],
        private int $declaredLots = 0,
    ) {
    }

    public function calculate(ContentSet $content): ReadinessReport
    {
        $questionsByItem = [];
        foreach ($content->questions as $question) {
            $questionsByItem[$question->officialItemId][] = $question;
        }

        $assessments = [];
        $byLot = [];

        foreach ($content->matrix->officialItems() as $item) {
            $questions = $questionsByItem[$item->id->value] ?? [];
            $audited = \in_array($item->lot, $this->auditedLots, true);
            $criteria = $this->criteriaFor($item, $questions, $content);

            $assessment = new ItemAssessment(
                itemId: $item->id->value,
                lot: $item->lot,
                officialItem: $item->officialItem,
                contentLevel: $item->contentLevel?->value ?? 'UNSET',
                criteria: $criteria,
                readiness: $this->classify($criteria, $audited, $item, $questions),
                lotAudited: $audited,
            );

            $assessments[] = $assessment;
            $byLot[$item->lot] ??= ['total' => 0, 'ready' => 0, 'audited' => $audited];
            ++$byLot[$item->lot]['total'];
            if ($assessment->readiness->countsTowardReadiness()) {
                ++$byLot[$item->lot]['ready'];
            }
        }

        ksort($byLot);

        return new ReadinessReport($assessments, $byLot, max($this->declaredLots, \count($byLot)));
    }

    /**
     * @param list<Question> $questions
     *
     * @return array<string, bool>
     */
    private function criteriaFor(OfficialItem $item, array $questions, ContentSet $content): array
    {
        $level = $item->contentLevel;
        $modes = $item->requiredAssessmentModes;

        // Universal: every item, whatever its level, must be teachable and
        // assessable, and must satisfy the assessment modes it declares for
        // itself rather than a shape imposed from outside.
        $criteria = [
            'R1_course' => [] !== $item->courseRefs,
            'R2_two_questions' => \count($questions) >= 2,
            'R3_declared_modes' => $this->declaredModesSatisfied($modes, $item, $questions),
            'R4_anchored_sources' => $this->sourcesAnchored($item, $questions),
        ];

        // A STANDARD item is one the project judged to need distinction AND
        // application, so recall alone cannot be evidence for it.
        if (ContentLevel::Standard === $level || ContentLevel::Deep === $level) {
            $criteria['R5_beyond_recall'] = $this->any(
                $questions,
                static fn (Question $q): bool => \in_array($q->cognitiveLevel, ['UNDERSTAND', 'APPLY'], true),
            );
            $criteria['R6_distinguishes'] = $this->any(
                $questions,
                static fn (Question $q): bool => \in_array($q->examSkill, ['DISTINGUISH', 'DIAGNOSE'], true),
            );
            $criteria['R7_exam_mode'] = $this->any(
                $questions,
                static fn (Question $q): bool => Pool::Validation === $q->pool,
            );
        }

        // A DEEP item is one whose errors and consequences are the point.
        if (ContentLevel::Deep === $level) {
            $criteria['R8_diagnoses'] = $this->any(
                $questions,
                static fn (Question $q): bool => 'DIAGNOSE' === $q->examSkill,
            );
            $criteria['R9_hard_question'] = $this->any(
                $questions,
                static fn (Question $q): bool => 'hard' === $q->difficulty,
            );
        }

        return $criteria;
    }

    /**
     * @param list<string>   $modes
     * @param list<Question> $questions
     */
    private function declaredModesSatisfied(array $modes, OfficialItem $item, array $questions): bool
    {
        foreach ($modes as $mode) {
            $ok = match ($mode) {
                'QUESTION' => \count($questions) >= 2,
                'FLASHCARD' => [] !== $item->flashcardRefs,
                'EXAM' => $this->any($questions, static fn (Question $q): bool => Pool::Validation === $q->pool),
                default => true,
            };
            if (!$ok) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<Question> $questions
     */
    private function sourcesAnchored(OfficialItem $item, array $questions): bool
    {
        $sets = [$item->officialSources];
        foreach ($questions as $question) {
            $sets[] = $question->officialSources;
        }

        // SourceRef already knows both rules, and SRC-001 enforces them at
        // build time. They are re-asked here rather than assumed, so that a
        // readiness figure never rests on another gate having run.
        foreach ($sets as $sources) {
            foreach ($sources as $source) {
                if (!$source instanceof SourceRef) {
                    return false;
                }
                if (!$source->isVersionAnchored() || !$source->hasAnchor()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param array<string, bool> $criteria
     * @param list<Question>      $questions
     */
    private function classify(array $criteria, bool $audited, OfficialItem $item, array $questions): ItemReadiness
    {
        if (\in_array(false, $criteria, true)) {
            return ItemReadiness::NotRefined;
        }

        if (!$audited) {
            return ItemReadiness::PartiallyRefined;
        }

        $topOfItsLevel = $this->any($questions, static fn (Question $q): bool => Pool::Validation === $q->pool)
            && $this->any($questions, static fn (Question $q): bool => 'DIAGNOSE' === $q->examSkill)
            && $this->any($questions, static fn (Question $q): bool => 'hard' === $q->difficulty);

        return $topOfItsLevel ? ItemReadiness::MasteredReady : ItemReadiness::Refined;
    }

    /**
     * @param list<Question>            $questions
     * @param callable(Question): bool  $predicate
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
}
