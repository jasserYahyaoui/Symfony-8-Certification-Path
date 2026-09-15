<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\ContentLevel;
use CertPath\Domain\Course;
use CertPath\Domain\Language;
use CertPath\Domain\LearningOutcome;
use CertPath\Domain\Pool;
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
        );

        $violations = (new QuestionArchetypeRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame('ARC-001', $violations[0]->ruleId);
        self::assertSame(Severity::Error, $violations[0]->severity);
    }

    /**
     * Was testAQuestionOutsideARefinedLotNeedsNoArchetype until 2026-09-15.
     *
     * The staging it asserted kept the rule honest while 550 questions written
     * before the axis existed carried no archetype: failing the build over them
     * would have forced either a fabricated archetype on each or the rule's
     * removal. Every one of them has since been annotated by its lot's
     * refinement pass, so ADR-0007's exit act removed the tolerance and the
     * field is required everywhere.
     */
    public function testAQuestionWithoutAnArchetypeIsRejectedInAnyLot(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-07']);
        $content = $this->content(
            [$item],
            [QuestionFactory::make(['officialItemId' => $item->id->value])],
        );

        $violations = (new QuestionArchetypeRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame(Severity::Error, $violations[0]->severity);
        self::assertStringContainsString('declares no question_archetype', $violations[0]->message);
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
        );

        self::assertSame([], (new OutcomeAssessmentRule())->check($content));
    }

    /**
     * The aggregate that keeps this rule from being silent — and therefore
     * untested — on a corpus where no outcome carries an id yet.
     *
     * The fixture is the case the aggregate cannot distinguish, and the reason
     * it proves nothing on its own: ONE question assessing BOTH outcomes. The
     * item is fully covered — no per-outcome error — and the count still trips,
     * because one question is fewer than two outcomes. Eight items of the real
     * corpus are in exactly this state; see
     * docs/audit/ped-003-shortfall-reading/.
     */
    public function testTheArithmeticShortfallIsReportedAsAWarningEvenWhenEveryOutcomeIsAssessed(): void
    {
        $one = Id::mint(EntityType::LearningOutcome);
        $two = Id::mint(EntityType::LearningOutcome);

        $item = ItemFactory::make([
            'lot' => 'lot-07',
            'learningOutcomes' => [new LearningOutcome('One', $one), new LearningOutcome('Two', $two)],
        ]);

        $content = $this->content(
            [$item],
            [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'assessesOutcomes' => [$one->value, $two->value],
            ])],
        );

        $violations = (new OutcomeAssessmentRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame(Severity::Warning, $violations[0]->severity);
        self::assertStringContainsString('fewer questions than declared learning outcomes', $violations[0]->message);
    }

    /**
     * BEHAVIOR_DIAGNOSIS describes the behaviour instead of shipping a listing.
     * Labelling a question that carries code with it would hide that code from
     * anyone querying the bank by shape — which is the only reason the axis
     * exists.
     */
    public function testABehaviorArchetypeOnAQuestionThatShipsCodeIsRejected(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-07']);
        $content = $this->content(
            [$item],
            [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'questionArchetype' => QuestionArchetype::BehaviorPrediction,
                'codeLanguage' => 'php',
            ])],
        );

        $violations = (new QuestionArchetypeRule())->check($content);

        self::assertCount(1, $violations);
        self::assertStringContainsString('ships code', $violations[0]->message);
    }

    public function testABehaviorDiagnosisThatDoesNotDiagnoseIsRejected(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-07']);
        $content = $this->content(
            [$item],
            [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'questionArchetype' => QuestionArchetype::BehaviorDiagnosis,
                'examSkill' => 'RECOGNIZE',
            ])],
        );

        $messages = array_map(static fn ($v): string => $v->message, (new QuestionArchetypeRule())->check($content));

        self::assertNotSame([], $messages);
        self::assertStringContainsString('exam_skill', implode(' ', $messages));
    }

    /**
     * BEHAVIOR_PREDICTION asks what results, not why, so it carries no skill
     * requirement. If this ever fails, the two behaviour archetypes have been
     * collapsed into one and the 2×2 they form has lost half its cells.
     */
    public function testBehaviorPredictionIsNotRequiredToDiagnose(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-07']);
        $content = $this->content(
            [$item],
            [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'questionArchetype' => QuestionArchetype::BehaviorPrediction,
                'examSkill' => 'RECOGNIZE',
            ])],
        );

        self::assertSame([], (new QuestionArchetypeRule())->check($content));
    }

    /**
     * A HOLDOUT question does not discharge an outcome: it reaches one payload,
     * sat once and unseen, so the learner can never practise that outcome and
     * the item's own minimum_evidence could not be produced for it.
     *
     * Found by linking Lot 01, where three outcomes — attribute targets and
     * IS_REPEATABLE, $this binding in a closure, and what a trait may contain —
     * were named by a holdout question and by nothing else.
     */
    public function testAnOutcomeNamedOnlyByAHoldoutQuestionIsNotAssessed(): void
    {
        $outcome = Id::mint(EntityType::LearningOutcome);
        $item = ItemFactory::make([
            'lot' => 'lot-01',
            'learningOutcomes' => [new LearningOutcome('Only in the holdout', $outcome)],
        ]);

        $content = $this->content(
            [$item],
            [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'pool' => Pool::Holdout,
                'assessesOutcomes' => [$outcome->value],
            ])],
        );

        $blocking = array_values(array_filter(
            (new OutcomeAssessmentRule())->check($content),
            static fn ($v): bool => Severity::Error === $v->severity,
        ));

        self::assertCount(1, $blocking);
        self::assertStringContainsString('HOLDOUT', $blocking[0]->message);
        self::assertStringContainsString('not assessable during study', $blocking[0]->message);
    }

    /** The same outcome, also named by a learning question, is assessed. */
    public function testAHoldoutQuestionBesideALearningOneIsHarmless(): void
    {
        $outcome = Id::mint(EntityType::LearningOutcome);
        $item = ItemFactory::make([
            'lot' => 'lot-01',
            'learningOutcomes' => [new LearningOutcome('Practised and held out', $outcome)],
        ]);

        $content = $this->content(
            [$item],
            [
                QuestionFactory::make([
                    'officialItemId' => $item->id->value,
                    'pool' => Pool::Holdout,
                    'assessesOutcomes' => [$outcome->value],
                ]),
                QuestionFactory::make([
                    'officialItemId' => $item->id->value,
                    'pool' => Pool::Learning,
                    'assessesOutcomes' => [$outcome->value],
                ]),
            ],
        );

        self::assertSame([], (new OutcomeAssessmentRule())->check($content));
    }

    // ---- REV-001 -----------------------------------------------------------


    public function testACourseOverTheBudgetForItsLevelFailsARefinedLot(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-01', 'contentLevel' => ContentLevel::Minimal]);
        $budget = RevisionBudgetRule::budgets()[ContentLevel::Minimal->value];

        $content = $this->content(
            [$item],
            [],
            courses: [$this->course($item->id->value, ContentLevel::Minimal, $budget + 1)],
        );

        $violations = (new RevisionBudgetRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame('REV-001', $violations[0]->ruleId);
        self::assertSame(Severity::Error, $violations[0]->severity);
    }

    /**
     * Was testTheSameExcessOutsideARefinedLotIsOnlyAWarning until 2026-09-15:
     * ADR-0007's exit act made the ceiling bite in every lot.
     */
    public function testTheSameExcessIsAnErrorInAnyLot(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-07', 'contentLevel' => ContentLevel::Minimal]);
        $budget = RevisionBudgetRule::budgets()[ContentLevel::Minimal->value];

        $content = $this->content(
            [$item],
            [],
            courses: [$this->course($item->id->value, ContentLevel::Minimal, $budget + 1)],
        );

        $violations = (new RevisionBudgetRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame(Severity::Error, $violations[0]->severity);
    }

    public function testACourseAtExactlyTheBudgetIsAccepted(): void
    {
        $item = ItemFactory::make(['lot' => 'lot-01', 'contentLevel' => ContentLevel::Minimal]);
        $budget = RevisionBudgetRule::budgets()[ContentLevel::Minimal->value];

        $content = $this->content(
            [$item],
            [],
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
     * @param list<Course>                        $courses
     */
    private function content(array $items, array $questions, array $courses = []): ContentSet
    {
        return new ContentSet(
            matrix: new SyllabusMatrix($items),
            questions: $questions,
            courses: $courses,
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
