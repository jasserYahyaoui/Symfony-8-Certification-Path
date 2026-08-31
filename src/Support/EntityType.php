<?php

declare(strict_types=1);

namespace CertPath\Support;

/**
 * The canonical entities of Master Plan §11 and their persistent-ID prefixes.
 *
 * Build-time entities live in versioned YAML under `docs/`. Runtime entities
 * (Attempt, Session, MasteryRecord, Weakness) exist only in the learner's
 * browser storage (§13) and are declared here so that the ID policy and the
 * storage schema share one canonical definition (ADR-0001).
 */
enum EntityType: string
{
    case OfficialTopic = 'OfficialTopic';
    case OfficialItem = 'OfficialItem';
    case LearningOutcome = 'LearningOutcome';
    case Course = 'Course';
    case Flashcard = 'Flashcard';
    case Question = 'Question';
    case Choice = 'Choice';
    case Exercise = 'Exercise';
    case Source = 'Source';
    case Attempt = 'Attempt';
    case Session = 'Session';
    case MasteryRecord = 'MasteryRecord';
    case Weakness = 'Weakness';
    case MockExam = 'MockExam';
    case ExamBlueprint = 'ExamBlueprint';
    case ContentVersion = 'ContentVersion';

    public function prefix(): string
    {
        return match ($this) {
            self::OfficialTopic => 'OTP',
            self::OfficialItem => 'OIT',
            self::LearningOutcome => 'OUT',
            self::Course => 'CRS',
            self::Flashcard => 'FLC',
            self::Question => 'QST',
            self::Choice => 'CHO',
            self::Exercise => 'EXC',
            self::Source => 'SRC',
            self::Attempt => 'ATT',
            self::Session => 'SES',
            self::MasteryRecord => 'MAS',
            self::Weakness => 'WKN',
            self::MockExam => 'MCK',
            self::ExamBlueprint => 'BLP',
            self::ContentVersion => 'CVR',
        };
    }

    /**
     * Runtime entities are never persisted in the repository; they are owned
     * by the browser (§13) and carry no build-time referential integrity.
     */
    public function isRuntimeOnly(): bool
    {
        return match ($this) {
            self::Attempt, self::Session, self::MasteryRecord, self::Weakness => true,
            default => false,
        };
    }

    public static function fromPrefix(string $prefix): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->prefix() === $prefix) {
                return $case;
            }
        }

        return null;
    }
}
