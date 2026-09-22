<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Support\Project;
use PHPUnit\Framework\TestCase;

/**
 * The "Nouveautés de Symfony 8.0" chapter is ENRICHMENT published beside the
 * revision path, and the whole point of building it outside the matrix is that
 * it changes no metric. That promise is worth exactly as much as the test that
 * holds it, so these assertions are the promise.
 *
 * The chapter was first built AS matrix items, and that route was abandoned
 * here rather than forced through: registering it would have required either
 * writing project-authored labels into `wording.lock.yml` — the artefact that
 * certifies the syllabus is verbatim — or narrowing the mandatory rule SYL-002
 * so it stopped looking at them. The first corrupts the guarantee; the second
 * weakens a control to get a green build (§12). The chapter is projected from
 * canonical Markdown instead, exactly like `docs/revision/`.
 */
final class WhatsNewChapterTest extends TestCase
{
    public function testChapterIsAbsentFromTheSyllabusMatrix(): void
    {
        $matrix = (new Project(self::root()))->loadMatrix();

        foreach ($matrix->items as $item) {
            self::assertNotSame(
                'lot-28',
                $item->lot,
                'The enrichment chapter must not be registered as a matrix item.',
            );
        }
    }

    /**
     * The coverage denominator is OFFICIAL items and nothing else (§3.5).
     * Publishing a chapter must leave it identical, which is only meaningful
     * if something checks it.
     */
    public function testCoverageDenominatorCountsOnlyOfficialItems(): void
    {
        $matrix = (new Project(self::root()))->loadMatrix();

        self::assertSame(
            \count($matrix->items),
            \count($matrix->officialItems()),
            'Every matrix item is OFFICIAL; the chapter adds none.',
        );
    }

    /**
     * Every page carries flashcards at the four levels the project uses
     * everywhere else. A chapter whose pages quietly lost their cards would
     * still build, still deploy and still read fine.
     */
    public function testEveryChapterPageCarriesFlashcardsAtFourLevels(): void
    {
        $files = glob(self::root().'/docs/whats-new/*.md') ?: [];

        self::assertNotEmpty($files, 'The chapter has no pages.');

        foreach ($files as $file) {
            $body = (string) file_get_contents($file);
            $name = basename($file);

            self::assertStringContainsString('## Flashcards', $body, $name);

            foreach (['Mémorisation', 'Compréhension', 'Application', 'Pièges'] as $level) {
                self::assertStringContainsString('### '.$level, $body, $name.' — '.$level);
            }

            self::assertGreaterThanOrEqual(
                4,
                substr_count($body, '<details>'),
                $name.' carries fewer than four cards.',
            );
        }
    }

    /**
     * Enrichment may never be scored (§1.2). A question pointing at a chapter
     * page would be exactly that, so nothing in the canonical question banks
     * may reference one.
     */
    public function testNoQuestionReferencesTheChapter(): void
    {
        $files = glob(self::root().'/content/questions/*.yml') ?: [];

        foreach ($files as $file) {
            self::assertStringNotContainsString(
                'docs/whats-new',
                (string) file_get_contents($file),
                basename($file).' references the enrichment chapter.',
            );
        }
    }

    private static function root(): string
    {
        return \dirname(__DIR__, 2);
    }
}
