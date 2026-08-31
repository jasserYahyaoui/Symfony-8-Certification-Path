<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Build\PayloadBuilder;
use CertPath\Build\SiteBuilder;
use CertPath\Domain\Pool;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Support\Project;
use CertPath\Tests\Support\ItemFactory;
use CertPath\Tests\Support\QuestionFactory;
use CertPath\Validation\ContentSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PayloadBuilder::class)]
#[CoversClass(SiteBuilder::class)]
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

    /**
     * The build must produce a deployable static site with no server runtime.
     */
    public function testBuildProducesADeployableStaticSite(): void
    {
        $project = Project::locate();
        $content = new ContentSet(matrix: new SyllabusMatrix([]));

        $written = (new SiteBuilder($project))->build($content);

        foreach (['index.html', 'practice.html', 'exam.html', '.nojekyll', 'data/practice.json', 'data/coverage.json', 'assets/css/app.css', 'assets/js/app.js'] as $expected) {
            self::assertContains($expected, $written, $expected.' should be generated');
            self::assertFileExists($project->buildDir().'/'.$expected);
        }

        $index = file_get_contents($project->buildDir().'/index.html');
        self::assertIsString($index);
        self::assertStringContainsString('<html lang="fr">', $index);
        self::assertStringNotContainsString('{{content}}', $index, 'no placeholder may survive rendering');
        self::assertStringNotContainsString('<?php', $index, 'the deployed artefact must contain no PHP');
    }

    /**
     * §19: an empty syllabus is reported as undefined, never as 0% coverage.
     */
    public function testCoveragePayloadReportsAnUndefinedDenominatorWhenTheSyllabusIsEmpty(): void
    {
        $project = Project::locate();
        (new SiteBuilder($project))->build(new ContentSet(matrix: new SyllabusMatrix([])));

        $json = file_get_contents($project->buildDir().'/data/coverage.json');
        self::assertIsString($json);

        $coverage = json_decode($json, true);
        self::assertIsArray($coverage);
        self::assertFalse($coverage['denominator_established']);
        self::assertSame(0, $coverage['total_official_items']);
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
