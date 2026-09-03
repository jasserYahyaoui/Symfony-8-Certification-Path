<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Domain\Pool;
use CertPath\Support\Project;
use PHPUnit\Framework\TestCase;

/**
 * Master Plan §10 — the Mock 4 blueprint.
 *
 * The blueprint is a specification, so it is checked like one: every figure in
 * it must follow from the canonical data rather than from whoever typed it.
 * These tests are the reason Unit A ships before any of the 48 questions is
 * written — a blueprint nothing verifies would send Unit B in the wrong
 * direction 48 times over.
 */
final class Mock4BlueprintTest extends TestCase
{
    private const int TOTAL_SLOTS = 75;
    private const int MINIMUM_PER_TOPIC = 2;
    private const int DURATION_SECONDS = 90 * 60;

    /** @return array<string, mixed> */
    private function blueprint(): array
    {
        $blueprint = Project::locate()->loadMockBlueprint('4');
        self::assertNotSame([], $blueprint, 'the Mock 4 blueprint must exist');

        return $blueprint;
    }

    public function testTheBlueprintCarriesTheOfficialConstraints(): void
    {
        $constraints = $this->blueprint()['official_constraints'];

        self::assertSame(75, $constraints['questions']);
        self::assertSame(90, $constraints['minutes']);
        self::assertSame('en', $constraints['language']);
        self::assertSame('8.0', $constraints['symfony']);
    }

    /**
     * §7.4 and §10: an internal distribution is never presented as official.
     */
    public function testTheDistributionIsLabelledTraining(): void
    {
        $blueprint = $this->blueprint();

        self::assertSame('TRAINING_DISTRIBUTION', $blueprint['distribution_label']);
        self::assertStringContainsString('never be presented as official', $blueprint['derivation']);
    }

    public function testSlotsSumToSeventyFiveAndCoverEveryTopic(): void
    {
        $blueprint = $this->blueprint();
        $matrix = Project::locate()->loadMatrix();

        $topicsInMatrix = [];
        foreach ($matrix->officialItems() as $item) {
            $topicsInMatrix[$item->officialTopic] = true;
        }

        $slots = 0;
        $seen = [];
        foreach ($blueprint['topics'] as $row) {
            $slots += $row['slots'];
            $seen[$row['topic']] = true;

            self::assertGreaterThanOrEqual(
                self::MINIMUM_PER_TOPIC,
                $row['slots'],
                $row['topic'].' falls below the minimum, so it could vanish from a sitting',
            );
        }

        self::assertSame(self::TOTAL_SLOTS, $slots);

        // Compared as sets: the blueprint orders topics by size so the biggest
        // gaps read first, the matrix by official order. Neither ordering is a
        // requirement, and asserting one would fail on a cosmetic reshuffle.
        $expected = array_keys($topicsInMatrix);
        $actual = array_keys($seen);
        sort($expected);
        sort($actual);
        self::assertSame($expected, $actual, 'every examinable topic needs slots');
        self::assertArrayHasKey('Miscellaneous', $seen, 'Miscellaneous is the largest topic and had no holdout question');
    }

    /**
     * The slot count per topic must be reproducible from the 163 atomic items,
     * not asserted. This recomputes it and compares.
     */
    public function testEveryTopicSlotCountFollowsFromTheAtomicItems(): void
    {
        $blueprint = $this->blueprint();

        foreach ($blueprint['topics'] as $row) {
            $expected = round(self::TOTAL_SLOTS * $row['atomic_items'] / 163, 2);

            self::assertSame(
                $expected,
                round((float) $row['proportional'], 2),
                $row['topic'].': the recorded proportional share does not follow from its atomic items',
            );
            self::assertLessThanOrEqual(
                $row['atomic_items'],
                $row['slots'],
                $row['topic'].' has more slots than atomic items, so one item would carry two questions',
            );
        }
    }

    /**
     * Every question the blueprint assigns must really exist, really be HOLDOUT,
     * really be English, and sit in the topic it is filed under. A `pool:
     * HOLDOUT` label on its own has never been sufficient evidence here.
     */
    public function testAssignedQuestionsAreRealEnglishHoldoutQuestions(): void
    {
        $blueprint = $this->blueprint();
        $content = Project::locate()->loadContentSet();

        $byId = [];
        foreach ($content->questions as $question) {
            $byId[$question->id->value] = $question;
        }

        $topicOf = [];
        foreach ($content->matrix->officialItems() as $item) {
            $topicOf[$item->id->value] = $item->officialTopic;
        }

        $assigned = 0;
        $ids = [];
        foreach ($blueprint['topics'] as $row) {
            foreach ($row['assigned_existing'] as $entry) {
                ++$assigned;
                $ids[] = $entry['id'];

                self::assertArrayHasKey($entry['id'], $byId, $entry['id'].' does not exist');
                $question = $byId[$entry['id']];

                self::assertSame(Pool::Holdout, $question->pool, $entry['id'].' is not in the HOLDOUT pool');
                self::assertSame('en', $question->language->value, $entry['id'].' is not English');
                self::assertSame($entry['official_item'], $question->officialItemId, $entry['id'].' is mapped elsewhere');
                self::assertSame($row['topic'], $topicOf[$question->officialItemId], $entry['id'].' is filed under the wrong topic');
            }
        }

        self::assertSame(27, $assigned, 'the blueprint must assign exactly the 27 existing holdout questions');
        self::assertSame($ids, array_unique($ids), 'a question is assigned to two slots');
    }

    public function testNoAtomicItemCarriesTwoAssignedQuestions(): void
    {
        $items = [];
        foreach ($this->blueprint()['topics'] as $row) {
            foreach ($row['assigned_existing'] as $entry) {
                $items[] = $entry['official_item'];
            }
        }

        self::assertSame($items, array_unique($items), 'an atomic item carries more than one question');
    }

    /**
     * The gap is what Unit B is briefed from, so it has to be arithmetic rather
     * than an estimate, and every topic must have enough untouched items left
     * to fill it without doubling up.
     */
    public function testTheGapIsExactAndFillable(): void
    {
        $blueprint = $this->blueprint();

        $new = 0;
        foreach ($blueprint['topics'] as $row) {
            $expected = $row['slots'] - \count($row['assigned_existing']);
            self::assertSame($expected, $row['new_required'], $row['topic'].': the recorded gap is wrong');

            self::assertGreaterThanOrEqual(
                $row['new_required'],
                $row['eligible_unused_items'],
                $row['topic'].' has fewer unused atomic items than questions to write',
            );
            $new += $row['new_required'];
        }

        self::assertSame(48, $new);
        self::assertSame(48, $blueprint['totals']['new_required']);
        self::assertSame(27 + 48, self::TOTAL_SLOTS);
    }

    /**
     * The existing 27 are all `hard` and all single-answer, so the targets exist
     * to stop the mock inheriting that shape by default.
     */
    public function testTheDifficultyAndAnswerModeTargetsAreReachable(): void
    {
        $blueprint = $this->blueprint();
        $difficulty = $blueprint['difficulty_target'];

        self::assertSame(
            self::TOTAL_SLOTS,
            $difficulty['easy'] + $difficulty['medium'] + $difficulty['hard'],
        );
        self::assertGreaterThanOrEqual(
            27,
            $difficulty['hard'],
            'the 27 assigned questions are all hard, so the target cannot ask for fewer',
        );
        self::assertGreaterThanOrEqual(1, $blueprint['answer_mode_target']['multiple_minimum']);
    }

    /**
     * §10 gives 75 questions in 90 minutes. The blueprint must leave the 48
     * unwritten questions a budget that a real question can be written inside.
     */
    public function testTheTimeBudgetFitsNinetyMinutes(): void
    {
        $timing = $this->blueprint()['timing'];

        self::assertSame(self::DURATION_SECONDS, $timing['budget_seconds']);
        self::assertLessThanOrEqual(72, $timing['target_mean_seconds']);
        self::assertSame(
            self::DURATION_SECONDS - $timing['existing_27_seconds'],
            $timing['remaining_budget_seconds'],
        );
        self::assertGreaterThan(
            30,
            $timing['new_question_mean_ceiling_seconds'],
            'the remaining budget must leave room for a real question',
        );
    }

    /**
     * The whole point of Option A: functional isolation is claimed, and
     * confidentiality is not. The blueprint may never drift from that.
     */
    public function testTheBlueprintAssignsOnlyQuestionsAbsentFromBothPayloads(): void
    {
        $content = Project::locate()->loadContentSet();

        $served = [];
        foreach ($content->questions as $question) {
            if (Pool::Holdout !== $question->pool) {
                $served[$question->id->value] = true;
            }
        }

        foreach ($this->blueprint()['topics'] as $row) {
            foreach ($row['assigned_existing'] as $entry) {
                self::assertArrayNotHasKey(
                    $entry['id'],
                    $served,
                    $entry['id'].' is also served by Practice or Exam Mode',
                );
            }
        }
    }
}
