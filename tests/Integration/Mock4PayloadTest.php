<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Build\PayloadBuilder;
use CertPath\Domain\Pool;
use CertPath\Support\Project;
use PHPUnit\Framework\TestCase;

/**
 * Master Plan §10 — the payload Mock 4 is served from.
 *
 * This is the only published payload that carries the holdout, so it is the
 * one place where "the holdout is never deployed" stops being true. What
 * replaces that sentence is not a weaker rule but a narrower and stricter one:
 * the learning payloads still carry none, and this payload must match the
 * blueprint exactly — a question missing from it is as much a failure as a
 * question leaking out of it.
 */
final class Mock4PayloadTest extends TestCase
{
    /** @return array{0: array<string, mixed>, 1: \CertPath\Validation\ContentSet, 2: array<string, mixed>} */
    private function fixture(): array
    {
        $project = Project::locate();
        $content = $project->loadContentSet();
        $blueprint = $project->loadMockBlueprint('4');

        self::assertNotSame([], $blueprint, 'the Mock 4 blueprint must exist');

        return [(new PayloadBuilder())->mockPayload($content, $blueprint), $content, $blueprint];
    }

    public function testThePayloadCarriesTheOfficialFormatAndTheSeventyFive(): void
    {
        [$payload, , $blueprint] = $this->fixture();

        self::assertSame(75, $blueprint['official_constraints']['questions']);
        self::assertCount(75, $payload['questions']);
        self::assertSame(75, $payload['question_count']);
        self::assertSame(90, $payload['duration_minutes']);
        self::assertSame('en', $payload['language']);
        self::assertSame(Pool::Holdout->value, $payload['pool']);

        // §7.4: an internal distribution is never presented as official.
        self::assertSame('TRAINING_DISTRIBUTION', $payload['distribution_label']);
    }

    public function testEveryQuestionIsAnEnglishHoldoutQuestionOnItsOwnAtomicItem(): void
    {
        [$payload, $content] = $this->fixture();

        $byId = [];
        foreach ($content->questions as $question) {
            $byId[$question->id->value] = $question;
        }

        $items = [];
        foreach ($payload['questions'] as $exported) {
            $question = $byId[$exported['id']] ?? null;

            self::assertNotNull($question, $exported['id'].' is not a known question');
            self::assertSame(Pool::Holdout, $question->pool);
            self::assertSame('en', $question->language->value);
            self::assertArrayNotHasKey($question->officialItemId, $items, 'two questions for one atomic item');

            $items[$question->officialItemId] = true;
        }

        self::assertCount(75, $items);
    }

    /**
     * The analysis §10 asks for is per topic *and* per learning outcome, so the
     * payload has to carry the outcomes: the page has no other source for them.
     */
    public function testEveryCoveredItemIsIndexedWithItsLearningOutcomes(): void
    {
        [$payload, $content] = $this->fixture();

        foreach ($payload['questions'] as $exported) {
            self::assertArrayHasKey(
                $exported['official_item'],
                $payload['items'],
                $exported['official_item'].' is not in the item index, so its result cannot be explained',
            );
        }

        foreach ($payload['items'] as $id => $entry) {
            $item = $content->matrix->findById((string) $id);

            self::assertNotNull($item);
            self::assertSame($item->officialItem, $entry['official_item']);
            self::assertSame($item->officialTopic, $entry['official_topic']);
            self::assertSame($item->learningOutcomes, $entry['learning_outcomes']);
            self::assertNotSame([], $entry['learning_outcomes'], $id.' has no learning outcome to report against');
        }
    }

    public function testTheTopicSpreadIsTheBlueprintSpread(): void
    {
        [$payload, , $blueprint] = $this->fixture();

        $perTopic = [];
        foreach ($payload['questions'] as $exported) {
            $perTopic[$exported['official_topic']] = ($perTopic[$exported['official_topic']] ?? 0) + 1;
        }

        foreach ($blueprint['topics'] as $row) {
            self::assertSame(
                $row['slots'],
                $perTopic[$row['topic']] ?? 0,
                $row['topic'].' does not carry the number of questions the blueprint allots it',
            );
        }
    }

    public function testTheLearningPayloadsStillCarryNoHoldoutQuestion(): void
    {
        $content = Project::locate()->loadContentSet();
        $builder = new PayloadBuilder();

        foreach ([$builder->practicePayload($content), $builder->examPayload($content)] as $payload) {
            PayloadBuilder::assertNoHoldoutLeak($payload, $content);
        }

        self::assertTrue(true, 'no holdout question reaches a learning payload');
    }

    public function testAMockShortOfAQuestionIsRejected(): void
    {
        [$payload, $content, $blueprint] = $this->fixture();

        array_pop($payload['questions']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('carries 74 questions, the blueprint requires 75');

        PayloadBuilder::assertMockMatchesBlueprint($payload, $content, $blueprint);
    }

    public function testAMockCarryingAServedQuestionIsRejected(): void
    {
        [$payload, $content, $blueprint] = $this->fixture();

        $served = null;
        foreach ($content->questions as $question) {
            if (Pool::Holdout !== $question->pool) {
                $served = $question;
                break;
            }
        }

        self::assertNotNull($served);

        array_pop($payload['questions']);
        $payload['questions'][] = ['id' => $served->id->value];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('the mock is the holdout');

        PayloadBuilder::assertMockMatchesBlueprint($payload, $content, $blueprint);
    }

    public function testAMockThatMovesAQuestionBetweenTopicsIsRejected(): void
    {
        [$payload, $content, $blueprint] = $this->fixture();

        // Swap one question for a second one on a topic that is already full:
        // the count stays 75, so only the per-topic check can catch it.
        $counts = [];
        foreach ($payload['questions'] as $exported) {
            $counts[$exported['official_topic']][] = $exported['id'];
        }

        $topics = array_keys($counts);
        $donor = $counts[$topics[0]][0];
        $receiver = $counts[$topics[1]][0];

        foreach ($payload['questions'] as $i => $exported) {
            if ($exported['id'] === $donor) {
                $payload['questions'][$i] = ['id' => $receiver];
            }
        }

        $this->expectException(\LogicException::class);

        PayloadBuilder::assertMockMatchesBlueprint($payload, $content, $blueprint);
    }
}
