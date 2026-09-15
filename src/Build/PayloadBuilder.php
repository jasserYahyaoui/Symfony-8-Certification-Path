<?php

declare(strict_types=1);

namespace CertPath\Build;

use CertPath\Domain\Choice;
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
        $index = [];

        foreach ($questions as $question) {
            $item = $content->matrix->findById($question->officialItemId);
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
            ];
        }

        ksort($index);

        return $index;
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
