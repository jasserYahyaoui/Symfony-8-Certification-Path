<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Build\PayloadBuilder;
use CertPath\Domain\Pool;
use CertPath\Support\Project;
use PHPUnit\Framework\TestCase;

/**
 * Master Plan §10 — Mock 5, weakness-based.
 *
 * Mock 5 has no fixed selection, so there is no sitting to check here. What the
 * payload owes is the candidate universe, the bounds, and the fallback that
 * refuses to run: the selection itself is made in the browser against the
 * learner's own history, which this build has no access to and must not
 * simulate.
 */
final class Mock5PayloadTest extends TestCase
{
    /** @return array{0: array<string, mixed>, 1: \CertPath\Validation\ContentSet} */
    private function fixture(): array
    {
        $project = Project::locate();
        $content = $project->loadContentSet();
        $blueprint = $project->loadMocksBlueprint();

        self::assertNotSame([], $blueprint);

        return [(new PayloadBuilder())->weaknessMockPayload($content, $blueprint), $content];
    }

    public function testTheCandidateUniverseIsEveryServedQuestion(): void
    {
        [$payload, $content] = $this->fixture();

        $served = 0;
        foreach ($content->questions as $question) {
            if (Pool::Holdout !== $question->pool) {
                ++$served;
            }
        }

        self::assertGreaterThan(0, $served);
        self::assertCount($served, $payload['questions'], 'the candidate universe is every non-holdout question');
        self::assertSame('VALIDATION+LEARNING', $payload['pool']);
    }

    /** §10 reserves the holdout for Mock 4; ADR-0005 spent it there entirely. */
    public function testNoHoldoutQuestionIsACandidate(): void
    {
        [$payload, $content] = $this->fixture();

        PayloadBuilder::assertNoHoldoutLeak($payload, $content);

        $holdout = [];
        foreach ($content->questions as $question) {
            if (Pool::Holdout === $question->pool) {
                $holdout[$question->id->value] = true;
            }
        }

        self::assertNotSame([], $holdout, 'no holdout question exists, so the check would pass vacuously');

        foreach ($payload['questions'] as $exported) {
            self::assertArrayNotHasKey($exported['id'], $holdout);
        }
    }

    /**
     * The bounds are the blueprint's, and the minimum is what the fallback
     * turns on. A payload that shipped a different one would let the page run
     * a sitting the blueprint says is not supported by evidence.
     */
    public function testTheBoundsAndTheFallbackComeFromTheBlueprint(): void
    {
        [$payload] = $this->fixture();
        $spec = PayloadBuilder::mockSpec(Project::locate()->loadMocksBlueprint(), 'mock-5');

        self::assertSame(10, $payload['minimum_questions']);
        self::assertSame(40, $payload['maximum_questions']);
        self::assertSame($spec['fallback']['name'], $payload['fallback']['name']);
        self::assertSame('INSUFFICIENT_EVIDENCE_FALLBACK', $payload['fallback']['name']);

        self::assertStringContainsString('does not pretend to be weakness-based', $payload['fallback']['behaviour']);
        self::assertStringContainsString('Never invent a weakness profile', $payload['fallback']['forbidden']);
    }

    /** The analysis needs the outcomes, and every candidate item must have some. */
    public function testEveryCandidateItemIsIndexedWithItsLearningOutcomes(): void
    {
        [$payload] = $this->fixture();

        foreach ($payload['questions'] as $exported) {
            self::assertArrayHasKey($exported['official_item'], $payload['items']);
        }

        foreach ($payload['items'] as $id => $entry) {
            self::assertNotSame([], $entry['learning_outcomes'], $id.' has no outcome to report against');
        }
    }

    /** Nothing about Mock 5 may read as an official format. */
    public function testNothingIsPresentedAsOfficial(): void
    {
        [$payload] = $this->fixture();

        self::assertSame('INTERNAL_TRAINING_FORMAT', $payload['format_label']);
        self::assertStringContainsString('never an official constraint', $payload['not_official']);
    }

    /**
     * One blueprint signal is deliberately not implemented, and the blueprint
     * says so. If someone implements it, this test is the reminder to remove
     * the annotation rather than leave a stale claim behind.
     */
    public function testTheUnimplementedSignalIsDeclaredAsSuch(): void
    {
        [$payload] = $this->fixture();

        $declared = false;
        foreach ($payload['weakness_evidence'] as $signal) {
            if (str_contains($signal, 'NOT_IMPLEMENTED')) {
                $declared = true;
                self::assertStringContainsString('per-question elapsed time', $signal);
            }
        }

        self::assertTrue($declared, 'the timing signal must be declared NOT_IMPLEMENTED while it is not computed');
    }
}
