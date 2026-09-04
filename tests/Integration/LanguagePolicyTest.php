<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Domain\Pool;
use CertPath\Support\Project;
use PHPUnit\Framework\TestCase;

/**
 * Master Plan §5 — the language policy, and the figures it publishes.
 *
 * The policy document states measured compliance. A measurement that is not
 * re-measured decays into a claim: its Mock 4 row read "27 of 27" for a day
 * after the pool reached 75, and its glossary row read "absent" for a day
 * after the glossary shipped. Neither was false when written and both were
 * false when read, which is the failure mode this test exists to close.
 *
 * It asserts the thresholds §5 actually sets, and that the document's own
 * headline figures agree with the corpus it claims to describe.
 */
final class LanguagePolicyTest extends TestCase
{
    /** @return array{questions: list<\CertPath\Domain\Question>, policy: string} */
    private function fixture(): array
    {
        $project = Project::locate();
        $policy = $project->path('docs/policy/language-policy.md');

        self::assertFileExists($policy, '§5 requires a written language policy');

        return [
            'questions' => $project->loadContentSet()->questions,
            'policy' => (string) file_get_contents($policy),
        ];
    }

    /**
     * §5: "at least 50% of advanced questions must be in English". `hard` is
     * this project's operational reading of "advanced" — the policy says so.
     */
    public function testAtLeastHalfOfAdvancedQuestionsAreEnglish(): void
    {
        ['questions' => $questions] = $this->fixture();

        $advanced = 0;
        $english = 0;
        foreach ($questions as $question) {
            if ('hard' !== $question->difficulty) {
                continue;
            }
            ++$advanced;
            if ('en' === $question->language->value) {
                ++$english;
            }
        }

        self::assertGreaterThan(0, $advanced, 'no advanced question — the threshold would pass vacuously');
        self::assertGreaterThanOrEqual(0.5, $english / $advanced);
    }

    /**
     * §5: Mock 4 must be 100% English, and the policy binds that requirement
     * to the HOLDOUT pool. Mock 4 is now built from the whole pool, so this is
     * the same assertion the payload makes, stated where §5 can be read.
     */
    public function testEveryHoldoutQuestionIsEnglish(): void
    {
        ['questions' => $questions] = $this->fixture();

        $holdout = 0;
        foreach ($questions as $question) {
            if (Pool::Holdout !== $question->pool) {
                continue;
            }
            ++$holdout;
            self::assertSame('en', $question->language->value, $question->id->value.' is not English (§5, Mock 4)');
        }

        self::assertGreaterThan(0, $holdout, 'no holdout question — the requirement would pass vacuously');
    }

    /** §5: Mock 3 must be primarily English; the policy binds that to VALIDATION. */
    public function testTheValidationPoolIsPrimarilyEnglish(): void
    {
        ['questions' => $questions] = $this->fixture();

        $validation = 0;
        $english = 0;
        foreach ($questions as $question) {
            if (Pool::Validation !== $question->pool) {
                continue;
            }
            ++$validation;
            if ('en' === $question->language->value) {
                ++$english;
            }
        }

        self::assertGreaterThan(0, $validation);
        self::assertGreaterThan(0.5, $english / $validation, 'the VALIDATION pool is not primarily English');
    }

    /**
     * The document publishes counts. If they no longer describe the corpus,
     * the document is misleading even though nothing in the corpus is wrong.
     */
    public function testThePublishedFiguresStillDescribeTheCorpus(): void
    {
        ['questions' => $questions, 'policy' => $policy] = $this->fixture();

        $total = \count($questions);
        $french = 0;
        $english = 0;
        $holdout = 0;
        foreach ($questions as $question) {
            if ('fr' === $question->language->value) {
                ++$french;
            } else {
                ++$english;
            }
            if (Pool::Holdout === $question->pool) {
                ++$holdout;
            }
        }

        self::assertStringContainsString(
            \sprintf('%d questions)', $total),
            $policy,
            'the policy heading names a corpus size that is no longer the corpus size',
        );

        self::assertStringContainsString(
            \sprintf('**%d English, %d French**', $english, $french),
            $policy,
            'the policy publishes an English/French split that no longer matches the corpus',
        );

        self::assertStringContainsString(
            \sprintf('%d of %d = **100%%**', $holdout, $holdout),
            $policy,
            'the policy publishes a Mock 4 count that no longer matches the holdout pool',
        );
    }

    /**
     * §5 requires the glossary to exist. It did not for a while, and the
     * policy said so; saying so again once it exists would be as wrong.
     */
    public function testTheGlossaryExistsAndThePolicyNoLongerCallsItAbsent(): void
    {
        ['policy' => $policy] = $this->fixture();

        $glossary = Project::locate()->loadGlossary();

        self::assertNotSame([], $glossary, '§5 requires a French-to-English certification glossary');
        self::assertStringNotContainsString(
            'The **glossary does not exist**',
            $policy,
            'the policy still reports the glossary as missing',
        );
    }
}
