<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Build\PayloadBuilder;
use CertPath\Domain\Pool;
use CertPath\Support\Project;
use PHPUnit\Framework\TestCase;

/**
 * The numbered mock exams (/examens), owner's request of 2026-10-14: official
 * format, six sittings that share no question, chosen from a menu.
 *
 * Every guarantee the menu states is asserted here on the real bank, and the
 * build-time check is shown able to refuse a broken series: a check that has
 * only ever passed has not been shown to check anything.
 */
final class ExamSeriesPayloadTest extends TestCase
{
    /** @return array{0: array<string, mixed>, 1: \CertPath\Validation\ContentSet} */
    private function fixture(): array
    {
        $project = Project::locate();
        $content = $project->loadContentSet();

        return [(new PayloadBuilder())->examSeriesPayload($content, $project->loadMockBlueprint('4')), $content];
    }

    public function testSixExamsInTheOfficialFormat(): void
    {
        [$payload] = $this->fixture();

        self::assertSame(75, $payload['question_count']);
        self::assertSame(90, $payload['duration_minutes']);
        self::assertSame('en', $payload['language']);
        self::assertCount(PayloadBuilder::EXAM_SERIES_SIZE, $payload['exams']);
        self::assertSame(75, array_sum($payload['topic_spread']));
        foreach ($payload['exams'] as $exam) {
            self::assertCount(75, $exam['question_ids']);
        }
    }

    public function testTheSixExamsShareNoQuestionAndNeverAskTwiceOnOneItem(): void
    {
        [$payload, $content] = $this->fixture();

        PayloadBuilder::assertExamSeries($payload, $content);

        $all = array_merge(...array_column($payload['exams'], 'question_ids'));
        self::assertSame(\count($all), \count(array_unique($all)));
    }

    public function testTheHoldoutStaysMockFoursAlone(): void
    {
        [$payload, $content] = $this->fixture();

        $holdout = [];
        foreach ($content->questions as $q) {
            if (Pool::Holdout === $q->pool) {
                $holdout[$q->id->value] = true;
            }
        }
        foreach ($payload['questions'] as $q) {
            self::assertArrayNotHasKey($q['id'], $holdout, 'a holdout question reached exams.json');
        }
    }

    public function testEveryTopicIsInEveryExam(): void
    {
        [$payload] = $this->fixture();

        $topics = [];
        foreach ($payload['questions'] as $q) {
            $topics[$q['id']] = $q['official_topic'];
        }
        foreach ($payload['exams'] as $exam) {
            $present = array_unique(array_map(static fn (string $id): string => $topics[$id], $exam['question_ids']));
            sort($present);
            self::assertSame(array_keys($payload['topic_spread']), $present);
        }
    }

    /** The build-time check refuses a series in which two exams share a question. */
    public function testTheBuildCheckRefusesASharedQuestion(): void
    {
        [$payload, $content] = $this->fixture();
        $payload['exams'][1]['question_ids'][0] = $payload['exams'][0]['question_ids'][0];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/share a question|asks twice|topic spread/');
        PayloadBuilder::assertExamSeries($payload, $content);
    }

    /** …and one that is a question short. */
    public function testTheBuildCheckRefusesAShortExam(): void
    {
        [$payload, $content] = $this->fixture();
        array_pop($payload['exams'][2]['question_ids']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/has 74 questions/');
        PayloadBuilder::assertExamSeries($payload, $content);
    }
}
