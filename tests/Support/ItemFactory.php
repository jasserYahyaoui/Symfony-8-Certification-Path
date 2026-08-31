<?php

declare(strict_types=1);

namespace CertPath\Tests\Support;

use CertPath\Domain\Classification;
use CertPath\Domain\ContentLevel;
use CertPath\Domain\ItemStatus;
use CertPath\Domain\OfficialItem;
use CertPath\Domain\SourceRef;
use CertPath\Domain\VerificationStatus;
use CertPath\Support\EntityType;
use CertPath\Support\Id;

/**
 * Builds matrix items for tests without repeating all 30 fields of §3.3.
 */
final class ItemFactory
{
    /**
     * @param array<string, mixed> $overrides
     */
    public static function make(array $overrides = []): OfficialItem
    {
        $defaults = [
            'id' => Id::mint(EntityType::OfficialItem),
            'officialTopicOrder' => 1,
            'officialTopic' => 'Topic',
            'officialItemOrder' => 1,
            'officialItem' => 'Item',
            'officialWording' => 'Verbatim wording',
            'learningDomain' => 'symfony',
            'lot' => 'lot-00',
            'chapter' => null,
            'classification' => Classification::Official,
            'contentLevel' => ContentLevel::Standard,
            'contentLevelJustification' => 'Requires a distinction between two related mechanisms.',
            'learningOutcomes' => ['Explain the mechanism'],
            'requiredAssessmentModes' => ['QUESTION'],
            'minimumEvidence' => '3 unique questions across 2 sessions',
            'exclusionBoundaries' => 'None',
            'versionConstraints' => 'Symfony 8.0',
            'officialSources' => [new SourceRef(
                url: 'https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst',
                anchor: 'routing',
                repository: 'symfony/symfony-docs',
                branch: '8.0',
                commitSha: 'eea05cbfe063b9cf99afaf303b8cad76757f43bb',
            )],
            'courseRefs' => ['CRS-000000000001'],
            'flashcardRefs' => [],
            'questionRefs' => ['QST-000000000001'],
            'exerciseRefs' => [],
            'examRefs' => [],
            'prerequisites' => [],
            'status' => ItemStatus::ExamReady,
            'verificationStatus' => VerificationStatus::Verified,
            'examReady' => true,
            'lastVerifiedAt' => '2026-08-31',
            'reviewedBy' => 'tech-lead',
            'notes' => null,
        ];

        $values = array_merge($defaults, $overrides);

        return new OfficialItem(
            id: $values['id'],
            officialTopicOrder: $values['officialTopicOrder'],
            officialTopic: $values['officialTopic'],
            officialItemOrder: $values['officialItemOrder'],
            officialItem: $values['officialItem'],
            officialWording: $values['officialWording'],
            learningDomain: $values['learningDomain'],
            lot: $values['lot'],
            chapter: $values['chapter'],
            classification: $values['classification'],
            contentLevel: $values['contentLevel'],
            contentLevelJustification: $values['contentLevelJustification'],
            learningOutcomes: $values['learningOutcomes'],
            requiredAssessmentModes: $values['requiredAssessmentModes'],
            minimumEvidence: $values['minimumEvidence'],
            exclusionBoundaries: $values['exclusionBoundaries'],
            versionConstraints: $values['versionConstraints'],
            officialSources: $values['officialSources'],
            courseRefs: $values['courseRefs'],
            flashcardRefs: $values['flashcardRefs'],
            questionRefs: $values['questionRefs'],
            exerciseRefs: $values['exerciseRefs'],
            examRefs: $values['examRefs'],
            prerequisites: $values['prerequisites'],
            status: $values['status'],
            verificationStatus: $values['verificationStatus'],
            examReady: $values['examReady'],
            lastVerifiedAt: $values['lastVerifiedAt'],
            reviewedBy: $values['reviewedBy'],
            notes: $values['notes'],
        );
    }

    public static function examReady(string $topic, string $lot): OfficialItem
    {
        return self::make(['officialTopic' => $topic, 'lot' => $lot]);
    }

    public static function notReady(string $topic, string $lot): OfficialItem
    {
        return self::make([
            'officialTopic' => $topic,
            'lot' => $lot,
            'status' => ItemStatus::Specified,
            'examReady' => false,
        ]);
    }

    /** exam_ready=true while the lifecycle says otherwise — must not count. */
    public static function flagOnlyExamReady(string $topic, string $lot): OfficialItem
    {
        return self::make([
            'officialTopic' => $topic,
            'lot' => $lot,
            'status' => ItemStatus::Implemented,
            'examReady' => true,
        ]);
    }

    public static function prerequisite(string $topic, string $lot): OfficialItem
    {
        return self::make([
            'officialTopic' => $topic,
            'lot' => $lot,
            'classification' => Classification::Prerequisite,
        ]);
    }

    public static function enrichment(string $topic, string $lot): OfficialItem
    {
        return self::make([
            'officialTopic' => $topic,
            'lot' => $lot,
            'classification' => Classification::Enrichment,
        ]);
    }
}
