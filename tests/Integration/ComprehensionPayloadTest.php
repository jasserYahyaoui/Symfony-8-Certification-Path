<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Build\PayloadBuilder;
use CertPath\Domain\Pool;
use CertPath\Support\CourseUrl;
use CertPath\Support\Project;
use CertPath\Validation\ContentSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The data contract of the comprehension checks (ADR-0010), held against the
 * REAL corpus: the payload carries the comprehension banks and nothing else,
 * and every link it ships resolves.
 */
#[CoversClass(PayloadBuilder::class)]
#[CoversClass(CourseUrl::class)]
final class ComprehensionPayloadTest extends TestCase
{
    private static function content(): ContentSet
    {
        static $content = null;

        return $content ??= (new Project(\dirname(__DIR__, 2)))->loadContentSet();
    }

    /** @return array<string, mixed> */
    private static function payload(): array
    {
        static $payload = null;

        return $payload ??= (new PayloadBuilder())->comprehensionPayload(self::content());
    }

    public function testThePayloadCarriesEveryComprehensionQuestionAndNoOther(): void
    {
        $payload = self::payload();

        self::assertSame(Pool::Comprehension->value, $payload['pool']);
        self::assertCount(\count(self::content()->comprehension), $payload['questions']);

        $examIds = array_map(static fn ($q): string => $q->id->value, self::content()->questions);
        foreach ($payload['questions'] as $question) {
            self::assertNotContains($question['id'], $examIds, 'an exam question reached comprehension.json');
        }

        // Does not throw: the build-time assertion agrees.
        PayloadBuilder::assertComprehensionIsolated($payload, self::content());
        PayloadBuilder::assertNoHoldoutLeak($payload, self::content());
    }

    public function testAnExamQuestionSmuggledIntoThePayloadFailsTheBuild(): void
    {
        $payload = self::payload();
        $exam = self::content()->questions[0];
        self::assertNotSame(Pool::Holdout, $exam->pool, 'the fixture must not print a holdout id');
        $payload['questions'][] = ['id' => $exam->id->value];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/reached the comprehension payload/');

        PayloadBuilder::assertComprehensionIsolated($payload, self::content());
    }

    public function testADroppedQuestionFailsTheBuild(): void
    {
        $payload = self::payload();
        array_pop($payload['questions']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/carries \d+ questions; the banks hold \d+/');

        PayloadBuilder::assertComprehensionIsolated($payload, self::content());
    }

    public function testEveryQuestionResolvesItsItemsOutcomesAndLot(): void
    {
        $payload = self::payload();

        foreach ($payload['questions'] as $question) {
            self::assertArrayHasKey($question['lot'], $payload['lots'], $question['id']);
            foreach ([$question['official_item'], ...$question['related_items']] as $itemId) {
                self::assertArrayHasKey($itemId, $payload['items'], $question['id']);
            }
            self::assertNotEmpty($question['assesses_outcomes'], $question['id']);
            foreach ($question['assesses_outcomes'] as $outcome) {
                self::assertArrayHasKey($outcome, $payload['outcomes'], $question['id']);
            }
        }
    }

    /**
     * The page and the payload name the same route: CourseUrl derives both, and
     * the generator writes the page at that path.
     */
    public function testEveryLotUrlIsTheGeneratedPage(): void
    {
        foreach (self::payload()['lots'] as $lot => $entry) {
            self::assertSame(CourseUrl::forComprehension($lot), $entry['url']);
            self::assertSame('/docs/'.CourseUrl::comprehensionPath($lot), $entry['url']);
            self::assertGreaterThan(0, $entry['questions']);
        }
    }
}
