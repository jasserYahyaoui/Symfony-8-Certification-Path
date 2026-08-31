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
        $content = self::contentWithBothPools();

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
        $content = self::contentWithBothPools();

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

    public function testExamPayloadContainsTheHoldoutPool(): void
    {
        $payload = (new PayloadBuilder())->examPayload(self::contentWithBothPools());

        self::assertSame(Pool::Holdout->value, $payload['pool']);
        self::assertCount(1, $payload['questions']);
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

    private static function contentWithBothPools(): ContentSet
    {
        $item = ItemFactory::make();

        return new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            questions: [
                QuestionFactory::make(['officialItemId' => $item->id->value, 'pool' => Pool::Learning]),
                QuestionFactory::make(['officialItemId' => $item->id->value, 'pool' => Pool::Holdout]),
            ],
        );
    }
}
