<?php

declare(strict_types=1);

namespace CertPath\Domain;

use CertPath\Schema\SchemaException;
use CertPath\Schema\SchemaRegistry;
use CertPath\Schema\YamlLoader;
use CertPath\Support\Id;

/**
 * Loads every question bank file under `content/questions/`.
 */
final readonly class QuestionLoader
{
    public function __construct(
        private YamlLoader $yaml = new YamlLoader(),
    ) {
    }

    /**
     * @return list<Question>
     */
    public function loadDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = glob(rtrim($directory, '/').'/*.yml') ?: [];
        sort($files);

        $questions = [];
        foreach ($files as $file) {
            foreach ($this->loadFile($file) as $question) {
                $questions[] = $question;
            }
        }

        return $questions;
    }

    /**
     * @return list<Question>
     */
    public function loadFile(string $path): array
    {
        $document = $this->yaml->load($path, SchemaRegistry::QUESTION_BANK);

        $raw = $document['questions'] ?? [];
        if (!\is_array($raw)) {
            throw new SchemaException(\sprintf('%s: `questions` must be a list.', $path));
        }

        $questions = [];
        foreach (array_values($raw) as $index => $entry) {
            if (!\is_array($entry)) {
                throw new SchemaException(\sprintf('%s: question #%d is not a mapping.', $path, $index));
            }
            $questions[] = $this->hydrate($entry, \sprintf('%s question #%d', basename($path), $index));
        }

        return $questions;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function hydrate(array $raw, string $ctx): Question
    {
        $choices = [];
        $rawChoices = $raw['choices'] ?? [];
        if (!\is_array($rawChoices)) {
            throw new SchemaException(\sprintf('%s: `choices` must be a list.', $ctx));
        }

        foreach (array_values($rawChoices) as $rawChoice) {
            if (!\is_array($rawChoice)) {
                throw new SchemaException(\sprintf('%s: a choice is not a mapping.', $ctx));
            }

            $choices[] = new Choice(
                id: Id::parse($this->req($rawChoice, 'id', $ctx)),
                text: $this->req($rawChoice, 'text', $ctx),
                correct: (bool) ($rawChoice['correct'] ?? false),
                explanation: isset($rawChoice['explanation']) ? (string) $rawChoice['explanation'] : null,
            );
        }

        $sources = [];
        foreach ((array) ($raw['official_sources'] ?? []) as $source) {
            $sources[] = \is_array($source) ? SourceRef::fromArray($source) : new SourceRef(url: (string) $source);
        }

        return new Question(
            id: Id::parse($this->req($raw, 'id', $ctx)),
            version: (int) ($raw['version'] ?? 1),
            officialTopic: $this->req($raw, 'official_topic', $ctx),
            officialItemId: $this->req($raw, 'official_item', $ctx),
            domain: $this->req($raw, 'domain', $ctx),
            subtopic: isset($raw['subtopic']) ? (string) $raw['subtopic'] : null,
            language: Language::from($this->req($raw, 'language', $ctx)),
            difficulty: $this->req($raw, 'difficulty', $ctx),
            cognitiveLevel: $this->req($raw, 'cognitive_level', $ctx),
            examSkill: $this->req($raw, 'exam_skill', $ctx),
            type: $this->req($raw, 'type', $ctx),
            answerMode: AnswerMode::from($this->req($raw, 'answer_mode', $ctx)),
            requiredAnswerCount: (int) ($raw['required_answer_count'] ?? 0),
            question: $this->req($raw, 'question', $ctx),
            choices: $choices,
            scoringPolicy: $this->req($raw, 'scoring_policy', $ctx),
            shuffleChoices: (bool) ($raw['shuffle_choices'] ?? true),
            negativeWording: (bool) ($raw['negative_wording'] ?? false),
            codeLanguage: isset($raw['code_language']) ? (string) $raw['code_language'] : null,
            estimatedTimeSeconds: (int) ($raw['estimated_time_seconds'] ?? 72),
            explanation: (string) ($raw['explanation'] ?? ''),
            officialSources: $sources,
            classification: Classification::from($this->req($raw, 'classification', $ctx)),
            pool: Pool::from($this->req($raw, 'pool', $ctx)),
            tags: array_map(strval(...), (array) ($raw['tags'] ?? [])),
            verificationStatus: VerificationStatus::from($this->req($raw, 'verification_status', $ctx)),
            reviewers: array_map(strval(...), (array) ($raw['reviewers'] ?? [])),
            reviewedAt: isset($raw['reviewed_at']) ? (string) $raw['reviewed_at'] : null,
        );
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function req(array $raw, string $key, string $ctx): string
    {
        if (!isset($raw[$key]) || !\is_scalar($raw[$key]) || '' === trim((string) $raw[$key])) {
            throw new SchemaException(\sprintf('%s: missing required field `%s`.', $ctx, $key));
        }

        return (string) $raw[$key];
    }
}
