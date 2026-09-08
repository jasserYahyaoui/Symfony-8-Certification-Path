<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\ContentLevel;
use CertPath\Domain\Course;
use CertPath\Domain\Language;
use CertPath\Domain\LearningOutcome;
use CertPath\Domain\QuestionArchetype;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Domain\VerificationStatus;
use CertPath\Support\EntityType;
use CertPath\Support\Id;
use CertPath\Tests\Support\ItemFactory;
use CertPath\Tests\Support\QuestionFactory;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule\OutcomeAssessmentRule;
use CertPath\Validation\Rule\QuestionArchetypeRule;
use CertPath\Validation\Rule\RevisionBudgetRule;
use CertPath\Validation\Severity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The three rules added by refinement framework version 2 (ADR-0007).
 *
 * Each rule is tested in both directions — it fires on the defect AND stays
 * silent on the sound case — because a rule that only ever passes has not been
 * shown to be capable of failing. Five checks in this project were found to be
 * vacuous exactly that way.
 */
#[CoversClass(QuestionArchetypeRule::class)]
#[CoversClass(OutcomeAssessmentRule::class)]
#[CoversClass(RevisionBudgetRule::class)]
final class RefinementFrameworkRuleTest extends TestCase
{
    // ---- ARC-001 -----------------------------------------------------------

    public function testAQuestionInARefinedLotWithoutAnArchetypeIsRejected(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-01']);
        $content = $this->content(
            [$item],
            [QuestionFactory::make(['officialItemId' => $item->id->value])],
            refined: ['lot-01'],
        );

        $violations = (new QuestionArchetypeRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame('ARC-001', $violations[0]->ruleId);
        self::assertSame(Severity::Error, $violations[0]->severity);
    }

    /**
     * The staging that keeps the rule honest: 550 questions were written before
     * the axis existed. Failing the build over them would have forced either a
     * fabricated archetype on each or the rule's removal.
     */
    public function testAQuestionOutsideARefinedLotNeedsNoArchetype(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-07']);
        $content = $this->content(
            [$item],
            [QuestionFactory::make(['officialItemId' => $item->id->value])],
            refined: ['lot-01'],
        );

        self::assertSame([], (new QuestionArchetypeRule())->check($content));
    }

    /**
     * The half that cannot be satisfied by writing the same label everywhere:
     * the declared archetype has to agree with the question actually written.
     */
    public function testAnArchetypeContradictingTheQuestionIsRejectedEverywhere(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-07']);
        $content = $this->content(
            [$item],
            [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                // CODE_OUTPUT shows code; this question declares no code_language.
                'questionArchetype' => QuestionArchetype::CodeOutput,
            ])],
            refined: [],
        );

        $violations = (new QuestionArchetypeRule())->check($content);

        self::assertCount(1, $violations);
        self::assertStringContainsString('code_language', $violations[0]->message);
    }

    public function testACodeDiagnosisQuestionThatDoesNotDiagnoseIsRejected(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-07']);
        $content = $this->content(
            [$item],
            [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'questionArchetype' => QuestionArchetype::CodeDiagnosis,
                'examSkill' => 'RECOGNIZE',
            ])],
            refined: [],
        );

        $messages = array_map(static fn ($v): string => $v->message, (new QuestionArchetypeRule())->check($content));

        self::assertNotSame([], $messages);
        self::assertStringContainsString('exam_skill', implode(' ', $messages));
    }

    public function testAVersionAttributionQuestionNamingNoVersionIsRejected(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-07']);
        $content = $this->content(
            [$item],
            [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'questionArchetype' => QuestionArchetype::VersionAttribution,
                'question' => 'Which feature was introduced most recently?',
            ])],
            refined: [],
        );

        $violations = (new QuestionArchetypeRule())->check($content);

        self::assertCount(1, $violations);
        self::assertStringContainsString('names no version', $violations[0]->message);
    }

    public function testAConsistentArchetypeIsAccepted(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-07']);
        $content = $this->content(
            [$item],
            [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'questionArchetype' => QuestionArchetype::VersionAttribution,
                'question' => 'Which of these was added in PHP 8.4?',
            ])],
            refined: ['lot-07'],
        );

        self::assertSame([], (new QuestionArchetypeRule())->check($content));
    }

    // ---- PED-003 -----------------------------------------------------------

    public function testAnOutcomeAssessedByNoQuestionFailsARefinedLot(): void
    {
        $assessed = Id::mint(EntityType::LearningOutcome);
        $orphan = Id::mint(EntityType::LearningOutcome);

        $item = ItemFactory::make([
            'lot' => 'lot-01',
            'learningOutcomes' => [
                new LearningOutcome('Assessed', $assessed),
                new LearningOutcome('Never assessed', $orphan),
            ],
        ]);

        $content = $this->content(
            [$item],
            [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'assessesOutcomes' => [$assessed->value],
            ])],
            refined: ['lot-01'],
        );

        $blocking = array_values(array_filter(
            (new OutcomeAssessmentRule())->check($content),
            static fn ($v): bool => Severity::Error === $v->severity,
        ));

        self::assertCount(1, $blocking);
        self::assertStringContainsString($orphan->value, $blocking[0]->message);
    }

    /**
     * A stale id is worse than no id: it keeps an outcome looking assessed
     * after the outcome it pointed at has been rewritten or moved.
     */
    public function testAQuestionClaimingAnOutcomeItsItemDoesNotDeclareIsRejected(): void
    {
        $declared = Id::mint(EntityType::LearningOutcome);
        $stale = Id::mint(EntityType::LearningOutcome);

        $item = ItemFactory::make([
            'lot' => 'lot-07',
            'learningOutcomes' => [new LearningOutcome('Declared', $declared)],
        ]);

        $content = $this->content(
            [$item],
            [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'assessesOutcomes' => [$declared->value, $stale->value],
            ])],
            refined: [],
        );

        $blocking = array_values(array_filter(
            (new OutcomeAssessmentRule())->check($content),
            static fn ($v): bool => Severity::Error === $v->severity,
        ));

        self::assertCount(1, $blocking);
        self::assertStringContainsString($stale->value, $blocking[0]->message);
    }

    public function testEveryOutcomeAssessedIsAccepted(): void
    {
        $one = Id::mint(EntityType::LearningOutcome);
        $two = Id::mint(EntityType::LearningOutcome);

        $item = ItemFactory::make([
            'lot' => 'lot-01',
            'learningOutcomes' => [new LearningOutcome('One', $one), new LearningOutcome('Two', $two)],
        ]);

        $content = $this->content(
            [$item],
            [
                QuestionFactory::make(['officialItemId' => $item->id->value, 'assessesOutcomes' => [$one->value]]),
                QuestionFactory::make(['officialItemId' => $item->id->value, 'assessesOutcomes' => [$two->value]]),
            ],
            refined: ['lot-01'],
        );

        self::assertSame([], (new OutcomeAssessmentRule())->check($content));
    }

    /**
     * The aggregate that keeps this rule from being silent — and therefore
     * untested — on a corpus where no outcome carries an id yet.
     */
    public function testTheArithmeticShortfallIsReportedAsAWarning(): void
    {
        $item = ItemFactory::make([
            'lot' => 'lot-07',
            'learningOutcomes' => [new LearningOutcome('One'), new LearningOutcome('Two')],
        ]);

        $content = $this->content(
            [$item],
            [QuestionFactory::make(['officialItemId' => $item->id->value])],
            refined: [],
        );

        $violations = (new OutcomeAssessmentRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame(Severity::Warning, $violations[0]->severity);
        self::assertStringContainsString('fewer questions than declared learning outcomes', $violations[0]->message);
    }

    // ---- REV-001 -----------------------------------------------------------

    public function testACourseOverTheBudgetForItsLevelFailsARefinedLot(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-01', 'contentLevel' => ContentLevel::Minimal]);
        $budget = RevisionBudgetRule::budgets()[ContentLevel::Minimal->value];

        $content = $this->content(
            [$item],
            [],
            refined: ['lot-01'],
            courses: [$this->course($item->id->value, ContentLevel::Minimal, $budget + 1)],
        );

        $violations = (new RevisionBudgetRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame('REV-001', $violations[0]->ruleId);
        self::assertSame(Severity::Error, $violations[0]->severity);
    }

    public function testTheSameExcessOutsideARefinedLotIsOnlyAWarning(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-07', 'contentLevel' => ContentLevel::Minimal]);
        $budget = RevisionBudgetRule::budgets()[ContentLevel::Minimal->value];

        $content = $this->content(
            [$item],
            [],
            refined: ['lot-01'],
            courses: [$this->course($item->id->value, ContentLevel::Minimal, $budget + 1)],
        );

        $violations = (new RevisionBudgetRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame(Severity::Warning, $violations[0]->severity);
    }

    public function testACourseAtExactlyTheBudgetIsAccepted(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-01', 'contentLevel' => ContentLevel::Minimal]);
        $budget = RevisionBudgetRule::budgets()[ContentLevel::Minimal->value];

        $content = $this->content(
            [$item],
            [],
            refined: ['lot-01'],
            courses: [$this->course($item->id->value, ContentLevel::Minimal, $budget)],
        );

        self::assertSame([], (new RevisionBudgetRule())->check($content));
    }

    /**
     * The budgets and the derivation published in docs/policy/revision-budget.md
     * are one claim; if they drift apart the policy stops describing the rule.
     */
    public function testThePublishedCorpusCeilingMatchesTheBudgets(): void
    {
        self::assertSame(136_500, RevisionBudgetRule::corpusCeiling(27, 125, 11));
    }

    /**
     * str_word_count() splits accented words, so a French corpus would be
     * over-counted against its own budget.
     */
    public function testBodyWordsAreCountedWithoutSplittingAccentedWords(): void
    {
        $course = new Course(
            id: Id::mint(EntityType::Course),
            officialItemId: 'OIT-000000000001',
            title: 'Course',
            contentLevel: ContentLevel::Minimal,
            body: 'défaut privée créée',
            officialSources: [],
            language: Language::French,
            verificationStatus: VerificationStatus::Verified,
        );

        self::assertSame(3, $course->wordCount());
    }

    // ---- fixtures ----------------------------------------------------------

    /**
     * @param list<\CertPath\Domain\OfficialItem> $items
     * @param list<\CertPath\Domain\Question>     $questions
     * @param list<string>                        $refined
     * @param list<Course>                        $courses
     */
    private function content(array $items, array $questions, array $refined, array $courses = []): ContentSet
    {
        return new ContentSet(
            matrix: new SyllabusMatrix($items),
            questions: $questions,
            courses: $courses,
            frameworkRefinedLots: $refined,
        );
    }

    private function course(string $itemId, ContentLevel $level, int $words): Course
    {
        return new Course(
            id: Id::mint(EntityType::Course),
            officialItemId: $itemId,
            title: 'Course',
            contentLevel: $level,
            body: trim(str_repeat('mot ', $words)),
            officialSources: [],
            language: Language::French,
            verificationStatus: VerificationStatus::Verified,
        );
    }
}
