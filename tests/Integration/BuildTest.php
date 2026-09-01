<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Build\DocsGenerator;
use CertPath\Build\PayloadBuilder;
use CertPath\Domain\Pool;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Support\Project;
use CertPath\Tests\Support\ItemFactory;
use CertPath\Tests\Support\QuestionFactory;
use CertPath\Validation\ContentSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PayloadBuilder::class)]
#[CoversClass(DocsGenerator::class)]
final class BuildTest extends TestCase
{
    /**
     * Master Plan §7.3 and §17: a holdout question must not merely be hidden
     * by the UI — it must never be written into the Practice payload.
     */
    public function testPracticePayloadNeverContainsHoldoutQuestions(): void
    {
        $content = self::contentWithAllPools();

        $payload = (new PayloadBuilder())->practicePayload($content);
        $ids = array_column($payload['questions'], 'id');

        self::assertCount(1, $ids);
        self::assertSame(Pool::Learning->value, $payload['pool']);

        foreach ($content->questions as $question) {
            if (Pool::Holdout === $question->pool) {
                self::assertNotContains($question->id->value, $ids);
            }
        }
    }

    public function testHoldoutLeakAssertionRejectsAContaminatedPayload(): void
    {
        $content = self::contentWithAllPools();

        $holdout = null;
        foreach ($content->questions as $question) {
            if (Pool::Holdout === $question->pool) {
                $holdout = $question;
            }
        }
        self::assertNotNull($holdout);

        $contaminated = ['questions' => [['id' => $holdout->id->value]]];

        $this->expectException(\LogicException::class);
        PayloadBuilder::assertNoHoldoutLeak($contaminated, $content);
    }

    /**
     * ADR-0006: Exam Mode serves the VALIDATION pool. It used to serve HOLDOUT,
     * which spent the pool §22 reserves for a protected unseen assessment on
     * every practice exam a learner sat.
     */
    public function testExamPayloadContainsTheValidationPool(): void
    {
        $payload = (new PayloadBuilder())->examPayload(self::contentWithAllPools());

        self::assertSame(Pool::Validation->value, $payload['pool']);
        self::assertCount(1, $payload['questions']);
    }

    /**
     * The holdout is not deployed at all, so neither published payload may
     * carry one. This is the invariant §17 treats as a critical blocker.
     */
    public function testNeitherPublishedPayloadCarriesAHoldoutQuestion(): void
    {
        $content = self::contentWithAllPools();
        $builder = new PayloadBuilder();

        foreach ([$builder->practicePayload($content), $builder->examPayload($content)] as $payload) {
            PayloadBuilder::assertNoHoldoutLeak($payload, $content);

            foreach ($payload['questions'] as $exported) {
                self::assertNotSame(Pool::Holdout->value, $exported['pool'] ?? null);
            }
        }
    }

    public function testGeneratorProducesTheDocusaurusContentTree(): void
    {
        $project = Project::locate();

        $written = (new DocsGenerator($project))->generate(new ContentSet(matrix: new SyllabusMatrix([])));

        foreach ([
            'docs/index.md',
            'docs/syllabus/coverage.md',
            'docs/syllabus/exclusions.md',
            'static/data/practice.json',
            'static/data/exam.json',
            'static/data/coverage.json',
        ] as $expected) {
            self::assertContains($expected, $written, $expected.' should be generated');
            self::assertFileExists($project->path('website/'.$expected));
        }
    }

    public function testGeneratedDocsCarryFrontMatterAndNoUnresolvedPlaceholder(): void
    {
        $project = Project::locate();
        (new DocsGenerator($project))->generate(new ContentSet(matrix: new SyllabusMatrix([])));

        $intro = file_get_contents($project->path('website/docs/index.md'));
        self::assertIsString($intro);
        self::assertStringStartsWith("---\ntitle:", $intro);
        self::assertStringNotContainsString('{{', $intro);
        self::assertStringNotContainsString('<?php', $intro);
    }

    /**
     * MDX parses `<https://…>` as JSX and fails on the first slash, so the
     * generator must never emit Markdown autolinks. This broke the site build
     * once; the test exists so it cannot break it again silently.
     */
    public function testGeneratedPagesContainNoMarkdownAutolinks(): void
    {
        $project = Project::locate();
        (new DocsGenerator($project))->generate($project->loadContentSet());

        $offenders = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($project->path('website/docs'), \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || 'md' !== $file->getExtension()) {
                continue;
            }

            $body = file_get_contents($file->getPathname());
            if (\is_string($body) && 1 === preg_match('/<https?:\/\//', $body)) {
                $offenders[] = $file->getFilename();
            }
        }

        self::assertSame([], $offenders, 'generated pages must use [text](url), never <url>');
    }

    /**
     * MDX reads a bare `<` as a JSX tag and a bare `{` as an expression, so a
     * matrix field such as `config/packages/<env>/` failed the site build with
     * a parse error pointing nowhere near the canonical file that caused it.
     * The generator escapes; the canonical data stays readable prose.
     */
    public function testGeneratedPagesEscapeMdxControlCharactersFromCanonicalProse(): void
    {
        $project = Project::locate();
        (new DocsGenerator($project))->generate($project->loadContentSet());

        // The learning-outcome list is generated wholly from the matrix, so it
        // is where unescaped canonical prose would surface. An authored course
        // body is exempt on purpose and may carry `<env>` inside inline code.
        $escaped = 0;
        foreach ($this->generatedPages($project) as $name => $body) {
            $outcomes = $this->sectionOf($body, "## Objectifs d'apprentissage");
            self::assertStringNotContainsString('<', $outcomes, $name.' leaks a bare JSX-like tag');

            // Only `<` is escaped: a bare `>` is harmless to MDX, and escaping
            // it too would make the canonical prose harder to read in a diff.
            if (str_contains($outcomes, '&lt;env>')) {
                ++$escaped;
            }
        }

        self::assertGreaterThan(0, $escaped, 'no generated page exercises the escaping');
    }

    /**
     * A flashcard front or back is emitted inside `<details>`/`<summary>`,
     * which is a JSX context in MDX. A bare `{` there — a route path such as
     * `/{page}/blog` — is read as a JS expression and fails the site build
     * with "page is not defined". HTML escaping alone does not touch braces,
     * so both escapings are needed.
     */
    public function testFlashcardMarkupEscapesBracesInsideTheJsxContext(): void
    {
        $project = Project::locate();
        (new DocsGenerator($project))->generate($project->loadContentSet());

        $checked = 0;
        foreach ($this->generatedPages($project) as $name => $body) {
            foreach ($this->summaryLinesOf($body) as $line) {
                self::assertStringNotContainsString('{', $line, $name.' leaks a bare brace into JSX');
                ++$checked;
            }
        }

        self::assertGreaterThan(0, $checked, 'no generated page renders a flashcard');
    }

    /** @return list<string> the `<summary>` lines of a generated page */
    private function summaryLinesOf(string $body): array
    {
        $lines = [];
        foreach (explode("\n", $body) as $line) {
            if (str_starts_with($line, '<summary>')) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * An authored course body is Markdown written for this pipeline: it uses
     * fenced code and `<details>` on purpose, so it must reach the page
     * unescaped. Escaping it would render the flashcard markup as text.
     */
    public function testAuthoredCourseMarkupIsNotEscaped(): void
    {
        $project = Project::locate();
        (new DocsGenerator($project))->generate($project->loadContentSet());

        $body = $this->generatedPageContaining($project, '<details>');

        self::assertStringContainsString('<summary>', $body);
        self::assertStringNotContainsString('&lt;details&gt;', $body);
    }

    /** The text between a heading and the next one, or '' when absent. */
    private function sectionOf(string $body, string $heading): string
    {
        $start = strpos($body, $heading);
        if (false === $start) {
            return '';
        }

        $start += \strlen($heading);
        $end = strpos($body, "\n#", $start);

        return false === $end ? substr($body, $start) : substr($body, $start, $end - $start);
    }

    /**
     * @return iterable<string, string> generated page bodies, keyed by filename
     */
    private function generatedPages(Project $project): iterable
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($project->path('website/docs'), \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || 'md' !== $file->getExtension()) {
                continue;
            }

            $body = file_get_contents($file->getPathname());
            if (\is_string($body)) {
                yield $file->getFilename() => $body;
            }
        }
    }

    private function generatedPageContaining(Project $project, string $needle): string
    {
        foreach ($this->generatedPages($project) as $body) {
            if (str_contains($body, $needle)) {
                return $body;
            }
        }

        self::fail(\sprintf('no generated page contains "%s"', $needle));
    }

    /**
     * §19: an empty syllabus is reported as undefined, never as 0% coverage.
     */
    public function testCoverageReportsAnUndefinedDenominatorWhenTheSyllabusIsEmpty(): void
    {
        $project = Project::locate();
        (new DocsGenerator($project))->generate(new ContentSet(matrix: new SyllabusMatrix([])));

        $json = file_get_contents($project->path('website/static/data/coverage.json'));
        self::assertIsString($json);

        $coverage = json_decode($json, true);
        self::assertIsArray($coverage);
        self::assertFalse($coverage['denominator_established']);
        self::assertSame(0, $coverage['total_official_items']);

        $page = file_get_contents($project->path('website/docs/syllabus/coverage.md'));
        self::assertIsString($page);
        self::assertStringContainsString('UNDEFINED', $page);
        self::assertStringNotContainsString('Couverture : **0', $page);
    }

    /**
     * The generated tree must contain a page per official item, so that adding
     * a syllabus item adds a course page and a sidebar entry with no second
     * place to keep in sync.
     */
    public function testEachOfficialItemGetsItsOwnPage(): void
    {
        $project = Project::locate();

        $written = (new DocsGenerator($project))->generate(new ContentSet(
            matrix: new SyllabusMatrix([
                ItemFactory::make(['lot' => 'lot-05', 'officialItem' => 'Route requirements']),
                ItemFactory::make(['lot' => 'lot-05', 'officialItem' => 'URL generation']),
            ]),
        ));

        self::assertContains('docs/courses/lot-05/index.md', $written);
        self::assertContains('docs/courses/lot-05/route-requirements.md', $written);
        self::assertContains('docs/courses/lot-05/url-generation.md', $written);

        $page = file_get_contents($project->path('website/docs/courses/lot-05/route-requirements.md'));
        self::assertIsString($page);
        self::assertStringContainsString('Verbatim wording', $page, 'the official wording must be reproduced');

        // Leave the working tree in the state the real build produces.
        (new DocsGenerator($project))->generate($project->loadContentSet());
    }

    private static function contentWithAllPools(): ContentSet
    {
        $item = ItemFactory::make();

        return new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            questions: [
                QuestionFactory::make(['officialItemId' => $item->id->value, 'pool' => Pool::Learning]),
                QuestionFactory::make(['officialItemId' => $item->id->value, 'pool' => Pool::Validation]),
                QuestionFactory::make(['officialItemId' => $item->id->value, 'pool' => Pool::Holdout]),
            ],
        );
    }
}
