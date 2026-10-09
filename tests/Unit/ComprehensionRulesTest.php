<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\Language;
use CertPath\Domain\LearningOutcome;
use CertPath\Domain\OfficialItem;
use CertPath\Domain\Pool;
use CertPath\Domain\Question;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Support\EntityType;
use CertPath\Support\Id;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule\ComprehensionCoverageRule;
use CertPath\Validation\Rule\ComprehensionIndependenceRule;
use CertPath\Validation\Rule\ComprehensionQualityRule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;
use CertPath\Tests\Support\ItemFactory;
use CertPath\Tests\Support\QuestionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The three comprehension rules of ADR-0010. Each defect has its own test, and
 * each rule has an accepted case beside it: a rule that has only ever been
 * silent is not passing (§16).
 */
#[CoversClass(ComprehensionCoverageRule::class)]
#[CoversClass(ComprehensionIndependenceRule::class)]
#[CoversClass(ComprehensionQualityRule::class)]
final class ComprehensionRulesTest extends TestCase
{
    // ---- CMP-001 -----------------------------------------------------------

    public function testAnOutcomeNoComprehensionQuestionAssessesFailsTheLot(): void
    {
        [$one, $two] = [Id::mint(EntityType::LearningOutcome), Id::mint(EntityType::LearningOutcome)];
        $item = $this->item('lot-12', [$one, $two]);

        $violations = (new ComprehensionCoverageRule())->check($this->content(
            [$item],
            comprehension: [$this->comprehension($item, [$one->value])],
        ));

        self::assertCount(1, $violations);
        self::assertSame(Severity::Error, $violations[0]->severity);
        self::assertSame($item->id->value, $violations[0]->subject);
        self::assertStringContainsString($two->value, $violations[0]->message);
    }

    /**
     * The lot is the unit, not the item: an item of the lot that no question
     * names at all is the omission the rule exists to catch.
     */
    public function testAnItemOfTheLotNoQuestionNamesFailsTheLot(): void
    {
        $covered = Id::mint(EntityType::LearningOutcome);
        $forgotten = Id::mint(EntityType::LearningOutcome);
        $first = $this->item('lot-12', [$covered]);
        $second = $this->item('lot-12', [$forgotten]);

        $violations = (new ComprehensionCoverageRule())->check($this->content(
            [$first, $second],
            comprehension: [$this->comprehension($first, [$covered->value])],
        ));

        self::assertCount(1, $violations);
        self::assertSame($second->id->value, $violations[0]->subject);
    }

    public function testASynthesisQuestionCountsForEveryItemItRelates(): void
    {
        $own = Id::mint(EntityType::LearningOutcome);
        $related = Id::mint(EntityType::LearningOutcome);
        $first = $this->item('lot-12', [$own]);
        $second = $this->item('lot-12', [$related]);

        self::assertSame([], (new ComprehensionCoverageRule())->check($this->content(
            [$first, $second],
            comprehension: [$this->comprehension($first, [$own->value, $related->value], [$second->id->value])],
        )));
    }

    public function testALotWithoutComprehensionQuestionsIsNotChecked(): void
    {
        $started = Id::mint(EntityType::LearningOutcome);
        $first = $this->item('lot-12', [$started]);
        $notStarted = $this->item('lot-13', [Id::mint(EntityType::LearningOutcome)]);

        self::assertSame([], (new ComprehensionCoverageRule())->check($this->content(
            [$first, $notStarted],
            comprehension: [$this->comprehension($first, [$started->value])],
        )));
    }

    /**
     * An exam question assessing an outcome does not count: the comprehension
     * check is a separate exercise, and its completeness is its own.
     */
    public function testAnExamQuestionDoesNotCoverAComprehensionOutcome(): void
    {
        [$one, $two] = [Id::mint(EntityType::LearningOutcome), Id::mint(EntityType::LearningOutcome)];
        $item = $this->item('lot-12', [$one, $two]);

        $violations = (new ComprehensionCoverageRule())->check($this->content(
            [$item],
            questions: [QuestionFactory::make(['officialItemId' => $item->id->value, 'assessesOutcomes' => [$two->value]])],
            comprehension: [$this->comprehension($item, [$one->value])],
        ));

        self::assertCount(1, $violations);
        self::assertStringContainsString($two->value, $violations[0]->message);
    }

    // ---- CMP-002 -----------------------------------------------------------

    public function testAPromptCopiedFromAnExamQuestionIsRejectedAndNamesIt(): void
    {
        $item = $this->item('lot-12', [Id::mint(EntityType::LearningOutcome)]);
        $prompt = 'Which method of a console command receives the input and output objects when it runs?';
        $exam = QuestionFactory::make(['officialItemId' => $item->id->value, 'question' => $prompt, 'pool' => Pool::Validation]);
        $copy = $this->comprehension($item, [], question: $prompt);

        $violations = (new ComprehensionIndependenceRule())->check($this->content([$item], [$exam], [$copy]));

        self::assertCount(1, $violations);
        self::assertSame($copy->id->value, $violations[0]->subject);
        self::assertStringContainsString($exam->id->value, $violations[0]->message);
    }

    public function testAPromptRewordedFromAnExamQuestionIsRejected(): void
    {
        $item = $this->item('lot-12', [Id::mint(EntityType::LearningOutcome)]);
        $exam = QuestionFactory::make([
            'officialItemId' => $item->id->value,
            'question' => 'Which method of a console command receives the input and output objects when it runs?',
        ]);
        $reworded = $this->comprehension(
            $item,
            [],
            question: 'Which method of a console command is given the input and output objects when it runs?',
        );

        self::assertCount(1, (new ComprehensionIndependenceRule())->check($this->content([$item], [$exam], [$reworded])));
    }

    /**
     * The holdout is compared like every other pool, and the report must not
     * become a way of locating one of its questions.
     */
    public function testAMatchAgainstTheHoldoutIsRejectedWithoutItsId(): void
    {
        $item = $this->item('lot-12', [Id::mint(EntityType::LearningOutcome)]);
        $prompt = 'Which exit code does a console command return when it succeeds?';
        $holdout = QuestionFactory::make(['officialItemId' => $item->id->value, 'question' => $prompt, 'pool' => Pool::Holdout]);

        $violations = (new ComprehensionIndependenceRule())->check(
            $this->content([$item], [$holdout], [$this->comprehension($item, [], question: $prompt)]),
        );

        self::assertCount(1, $violations);
        self::assertStringContainsString('id withheld', $violations[0]->message);
        self::assertStringNotContainsString($holdout->id->value, $violations[0]->message);
    }

    public function testAQuestionWrittenApartIsAccepted(): void
    {
        $item = $this->item('lot-12', [Id::mint(EntityType::LearningOutcome)]);
        $exam = QuestionFactory::make([
            'officialItemId' => $item->id->value,
            'question' => 'Which method of a console command receives the input and output objects when it runs?',
        ]);
        $apart = $this->comprehension(
            $item,
            [],
            question: 'A teammate wants a required argument after an optional one. What happens when the definition is built?',
        );

        self::assertSame([], (new ComprehensionIndependenceRule())->check($this->content([$item], [$exam], [$apart])));
    }

    // ---- CMP-003 -----------------------------------------------------------

    public function testAComprehensionQuestionInFrenchIsRejected(): void
    {
        $outcome = Id::mint(EntityType::LearningOutcome);
        $item = $this->item('lot-12', [$outcome]);
        $question = $this->comprehension($item, [$outcome->value], language: Language::French);

        $own = $this->own((new ComprehensionQualityRule())->check($this->content([$item], comprehension: [$question])));

        self::assertCount(1, $own);
        self::assertStringContainsString('English', $own[0]->message);
    }

    public function testAnExamPoolQuestionInTheComprehensionSetIsRejected(): void
    {
        $outcome = Id::mint(EntityType::LearningOutcome);
        $item = $this->item('lot-12', [$outcome]);
        $question = $this->comprehension($item, [$outcome->value], pool: Pool::Learning);

        $own = $this->own((new ComprehensionQualityRule())->check($this->content([$item], comprehension: [$question])));

        self::assertCount(1, $own);
        self::assertStringContainsString('LEARNING', $own[0]->message);
    }

    public function testARelatedItemOfAnotherLotIsRejected(): void
    {
        $outcome = Id::mint(EntityType::LearningOutcome);
        $item = $this->item('lot-12', [$outcome]);
        $elsewhere = $this->item('lot-13', [Id::mint(EntityType::LearningOutcome)]);
        $question = $this->comprehension($item, [$outcome->value], [$elsewhere->id->value]);

        $own = $this->own((new ComprehensionQualityRule())->check($this->content([$item, $elsewhere], comprehension: [$question])));

        self::assertCount(1, $own);
        self::assertStringContainsString('lot-13', $own[0]->message);
    }

    public function testAnUnknownRelatedItemAndTheQuestionsOwnItemAreRejected(): void
    {
        $outcome = Id::mint(EntityType::LearningOutcome);
        $item = $this->item('lot-12', [$outcome]);
        $unknown = Id::mint(EntityType::OfficialItem)->value;
        $question = $this->comprehension($item, [$outcome->value], [$item->id->value, $unknown, $unknown]);

        $messages = array_map(
            static fn (Violation $v): string => $v->message,
            $this->own((new ComprehensionQualityRule())->check($this->content([$item], comprehension: [$question]))),
        );

        self::assertCount(3, $messages);
        self::assertStringContainsString('its own item', $messages[0]);
        self::assertStringContainsString('does not exist', $messages[1]);
        self::assertStringContainsString('listed twice', $messages[2]);
    }

    public function testAnOutcomeOfAnItemTheQuestionDoesNotCoverIsRejected(): void
    {
        $own = Id::mint(EntityType::LearningOutcome);
        $foreign = Id::mint(EntityType::LearningOutcome);
        $item = $this->item('lot-12', [$own]);
        $other = $this->item('lot-12', [$foreign]);

        $violations = $this->own((new ComprehensionQualityRule())->check($this->content(
            [$item, $other],
            comprehension: [$this->comprehension($item, [$own->value, $foreign->value])],
        )));

        self::assertCount(1, $violations);
        self::assertStringContainsString($foreign->value, $violations[0]->message);
    }

    public function testAQuestionAssessingNoOutcomeIsRejected(): void
    {
        $item = $this->item('lot-12', [Id::mint(EntityType::LearningOutcome)]);

        $own = $this->own((new ComprehensionQualityRule())->check(
            $this->content([$item], comprehension: [$this->comprehension($item, [])]),
        ));

        self::assertCount(1, $own);
        self::assertStringContainsString('assesses no learning outcome', $own[0]->message);
    }

    public function testASynthesisQuestionInsideItsLotIsAccepted(): void
    {
        $own = Id::mint(EntityType::LearningOutcome);
        $related = Id::mint(EntityType::LearningOutcome);
        $item = $this->item('lot-12', [$own]);
        $other = $this->item('lot-12', [$related]);

        self::assertSame([], $this->own((new ComprehensionQualityRule())->check($this->content(
            [$item, $other],
            comprehension: [$this->comprehension($item, [$own->value, $related->value], [$other->id->value])],
        ))));
    }

    /**
     * The exam-bank rules run on the comprehension set, and their failures
     * reach the report under their own id.
     */
    public function testAWrappedQuestionRuleReportsUnderItsOwnId(): void
    {
        $outcome = Id::mint(EntityType::LearningOutcome);
        $item = $this->item('lot-12', [$outcome]);
        $question = $this->comprehension($item, [$outcome->value], explanation: '');

        $wrapped = array_values(array_filter(
            (new ComprehensionQualityRule())->check($this->content([$item], comprehension: [$question])),
            static fn (Violation $v): bool => 'QST-001' === $v->ruleId,
        ));

        self::assertCount(1, $wrapped);
        self::assertSame($question->id->value, $wrapped[0]->subject);
        self::assertStringStartsWith('comprehension bank: ', $wrapped[0]->message);
    }

    /**
     * The wrapped rules see a set without the exam questions, so their
     * matrix-level findings — every item's question refs now dangling — are
     * artefacts of the wrapping and must not reach the report.
     */
    public function testOnlyFindingsAboutComprehensionQuestionsAreKept(): void
    {
        $outcome = Id::mint(EntityType::LearningOutcome);
        $item = $this->item('lot-12', [$outcome]);
        $question = $this->comprehension($item, [$outcome->value]);

        $subjects = array_unique(array_map(
            static fn (Violation $v): ?string => $v->subject,
            (new ComprehensionQualityRule())->check($this->content([$item], comprehension: [$question])),
        ));

        self::assertNotContains($item->id->value, $subjects);
        self::assertSame([], array_diff($subjects, [$question->id->value]));
    }

    public function testAnIdSharedWithAnExamQuestionIsRejected(): void
    {
        $outcome = Id::mint(EntityType::LearningOutcome);
        $item = $this->item('lot-12', [$outcome]);
        $exam = QuestionFactory::make(['officialItemId' => $item->id->value]);
        $question = QuestionFactory::make([
            'id' => $exam->id,
            'officialItemId' => $item->id->value,
            'pool' => Pool::Comprehension,
            'assessesOutcomes' => [$outcome->value],
        ]);

        $own = $this->own((new ComprehensionQualityRule())->check(
            $this->content([$item], [$exam], [$question]),
        ));

        self::assertCount(1, $own);
        self::assertStringContainsString('shares its id', $own[0]->message);
    }

    public function testAnIdSharedWithAHoldoutQuestionIsNotPrinted(): void
    {
        $outcome = Id::mint(EntityType::LearningOutcome);
        $item = $this->item('lot-12', [$outcome]);
        $holdout = QuestionFactory::make(['officialItemId' => $item->id->value, 'pool' => Pool::Holdout]);
        $question = QuestionFactory::make([
            'id' => $holdout->id,
            'officialItemId' => $item->id->value,
            'pool' => Pool::Comprehension,
            'assessesOutcomes' => [$outcome->value],
        ]);

        $own = $this->own((new ComprehensionQualityRule())->check(
            $this->content([$item], [$holdout], [$question]),
        ));

        self::assertCount(1, $own);
        self::assertNull($own[0]->subject);
        self::assertStringContainsString('id withheld', $own[0]->message);
        self::assertStringNotContainsString($holdout->id->value, $own[0]->message);
    }

    public function testNoComprehensionQuestionMeansNoFinding(): void
    {
        $item = $this->item('lot-12', [Id::mint(EntityType::LearningOutcome)]);

        self::assertSame([], (new ComprehensionQualityRule())->check($this->content([$item])));
    }

    // ---- helpers -----------------------------------------------------------

    /**
     * @param list<Id> $outcomes
     */
    private function item(string $lot, array $outcomes): OfficialItem
    {
        return ItemFactory::make([
            'lot' => $lot,
            'learningOutcomes' => array_map(
                static fn (Id $id): LearningOutcome => new LearningOutcome('Outcome '.$id->value, $id),
                $outcomes,
            ),
        ]);
    }

    /**
     * @param list<string> $assesses
     * @param list<string> $related
     */
    private function comprehension(
        OfficialItem $item,
        array $assesses,
        array $related = [],
        string $question = 'Which statement about this mechanism is true?',
        Language $language = Language::English,
        Pool $pool = Pool::Comprehension,
        string $explanation = 'Because the mechanism works this way.',
    ): Question {
        return QuestionFactory::make([
            'officialItemId' => $item->id->value,
            'pool' => $pool,
            'language' => $language,
            'question' => $question,
            'explanation' => $explanation,
            'assessesOutcomes' => $assesses,
            'relatedItems' => $related,
        ]);
    }

    /**
     * @param list<OfficialItem> $items
     * @param list<Question>     $questions
     * @param list<Question>     $comprehension
     */
    private function content(array $items, array $questions = [], array $comprehension = []): ContentSet
    {
        return new ContentSet(
            matrix: new SyllabusMatrix($items),
            questions: $questions,
            comprehension: $comprehension,
        );
    }

    /**
     * @param list<Violation> $violations
     *
     * @return list<Violation>
     */
    private function own(array $violations): array
    {
        return array_values(array_filter(
            $violations,
            static fn (Violation $v): bool => 'CMP-003' === $v->ruleId,
        ));
    }
}
