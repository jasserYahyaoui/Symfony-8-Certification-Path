<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Schema\SchemaRegistry;
use CertPath\Support\Project;
use CertPath\Validation\RuleSet;
use CertPath\Validation\Severity;
use CertPath\Validation\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Guards the repository's own canonical data, so a malformed YAML file fails
 * the test suite rather than the deployment.
 */
final class CanonicalDataTest extends TestCase
{
    public function testEveryMandatorySyllabusFileExists(): void
    {
        $project = Project::locate();

        foreach ([
            'docs/syllabus/official-syllabus.md',
            'docs/syllabus/syllabus-matrix.yml',
            'docs/syllabus/source-map.yml',
            'docs/syllabus/exclusions.yml',
            'docs/syllabus/coverage-report.md',
        ] as $required) {
            self::assertFileExists($project->path($required), $required.' is required by Master Plan §3.2');
        }
    }

    public function testCanonicalDataLoadsAndPassesTheMandatoryRules(): void
    {
        $content = Project::locate()->loadContentSet();

        $violations = (new Validator(RuleSet::mandatory()))->run($content);

        $blocking = array_filter($violations, static fn ($v): bool => Severity::Error === $v->severity);

        self::assertSame([], array_map(static fn ($v): string => $v->format(), $blocking));
    }

    public function testExclusionsCoverEveryProhibitedTopicOfSection1_5(): void
    {
        $terms = Project::locate()->loadExcludedTerms();
        $joined = mb_strtolower(implode(' ', $terms));

        foreach (['symfony ux', 'symfony ai', 'doctrine', 'monolog', 'assetmapper', 'polyfill', 'phpunit bridge', 'esi'] as $expected) {
            self::assertStringContainsString($expected, $joined, $expected.' must be listed in exclusions.yml');
        }
    }

    public function testEverySchemaHasADeclaredVersion(): void
    {
        foreach (SchemaRegistry::all() as $name => $version) {
            self::assertGreaterThanOrEqual(1, $version, $name.' must declare a positive schema version');
        }
    }
}
