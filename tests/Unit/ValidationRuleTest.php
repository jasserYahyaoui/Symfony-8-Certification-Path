<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\AnswerMode;
use CertPath\Domain\Choice;
use CertPath\Domain\Classification;
use CertPath\Domain\ContentLevel;
use CertPath\Domain\ItemStatus;
use CertPath\Domain\Pool;
use CertPath\Domain\SourceRef;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Support\EntityType;
use CertPath\Support\Id;
use CertPath\Tests\Support\ItemFactory;
use CertPath\Tests\Support\QuestionFactory;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule\DuplicateQuestionRule;
use CertPath\Validation\Rule\EnrichmentBudgetRule;
use CertPath\Validation\Rule\ExamReadyEvidenceRule;
use CertPath\Validation\Rule\HoldoutIsolationRule;
use CertPath\Validation\Rule\LearningOutcomeRule;
use CertPath\Validation\Rule\OfficialWordingLockRule;
use CertPath\Validation\Rule\OutOfScopeContaminationRule;
use CertPath\Validation\Rule\QuestionIntegrityRule;
use CertPath\Validation\Rule\ReferentialIntegrityRule;
use CertPath\Validation\Rule\SourceAnchorRule;
use CertPath\Validation\Rule\UniqueItemIdsRule;
use CertPath\Validation\Rule\VersionContaminationRule;
use CertPath\Validation\Severity;
use CertPath\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidationRuleTest extends TestCase
{
    public function testDuplicateItemIdsAreRejected(): void
    {
        $id = Id::mint(EntityType::OfficialItem);
        $content = self::content([
            ItemFactory::make(['id' => $id]),
            ItemFactory::make(['id' => $id]),
        ]);

        self::assertViolates(new UniqueItemIdsRule(), $content, 'SYL-001');
    }

    /**
     * §3.1: official wording is verbatim; changing it is a syllabus refresh.
     */
    public function testChangedOfficialWordingWithoutARefreshFails(): void
    {
        $item = ItemFactory::make(['officialWording' => 'New wording']);
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            wordingFingerprints: [$item->id->value => OfficialWordingLockRule::fingerprint('Original wording')],
        );

        self::assertViolates(new OfficialWordingLockRule(), $content, 'SYL-002');
    }

    public function testMatchingWordingPassesTheLock(): void
    {
        $item = ItemFactory::make(['officialWording' => 'Stable wording']);
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            wordingFingerprints: [$item->id->value => OfficialWordingLockRule::fingerprint('Stable wording')],
        );

        self::assertSame([], (new OfficialWordingLockRule())->check($content));
    }

    public function testOfficialItemWithoutLearningOutcomeFails(): void
    {
        $content = self::content([ItemFactory::make(['learningOutcomes' => []])]);

        self::assertViolates(new LearningOutcomeRule(), $content, 'PED-001');
    }

    /**
     * §4.1: DEEP must never be the default, so it needs a real justification.
     */
    public function testDeepLevelWithAThinJustificationFails(): void
    {
        $content = self::content([ItemFactory::make([
            'contentLevel' => ContentLevel::Deep,
            'contentLevelJustification' => 'complex',
        ])]);

        self::assertViolates(new LearningOutcomeRule(), $content, 'PED-001');
    }

    public function testSourceFromCurrentDocsIsRejected(): void
    {
        $content = self::content([ItemFactory::make([
            'officialSources' => [new SourceRef(url: 'https://symfony.com/doc/current/routing.html', branch: '8.0')],
        ])]);

        self::assertViolates(new SourceAnchorRule(), $content, 'SRC-001');
    }

    public function testSourceWithoutAVersionAnchorIsRejected(): void
    {
        $content = self::content([ItemFactory::make([
            'officialSources' => [new SourceRef(url: 'https://example.test/page', anchor: 'x')],
        ])]);

        self::assertViolates(new SourceAnchorRule(), $content, 'SRC-001');
    }

    public function testSourceOutsideSymfony80IsFlaggedAsContamination(): void
    {
        $content = self::content([ItemFactory::make([
            'officialSources' => [new SourceRef(url: 'https://symfony.com/doc/8.4/routing.html', branch: '8.4')],
        ])]);

        self::assertViolates(new VersionContaminationRule(), $content, 'VER-001');
    }

    public function testQuestionMappedToAnUnknownItemFails(): void
    {
        $content = new ContentSet(
            matrix: new SyllabusMatrix([]),
            questions: [QuestionFactory::make(['officialItemId' => 'OIT-999999999999'])],
        );

        self::assertViolates(new QuestionIntegrityRule(), $content, 'QST-001');
    }

    public function testAnswerCountMismatchFails(): void
    {
        $item = ItemFactory::make();
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            questions: [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'answerMode' => AnswerMode::Multiple,
                'requiredAnswerCount' => 2,
            ])],
        );

        self::assertViolates(new QuestionIntegrityRule(), $content, 'QST-001');
    }

    public function testDistractorWithoutAnExplanationFails(): void
    {
        $item = ItemFactory::make();
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            questions: [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'choices' => [
                    new Choice(Id::mint(EntityType::Choice), 'Right', true),
                    new Choice(Id::mint(EntityType::Choice), 'Wrong', false),
                ],
            ])],
        );

        self::assertViolates(new QuestionIntegrityRule(), $content, 'QST-001');
    }

    /**
     * §7.3 and §17: holdout leakage is a critical blocker.
     */
    public function testHoldoutQuestionReferencedAsLearningMaterialFails(): void
    {
        $questionId = Id::mint(EntityType::Question);
        $item = ItemFactory::make(['questionRefs' => [$questionId->value]]);

        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            questions: [QuestionFactory::make(['id' => $questionId, 'pool' => Pool::Holdout])],
        );

        self::assertViolates(new HoldoutIsolationRule(), $content, 'POOL-001');
    }

    public function testExcludedTopicInAScoredQuestionFails(): void
    {
        $item = ItemFactory::make();
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            questions: [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'question' => 'How does Doctrine map this entity?',
            ])],
            excludedTerms: ['doctrine'],
        );

        self::assertViolates(new OutOfScopeContaminationRule(), $content, 'SCOPE-001');
    }

    /**
     * §1.5 permits an excluded term inside a labelled exclusion explanation.
     */
    public function testExcludedTopicIsAllowedWhenTaggedAsAnExclusionNote(): void
    {
        $item = ItemFactory::make();
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            questions: [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'question' => 'Doctrine is out of scope for this exam.',
                'tags' => ['exclusion-note'],
            ])],
            excludedTerms: ['doctrine'],
        );

        self::assertSame([], (new OutOfScopeContaminationRule())->check($content));
    }

    public function testNearDuplicateQuestionsAreDetected(): void
    {
        $item = ItemFactory::make();
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            questions: [
                QuestionFactory::make(['question' => 'Which method returns the request?']),
                QuestionFactory::make(['question' => '  which   method returns the REQUEST??  ']),
            ],
        );

        self::assertViolates(new DuplicateQuestionRule(), $content, 'DUP-001');
    }

    public function testBrokenPrerequisiteFails(): void
    {
        $content = self::content([ItemFactory::make(['prerequisites' => ['OIT-999999999999']])]);

        self::assertViolates(new ReferentialIntegrityRule(), $content, 'REF-001');
    }

    public function testSelfPrerequisiteFails(): void
    {
        $id = Id::mint(EntityType::OfficialItem);
        $content = self::content([ItemFactory::make(['id' => $id, 'prerequisites' => [$id->value]])]);

        self::assertViolates(new ReferentialIntegrityRule(), $content, 'REF-001');
    }

    public function testEnrichmentAboveTenPercentFails(): void
    {
        $items = [ItemFactory::enrichment('T', 'lot-01')];
        for ($i = 0; $i < 5; ++$i) {
            $items[] = ItemFactory::examReady('T', 'lot-01');
        }

        self::assertViolates(new EnrichmentBudgetRule(), self::content($items), 'SCOPE-002');
    }

    public function testExamReadyWithoutEvidenceFails(): void
    {
        $content = self::content([ItemFactory::make([
            'status' => ItemStatus::ExamReady,
            'examReady' => true,
            'courseRefs' => [],
            'questionRefs' => [],
            'lastVerifiedAt' => null,
        ])]);

        self::assertViolates(new ExamReadyEvidenceRule(), $content, 'RDY-001');
    }

    public function testFullyEvidencedExamReadyItemPasses(): void
    {
        self::assertSame([], (new ExamReadyEvidenceRule())->check(self::content([ItemFactory::make()])));
    }

    public function testValidatorReportsBlockingViolations(): void
    {
        $violations = (new Validator([new UniqueItemIdsRule()]))->run(self::content([
            ItemFactory::make(['id' => $id = Id::mint(EntityType::OfficialItem)]),
            ItemFactory::make(['id' => $id]),
        ]));

        self::assertTrue(Validator::hasBlockingViolation($violations));
    }

    public function testOutOfScopeClassifiedQuestionIsNeverScorable(): void
    {
        self::assertFalse(Classification::OutOfScope->isScorable());
        self::assertFalse(Classification::Enrichment->isScorable());
        self::assertTrue(Classification::Official->isScorable());
    }

    /**
     * @param list<\CertPath\Domain\OfficialItem> $items
     */
    private static function content(array $items): ContentSet
    {
        return new ContentSet(matrix: new SyllabusMatrix($items));
    }

    private static function assertViolates(object $rule, ContentSet $content, string $expectedRuleId): void
    {
        /** @var \CertPath\Validation\Rule $rule */
        $violations = $rule->check($content);

        self::assertNotEmpty($violations, 'expected rule '.$expectedRuleId.' to report a violation');
        self::assertSame($expectedRuleId, $violations[0]->ruleId);
        self::assertSame(Severity::Error, $violations[0]->severity);
    }
}
