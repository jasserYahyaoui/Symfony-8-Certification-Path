<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Domain\Classification;
use CertPath\Domain\Pool;
use CertPath\Domain\Question;
use CertPath\Domain\VerificationStatus;
use CertPath\Support\Project;
use PHPUnit\Framework\TestCase;

/**
 * Master Plan §10 — Unit B, the 48 questions the Mock 4 blueprint still needs.
 *
 * Unit B is written in batches, so the blueprint's constraints have to hold
 * after every batch rather than only at the end: a batch that quietly spends
 * two `hard` slots or writes a second question for an atomic item is cheap to
 * fix on the day and expensive to find at 48. The blueprint itself supplies
 * the denominator — a new question is a HOLDOUT question that the blueprint
 * does not list among its 27 assigned ones.
 */
final class Mock4HoldoutBatchTest extends TestCase
{
    private const int NEW_REQUIRED = 48;

    /** @return array<string, mixed> */
    private function blueprint(): array
    {
        return Project::locate()->loadMockBlueprint('4');
    }

    /**
     * @return array{assigned: array<string, true>, items: array<string, true>, rows: array<string, array<string, mixed>>}
     */
    private function plan(): array
    {
        $assigned = [];
        $items = [];
        $rows = [];

        foreach ($this->blueprint()['topics'] as $row) {
            $rows[$row['topic']] = $row;
            foreach ($row['assigned_existing'] as $entry) {
                $assigned[$entry['id']] = true;
                $items[$entry['official_item']] = true;
            }
        }

        return ['assigned' => $assigned, 'items' => $items, 'rows' => $rows];
    }

    /**
     * @return list<Question>
     */
    private function newQuestions(): array
    {
        $assigned = $this->plan()['assigned'];

        $new = [];
        foreach (Project::locate()->loadContentSet()->questions as $question) {
            if (Pool::Holdout === $question->pool && !isset($assigned[$question->id->value])) {
                $new[] = $question;
            }
        }

        return $new;
    }

    public function testNoMoreThanFortyEightNewHoldoutQuestionsExist(): void
    {
        self::assertLessThanOrEqual(
            self::NEW_REQUIRED,
            \count($this->newQuestions()),
            'more new holdout questions exist than Mock 4 has slots for',
        );
    }

    public function testEveryNewQuestionIsAnOfficialEnglishVerifiedHoldoutQuestion(): void
    {
        foreach ($this->newQuestions() as $question) {
            $id = $question->id->value;

            self::assertSame(Pool::Holdout, $question->pool, $id.' is not in the HOLDOUT pool');
            self::assertSame('en', $question->language->value, $id.' is not English (§10)');
            self::assertSame(Classification::Official, $question->classification, $id.' is not OFFICIAL');
            self::assertSame(
                VerificationStatus::Verified,
                $question->verificationStatus,
                $id.' is scored without being VERIFIED (§2.5)',
            );
        }
    }

    /**
     * `max_questions_per_atomic_item: 1` covers the whole sitting, so a new
     * question may neither share an item with another new one nor with one of
     * the 27 the blueprint already assigns.
     */
    public function testNoAtomicItemCarriesTwoMockFourQuestions(): void
    {
        $alreadyAssigned = $this->plan()['items'];

        $seen = [];
        foreach ($this->newQuestions() as $question) {
            $item = $question->officialItemId;
            $id = $question->id->value;

            self::assertArrayNotHasKey(
                $item,
                $alreadyAssigned,
                $id.' maps to '.$item.', which one of the 27 assigned questions already covers',
            );
            self::assertArrayNotHasKey($item, $seen, $id.' maps to '.$item.', already covered by '.($seen[$item] ?? ''));

            $seen[$item] = $id;
        }
    }

    /**
     * A batch may fill fewer slots than its topic has, never more.
     */
    public function testNoTopicHasMoreNewQuestionsThanItsGap(): void
    {
        $rows = $this->plan()['rows'];
        $matrix = Project::locate()->loadMatrix();

        $topicOf = [];
        foreach ($matrix->officialItems() as $item) {
            $topicOf[$item->id->value] = $item->officialTopic;
        }

        $written = [];
        foreach ($this->newQuestions() as $question) {
            $topic = $topicOf[$question->officialItemId] ?? null;
            self::assertNotNull($topic, $question->id->value.' maps to an item outside the official matrix');

            $written[$topic] = ($written[$topic] ?? 0) + 1;
        }

        foreach ($written as $topic => $count) {
            self::assertArrayHasKey($topic, $rows, $topic.' is not a topic the blueprint plans for');
            self::assertLessThanOrEqual(
                $rows[$topic]['new_required'],
                $count,
                $topic.' has more new questions than the blueprint leaves slots for',
            );
        }
    }

    /**
     * The 27 existing questions are all `hard`, which is why the blueprint
     * leaves the 48 exactly 5 easy, 40 medium and 3 hard. Overshooting a
     * bucket early cannot be repaired later without rewriting a batch.
     */
    public function testNoDifficultyBucketIsOverspent(): void
    {
        $target = $this->blueprint()['difficulty_target'];
        $budget = [
            'easy' => $target['easy'],
            'medium' => $target['medium'],
            'hard' => $target['hard'] - 27,
        ];

        $spent = ['easy' => 0, 'medium' => 0, 'hard' => 0];
        foreach ($this->newQuestions() as $question) {
            self::assertArrayHasKey(
                $question->difficulty,
                $spent,
                $question->id->value.' declares an unknown difficulty "'.$question->difficulty.'"',
            );
            ++$spent[$question->difficulty];
        }

        foreach ($budget as $difficulty => $allowed) {
            self::assertLessThanOrEqual(
                $allowed,
                $spent[$difficulty],
                \sprintf('%d new questions are %s; the blueprint leaves %d', $spent[$difficulty], $difficulty, $allowed),
            );
        }
    }

    /**
     * 90 minutes is a hard constraint, so the running mean of what has been
     * written must stay under the ceiling the blueprint derived — a batch that
     * exceeds it forces every later batch to compensate.
     */
    public function testTheRunningMeanStaysUnderTheTimeCeiling(): void
    {
        $new = $this->newQuestions();
        if ([] === $new) {
            self::markTestSkipped('no new question written yet');
        }

        $ceiling = (float) $this->blueprint()['timing']['new_question_mean_ceiling_seconds'];

        $total = 0;
        foreach ($new as $question) {
            $total += $question->estimatedTimeSeconds;
        }

        self::assertLessThanOrEqual(
            $ceiling,
            $total / \count($new),
            \sprintf('the %d new questions average %.1fs, over the %.1fs ceiling', \count($new), $total / \count($new), $ceiling),
        );
    }
}
