<?php

declare(strict_types=1);

namespace CertPath\Domain;

use CertPath\Support\Id;

/**
 * One atomic official syllabus item — the only unit coverage is ever computed
 * from (Master Plan §3.5).
 *
 * `officialWording` is imported verbatim (§3.1) and is never rewritten by this
 * codebase; a change to it is a syllabus refresh, detected by CI (§12).
 */
final readonly class OfficialItem
{
    /**
     * @param list<LearningOutcome> $learningOutcomes
     * @param list<string>    $requiredAssessmentModes
     * @param list<SourceRef> $officialSources
     * @param list<string>    $courseRefs
     * @param list<string>    $flashcardRefs
     * @param list<string>    $questionRefs
     * @param list<string>    $exerciseRefs
     * @param list<string>    $examRefs
     * @param list<string>    $prerequisites
     */
    public function __construct(
        public Id $id,
        public int $officialTopicOrder,
        public string $officialTopic,
        public int $officialItemOrder,
        public string $officialItem,
        public string $officialWording,
        public string $learningDomain,
        public string $lot,
        public ?string $chapter,
        public Classification $classification,
        public ?ContentLevel $contentLevel,
        public ?string $contentLevelJustification,
        public array $learningOutcomes,
        public array $requiredAssessmentModes,
        public ?string $minimumEvidence,
        public string $exclusionBoundaries,
        public string $versionConstraints,
        public array $officialSources,
        public array $courseRefs,
        public array $flashcardRefs,
        public array $questionRefs,
        public array $exerciseRefs,
        public array $examRefs,
        public array $prerequisites,
        public ItemStatus $status,
        public VerificationStatus $verificationStatus,
        public bool $examReady,
        public ?string $lastVerifiedAt,
        public ?string $reviewedBy,
        public ?string $notes,
    ) {
    }

    /**
     * §3.5: an item counts as covered only when it is genuinely EXAM_READY.
     *
     * The `exam_ready` flag alone is never trusted — it must agree with the
     * lifecycle status and with source verification, so that a hand-edited
     * boolean cannot inflate the coverage figure.
     */
    public function isCovered(): bool
    {
        return $this->classification->countsTowardCoverage()
            && $this->examReady
            && $this->status->isExamReady()
            && $this->verificationStatus->mayBeScored();
    }

    public function countsTowardDenominator(): bool
    {
        return $this->classification->countsTowardCoverage();
    }

    /**
     * The outcome texts, for the consumers that legitimately want prose: the
     * generated item page and the mock payloads' item index, whose published
     * JSON shape is `learning_outcomes: string[]` and must not change.
     *
     * @return list<string>
     */
    public function learningOutcomeTexts(): array
    {
        return array_map(static fn (LearningOutcome $o): string => $o->text, $this->learningOutcomes);
    }

    /**
     * The minted ids of the outcomes that carry one. During the staged rollout
     * of ADR-0007 an unrefined item's outcomes have none, so this is empty
     * rather than an error.
     *
     * @return list<string>
     */
    public function learningOutcomeIds(): array
    {
        $ids = [];
        foreach ($this->learningOutcomes as $outcome) {
            if (null !== $outcome->id) {
                $ids[] = $outcome->id->value;
            }
        }

        return $ids;
    }

    public function hasAssessment(): bool
    {
        return [] !== $this->questionRefs
            || [] !== $this->exerciseRefs
            || [] !== $this->examRefs;
    }
}
