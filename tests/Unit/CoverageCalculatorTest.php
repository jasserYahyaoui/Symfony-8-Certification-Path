<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Coverage\CoverageCalculator;
use CertPath\Coverage\CoverageMarkdownRenderer;
use CertPath\Coverage\CoverageReport;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Tests\Support\ItemFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CoverageCalculator::class)]
#[CoversClass(CoverageReport::class)]
#[CoversClass(CoverageMarkdownRenderer::class)]
final class CoverageCalculatorTest extends TestCase
{
    /**
     * Master Plan §3.5: coverage counts EXAM_READY official items only.
     */
    public function testCoverageCountsOnlyExamReadyOfficialItems(): void
    {
        $matrix = new SyllabusMatrix(
            items: [
                ItemFactory::examReady('Topic A', 'lot-01'),
                ItemFactory::examReady('Topic A', 'lot-01'),
                ItemFactory::notReady('Topic B', 'lot-02'),
                ItemFactory::notReady('Topic B', 'lot-02'),
            ],
            syllabusComplete: true,
        );

        $report = (new CoverageCalculator())->calculate($matrix);

        self::assertSame(4, $report->totalOfficialItems);
        self::assertSame(2, $report->examReadyItems);
        self::assertSame(50.0, $report->percentage());
    }

    /**
     * §3.5: prerequisites and enrichment are learning content but are never
     * part of the official denominator.
     */
    public function testNonOfficialItemsAreExcludedFromTheDenominator(): void
    {
        $matrix = new SyllabusMatrix(
            items: [
                ItemFactory::examReady('Topic A', 'lot-01'),
                ItemFactory::prerequisite('Topic A', 'lot-01'),
                ItemFactory::enrichment('Topic A', 'lot-01'),
            ],
            syllabusComplete: true,
        );

        $report = (new CoverageCalculator())->calculate($matrix);

        self::assertSame(1, $report->totalOfficialItems);
        self::assertSame(100.0, $report->percentage());
    }

    /**
     * The guard that stops a hand-edited boolean inflating coverage.
     */
    public function testExamReadyFlagAloneDoesNotCountAsCovered(): void
    {
        $matrix = new SyllabusMatrix([ItemFactory::flagOnlyExamReady('Topic A', 'lot-01')]);

        $report = (new CoverageCalculator())->calculate($matrix);

        self::assertSame(1, $report->totalOfficialItems);
        self::assertSame(0, $report->examReadyItems);
    }

    /**
     * The dangerous case: a partial import yields a credible-looking
     * percentage against the wrong total, and it always reads high.
     */
    public function testPartialSyllabusImportNeverPublishesAPercentage(): void
    {
        $matrix = new SyllabusMatrix(
            items: [
                ItemFactory::examReady('Routing', 'lot-05'),
                ItemFactory::notReady('Routing', 'lot-05'),
            ],
            syllabusRevision: '2026-08-31-partial',
            syllabusComplete: false,
        );

        $report = (new CoverageCalculator())->calculate($matrix);

        self::assertTrue($report->isPartialImport());
        self::assertFalse($report->isDenominatorEstablished());

        $markdown = (new CoverageMarkdownRenderer())->render($report, $matrix->syllabusRevision);

        self::assertStringContainsString('UNDEFINED', $markdown);
        self::assertStringContainsString('incomplete', $markdown);
        self::assertStringNotContainsString('Coverage: 50%', $markdown);
    }

    public function testCompleteSyllabusPublishesAPercentage(): void
    {
        $matrix = new SyllabusMatrix(
            items: [ItemFactory::examReady('Routing', 'lot-05'), ItemFactory::notReady('Routing', 'lot-05')],
            syllabusRevision: '2026-09-01',
            syllabusComplete: true,
        );

        $report = (new CoverageCalculator())->calculate($matrix);

        self::assertTrue($report->isDenominatorEstablished());
        self::assertFalse($report->isPartialImport());
        self::assertSame(50.0, $report->percentage());
    }

    public function testEmptyMatrixReportsAnUndefinedDenominatorRatherThanZeroPercent(): void
    {
        $report = (new CoverageCalculator())->calculate(new SyllabusMatrix([]));

        self::assertFalse($report->isDenominatorEstablished());
        self::assertSame(0, $report->totalOfficialItems);

        $markdown = (new CoverageMarkdownRenderer())->render($report);

        self::assertStringContainsString('UNDEFINED', $markdown);
        self::assertStringNotContainsString('Coverage: 0%', $markdown);
    }

    public function testBreakdownsAreGroupedByTopicAndLot(): void
    {
        $matrix = new SyllabusMatrix(
            items: [
                ItemFactory::examReady('Routing', 'lot-05'),
                ItemFactory::notReady('Routing', 'lot-05'),
                ItemFactory::examReady('Security', 'lot-10'),
            ],
            syllabusComplete: true,
        );

        $report = (new CoverageCalculator())->calculate($matrix);

        self::assertSame(['total' => 2, 'ready' => 1], $report->byTopic['Routing']);
        self::assertSame(['total' => 1, 'ready' => 1], $report->byLot['lot-10']);
        self::assertCount(1, $report->blockedItemIds);
    }

    public function testRenderedReportStatesTheOfficialFormula(): void
    {
        $matrix = new SyllabusMatrix(
            items: [ItemFactory::examReady('Routing', 'lot-05')],
            syllabusRevision: 'rev-2026-01',
            syllabusComplete: true,
        );

        $markdown = (new CoverageMarkdownRenderer())
            ->render((new CoverageCalculator())->calculate($matrix), $matrix->syllabusRevision);

        self::assertStringContainsString('EXAM_READY atomic official items / total atomic official items * 100', $markdown);
        self::assertStringContainsString('rev-2026-01', $markdown);
        self::assertStringContainsString('100%', $markdown);
    }
}
