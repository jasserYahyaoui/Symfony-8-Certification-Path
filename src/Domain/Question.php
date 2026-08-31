<?php

declare(strict_types=1);

namespace CertPath\Domain;

use CertPath\Support\Id;

/**
 * Master Plan §7. Every field required by §7.1 is represented; the empirical
 * fields of §7.5 are optional and stay null until real attempts exist.
 */
final readonly class Question
{
    /**
     * @param list<Choice>    $choices
     * @param list<SourceRef> $officialSources
     * @param list<string>    $tags
     * @param list<string>    $reviewers
     */
    public function __construct(
        public Id $id,
        public int $version,
        public string $officialTopic,
        public string $officialItemId,
        public string $domain,
        public ?string $subtopic,
        public Language $language,
        public string $difficulty,
        public string $cognitiveLevel,
        public string $examSkill,
        public string $type,
        public AnswerMode $answerMode,
        public int $requiredAnswerCount,
        public string $question,
        public array $choices,
        public string $scoringPolicy,
        public bool $shuffleChoices,
        public bool $negativeWording,
        public ?string $codeLanguage,
        public int $estimatedTimeSeconds,
        public string $explanation,
        public array $officialSources,
        public Classification $classification,
        public Pool $pool,
        public array $tags,
        public VerificationStatus $verificationStatus,
        public array $reviewers,
        public ?string $reviewedAt,
    ) {
    }

    /**
     * @return list<Choice>
     */
    public function correctChoices(): array
    {
        return array_values(array_filter($this->choices, static fn (Choice $c): bool => $c->correct));
    }

    public function correctAnswerCount(): int
    {
        return \count($this->correctChoices());
    }

    /**
     * §7.2: the declared number of correct answers must match reality, and a
     * single-answer question must have exactly one.
     */
    public function hasConsistentAnswerCount(): bool
    {
        $actual = $this->correctAnswerCount();

        if ($actual !== $this->requiredAnswerCount) {
            return false;
        }

        return match ($this->answerMode) {
            AnswerMode::Single => 1 === $actual,
            AnswerMode::Multiple => $actual >= 2,
        };
    }

    /**
     * §7.2: every distractor needs an explanation, so a learner can see why a
     * plausible-looking choice is wrong.
     */
    public function hasDistractorExplanations(): bool
    {
        foreach ($this->choices as $choice) {
            if (!$choice->correct && (null === $choice->explanation || '' === trim($choice->explanation))) {
                return false;
            }
        }

        return true;
    }

    public function mayAppearInPracticeMode(): bool
    {
        return $this->pool->isExposedInPracticeMode();
    }
}
