<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Build\PayloadBuilder;
use CertPath\Domain\Pool;
use CertPath\Support\Project;
use CertPath\Validation\ContentSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The first-exposure rules of docs/policy/first-exposure.md (remediation P3.2):
 * where a learner may meet a question of each pool for the first time.
 *
 * Until now the Practice payload's guarantee was its `pool` label —
 * PracticePayloadTest asserts the label, not the questions under it. Every
 * payload a learner can sit is read here question by question, on the real
 * corpus, and the guard is shown to fire: a rule that has only been silent is
 * not passing (§16).
 */
#[CoversClass(PayloadBuilder::class)]
final class FirstExposureRulesTest extends TestCase
{
    private static function content(): ContentSet
    {
        static $content = null;

        return $content ??= (new Project(\dirname(__DIR__, 2)))->loadContentSet();
    }

    /** @return array<string, array{0: array<string, mixed>, 1: list<Pool>}> */
    private static function payloads(): array
    {
        $project = new Project(\dirname(__DIR__, 2));
        $content = self::content();
        $builder = new PayloadBuilder();
        $mocks = $project->loadMocksBlueprint();
        $four = $project->loadMockBlueprint('4');

        $payloads = [
            'practice' => [$builder->practicePayload($content), [Pool::Learning]],
            'exam' => [$builder->examPayload($content), [Pool::Validation]],
            'mock-4' => [$builder->mockPayload($content, $four), [Pool::Holdout]],
            'mock-5' => [$builder->weaknessMockPayload($content, $mocks), [Pool::Validation, Pool::Learning]],
            'exams' => [$builder->examSeriesPayload($content, $four), [Pool::Validation, Pool::Learning]],
        ];
        foreach (['mock-1', 'mock-2', 'mock-3'] as $id) {
            $payloads[$id] = [$builder->trainingMockPayload($content, $mocks, $id), [Pool::Validation]];
        }

        return $payloads;
    }

    public function testEveryPayloadCarriesOnlyThePoolsItMay(): void
    {
        $served = [];
        foreach (self::payloads() as $name => [$payload, $allowed]) {
            self::assertNotEmpty($payload['questions'], "$name is empty: the rule would hold vacuously");
            PayloadBuilder::assertPoolsAllowed($payload, self::content(), $allowed);
            $served[$name] = \count($payload['questions']);
        }

        // Each pool reaches the payloads the policy names, and no other.
        self::assertSame(
            \count(array_filter(self::content()->questions, static fn ($q): bool => Pool::Learning === $q->pool)),
            $served['practice'],
            'Practice Mode serves every LEARNING question',
        );
        self::assertSame(
            \count(array_filter(self::content()->questions, static fn ($q): bool => Pool::Holdout === $q->pool)),
            $served['mock-4'],
            'Mock 4 carries the whole holdout',
        );
    }

    public function testAValidationQuestionInPracticeModeIsRefused(): void
    {
        $payloads = self::payloads();
        $practice = $payloads['practice'][0];
        $practice['questions'][] = $payloads['exam'][0]['questions'][0];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/of the VALIDATION pool reached a payload that carries only LEARNING/');
        PayloadBuilder::assertPoolsAllowed($practice, self::content(), [Pool::Learning]);
    }

    public function testALearningQuestionInExamModeIsRefused(): void
    {
        $payloads = self::payloads();
        $exam = $payloads['exam'][0];
        $exam['questions'][] = $payloads['practice'][0]['questions'][0];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/of the LEARNING pool reached a payload that carries only VALIDATION/');
        PayloadBuilder::assertPoolsAllowed($exam, self::content(), [Pool::Validation]);
    }

    public function testAHoldoutQuestionOutsideMockFourIsRefusedWithoutItsId(): void
    {
        $payloads = self::payloads();
        $exams = $payloads['exams'][0];
        $holdout = $payloads['mock-4'][0]['questions'][0];
        $exams['questions'][] = $holdout;

        try {
            PayloadBuilder::assertPoolsAllowed($exams, self::content(), [Pool::Validation, Pool::Learning]);
            self::fail('A HOLDOUT question in the exam series was accepted.');
        } catch (\LogicException $e) {
            // assertTrue rather than the string assertions: a failure must not
            // print a message that might carry the id it is guarding.
            self::assertTrue(str_contains($e->getMessage(), 'its id is withheld'), 'The refusal does not say the id is withheld.');
            self::assertTrue(!str_contains($e->getMessage(), (string) $holdout['id']), 'The refusal printed a HOLDOUT id.');
        }
    }

    public function testAComprehensionQuestionInAnExamPayloadIsRefused(): void
    {
        $payloads = self::payloads();
        $mock = $payloads['mock-1'][0];
        $comprehension = (new PayloadBuilder())->comprehensionPayload(self::content());
        $mock['questions'][] = $comprehension['questions'][0];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/is not a question of the exam banks/');
        PayloadBuilder::assertPoolsAllowed($mock, self::content(), [Pool::Validation]);
    }
}
