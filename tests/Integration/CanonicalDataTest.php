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
            'docs/syllabus/glossary.yml',
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

    /**
     * Master Plan §5 requires a French-to-English glossary. It has to stay a
     * revision aid, not drift into a Symfony dictionary, so every entry must
     * point at an official item that really exists and must carry both sides
     * of the pair.
     */
    public function testEveryGlossaryEntryResolvesToAnOfficialItem(): void
    {
        $project = Project::locate();
        $entries = $project->loadGlossary();
        $labels = [];
        foreach ($project->loadMatrix()->officialItems() as $item) {
            $labels[$item->officialItem] = true;
        }

        self::assertNotEmpty($entries, '§5 requires a glossary');

        $seen = [];
        foreach ($entries as $entry) {
            self::assertNotSame('', $entry['en'], 'every entry needs an English term');
            self::assertNotSame('', $entry['fr'], 'every entry needs a French rendering');
            self::assertArrayHasKey(
                $entry['see'],
                $labels,
                'glossary entry "'.$entry['en'].'" points at an unknown official item: '.$entry['see'],
            );

            self::assertArrayNotHasKey(
                mb_strtolower($entry['en']),
                $seen,
                'duplicate glossary term: '.$entry['en'],
            );
            $seen[mb_strtolower($entry['en'])] = true;
        }
    }

    /**
     * The glossary may never smuggle an out-of-scope topic back in (§1.5): it
     * is read by the learner exactly like a course page, and nothing tags it.
     */
    public function testTheGlossaryCarriesNoExcludedTerm(): void
    {
        $project = Project::locate();
        $terms = $project->loadExcludedTerms();

        foreach ($project->loadGlossary() as $entry) {
            $haystack = mb_strtolower($entry['en'].' '.$entry['fr'].' '.($entry['note'] ?? ''));

            foreach ($terms as $term) {
                self::assertFalse(
                    OutOfScopeContaminationRule::mentionsTerm($haystack, mb_strtolower(trim($term))),
                    'glossary entry "'.$entry['en'].'" references the excluded topic "'.$term.'"',
                );
            }
        }
    }

    /**
     * FR-1 and FR-2 both began as unaccented French written by a generator.
     * The glossary is new French prose, so it is pinned from the start.
     */
    public function testTheGlossaryFrenchIsAccented(): void
    {
        $accented = 0;
        foreach (Project::locate()->loadGlossary() as $entry) {
            if (preg_match('/[\x{00C0}-\x{00FF}]/u', $entry['fr'].' '.($entry['note'] ?? ''))) {
                ++$accented;
            }
        }

        self::assertGreaterThan(
            20,
            $accented,
            'the French side of the glossary reads as unaccented text — see issues FR-1 and FR-2',
        );
    }

    public function testEverySchemaHasADeclaredVersion(): void
    {
        foreach (SchemaRegistry::all() as $name => $version) {
            self::assertGreaterThanOrEqual(1, $version, $name.' must declare a positive schema version');
        }
    }

    /**
     * YAML discards silently, and twice in this matrix it did.
     *
     * An unquoted scalar loses everything after a ` #` — the rest of the line
     * is a comment — so `- Nommer les paramètres de #[AsCommand]` loaded as
     * "Nommer les paramètres de". An unquoted scalar containing ` : ` is
     * parsed as a mapping instead, and MatrixLoader coerces a non-scalar to
     * the empty string, so the outcome vanished altogether. Six learning
     * outcomes were affected and nothing noticed, because nothing rendered
     * them until Mock 4 reported results against them.
     *
     * This asserts the raw file rather than the parsed one: by the time the
     * parser has finished, the evidence of both losses is gone.
     */
    public function testNoCanonicalScalarIsSilentlyTruncatedByYaml(): void
    {
        $project = Project::locate();

        $files = glob($project->path('docs/syllabus').'/*.yml')
            ?: [];
        $files = array_merge($files, glob($project->path('content/questions').'/*.yml') ?: []);
        $files = array_merge($files, glob($project->path('docs/mocks').'/*.yml') ?: []);

        self::assertNotSame([], $files, 'no canonical YAML found — the check would pass vacuously');

        $truncated = [];

        foreach ($files as $file) {
            $lines = file($file, \FILE_IGNORE_NEW_LINES) ?: [];

            foreach ($lines as $number => $line) {
                // Only value lines: a list entry or a `key: value` pair whose
                // value is an unquoted, non-comment scalar.
                if (1 !== preg_match('/^\s*(?:-\s+|[A-Za-z_][\w.]*:\s+)(?<value>[^\s"\'#].*)$/u', $line, $m)) {
                    continue;
                }

                if (str_contains($m['value'], ' #')) {
                    $truncated[] = \sprintf('%s:%d cut short by an unquoted "#": %s', basename($file), $number + 1, trim($line));
                }
            }
        }

        self::assertSame([], $truncated, "an unquoted scalar loses everything after ' #'; quote it");
    }

    public function testEveryLearningOutcomeSurvivedParsing(): void
    {
        $raw = (new \Symfony\Component\Yaml\Parser())->parseFile(Project::locate()->matrixPath());

        $lost = [];
        foreach ($raw['items'] as $item) {
            foreach ($item['learning_outcomes'] ?? [] as $outcome) {
                if (!\is_string($outcome) || '' === trim($outcome)) {
                    $lost[] = $item['id'];
                }
            }
        }

        self::assertSame(
            [],
            $lost,
            'a learning outcome parsed to something other than a non-empty string — '
            .'an unquoted " : " makes YAML read it as a mapping, and the loader then coerces it to ""',
        );
    }
}
