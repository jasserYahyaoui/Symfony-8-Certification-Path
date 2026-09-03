<?php

declare(strict_types=1);

namespace CertPath\Schema;

/**
 * The single place where the current version of every canonical schema is
 * declared (Master Plan §11). Bumping a version here without providing the
 * matching migration is a hard failure, surfaced by MigrationRunner.
 */
final class SchemaRegistry
{
    public const string SYLLABUS_MATRIX = 'syllabus-matrix';
    public const string SOURCE_MAP = 'source-map';
    public const string EXCLUSIONS = 'exclusions';
    public const string GLOSSARY = 'glossary';
    public const string MOCK_BLUEPRINT = 'mock_blueprint';
    public const string ID_REGISTRY = 'id-registry';
    public const string QUESTION_BANK = 'question-bank';
    public const string FLASHCARD_DECK = 'flashcard-deck';
    public const string EXAM_BLUEPRINT = 'exam-blueprint';

    /** Browser-side storage schema (§13), versioned alongside the build-time ones. */
    public const string LEARNER_STATE = 'learner-state';

    /**
     * @var array<string, int>
     */
    private const array CURRENT_VERSIONS = [
        self::SYLLABUS_MATRIX => 1,
        self::SOURCE_MAP => 1,
        self::EXCLUSIONS => 1,
        self::GLOSSARY => 1,
        self::MOCK_BLUEPRINT => 1,
        self::ID_REGISTRY => 1,
        self::QUESTION_BANK => 1,
        self::FLASHCARD_DECK => 1,
        self::EXAM_BLUEPRINT => 1,
        self::LEARNER_STATE => 1,
    ];

    public static function currentVersion(string $schemaName): int
    {
        if (!isset(self::CURRENT_VERSIONS[$schemaName])) {
            throw new \InvalidArgumentException(\sprintf('Unknown schema "%s".', $schemaName));
        }

        return self::CURRENT_VERSIONS[$schemaName];
    }

    /**
     * @return array<string, int>
     */
    public static function all(): array
    {
        return self::CURRENT_VERSIONS;
    }
}
