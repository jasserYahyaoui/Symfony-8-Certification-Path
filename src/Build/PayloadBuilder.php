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
     * @return array<string, mixed>
     */
    public function examPayload(ContentSet $content, Pool $pool = Pool::Holdout): array
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
     * Asserts the invariant that §17 treats as a critical blocker.
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
                    'Holdout question "%s" reached the Practice Mode payload (§7.3).',
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
