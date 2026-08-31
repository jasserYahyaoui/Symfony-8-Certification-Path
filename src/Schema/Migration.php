<?php

declare(strict_types=1);

namespace CertPath\Schema;

/**
 * Master Plan §11: every schema requires an explicit version and a migration
 * strategy. A migration upgrades one document from `fromVersion()` to
 * `fromVersion() + 1`.
 */
interface Migration
{
    public function schemaName(): string;

    public function fromVersion(): int;

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    public function upgrade(array $document): array;
}
