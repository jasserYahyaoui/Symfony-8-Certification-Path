<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Build\DocsGenerator;
use CertPath\Domain\Course;
use CertPath\Domain\Pool;
use CertPath\Support\Project;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * docs/corrections/changelog.yml against the content as it stands (P3.3).
 *
 * tools/corrections/derive.py --check holds each entry to its commit: the ids
 * it names were changed there. This holds it to today: every id still exists,
 * every section it links to is still a heading of its course, no question is
 * HOLDOUT — the page would publish it — and the page and the versions payload
 * are built from it.
 */
#[CoversNothing]
final class CorrectionsChangelogTest extends TestCase
{
    private const array KINDS = ['CORRECTION', 'SCOPE', 'EXTENSION'];

    /** @return list<array<string, mixed>> */
    private static function entries(): array
    {
        $log = Yaml::parseFile(Project::locate()->path('docs/corrections/changelog.yml'));
        self::assertSame(1, $log['schema_version']);

        return $log['entries'];
    }

    public function testEveryEntryIsWellFormed(): void
    {
        $ids = [];
        $previous = '';
        foreach (self::entries() as $i => $entry) {
            $where = $entry['id'] ?? "entry #$i";
            self::assertMatchesRegularExpression('/^CVR-[0-9a-z]{12}$/', $entry['id'], "$where: id");
            self::assertContains($entry['kind'], self::KINDS, "$where: kind");
            self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $entry['date'], "$where: date, quoted");
            self::assertMatchesRegularExpression('/^[0-9a-f]{7,40}$/', $entry['commit'], "$where: commit, quoted");
            self::assertIsInt($entry['pull_request'], "$where: pull_request");
            self::assertNotSame('', trim($entry['title']), "$where: title");
            self::assertNotSame('', trim($entry['now']), "$where: now");
            // A correction and a lifted restriction say what was there; an
            // extension did not replace anything, so it must not pretend to.
            self::assertSame('EXTENSION' !== $entry['kind'], isset($entry['was']), "$where: `was` belongs to CORRECTION and SCOPE only");
            self::assertGreaterThanOrEqual($previous, $entry['date'], "$where: entries are kept in delivery order");
            $previous = $entry['date'];
            $ids[] = $entry['id'];
        }
        self::assertSame(array_unique($ids), $ids, 'A changelog id is used twice.');
    }

    public function testEveryLinkedIdExistsAndNoQuestionIsHoldout(): void
    {
        $content = Project::locate()->loadContentSet();
        $courses = [];
        foreach ($content->courses as $course) {
            $courses[$course->id->value] = $course;
        }
        $cards = [];
        foreach ($content->flashcards as $card) {
            $cards[$card->id->value] = true;
        }
        $questions = [];
        foreach ([...$content->questions, ...$content->comprehension] as $question) {
            $questions[$question->id->value] = $question->pool;
        }
        $facts = Yaml::parseFile(Project::locate()->path('docs/facts/central-facts.yml'))['facts'];

        foreach (self::entries() as $entry) {
            $where = $entry['id'];
            foreach ($entry['official_items'] as $item) {
                self::assertNotNull($content->matrix->findById($item), "$where: $item is not in the matrix");
            }
            foreach ($entry['facts'] as $fact) {
                self::assertArrayHasKey($fact, $facts, "$where: $fact is not a central fact");
            }
            foreach ($entry['courses'] as $ref) {
                self::assertArrayHasKey($ref['id'], $courses, "$where: {$ref['id']} is not a course");
                self::assertNotEmpty($ref['sections'], "$where: {$ref['id']} links no section");
                foreach ($ref['sections'] as $heading) {
                    self::assertContains($heading, $courses[$ref['id']]->sectionHeadings(), "$where: {$ref['id']} has no section « $heading » — renamed since? Update the entry.");
                }
            }
            foreach ($entry['pages'] ?? [] as $page) {
                $path = Project::locate()->path($page['path']);
                self::assertFileExists($path, "$where: {$page['path']}");
                foreach ($page['sections'] as $heading) {
                    self::assertContains($heading, Course::headingsOf((string) file_get_contents($path)), "$where: {$page['path']} has no section « $heading »");
                }
            }
            foreach ($entry['flashcards'] as $card) {
                self::assertArrayHasKey($card, $cards, "$where: $card is not a flashcard");
            }
            foreach ($entry['questions'] as $id) {
                self::assertArrayHasKey($id, $questions, "$where: $id is not a question");
                // assertTrue, not assertNotSame on the pool: the failure names
                // the entry, never the HOLDOUT question.
                self::assertTrue(Pool::Holdout !== $questions[$id], "$where lists a HOLDOUT question (id withheld).");
            }
            self::assertSame([], array_diff($entry['reversioned'] ?? [], $entry['questions']), "$where: reversioned names a question the entry does not list");
        }
    }

    public function testThePageAndTheVersionsPayloadAreBuilt(): void
    {
        $project = Project::locate();
        $content = $project->loadContentSet();
        (new DocsGenerator($project))->generate($content);

        $page = (string) file_get_contents($project->path('website/docs/corrections.md'));
        foreach (self::entries() as $entry) {
            self::assertStringContainsString('{#'.strtolower($entry['id']).'}', $page, "{$entry['id']} has no anchor on the page");
        }
        foreach ([...$content->questions, ...$content->comprehension] as $question) {
            // No question id is printed: the page counts questions by pool.
            self::assertStringNotContainsString($question->id->value, $page, 'The corrections page prints a question id.');
        }

        $versions = json_decode((string) file_get_contents($project->path('website/static/data/question-versions.json')), true);
        $published = [];
        foreach ($versions['questions'] as $row) {
            $published[$row['id']] = $row['version'];
        }
        foreach ([...$content->questions, ...$content->comprehension] as $question) {
            if (Pool::Holdout === $question->pool) {
                self::assertTrue(!isset($published[$question->id->value]), 'A HOLDOUT question reached question-versions.json (id withheld).');
            } elseif ($question->version > 1) {
                self::assertSame($question->version, $published[$question->id->value] ?? null, $question->id->value.' is missing from question-versions.json');
            }
        }
        foreach (self::entries() as $entry) {
            foreach ($entry['reversioned'] ?? [] as $id) {
                self::assertArrayHasKey($id, $published, "{$entry['id']}: $id was rewritten but has no published version");
            }
        }
    }
}
