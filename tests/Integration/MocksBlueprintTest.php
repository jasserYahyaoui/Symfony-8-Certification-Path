<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Domain\Pool;
use CertPath\Domain\Question;
use CertPath\Support\Project;
use PHPUnit\Framework\TestCase;

/**
 * Master Plan §10 — the blueprint for Mocks 1, 2, 3 and 5.
 *
 * §10 fixes a question count and a duration for Mock 4 only. Everything
 * numeric here is this project's decision, so it is checked like one: every
 * figure must follow from the bank rather than from whoever typed it, and no
 * value may be inherited from Mock 4 or presented as official.
 */
final class MocksBlueprintTest extends TestCase
{
    private const int DURATION_CEILING_SECONDS = 3600;
    private const float MARGIN = 1.15;

    /** @return array<string, mixed> */
    private function blueprint(): array
    {
        $blueprint = Project::locate()->loadMocksBlueprint();
        self::assertNotSame([], $blueprint, 'the Mocks 1/2/3/5 blueprint must exist');

        return $blueprint;
    }

    /** @return list<Question> */
    private function eligible(array $filter): array
    {
        $questions = Project::locate()->loadContentSet()->questions;

        return array_values(array_filter($questions, static function (Question $q) use ($filter): bool {
            if (Pool::Validation !== $q->pool) {
                return false;
            }

            if (isset($filter['exam_skill_in'])) {
                return \in_array($q->examSkill, $filter['exam_skill_in'], true);
            }

            if (isset($filter['difficulty'])) {
                return $q->difficulty === $filter['difficulty'];
            }

            if (isset($filter['exam_skill'], $filter['or_cognitive_level'])) {
                return $q->examSkill === $filter['exam_skill']
                    || $q->cognitiveLevel === $filter['or_cognitive_level'];
            }

            return false;
        }));
    }

    /** §10, and the labels the Master Plan imposes on anything this project decides. */
    public function testNothingIsPresentedAsOfficial(): void
    {
        $blueprint = $this->blueprint();

        self::assertSame('INTERNAL_TRAINING_FORMAT', $blueprint['format_label']);
        self::assertSame('TRAINING_DISTRIBUTION', $blueprint['distribution_label']);
        self::assertStringContainsString('never an official constraint', $blueprint['not_official']);

        foreach ($blueprint['mocks'] as $mock) {
            self::assertSame('INTERNAL_TRAINING_FORMAT', $mock['label'], $mock['id'].' is not labelled');
        }
    }

    /**
     * The count rule is arithmetic, so it is recomputed rather than trusted.
     * A number chosen for being round would fail this.
     */
    public function testEveryCountAndDurationFollowsFromTheBank(): void
    {
        foreach ($this->blueprint()['mocks'] as $mock) {
            if ('mock-5' === $mock['id']) {
                continue; // generated per learner; covered separately below.
            }

            $eligible = $this->eligible($mock['eligible_filter']);
            $id = $mock['id'];

            self::assertSame($mock['eligible_questions'], \count($eligible), $id.': eligible count is wrong');
            self::assertNotSame([], $eligible, $id.': no eligible question, the rule would divide by zero');

            $mean = array_sum(array_map(static fn (Question $q): int => $q->estimatedTimeSeconds, $eligible)) / \count($eligible);
            self::assertEqualsWithDelta($mock['mean_estimated_seconds'], $mean, 0.05, $id.': mean time is wrong');

            $capVariation = (int) floor(\count($eligible) * 2 / 3);
            $capDuration = (int) floor(self::DURATION_CEILING_SECONDS / ($mean * self::MARGIN));

            self::assertSame($capVariation, $mock['cap_from_variation'], $id.': variation cap is wrong');
            self::assertSame($capDuration, $mock['cap_from_duration'], $id.': duration cap is wrong');
            self::assertSame(min($capVariation, $capDuration), $mock['question_count'], $id.': count is not the rule');
            self::assertSame(
                (int) ceil($mock['question_count'] * $mean * self::MARGIN / 60),
                $mock['duration_minutes'],
                $id.': duration is not the rule',
            );
        }
    }

    /** At least a third of each eligible pool stays unused, so two sittings differ. */
    public function testEachSittingLeavesRoomForTheNextOne(): void
    {
        foreach ($this->blueprint()['mocks'] as $mock) {
            if ('mock-5' === $mock['id']) {
                continue;
            }

            self::assertSame(
                $mock['eligible_questions'] - $mock['question_count'],
                $mock['unused_after_selection'],
                $mock['id'].': the unused figure does not add up',
            );
            self::assertGreaterThanOrEqual(
                $mock['eligible_questions'] / 3,
                $mock['unused_after_selection'],
                $mock['id'].': too little of the pool is left for a second sitting',
            );
        }
    }

    /** Every topic appears, the spread sums to the count, and no topic is over-drawn. */
    public function testTheTopicSpreadIsFillableAndComplete(): void
    {
        $matrix = Project::locate()->loadMatrix();
        $topicOf = [];
        foreach ($matrix->officialItems() as $item) {
            $topicOf[$item->id->value] = $item->officialTopic;
        }

        $allTopics = array_unique(array_values($topicOf));

        foreach ($this->blueprint()['mocks'] as $mock) {
            if ('mock-5' === $mock['id']) {
                continue;
            }

            $id = $mock['id'];
            $spread = $mock['topic_spread'];

            self::assertSame($mock['question_count'], array_sum($spread), $id.': the spread does not sum to the count');
            self::assertCount(\count($allTopics), $spread, $id.': a topic is missing from the spread');

            $available = [];
            foreach ($this->eligible($mock['eligible_filter']) as $question) {
                $topic = $topicOf[$question->officialItemId];
                $available[$topic] = ($available[$topic] ?? 0) + 1;
            }

            foreach ($spread as $topic => $wanted) {
                self::assertGreaterThanOrEqual(1, $wanted, $id.': '.$topic.' would be absent from a sitting');
                self::assertLessThanOrEqual(
                    $available[$topic] ?? 0,
                    $wanted,
                    $id.': '.$topic.' asks for more questions than the bank holds',
                );
            }
        }
    }

    /** §10 reserves HOLDOUT for Mock 4; ADR-0005 spent it there entirely. */
    public function testNoMockDrawsOnTheHoldout(): void
    {
        $blueprint = $this->blueprint();

        self::assertStringContainsString('NEVER', $blueprint['cross_mock_rules']['holdout_use']);

        foreach ($blueprint['mocks'] as $mock) {
            $filter = $mock['eligible_filter'];
            $pools = $filter['pool_in'] ?? [$filter['pool'] ?? ''];

            self::assertNotContains(Pool::Holdout->value, $pools, $mock['id'].' would draw on the holdout');
        }
    }

    /** Mock 5 is generated, so what is checked is that it cannot invent a weakness. */
    public function testMockFiveDeclaresItsEvidenceAndItsFallback(): void
    {
        $five = null;
        foreach ($this->blueprint()['mocks'] as $mock) {
            if ('mock-5' === $mock['id']) {
                $five = $mock;
            }
        }

        self::assertNotNull($five, 'Mock 5 is missing from the blueprint');
        self::assertNotSame([], $five['weakness_evidence']);

        $fallback = $five['fallback'];
        self::assertSame('INSUFFICIENT_EVIDENCE_FALLBACK', $fallback['name']);
        self::assertStringContainsString('does not pretend to be weakness-based', $fallback['behaviour']);
        self::assertStringContainsString('Never invent a weakness profile', $fallback['forbidden']);
    }

    /** §10 lists what every mock must report once it is submitted. */
    public function testTheRequiredReportingIsRecorded(): void
    {
        $reporting = $this->blueprint()['reporting_after_every_mock'];

        foreach (['score', 'time', 'unanswered', 'incorrect', 'topics', 'items', 'learning outcomes', 'next actions', 'readiness'] as $needle) {
            $found = false;
            foreach ($reporting as $line) {
                if (str_contains($line, $needle)) {
                    $found = true;
                    break;
                }
            }
            self::assertTrue($found, '§10 requires reporting on "'.$needle.'"');
        }
    }
}
