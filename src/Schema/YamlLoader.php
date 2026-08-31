<?php

declare(strict_types=1);

namespace CertPath\Schema;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads a canonical YAML document and brings it to the current schema version.
 */
final readonly class YamlLoader
{
    public function __construct(
        private MigrationRunner $migrations = new MigrationRunner(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function load(string $path, string $schemaName): array
    {
        if (!is_file($path)) {
            throw new SchemaException(\sprintf('Missing canonical file "%s".', $path));
        }

        try {
            $parsed = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new SchemaException(\sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (null === $parsed) {
            $parsed = [];
        }

        if (!\is_array($parsed)) {
            throw new SchemaException(\sprintf('"%s" must contain a YAML mapping.', $path));
        }

        return $this->migrations->migrate($schemaName, $parsed);
    }
}
