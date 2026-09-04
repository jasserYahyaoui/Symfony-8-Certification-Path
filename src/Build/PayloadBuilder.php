<?php

declare(strict_types=1);

namespace CertPath\Build;

use CertPath\Domain\Choice;
use CertPath\Domain\Pool;
use CertPath\Domain\Question;
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
                'learning_outcomes' => $item->learningOutcomes,
            ];
        }

        ksort($index);

        return $index;
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
                static fn (object $s): array => ['url' => $s->url, 'anchor' => $s->anchor],
                $question->officialSources,
            ),
            'tags' => $question->tags,
        ];
    }
}
