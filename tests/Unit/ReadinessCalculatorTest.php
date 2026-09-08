<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\ContentLevel;
use CertPath\Domain\LearningOutcome;
use CertPath\Domain\Pool;
use CertPath\Domain\QuestionArchetype;
use CertPath\Domain\SourceRef;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Readiness\ItemReadiness;
use CertPath\Readiness\ReadinessCalculator;
use CertPath\Support\EntityType;
use CertPath\Support\Id;
use CertPath\Tests\Support\ItemFactory;
use CertPath\Tests\Support\QuestionFactory;
use CertPath\Validation\ContentSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReadinessCalculator::class)]
final class ReadinessCalculatorTest extends TestCase
{
    /**
     * The whole point of the metric: an item can satisfy every automated
     * criterion and still not count, because no one has audited its lot. If
     * this ever passes, Readiness has silently become Coverage again.
     */
    public function testAnUnauditedLotIsOnlyPartiallyRefined(): void
    {
        $report = $this->calculate(auditedLots: []);

        self::assertSame(ItemReadiness::PartiallyRefined, $report->assessments[0]->readiness);
        self::assertSame(0.0, $report->percentage(), 'an unaudited lot must not count toward readiness');
    }

    public function testAnAuditedLotWithEveryCriterionCounts(): void
    {
        $report = $this->calculate(auditedLots: ['lot-01']);

        self::assertTrue($report->assessments[0]->readiness->countsTowardReadiness());
        self::assertSame(100.0, $report->percentage());
    }

    /** MASTERED_READY is a tier above REFINED, not a synonym for it. */
    public function testMasteredReadyNeedsExamModeDiagnosisAndAHardQuestion(): void
    {
        $refined = $this->calculate(auditedLots: ['lot-01']);
        self::assertSame(ItemReadiness::MasteredReady, $refined->assessments[0]->readiness);

        $withoutHard = $this->calculate(auditedLots: ['lot-01'], hard: false);
        self::assertSame(ItemReadiness::Refined, $withoutHard->assessments[0]->readiness);
        self::assertTrue(
            $withoutHard->assessments[0]->readiness->countsTowardReadiness(),
            'falling short of MASTERED_READY must not remove the item from readiness',
        );
    }

    /**
     * Concept-awareness, stated as a test: a MINIMAL item is not marked short
     * for lacking evidence its level never asked for.
     */
    public function testAMinimalItemIsNotPenalisedForLackingApplicationEvidence(): void
    {
        $report = $this->calculate(
            auditedLots: ['lot-01'],
            level: ContentLevel::Minimal,
            pool: Pool::Learning,
            cognitive: 'KNOW',
            skill: 'RECOGNIZE',
            hard: false,
        );

        self::assertSame([], $report->assessments[0]->unmetCriteria());
        self::assertArrayNotHasKey('R7_exam_mode', $report->assessments[0]->criteria);
    }

    /** The same evidence gap DOES fail a STANDARD item, which is the contrast. */
    public function testAStandardItemWithoutExamModeEvidenceIsNotRefined(): void
    {
        $report = $this->calculate(
            auditedLots: ['lot-01'],
            pool: Pool::Learning,
            cognitive: 'KNOW',
            skill: 'RECOGNIZE',
        );

        self::assertSame(ItemReadiness::NotRefined, $report->assessments[0]->readiness);
        self::assertContains('R7_exam_mode', $report->assessments[0]->unmetCriteria());
        self::assertSame(0.0, $report->percentage());
    }

    /** A source without an anchor cannot be evidence, so it cannot be readiness. */
    public function testAnUnanchoredSourceFailsTheItem(): void
    {
        $report = $this->calculate(
            auditedLots: ['lot-01'],
            sources: [new SourceRef(url: 'https://symfony.com/doc/current/routing.html')],
        );

        self::assertContains('R4_anchored_sources', $report->assessments[0]->unmetCriteria());
    }

    /** Lot 27 holds no atomic item, so the denominator comes from the registry. */
    public function testTheLotDenominatorComesFromTheDeclaredRegistry(): void
    {
        $report = $this->calculate(auditedLots: ['lot-01'], declaredLots: 27);

        self::assertSame(27, $report->toArray()['lots_total']);
        self::assertSame(1, $report->lotsRefined());
    }

    /**
     * Framework version 2 (ADR-0007). An outcome nobody can point at cannot be
     * shown to be assessed, so an item whose outcomes carry no minted id is not
     * refined however good its questions are.
     */
    public function testAnItemWhoseOutcomesHaveNoIdIsNotRefined(): void
    {
        $report = $this->calculate(
            auditedLots: ['lot-01'],
            outcomes: [new LearningOutcome('Explain the mechanism')],
        );

        self::assertContains('R10_outcomes_identified', $report->assessments[0]->unmetCriteria());
        self::assertSame(ItemReadiness::NotRefined, $report->assessments[0]->readiness);
    }

    /**
     * The gap PED-002 could not express: an item may declare five outcomes,
     * assess one, and read as "has an assessment".
     */
    public function testAnOutcomeNoQuestionAssessesFailsTheItem(): void
    {
        $report = $this->calculate(auditedLots: ['lot-01'], assessesOutcome: false);

        self::assertContains('R11_outcomes_assessed', $report->assessments[0]->unmetCriteria());
    }

    public function testAQuestionWithoutAnArchetypeFailsTheItem(): void
    {
        $report = $this->calculate(auditedLots: ['lot-01'], secondArchetype: null);

        self::assertContains('R12_archetypes_declared', $report->assessments[0]->unmetCriteria());
    }

    /**
     * A STANDARD item asked for more than one kind of thinking, so the same
     * question mould twice is not evidence for it.
     */
    public function testAStandardItemAssessedTwiceByTheSameArchetypeIsNotRefined(): void
    {
        $report = $this->calculate(
            auditedLots: ['lot-01'],
            archetype: QuestionArchetype::ScenarioChoice,
            secondArchetype: QuestionArchetype::ScenarioChoice,
        );

        self::assertContains('R13_archetype_variety', $report->assessments[0]->unmetCriteria());
    }

    /** Concept-awareness again: variety is not asked of a MINIMAL item. */
    public function testAMinimalItemIsNotAskedForArchetypeVariety(): void
    {
        $report = $this->calculate(
            auditedLots: ['lot-01'],
            level: ContentLevel::Minimal,
            pool: Pool::Learning,
            cognitive: 'KNOW',
            skill: 'RECOGNIZE',
            hard: false,
            archetype: QuestionArchetype::DefinitionRecall,
            secondArchetype: QuestionArchetype::DefinitionRecall,
        );

        self::assertArrayNotHasKey('R13_archetype_variety', $report->assessments[0]->criteria);
        self::assertSame([], $report->assessments[0]->unmetCriteria());
    }

    /**
     * @param list<string>               $auditedLots
     * @param list<SourceRef>            $sources
     * @param list<LearningOutcome>|null $outcomes
     */
    private function calculate(
        array $auditedLots,
        ContentLevel $level = ContentLevel::Standard,
        Pool $pool = Pool::Validation,
        string $cognitive = 'APPLY',
        string $skill = 'DIAGNOSE',
        bool $hard = true,
        array $sources = [],
        int $declaredLots = 1,
        ?QuestionArchetype $archetype = QuestionArchetype::ScenarioChoice,
        ?QuestionArchetype $secondArchetype = QuestionArchetype::DefinitionRecall,
        bool $assessesOutcome = true,
        ?array $outcomes = null,
    ) {
        $outcome = new LearningOutcome('Explain the mechanism', Id::mint(EntityType::LearningOutcome));

        $item = ItemFactory::make([
            'lot' => 'lot-01',
            'contentLevel' => $level,
            'requiredAssessmentModes' => ['QUESTION'],
            'learningOutcomes' => $outcomes ?? [$outcome],
        ] + ([] === $sources ? [] : ['officialSources' => $sources]));

        $assessed = $assessesOutcome ? [$outcome->id?->value ?? ''] : [];

        $questions = [
            QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'pool' => $pool,
                'cognitiveLevel' => $cognitive,
                'examSkill' => $skill,
                'difficulty' => $hard ? 'hard' : 'medium',
                'questionArchetype' => $archetype,
                'assessesOutcomes' => $assessed,
            ]),
            QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'pool' => Pool::Learning,
                'cognitiveLevel' => 'KNOW',
                'examSkill' => 'RECOGNIZE',
                'questionArchetype' => $secondArchetype,
                'assessesOutcomes' => $assessed,
            ]),
        ];

        $content = new ContentSet(
            matrix: new SyllabusMatrix(items: [$item], syllabusRevision: 'test', syllabusComplete: true),
            questions: $questions,
        );

        return (new ReadinessCalculator($auditedLots, $declaredLots))->calculate($content);
    }
}
