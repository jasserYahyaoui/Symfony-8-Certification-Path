<?php

declare(strict_types=1);

namespace CertPath\Build;

use CertPath\Domain\AnswerMode;
use CertPath\Domain\Choice;
use CertPath\Domain\OfficialItem;
use CertPath\Domain\Pool;
use CertPath\Domain\Question;
use CertPath\Support\CourseUrl;
use CertPath\Validation\ContentSet;

/**
 * Turns canonical content into the JSON payloads the static front-end loads.
 *
 * Pool isolation (§7.3, §17) is enforced *here*, at build time: the Practice
 * Mode payload is assembled from the learning pool only, so a holdout question
 * is not merely hidden by the UI — it is never written into the file the
 * Practice page fetches.
 */
final class PayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function practicePayload(ContentSet $content): array
    {
        $questions = array_values(array_filter(
            $content->questions,
            static fn (Question $q): bool => $q->mayAppearInPracticeMode(),
        ));

        return [
            'generated_at' => gmdate('c'),
            'pool' => Pool::Learning->value,
            // Lot 27: Practice Mode has to tell the learner WHICH concept they
            // missed and WHERE to revise it. Both are properties of the item,
            // not of the question, so they travel in the same index the mock
            // payloads already carry rather than being repeated on every
            // question — or, worse, retyped inside a React component.
            'items' => $this->itemIndex($content, $questions),
            'questions' => array_map($this->exportQuestion(...), $questions),
        ];
    }

    /**
     * The exam-mode bank served during study.
     *
     * ADR-0006: this is the VALIDATION pool, not the holdout. Exam Mode used to
     * serve HOLDOUT, which spent the pool §22 reserves for a protected unseen
     * final assessment on every practice exam a learner sat.
     *
     * @return array<string, mixed>
     */
    public function examPayload(ContentSet $content, Pool $pool = Pool::Validation): array
    {
        $questions = array_values(array_filter(
            $content->questions,
            static fn (Question $q): bool => $q->pool === $pool,
        ));

        return [
            'generated_at' => gmdate('c'),
            'pool' => $pool->value,
            'questions' => array_map($this->exportQuestion(...), $questions),
        ];
    }

    /**
     * The comprehension checks of ADR-0010, one series per lot.
     *
     * Read from the content set's own `comprehension` field, which only the
     * comprehension loader fills: an exam question cannot reach this payload
     * by a filter going wrong, and `assertComprehensionIsolated()` checks it
     * anyway. Questions keep their file order — the author's teaching order,
     * which the page follows — and carry their lot and their related items, so
     * a synthesis question links to every course it ties together.
     *
     * @return array<string, mixed>
     */
    public function comprehensionPayload(ContentSet $content): array
    {
        $lots = [];
        $questions = [];
        $covered = [];

        foreach ($content->comprehension as $question) {
            $item = $content->matrix->findById($question->officialItemId);
            if (null === $item) {
                // CMP-003 (wrapping QST-001) already failed the validation.
                throw new \LogicException(\sprintf(
                    'Comprehension question "%s" names an item absent from the matrix.',
                    $question->id->value,
                ));
            }

            $lots[$item->lot] ??= [
                'url' => CourseUrl::forComprehension($item->lot),
                'questions' => 0,
            ];
            ++$lots[$item->lot]['questions'];

            $questions[] = [
                ...$this->exportQuestion($question),
                'lot' => $item->lot,
                'related_items' => $question->relatedItems,
                'assesses_outcomes' => $question->assessesOutcomes,
            ];

            $covered[] = $question->officialItemId;
            array_push($covered, ...$question->relatedItems);
        }

        ksort($lots);

        // The correction's « à retenir » names the outcomes the question
        // assessed, in the matrix's own words — never a sentence of the page.
        $outcomes = [];
        foreach (array_unique($covered) as $itemId) {
            foreach ($content->matrix->findById($itemId)?->learningOutcomes ?? [] as $outcome) {
                if (null !== $outcome->idValue()) {
                    $outcomes[$outcome->idValue()] = $outcome->text;
                }
            }
        }
        ksort($outcomes);

        return [
            'generated_at' => gmdate('c'),
            'pool' => Pool::Comprehension->value,
            'lots' => $lots,
            'items' => $this->itemIndexFor($content, $covered),
            'outcomes' => $outcomes,
            'questions' => $questions,
        ];
    }

    /**
     * Mock 4, the official-format simulation (§10, ADR-0005 Option A).
     *
     * This is the one payload that carries the holdout, and it exists because
     * the holdout has no other purpose: a final mock nobody can sit is not an
     * assessment. Option A settles what "unseen" means — never served by
     * Practice Mode, Exam Mode or any other *learning* mode — and Mock 4 is
     * the final assessment rather than a learning mode.
     *
     * What it does not settle is confidentiality, which this repository being
     * public makes impossible and which is recorded as impossible in ADR-0005.
     * Shipping the payload changes nothing there: the answers were already
     * readable in `content/questions/*.yml` by anyone who looked.
     *
     * @param array<string, mixed> $blueprint
     *
     * @return array<string, mixed>
     */
    public function mockPayload(ContentSet $content, array $blueprint): array
    {
        $questions = array_values(array_filter(
            $content->questions,
            static fn (Question $q): bool => Pool::Holdout === $q->pool,
        ));

        // Deterministic order in the file. The page shuffles at run time, so
        // this only decides what a diff of the payload looks like — and a
        // payload whose order drifts on every build hides real changes.
        usort($questions, static fn (Question $a, Question $b): int => [$a->officialTopic, $a->id->value] <=> [$b->officialTopic, $b->id->value]);

        $constraints = $blueprint['official_constraints'] ?? [];

        return [
            'generated_at' => gmdate('c'),
            'pool' => Pool::Holdout->value,
            'mock' => $blueprint['mock'] ?? 'Mock 4',
            'question_count' => $constraints['questions'] ?? 75,
            'duration_minutes' => $constraints['minutes'] ?? 90,
            'language' => $constraints['language'] ?? 'en',
            'symfony' => $constraints['symfony'] ?? '8.0',
            'distribution_label' => $blueprint['distribution_label'] ?? 'TRAINING_DISTRIBUTION',
            'items' => $this->itemIndex($content, $questions),
            'questions' => array_map($this->exportQuestion(...), $questions),
        ];
    }

    /**
     * The atomic items the mock covers, with the learning outcomes §10 wants
     * the analysis reported against. Without them the results page could only
     * name a topic, which tells a learner where they lost points but not what
     * to go and learn.
     *
     * @param list<Question> $questions
     *
     * @return array<string, array<string, mixed>>
     */
    private function itemIndex(ContentSet $content, array $questions): array
    {
        return $this->itemIndexFor(
            $content,
            array_map(static fn (Question $q): string => $q->officialItemId, $questions),
        );
    }

    /**
     * @param list<string> $itemIds
     *
     * @return array<string, array<string, mixed>>
     */
    private function itemIndexFor(ContentSet $content, array $itemIds): array
    {
        $index = [];

        foreach ($itemIds as $itemId) {
            $item = $content->matrix->findById($itemId);
            if (null === $item) {
                continue;
            }

            $index[$item->id->value] = [
                'official_item' => $item->officialItem,
                'official_topic' => $item->officialTopic,
                'learning_outcomes' => $item->learningOutcomeTexts(),
                // Derived by CourseUrl, the same helper that writes the page,
                // so a link shipped to the learner cannot point at a route the
                // build did not produce.
                'course_url' => CourseUrl::forItem($item),
                // Where each outcome is taught, so a wrong answer opens the
                // section rather than the top of the page. SEC-001 keeps every
                // heading named here present in the course.
                'outcome_sections' => $this->outcomeSections($content, $item),
            ];
        }

        ksort($index);

        return $index;
    }

    /**
     * @return array<string, array{title: string, url: string}>
     */
    private function outcomeSections(ContentSet $content, OfficialItem $item): array
    {
        $sections = [];
        foreach ($content->courses as $course) {
            if ($course->officialItemId !== $item->id->value) {
                continue;
            }
            foreach ($course->outcomeSections as $outcome => $heading) {
                $sections[$outcome] = [
                    'title' => str_replace('`', '', $heading),
                    'url' => CourseUrl::forSection($item, $heading),
                ];
            }
        }

        return $sections;
    }

    /**
     * Mock 5 — weakness-based (Master Plan §10).
     *
     * There is no fixed selection: §10 requires it to be generated from
     * demonstrated weak outcomes, so the payload is the *candidate universe*
     * and the page selects from it against the learner's own local history.
     * Two learners never sit the same paper, and a learner with no recorded
     * failures does not sit one at all — the fallback says so rather than
     * inventing a weakness profile.
     *
     * @param array<string, mixed> $blueprint
     *
     * @return array<string, mixed>
     */
    public function weaknessMockPayload(ContentSet $content, array $blueprint): array
    {
        $spec = self::mockSpec($blueprint, 'mock-5');

        $questions = array_values(array_filter(
            $content->questions,
            static fn (Question $q): bool => Pool::Holdout !== $q->pool,
        ));

        usort($questions, static fn (Question $a, Question $b): int => [$a->officialTopic, $a->id->value] <=> [$b->officialTopic, $b->id->value]);

        return [
            'generated_at' => gmdate('c'),
            'pool' => 'VALIDATION+LEARNING',
            'mock' => $spec['name'],
            'purpose' => $spec['purpose'],
            'minimum_questions' => 10,
            'maximum_questions' => 40,
            'time_margin' => 1.15,
            'language' => 'mixed',
            'format_label' => $blueprint['format_label'],
            'distribution_label' => $blueprint['distribution_label'],
            'not_official' => $blueprint['not_official'],
            'scoring_policy' => $spec['scoring_policy'],
            'weakness_evidence' => $spec['weakness_evidence'],
            'fallback' => $spec['fallback'],
            'items' => $this->itemIndex($content, $questions),
            'questions' => array_map($this->exportQuestion(...), $questions),
        ];
    }

    /**
     * A training mock (Mocks 1, 2 and 3 — Master Plan §10).
     *
     * Unlike Mock 4 this payload carries the *eligible pool*, not the sitting:
     * the blueprint's count is smaller than the pool on purpose, so that two
     * consecutive sittings differ. The page draws the sitting from it using the
     * recorded topic spread.
     *
     * Every figure travelling with the payload is INTERNAL_TRAINING_FORMAT.
     * §10 fixes a count and a duration for Mock 4 only, and none of these is
     * derived from it.
     *
     * @param array<string, mixed> $blueprint
     *
     * @return array<string, mixed>
     */
    public function trainingMockPayload(ContentSet $content, array $blueprint, string $mockId): array
    {
        $spec = self::mockSpec($blueprint, $mockId);
        $eligible = self::eligibleFor($content, $spec);

        usort($eligible, static fn (Question $a, Question $b): int => [$a->officialTopic, $a->id->value] <=> [$b->officialTopic, $b->id->value]);

        return [
            'generated_at' => gmdate('c'),
            'pool' => Pool::Validation->value,
            'mock' => $spec['name'],
            'purpose' => $spec['purpose'],
            'question_count' => $spec['question_count'],
            'duration_minutes' => $spec['duration_minutes'],
            'language' => $spec['language'],
            'format_label' => $blueprint['format_label'],
            'distribution_label' => $blueprint['distribution_label'],
            'not_official' => $blueprint['not_official'],
            'scoring_policy' => $spec['scoring_policy'],
            'topic_spread' => $spec['topic_spread'],
            'items' => $this->itemIndex($content, $eligible),
            'questions' => array_map($this->exportQuestion(...), $eligible),
        ];
    }

    /** Fixed, question-disjoint sittings in the official format (owner, 2026-10-14). */
    public const int EXAM_SERIES_SIZE = 6;

    /**
     * The numbered mock exams (/examens): official format, chosen from a menu.
     *
     * Owner's request, 2026-10-14: « refait des examens blancs, format symfony
     * exam 8.0, je dois depuis menu choisir quel examen passer avec une
     * recommandation 1 2 3 … ». Measured first: 637 English questions outside
     * the holdout make at most eight disjoint 75-question exams, and six once
     * every official topic keeps its share in every exam (Automated Tests has
     * 27 questions). The owner chose six disjoint exams plus free draws beyond.
     *
     * - Count, duration and language come from the Mock 4 blueprint's
     *   `official_constraints` (§10): 75 questions, 90 minutes, English.
     * - The topic spread is TRAINING_DISTRIBUTION: each topic's share of the
     *   atomic official items, by largest remainder. No official weighting is
     *   published, and none is invented.
     * - Exams 1 to 6 are fixed here, at build time, and share no question; no
     *   exam asks two questions on one item. Within a topic the questions are
     *   dealt by difficulty, so no exam gets all the hard ones.
     * - The holdout is never used: it stays Mock 4's alone.
     * - The payload also carries the whole eligible pool, from which the page
     *   draws exam 7, 8, 9… preferring questions the learner has not seen.
     *
     * None of these questions is unseen: they are served by Practice and Exam
     * Mode too. The payload says so, and the page repeats it.
     *
     * @param array<string, mixed> $mockFour the Mock 4 blueprint
     *
     * @return array<string, mixed>
     */
    public function examSeriesPayload(ContentSet $content, array $mockFour): array
    {
        $count = (int) $mockFour['official_constraints']['questions'];
        $minutes = (int) $mockFour['official_constraints']['minutes'];

        $eligible = array_values(array_filter(
            $content->questions,
            static fn (Question $q): bool => Pool::Holdout !== $q->pool && 'en' === $q->language->value,
        ));
        usort($eligible, static fn (Question $a, Question $b): int => [$a->officialTopic, $a->id->value] <=> [$b->officialTopic, $b->id->value]);

        $spread = self::topicSpread($content, $count);
        $exams = self::dealExams($eligible, $spread, self::EXAM_SERIES_SIZE);

        return [
            'generated_at' => gmdate('c'),
            'pool' => 'VALIDATION+LEARNING',
            'question_count' => $count,
            'duration_minutes' => $minutes,
            'language' => 'en',
            'format_label' => 'OFFICIAL_FORMAT',
            'format_note' => 'Count, duration and language are the published constraints of the Symfony 8.0 exam, as recorded in the Mock 4 blueprint.',
            'distribution_label' => 'TRAINING_DISTRIBUTION',
            'distribution_note' => 'No official weighting per topic is published. Each topic gets its share of the atomic official items.',
            'not_unseen' => 'These questions are also served by Practice Mode and Exam Mode: a score here is training, not an independent measure. Only Mock 4 is kept unseen.',
            'answer_modes_note' => 'Single-answer questions are answered with radio buttons, multiple-answer questions with checkboxes and the number of answers expected. The official exam asks both kinds; the share of each is not published, so this series guarantees only that every exam asks both, spread evenly.',
            'scoring_policy' => 'all-or-nothing',
            'topic_spread' => $spread,
            'exams' => $exams,
            'items' => $this->itemIndex($content, $eligible),
            'questions' => array_map($this->exportQuestion(...), $eligible),
        ];
    }

    /** Whether the bank holds enough eligible questions to fill the series at all. */
    public static function examSeriesFits(ContentSet $content, int $count): bool
    {
        $eligible = 0;
        foreach ($content->questions as $q) {
            if (Pool::Holdout !== $q->pool && 'en' === $q->language->value) {
                ++$eligible;
            }
        }

        return $eligible >= self::EXAM_SERIES_SIZE * $count;
    }

    /**
     * Each official topic's share of $count, in proportion to its atomic
     * official items, by largest remainder (ties broken by topic name).
     *
     * @return array<string, int>
     */
    public static function topicSpread(ContentSet $content, int $count): array
    {
        $items = [];
        foreach ($content->matrix->items as $item) {
            $items[$item->officialTopic] = ($items[$item->officialTopic] ?? 0) + 1;
        }
        ksort($items);
        $total = array_sum($items);
        $spread = [];
        $remainders = [];
        foreach ($items as $topic => $n) {
            $exact = $n * $count / $total;
            $spread[$topic] = (int) floor($exact);
            $remainders[$topic] = $exact - floor($exact);
        }
        arsort($remainders);
        foreach (array_slice(array_keys($remainders), 0, $count - array_sum($spread)) as $topic) {
            ++$spread[$topic];
        }
        ksort($spread);

        return $spread;
    }

    /**
     * Deals $size disjoint exams: per topic, questions ordered by difficulty
     * then by a stable hash of their id, each going to the exam with the fewest
     * questions of that topic that does not yet ask on its item.
     *
     * @param list<Question>     $eligible
     * @param array<string, int> $spread
     *
     * @return list<array{number: int, question_ids: list<string>}>
     */
    private static function dealExams(array $eligible, array $spread, int $size): array
    {
        $rank = ['easy' => 0, 'medium' => 1, 'hard' => 2];
        $byTopic = [];
        foreach ($eligible as $q) {
            $byTopic[$q->officialTopic][] = $q;
        }

        $ids = array_fill(0, $size, []);
        $itemsUsed = array_fill(0, $size, []);
        $multiple = array_fill(0, $size, 0);
        $taken = [];
        $pools = [];
        foreach ($spread as $topic => $wanted) {
            $pool = $byTopic[$topic] ?? [];
            usort($pool, static fn (Question $a, Question $b): int => [$rank[$a->difficulty] ?? 1, sha1($a->id->value)] <=> [$rank[$b->difficulty] ?? 1, sha1($b->id->value)]);
            $pools[$topic] = $pool;
            $taken[$topic] = array_fill(0, $size, 0);
        }

        // Two passes. The multiple-answer questions are dealt first, each to the
        // exam holding the fewest of them, so that every exam asks both kinds the
        // official exam asks — single answer and several answers — instead of
        // the few multiple-answer questions gathering in the first exams.
        foreach ([true, false] as $multiplePass) {
            foreach ($spread as $topic => $wanted) {
                foreach ($pools[$topic] as $q) {
                    if ((AnswerMode::Multiple === $q->answerMode) !== $multiplePass) {
                        continue;
                    }
                    $best = null;
                    for ($e = 0; $e < $size; ++$e) {
                        if ($taken[$topic][$e] >= $wanted || isset($itemsUsed[$e][$q->officialItemId])) {
                            continue;
                        }
                        $key = $multiplePass ? [$multiple[$e], $taken[$topic][$e]] : [$taken[$topic][$e]];
                        if (null === $best || $key < $bestKey) {
                            $best = $e;
                            $bestKey = $key;
                        }
                    }
                    if (null === $best) {
                        continue;
                    }
                    $ids[$best][] = $q->id->value;
                    $itemsUsed[$best][$q->officialItemId] = true;
                    ++$taken[$topic][$best];
                    $multiple[$best] += $multiplePass ? 1 : 0;
                }
            }
        }

        foreach ($spread as $topic => $wanted) {
            if (min($taken[$topic]) < $wanted) {
                throw new \RuntimeException(\sprintf(
                    'Topic "%s" cannot give %d questions to each of %d disjoint exams: it has %d eligible questions.',
                    $topic, $wanted, $size, \count($pools[$topic]),
                ));
            }
        }

        // Numbered by estimated difficulty, easiest first: the menu recommends
        // sitting them in order because they rise. Dealing by difficulty within
        // each topic makes them rise nearly on its own; the multiple-answer pass
        // can swap two neighbours, so the order is fixed here rather than hoped
        // for, and assertExamSeries() checks it.
        $byId = [];
        foreach ($eligible as $q) {
            $byId[$q->id->value] = $q;
        }
        $order = array_keys($ids);
        usort($order, static fn (int $a, int $b): int => [self::meanDifficulty($ids[$a], $byId, $rank), $a] <=> [self::meanDifficulty($ids[$b], $byId, $rank), $b]);

        $exams = [];
        foreach ($order as $n => $e) {
            $exams[] = ['number' => $n + 1, 'question_ids' => $ids[$e], 'multiple_answer_count' => $multiple[$e]];
        }

        return $exams;
    }

    /**
     * @param list<string>            $ids
     * @param array<string, Question> $byId
     * @param array<string, int>      $rank
     */
    private static function meanDifficulty(array $ids, array $byId, array $rank): float
    {
        return array_sum(array_map(static fn (string $id): int => $rank[$byId[$id]->difficulty] ?? 1, $ids)) / max(1, \count($ids));
    }

    /**
     * The guarantees the page and the learner rely on, checked at build time.
     *
     * @param array<string, mixed> $payload
     */
    public static function assertExamSeries(array $payload, ContentSet $content): void
    {
        $byId = [];
        foreach ($payload['questions'] as $q) {
            $byId[$q['id']] = $q;
        }
        $seen = [];
        $counts = [];
        foreach ($payload['exams'] as $exam) {
            $ids = $exam['question_ids'];
            if (\count($ids) !== $payload['question_count']) {
                throw new \LogicException(\sprintf('Exam %d has %d questions, not %d.', $exam['number'], \count($ids), $payload['question_count']));
            }
            $items = [];
            $topics = [];
            foreach ($ids as $id) {
                if (isset($seen[$id])) {
                    throw new \LogicException(\sprintf('Exams %d and %d share a question.', $seen[$id], $exam['number']));
                }
                $seen[$id] = $exam['number'];
                $q = $byId[$id] ?? throw new \LogicException('An exam names a question the payload does not carry.');
                if ('en' !== $q['language']) {
                    throw new \LogicException(\sprintf('Exam %d carries a question that is not in English.', $exam['number']));
                }
                if (isset($items[$q['official_item']])) {
                    throw new \LogicException(\sprintf('Exam %d asks twice on one item.', $exam['number']));
                }
                $items[$q['official_item']] = true;
                $topics[$q['official_topic']] = ($topics[$q['official_topic']] ?? 0) + 1;
            }
            ksort($topics);
            if ($topics !== $payload['topic_spread']) {
                throw new \LogicException(\sprintf('Exam %d does not follow the topic spread.', $exam['number']));
            }
            $counts[$exam['number']] = \count(array_filter($ids, static fn (string $id): bool => 'multiple' === $byId[$id]['answer_mode']));
            if ($counts[$exam['number']] !== ($exam['multiple_answer_count'] ?? null)) {
                throw new \LogicException(\sprintf('Exam %d misstates its number of multiple-answer questions.', $exam['number']));
            }
        }
        // The menu recommends the fixed exams in order because they rise in
        // estimated difficulty: easy 0, medium 1, hard 2, averaged.
        $rank = ['easy' => 0, 'medium' => 1, 'hard' => 2];
        $means = [];
        foreach ($payload['exams'] as $exam) {
            $means[] = array_sum(array_map(static fn (string $id): int => $rank[$byId[$id]['difficulty']] ?? 1, $exam['question_ids'])) / \count($exam['question_ids']);
        }
        for ($i = 1; $i < \count($means); ++$i) {
            if ($means[$i] < $means[$i - 1]) {
                throw new \LogicException(\sprintf('Exam %d is easier than exam %d: the series does not rise in estimated difficulty.', $i + 1, $i));
            }
        }
        // Both kinds of question in every exam, spread evenly: the menu states
        // it, and a series where one exam has none would not be the format.
        if ([] !== $counts && (min($counts) < 1 || max($counts) - min($counts) > 1)) {
            throw new \LogicException(\sprintf('The multiple-answer questions are not spread over every exam (%s).', implode(', ', $counts)));
        }
        self::assertNoHoldoutLeak($payload, $content);
    }

    /**
     * The simulations hub (/simulations): what each mock is for, and when to sit it.
     *
     * WHY A PAYLOAD AND NOT PROSE IN THE PAGE. Every sentence and every figure
     * below already exists in a blueprint, and a React component holding its own
     * copy is a component that will one day tell a learner a mock lasts 41
     * minutes after the bank made it 44. The page renders this file and states
     * nothing of its own.
     *
     * WHAT IT MUST NOT CARRY. No question, no choice, no answer, no question id
     * — for Mock 4 above all, whose whole value is that its bank is unseen. The
     * hub describes the sittings; it never samples them. assertNoQuestionLeak()
     * refuses a payload that has started to.
     *
     * The count and duration of Mock 4 are OFFICIAL_FORMAT (§10 fixes them).
     * Those of Mocks 1, 2, 3 and 5 are this project's decision, carried with the
     * blueprint's own `not_official` sentence so the page cannot drop it.
     *
     * @param array<string, mixed> $mocks     the Mocks 1-2-3-5 blueprint
     * @param array<string, mixed> $mockFour  the Mock 4 blueprint
     *
     * @return array<string, mixed>
     */
    public function simulationsPayload(array $mocks, array $mockFour): array
    {
        $entries = [];

        foreach ((array) ($mocks['mocks'] ?? []) as $spec) {
            $entries[] = [
                'id' => $spec['id'],
                'route' => '/'.$spec['id'],
                'name' => $spec['name'],
                'purpose' => $spec['purpose'],
                'when_to_use' => $spec['when_to_use'],
                'sequence' => $spec['sequence'],
                'repeatable' => $spec['repeatable'],
                // A blueprint entry whose count and duration are a rule rather
                // than a number carries a learner-facing restatement of that
                // rule; it is preferred here, and it is still blueprint text.
                'question_count' => (string) ($spec['question_count_summary'] ?? $spec['question_count']),
                'duration_minutes' => (string) ($spec['duration_summary'] ?? $spec['duration_minutes']),
                // Mock 5 draws from what the learner has failed, so it has no
                // fixed pool to report; the others state what they draw from,
                // which is what makes "two sittings differ" checkable.
                'eligible_questions' => $spec['eligible_questions'] ?? null,
                'language' => $spec['language'],
                // The same vocabulary weaknessMockPayload already publishes in
                // mock-5.json, so the hub and the payload cannot describe one
                // sitting two ways.
                'pool' => 'mock-5' === $spec['id'] ? 'VALIDATION+LEARNING' : Pool::Validation->value,
                'scoring_policy' => $spec['scoring_policy'],
                'format_label' => $mocks['format_label'],
            ];
        }

        $constraints = (array) ($mockFour['official_constraints'] ?? []);

        $entries[] = [
            'id' => 'mock-4',
            'route' => '/mock-4',
            'name' => $mockFour['mock'],
            'purpose' => $mockFour['purpose'],
            'when_to_use' => $mockFour['when_to_use'],
            'sequence' => $mockFour['sequence'],
            'repeatable' => $mockFour['repeatable'],
            'question_count' => (string) $constraints['questions'],
            'duration_minutes' => (string) $constraints['minutes'],
            'eligible_questions' => null,
            'language' => $constraints['language'],
            'pool' => Pool::Holdout->value,
            'scoring_policy' => 'all-or-nothing, INTERNAL_TRAINING_FORMAT; the official policy is not published',
            // The only entry whose count and duration are published constraints
            // rather than this project's decision. Labelling all five the same
            // way would be false in one direction or the other.
            'format_label' => 'OFFICIAL_FORMAT',
        ];

        usort($entries, static fn (array $a, array $b): int => $a['sequence'] <=> $b['sequence']);

        return [
            'generated_at' => gmdate('c'),
            'not_official' => $mocks['not_official'],
            'mocks' => $entries,
        ];
    }

    /**
     * The hub describes sittings; it must never sample one.
     *
     * Mock 4's bank is reserved and unseen (ADR-0005 Option A). A hub page that
     * quoted one of its questions would spend that reservation quietly, and no
     * other check in this repository looks at this payload's shape.
     *
     * @param array<string, mixed> $payload
     */
    public static function assertNoQuestionLeak(array $payload): void
    {
        $forbidden = ['questions', 'choices', 'question', 'explanation', 'items'];

        foreach ($forbidden as $key) {
            if (\array_key_exists($key, $payload)) {
                throw new \LogicException(\sprintf('The simulations payload carries "%s"; it describes sittings and must never sample one.', $key));
            }
        }

        foreach ((array) ($payload['mocks'] ?? []) as $entry) {
            foreach ($forbidden as $key) {
                if (\array_key_exists($key, (array) $entry)) {
                    throw new \LogicException(\sprintf('%s carries "%s" in the simulations payload.', $entry['id'] ?? '?', $key));
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $blueprint
     *
     * @return array<string, mixed>
     */
    public static function mockSpec(array $blueprint, string $mockId): array
    {
        foreach ((array) ($blueprint['mocks'] ?? []) as $mock) {
            if (($mock['id'] ?? null) === $mockId) {
                return $mock;
            }
        }

        throw new \LogicException(\sprintf('The blueprint declares no mock "%s".', $mockId));
    }

    /**
     * @param array<string, mixed> $spec
     *
     * @return list<Question>
     */
    public static function eligibleFor(ContentSet $content, array $spec): array
    {
        $filter = $spec['eligible_filter'];

        return array_values(array_filter($content->questions, static function (Question $q) use ($filter): bool {
            // §10 reserves the holdout for Mock 4, and ADR-0005 spent it there
            // entirely. No training mock may reach it, whatever a filter says.
            if (Pool::Holdout === $q->pool) {
                return false;
            }

            if (($filter['pool'] ?? null) !== $q->pool->value) {
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

    /**
     * The training mock's invariant: it ships the eligible pool, that pool is
     * big enough for the sitting the blueprint specifies, every topic in the
     * spread can actually be filled, and no holdout question is present.
     *
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $blueprint
     */
    public static function assertTrainingMockMatchesBlueprint(array $payload, ContentSet $content, array $blueprint, string $mockId): void
    {
        $spec = self::mockSpec($blueprint, $mockId);
        $exported = (array) ($payload['questions'] ?? []);

        if (\count($exported) !== (int) $spec['eligible_questions']) {
            throw new \LogicException(\sprintf(
                '%s ships %d eligible questions, the blueprint measured %d.',
                $mockId,
                \count($exported),
                $spec['eligible_questions'],
            ));
        }

        if (\count($exported) < (int) $spec['question_count']) {
            throw new \LogicException(\sprintf(
                '%s cannot seat a sitting of %d from %d eligible questions.',
                $mockId,
                $spec['question_count'],
                \count($exported),
            ));
        }

        $byId = [];
        foreach ($content->questions as $question) {
            $byId[$question->id->value] = $question;
        }

        $perTopic = [];
        foreach ($exported as $entry) {
            $question = $byId[(string) ($entry['id'] ?? '')] ?? null;

            if (null === $question) {
                throw new \LogicException(\sprintf('%s ships unknown question "%s".', $mockId, $entry['id'] ?? ''));
            }

            if (Pool::Holdout === $question->pool) {
                throw new \LogicException(\sprintf(
                    '%s ships holdout question "%s"; the holdout is Mock 4\'s alone (§10, ADR-0005).',
                    $mockId,
                    $question->id->value,
                ));
            }

            $perTopic[$question->officialTopic] = ($perTopic[$question->officialTopic] ?? 0) + 1;
        }

        foreach ((array) $spec['topic_spread'] as $topic => $wanted) {
            if (($perTopic[$topic] ?? 0) < (int) $wanted) {
                throw new \LogicException(\sprintf(
                    '%s asks for %d question(s) in "%s" but ships only %d eligible.',
                    $mockId,
                    $wanted,
                    $topic,
                    $perTopic[$topic] ?? 0,
                ));
            }
        }
    }

    /**
     * The mock payload's own invariant, and the reason `assertNoHoldoutLeak()`
     * is not weakened to accommodate it: this one is stricter, not looser.
     *
     * A leak in either direction fails the build — a holdout question missing
     * from the mock is as wrong as one appearing in a learning payload, since
     * the sitting would then be short of the official 75 without anything
     * saying so.
     *
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $blueprint
     */
    public static function assertMockMatchesBlueprint(array $payload, ContentSet $content, array $blueprint): void
    {
        $exported = (array) ($payload['questions'] ?? []);
        $expected = (int) ($blueprint['official_constraints']['questions'] ?? 75);

        if (\count($exported) !== $expected) {
            throw new \LogicException(\sprintf(
                'Mock payload carries %d questions, the blueprint requires %d (§10).',
                \count($exported),
                $expected,
            ));
        }

        $byId = [];
        foreach ($content->questions as $question) {
            $byId[$question->id->value] = $question;
        }

        $perTopic = [];
        $items = [];

        foreach ($exported as $entry) {
            $id = (string) ($entry['id'] ?? '');
            $question = $byId[$id] ?? null;

            if (null === $question) {
                throw new \LogicException(\sprintf('Mock payload carries unknown question "%s".', $id));
            }

            if (Pool::Holdout !== $question->pool) {
                throw new \LogicException(\sprintf(
                    'Mock payload carries "%s", which is in the %s pool: the mock is the holdout (§7.3).',
                    $id,
                    $question->pool->value,
                ));
            }

            if ('en' !== $question->language->value) {
                throw new \LogicException(\sprintf('Mock payload carries "%s", which is not English (§10).', $id));
            }

            if (isset($items[$question->officialItemId])) {
                throw new \LogicException(\sprintf(
                    'Mock payload carries two questions for atomic item "%s".',
                    $question->officialItemId,
                ));
            }
            $items[$question->officialItemId] = true;

            $perTopic[$question->officialTopic] = ($perTopic[$question->officialTopic] ?? 0) + 1;
        }

        foreach ((array) ($blueprint['topics'] ?? []) as $row) {
            $topic = (string) $row['topic'];
            $slots = (int) $row['slots'];

            if (($perTopic[$topic] ?? 0) !== $slots) {
                throw new \LogicException(\sprintf(
                    'Mock payload carries %d question(s) for topic "%s", the blueprint allots %d.',
                    $perTopic[$topic] ?? 0,
                    $topic,
                    $slots,
                ));
            }
        }
    }

    /**
     * Asserts the invariant that §17 treats as a critical blocker.
     *
     * ADR-0006 widened it: no *published* payload may carry a holdout question,
     * not just the practice one. The holdout is not deployed at all.
     *
     * @param array<string, mixed> $payload
     */
    public static function assertNoHoldoutLeak(array $payload, ContentSet $content): void
    {
        $holdout = [];
        foreach ($content->questions as $question) {
            if (Pool::Holdout === $question->pool) {
                $holdout[$question->id->value] = true;
            }
        }

        foreach ((array) ($payload['questions'] ?? []) as $exported) {
            $id = (string) ($exported['id'] ?? '');
            if (isset($holdout[$id])) {
                throw new \LogicException(\sprintf(
                    'Holdout question "%s" reached a published payload (§7.3, ADR-0006).',
                    $id,
                ));
            }
        }
    }

    /**
     * ADR-0010's isolation, both ways: the comprehension payload carries only
     * comprehension questions, and none of them shares an id with a question
     * of the exam banks — the holdout included, whose ids are never printed.
     *
     * @param array<string, mixed> $payload
     */
    public static function assertComprehensionIsolated(array $payload, ContentSet $content): void
    {
        if (Pool::Comprehension->value !== ($payload['pool'] ?? null)) {
            throw new \LogicException('The comprehension payload must declare the COMPREHENSION pool (ADR-0010).');
        }

        $comprehension = [];
        foreach ($content->comprehension as $question) {
            $comprehension[$question->id->value] = true;
        }

        $exam = [];
        foreach ($content->questions as $question) {
            $exam[$question->id->value] = $question->pool;
        }

        foreach ((array) ($payload['questions'] ?? []) as $exported) {
            $id = (string) ($exported['id'] ?? '');

            if (isset($exam[$id])) {
                throw new \LogicException(Pool::Holdout === $exam[$id]
                    ? 'A HOLDOUT question reached the comprehension payload (ADR-0010); its id is withheld.'
                    : \sprintf('Question "%s" of the %s pool reached the comprehension payload (ADR-0010).', $id, $exam[$id]->value));
            }

            if (!isset($comprehension[$id])) {
                throw new \LogicException(\sprintf('Question "%s" in the comprehension payload is not a comprehension question (ADR-0010).', $id));
            }
        }

        if (\count((array) ($payload['questions'] ?? [])) !== \count($comprehension)) {
            throw new \LogicException(\sprintf(
                'The comprehension payload carries %d questions; the banks hold %d (ADR-0010).',
                \count((array) ($payload['questions'] ?? [])),
                \count($comprehension),
            ));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function exportQuestion(Question $question): array
    {
        return [
            'id' => $question->id->value,
            'version' => $question->version,
            'official_topic' => $question->officialTopic,
            'official_item' => $question->officialItemId,
            'domain' => $question->domain,
            'subtopic' => $question->subtopic,
            'language' => $question->language->value,
            'difficulty' => $question->difficulty,
            'cognitive_level' => $question->cognitiveLevel,
            'exam_skill' => $question->examSkill,
            'answer_mode' => $question->answerMode->value,
            'required_answer_count' => $question->requiredAnswerCount,
            'question' => $question->question,
            'code_language' => $question->codeLanguage,
            'shuffle_choices' => $question->shuffleChoices,
            'negative_wording' => $question->negativeWording,
            'estimated_time_seconds' => $question->estimatedTimeSeconds,
            'scoring_policy' => $question->scoringPolicy,
            'choices' => array_map(
                static fn (Choice $c): array => [
                    'id' => $c->id->value,
                    'text' => $c->text,
                    'correct' => $c->correct,
                    'explanation' => $c->explanation,
                ],
                $question->choices,
            ),
            'explanation' => $question->explanation,
            // Outcome ids, not text: the page looks each one up in the item
            // index to find the course section that teaches it.
            'assesses_outcomes' => $question->assessesOutcomes,
            'official_sources' => array_map(
                // Both spellings travel: `url` is what the claim was verified
                // against, `readable_url` is what a learner opens. A payload
                // carrying only one of them forces the page to choose between
                // being checkable and being followable.
                static fn (object $s): array => [
                    'url' => $s->url,
                    'readable_url' => $s->displayUrl(),
                    'anchor' => $s->anchor,
                ],
                $question->officialSources,
            ),
            'tags' => $question->tags,
        ];
    }
}
