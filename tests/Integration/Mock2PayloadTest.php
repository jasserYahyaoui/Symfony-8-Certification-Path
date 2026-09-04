<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Build\PayloadBuilder;
use CertPath\Domain\Pool;
use CertPath\Support\Project;
use PHPUnit\Framework\TestCase;

/**
 * Master Plan §10 — Mock 2, Application.
 *
 * The payload carries the eligible pool rather than the sitting, because the
 * blueprint's count is deliberately smaller than the pool so two consecutive
 * sittings differ. What is checked here is that the pool matches the blueprint,
 * that a sitting can actually be drawn from it, and that nothing official is
 * claimed by a format this project decided.
 */
final class Mock2PayloadTest extends TestCase
{
    private const string MOCK = 'mock-2';

    /** @return array{0: array<string, mixed>, 1: \CertPath\Validation\ContentSet, 2: array<string, mixed>} */
    private function fixture(): array
    {
        $project = Project::locate();
        $content = $project->loadContentSet();
        $blueprint = $project->loadMocksBlueprint();

        self::assertNotSame([], $blueprint, 'the mocks blueprint must exist');

        return [
            (new PayloadBuilder())->trainingMockPayload($content, $blueprint, self::MOCK),
            $content,
            $blueprint,
        ];
    }

    public function testThePayloadCarriesTheEligiblePoolAndTheInternalFormat(): void
    {
        [$payload, , $blueprint] = $this->fixture();
        $spec = PayloadBuilder::mockSpec($blueprint, self::MOCK);

        self::assertCount($spec['eligible_questions'], $payload['questions']);
        self::assertSame($spec['question_count'], $payload['question_count']);
        self::assertSame($spec['duration_minutes'], $payload['duration_minutes']);
        self::assertSame(Pool::Validation->value, $payload['pool']);

        // §10 fixes a count and a duration for Mock 4 only, so nothing here may
        // read as official.
        self::assertSame('INTERNAL_TRAINING_FORMAT', $payload['format_label']);
        self::assertSame('TRAINING_DISTRIBUTION', $payload['distribution_label']);
        self::assertStringContainsString('never an official constraint', $payload['not_official']);
    }

    public function testTheSittingIsSmallerThanThePoolSoTwoSittingsDiffer(): void
    {
        [$payload] = $this->fixture();

        self::assertLessThan(
            \count($payload['questions']),
            $payload['question_count'],
            'a sitting that used the whole pool would repeat itself exactly',
        );
    }

    /** Every question is VALIDATION, English, and on its own atomic item. */
    public function testEveryEligibleQuestionIsAValidationQuestionOnItsOwnItem(): void
    {
        [$payload, $content] = $this->fixture();

        $byId = [];
        foreach ($content->questions as $question) {
            $byId[$question->id->value] = $question;
        }

        $items = [];
        foreach ($payload['questions'] as $exported) {
            $question = $byId[$exported['id']] ?? null;

            self::assertNotNull($question);
            self::assertSame(Pool::Validation, $question->pool);
            self::assertSame('en', $question->language->value);
            self::assertTrue(
                'DIAGNOSE' === $question->examSkill || 'APPLY' === $question->cognitiveLevel,
                $question->id->value.' is neither a diagnosis nor an application question',
            );
            self::assertArrayNotHasKey($question->officialItemId, $items, 'two eligible questions share an atomic item');

            $items[$question->officialItemId] = true;
        }
    }

    /** The spread sums to the sitting and every topic it names can be filled. */
    public function testTheTopicSpreadCanBeFilledFromThePool(): void
    {
        [$payload, $content, $blueprint] = $this->fixture();
        $spec = PayloadBuilder::mockSpec($blueprint, self::MOCK);

        self::assertSame($spec['question_count'], array_sum($payload['topic_spread']));

        $available = [];
        foreach ($payload['questions'] as $exported) {
            $available[$exported['official_topic']] = ($available[$exported['official_topic']] ?? 0) + 1;
        }

        foreach ($payload['topic_spread'] as $topic => $wanted) {
            self::assertGreaterThanOrEqual(1, $wanted, $topic.' would be absent from a sitting');
            self::assertGreaterThanOrEqual($wanted, $available[$topic] ?? 0, $topic.' cannot be filled');
        }

        PayloadBuilder::assertTrainingMockMatchesBlueprint($payload, $content, $blueprint, self::MOCK);
    }

    /** The analysis §10 requires needs the outcomes; the payload is their only source. */
    public function testEveryItemIsIndexedWithItsLearningOutcomes(): void
    {
        [$payload] = $this->fixture();

        foreach ($payload['questions'] as $exported) {
            self::assertArrayHasKey($exported['official_item'], $payload['items']);
        }

        foreach ($payload['items'] as $id => $entry) {
            self::assertNotSame([], $entry['learning_outcomes'], $id.' has no outcome to report against');
        }
    }

    /** §10 reserves the holdout for Mock 4; ADR-0005 spent it there entirely. */
    public function testNoHoldoutQuestionCanReachIt(): void
    {
        [$payload, $content] = $this->fixture();

        PayloadBuilder::assertNoHoldoutLeak($payload, $content);

        // And the filter refuses the holdout even when a blueprint asks for it:
        // this spec would select 30 hard holdout questions if the guard in
        // eligibleFor() were removed.
        $eligible = PayloadBuilder::eligibleFor($content, [
            'eligible_filter' => ['pool' => Pool::Holdout->value, 'difficulty' => 'hard'],
        ]);

        self::assertSame([], $eligible, 'a holdout filter must yield nothing');
    }

    /**
     * §10 wants code, configuration and scenarios. The blueprint declined the
     * LEARNING reuse it permits because the VALIDATION pool already carries
     * them; that claim is measured here rather than trusted.
     */
    public function testThePoolActuallyCarriesCodeOrScenarios(): void
    {
        [$payload, , $blueprint] = $this->fixture();
        $fit = PayloadBuilder::mockSpec($blueprint, self::MOCK)['purpose_fit'];

        // A union is at least its largest part and at most their sum.
        self::assertGreaterThanOrEqual(max($fit['code_bearing'], $fit['scenario_phrased']), $fit['either']);
        self::assertLessThanOrEqual($fit['code_bearing'] + $fit['scenario_phrased'], $fit['either']);

        self::assertGreaterThan(
            \count($payload['questions']) / 2,
            $fit['either'],
            'fewer than half the eligible questions carry code or a scenario, so the pool does not serve §10\'s Application role',
        );
    }

    public function testAPoolTooSmallForTheSittingIsRejected(): void
    {
        [$payload, $content, $blueprint] = $this->fixture();

        $payload['questions'] = \array_slice($payload['questions'], 0, 5);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('eligible questions, the blueprint measured');

        PayloadBuilder::assertTrainingMockMatchesBlueprint($payload, $content, $blueprint, self::MOCK);
    }
}
