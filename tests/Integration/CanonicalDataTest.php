<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Schema\SchemaRegistry;
use CertPath\Support\Project;
use CertPath\Validation\Rule\OutOfScopeContaminationRule;
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

    /**
     * `exclusion-note` is a narrow allowance, not a way to keep an out-of-scope
     * aside that happens to trip SCOPE-001. §1.5 permits an excluded term only
     * inside a *clearly labelled* explanation of an exclusion or comparison, so
     * a question claiming the tag must actually say something is out of scope,
     * excluded, or not examinable.
     *
     * Without this, tagging is a general exemption: the pre-Lot-27 audit found
     * exactly that pattern proposed for QST-psqn0fe95khc, where a Doctrine
     * anecdote earned no point and taught nothing.
     */
    public function testEveryExclusionNoteActuallyExplainsABoundary(): void
    {
        // A genuine note states the boundary; an anecdote does not. The guard
        // must separate them, or the tag becomes a blanket exemption.
        self::assertTrue(self::explainsABoundary('Doctrine est hors du périmètre de l\'examen.'));
        self::assertFalse(self::explainsABoundary("Ce namespace date de l'époque des annotations Doctrine."));

        $content = Project::locate()->loadContentSet();
        $tagged = 0;

        foreach ($content->questions as $question) {
            if (!\in_array('exclusion-note', $question->tags, true)) {
                continue;
            }

            ++$tagged;
            $haystack = $question->question.' '.$question->explanation;
            foreach ($question->choices as $choice) {
                $haystack .= ' '.$choice->text.' '.($choice->explanation ?? '');
            }

            self::assertTrue(
                self::explainsABoundary($haystack),
                $question->id->value.' carries `exclusion-note` but never states a boundary. '
                .'The tag is not an exemption for keeping out-of-scope content (§1.5).',
            );
        }

        // Recorded, not asserted as a target: the corpus may legitimately hold
        // none. What must never happen is a tagged question that explains nothing.
        self::assertGreaterThanOrEqual(0, $tagged);
    }

    /**
     * §1.5 wants a *clearly labelled* exclusion or comparison, so the text has
     * to say something is out of scope rather than merely name an excluded tool.
     */
    private static function explainsABoundary(string $text): bool
    {
        $haystack = mb_strtolower($text);

        foreach (['hors du périmètre', 'hors scope', 'hors programme', 'pas au programme', 'exclu', 'not included', 'out of scope'] as $marker) {
            if (str_contains($haystack, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The specific question the audit corrected: it must answer on the Symfony
     * 8.0 namespace alone, with no excluded term anywhere in it — so it passes
     * on its content rather than on a tag.
     */
    public function testTheCorrectedRoutingImportQuestionCarriesNoExcludedTerm(): void
    {
        $project = Project::locate();
        $content = $project->loadContentSet();
        $terms = $project->loadExcludedTerms();

        $question = null;
        foreach ($content->questions as $candidate) {
            if ('QST-psqn0fe95khc' === $candidate->id->value) {
                $question = $candidate;
                break;
            }
        }

        self::assertNotNull($question, 'QST-psqn0fe95khc must still exist');
        self::assertNotContains('exclusion-note', $question->tags, 'no boundary is explained, so the tag must be gone');

        $haystack = mb_strtolower($question->question.' '.$question->explanation);
        foreach ($question->choices as $choice) {
            $haystack .= ' '.mb_strtolower($choice->text.' '.($choice->explanation ?? ''));
        }

        foreach ($terms as $term) {
            self::assertFalse(
                OutOfScopeContaminationRule::mentionsTerm($haystack, mb_strtolower(trim($term))),
                'QST-psqn0fe95khc must not depend on the excluded term "'.$term.'"',
            );
        }
    }

    public function testEverySchemaHasADeclaredVersion(): void
    {
        foreach (SchemaRegistry::all() as $name => $version) {
            self::assertGreaterThanOrEqual(1, $version, $name.' must declare a positive schema version');
        }
    }
}
