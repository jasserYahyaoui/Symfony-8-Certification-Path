<?php

declare(strict_types=1);

namespace CertPath\Schema;

use CertPath\Schema\Migrations\FlashcardDeckReadableUrl;
use CertPath\Schema\Migrations\QuestionBankReadableUrl;
use CertPath\Schema\Migrations\SyllabusMatrixOutcomeIdentity;

/**
 * Applies the registered migrations until a document reaches the current
 * schema version. A gap in the migration chain is a hard failure rather than
 * a silent pass, because a silently un-migrated document would corrupt
 * coverage figures downstream.
 */
final class MigrationRunner
{
    /** @var list<Migration> */
    private array $migrations;

    /**
     * @param list<Migration>|null $migrations null takes the project's registered
     *        migrations; an explicit list (including an empty one) is used as given,
     *        which is what lets a test drive the runner in isolation.
     */
    public function __construct(?array $migrations = null)
    {
        $this->migrations = $migrations ?? self::registered();
    }

    /**
     * Every migration this project ships, in no particular order.
     *
     * A version bumped in SchemaRegistry without its migration listed here is a
     * hard failure at load time rather than a silent pass — see migrate().
     *
     * @return list<Migration>
     */
    public static function registered(): array
    {
        return [
            new SyllabusMatrixOutcomeIdentity(),
            new QuestionBankReadableUrl(),
            new FlashcardDeckReadableUrl(),
        ];
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    public function migrate(string $schemaName, array $document): array
    {
        $target = SchemaRegistry::currentVersion($schemaName);
        $version = $document['schema_version'] ?? null;

        if (!\is_int($version)) {
            throw new SchemaException(\sprintf(
                'Schema "%s": missing or non-integer `schema_version`.',
                $schemaName,
            ));
        }

        if ($version > $target) {
            throw new SchemaException(\sprintf(
                'Schema "%s": document version %d is newer than the supported version %d. '
                .'Upgrade the toolchain rather than downgrading the document.',
                $schemaName,
                $version,
                $target,
            ));
        }

        while ($version < $target) {
            $migration = $this->find($schemaName, $version);
            if (null === $migration) {
                throw new SchemaException(\sprintf(
                    'Schema "%s": no migration registered from version %d to %d.',
                    $schemaName,
                    $version,
                    $version + 1,
                ));
            }

            $document = $migration->upgrade($document);
            ++$version;
            $document['schema_version'] = $version;
        }

        return $document;
    }

    private function find(string $schemaName, int $fromVersion): ?Migration
    {
        foreach ($this->migrations as $migration) {
            if ($migration->schemaName() === $schemaName && $migration->fromVersion() === $fromVersion) {
                return $migration;
            }
        }

        return null;
    }
}
