<?php

declare(strict_types=1);

namespace CertPath\Schema;

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
     * @param list<Migration> $migrations
     */
    public function __construct(array $migrations = [])
    {
        $this->migrations = $migrations;
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
