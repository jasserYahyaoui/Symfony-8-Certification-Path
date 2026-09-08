<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\ContentLevel;
use CertPath\Domain\Pool;
use CertPath\Domain\SourceRef;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Readiness\ItemReadiness;
use CertPath\Readiness\ReadinessCalculator;
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
     * @param list<string>    $auditedLots
     * @param list<SourceRef> $sources
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
    ) {
        $item = ItemFactory::make([
            'lot' => 'lot-01',
            'contentLevel' => $level,
            'requiredAssessmentModes' => ['QUESTION'],
        ] + ([] === $sources ? [] : ['officialSources' => $sources]));

        $questions = [
            QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'pool' => $pool,
                'cognitiveLevel' => $cognitive,
                'examSkill' => $skill,
                'difficulty' => $hard ? 'hard' : 'medium',
            ]),
            QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'pool' => Pool::Learning,
                'cognitiveLevel' => 'KNOW',
                'examSkill' => 'RECOGNIZE',
            ]),
        ];

        $content = new ContentSet(
            matrix: new SyllabusMatrix(items: [$item], syllabusRevision: 'test', syllabusComplete: true),
            questions: $questions,
        );

        return (new ReadinessCalculator($auditedLots, $declaredLots))->calculate($content);
    }
}
