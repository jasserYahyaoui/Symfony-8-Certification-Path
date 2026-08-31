<?php

declare(strict_types=1);

namespace CertPath\Tests\Support;

use CertPath\Domain\AnswerMode;
use CertPath\Domain\Choice;
use CertPath\Domain\Classification;
use CertPath\Domain\Language;
use CertPath\Domain\Pool;
use CertPath\Domain\Question;
use CertPath\Domain\SourceRef;
use CertPath\Domain\VerificationStatus;
use CertPath\Support\EntityType;
use CertPath\Support\Id;

final class QuestionFactory
{
    /**
     * @param array<string, mixed> $overrides
     */
    public static function make(array $overrides = []): Question
    {
        $choices = $overrides['choices'] ?? [
            new Choice(Id::mint(EntityType::Choice), 'The correct one', true),
            new Choice(Id::mint(EntityType::Choice), 'A plausible distractor', false, 'It applies to a different lifecycle stage.'),
        ];

        return new Question(
            id: $overrides['id'] ?? Id::mint(EntityType::Question),
            version: 1,
            officialTopic: $overrides['officialTopic'] ?? 'Routing',
            officialItemId: $overrides['officialItemId'] ?? 'OIT-000000000001',
            domain: 'symfony',
            subtopic: null,
            language: $overrides['language'] ?? Language::English,
            difficulty: 'medium',
            cognitiveLevel: 'APPLY',
            examSkill: 'DISTINGUISH',
            type: 'mcq',
            answerMode: $overrides['answerMode'] ?? AnswerMode::Single,
            requiredAnswerCount: $overrides['requiredAnswerCount'] ?? 1,
            question: $overrides['question'] ?? 'Which statement is true?',
            choices: $choices,
            scoringPolicy: 'all-or-nothing',
            shuffleChoices: true,
            negativeWording: false,
            codeLanguage: null,
            estimatedTimeSeconds: 72,
            explanation: $overrides['explanation'] ?? 'Because the router matches in declaration order.',
            officialSources: $overrides['officialSources'] ?? [new SourceRef(
                url: 'https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst',
                anchor: 'routing',
                branch: '8.0',
            )],
            classification: $overrides['classification'] ?? Classification::Official,
            pool: $overrides['pool'] ?? Pool::Learning,
            tags: $overrides['tags'] ?? [],
            verificationStatus: $overrides['verificationStatus'] ?? VerificationStatus::Verified,
            reviewers: ['tech-lead'],
            reviewedAt: '2026-08-31',
        );
    }
}
