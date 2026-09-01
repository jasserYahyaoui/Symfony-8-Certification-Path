<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\Choice;
use CertPath\Domain\ContentLevel;
use CertPath\Domain\Course;
use CertPath\Domain\Flashcard;
use CertPath\Domain\Language;
use CertPath\Domain\SourceRef;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Domain\VerificationStatus;
use CertPath\Support\EntityType;
use CertPath\Support\Id;
use CertPath\Tests\Support\ItemFactory;
use CertPath\Tests\Support\QuestionFactory;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule\CourseIntegrityRule;
use CertPath\Validation\Rule\FlashcardIntegrityRule;
use CertPath\Validation\Severity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CourseIntegrityRule::class)]
#[CoversClass(FlashcardIntegrityRule::class)]
#[CoversClass(Course::class)]
#[CoversClass(Flashcard::class)]
final class CourseAndFlashcardRuleTest extends TestCase
{
    private const string LEAKED_ANSWER = 'Give blog_list a higher priority, e.g. priority: 2';

    /**
     * §4.3: a course must not give away a question's phrasing in prose.
     */
    public function testCourseLeakingACorrectAnswerInProseIsRejected(): void
    {
        $content = self::content(
            courseBody: 'To fix the collision: '.self::LEAKED_ANSWER.' and rebuild.',
            correctChoiceText: self::LEAKED_ANSWER,
        );

        $violations = (new CourseIntegrityRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame('CRS-001', $violations[0]->ruleId);
        self::assertSame(Severity::Error, $violations[0]->severity);
    }

    /**
     * The same text inside a fenced code block is not a leak **when the question
     * belongs to the same official item**: a course must be able to show the
     * code its own item teaches, and a question on that item must be able to
     * test it.
     */
    public function testCourseShowingItsOwnItemsAnswerInsideACodeFenceIsAllowed(): void
    {
        $body = "Use the right import:\n\n```php\n".self::LEAKED_ANSWER."\n```\n\nThat is all.";

        $content = self::content(courseBody: $body, correctChoiceText: self::LEAKED_ANSWER);

        self::assertSame([], (new CourseIntegrityRule())->check($content));
    }

    /**
     * The narrowing must not become a loophole: prose outside the fence is
     * still checked even when a fence is present elsewhere in the page.
     */
    public function testAFenceElsewhereDoesNotExemptTheRestOfThePage(): void
    {
        $body = "```php\n\$x = 1;\n```\n\nRemember: ".self::LEAKED_ANSWER;

        $content = self::content(courseBody: $body, correctChoiceText: self::LEAKED_ANSWER);

        self::assertCount(1, (new CourseIntegrityRule())->check($content));
    }

    /**
     * The fence exemption is scoped to the course's own item. A correct answer
     * belonging to ANOTHER item is a leak wherever it appears: the learner
     * reading the page sees it either way, and the fence only hides it from
     * this rule.
     *
     * Lot 05 proved the point — a routing course quoted a composer.json excerpt
     * that happened to contain the verbatim answer to a Lot 03 deprecation
     * question, and moving the string into a fence made the violation vanish
     * from the report while leaving it fully visible on the published page.
     */
    public function testAnotherItemsAnswerInsideACodeFenceIsStillALeak(): void
    {
        $courseItem = ItemFactory::make();
        $otherItem = ItemFactory::make();

        $body = "Dependencies:\n\n```json\n".self::LEAKED_ANSWER."\n```\n\nThat is all.";

        $content = new ContentSet(
            matrix: new SyllabusMatrix([$courseItem, $otherItem]),
            questions: [QuestionFactory::make([
                'officialItemId' => $otherItem->id->value,
                'choices' => [
                    new Choice(Id::mint(EntityType::Choice), self::LEAKED_ANSWER, true),
                    new Choice(Id::mint(EntityType::Choice), 'Something else', false, 'Wrong because.'),
                ],
            ])],
            courses: [self::course($courseItem->id->value, $body)],
        );

        $violations = (new CourseIntegrityRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame('CRS-001', $violations[0]->ruleId);
    }

    public function testShortChoicesNeverCountAsLeaks(): void
    {
        $content = self::content(courseBody: 'A 404 response means not found.', correctChoiceText: '404');

        self::assertSame([], (new CourseIntegrityRule())->check($content));
    }

    public function testCourseWithoutASourceIsRejected(): void
    {
        $item = ItemFactory::make();
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            courses: [self::course($item->id->value, 'Body text.', sources: [])],
        );

        $violations = (new CourseIntegrityRule())->check($content);

        self::assertNotEmpty($violations);
        self::assertStringContainsString('no official source', $violations[0]->message);
    }

    public function testCourseMappedToAnUnknownItemIsRejected(): void
    {
        $content = new ContentSet(
            matrix: new SyllabusMatrix([]),
            courses: [self::course('OIT-999999999999', 'Body text.')],
        );

        self::assertNotEmpty((new CourseIntegrityRule())->check($content));
    }

    public function testNearDuplicateFlashcardsAreRejected(): void
    {
        $item = ItemFactory::make();
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            flashcards: [
                self::flashcard($item->id->value, 'What does 403 mean?'),
                self::flashcard($item->id->value, '  what  does 403 MEAN??  '),
            ],
        );

        $violations = (new FlashcardIntegrityRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame('FLC-001', $violations[0]->ruleId);
    }

    public function testUnverifiedFlashcardIsRejected(): void
    {
        $item = ItemFactory::make();
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            flashcards: [self::flashcard(
                $item->id->value,
                'Front',
                status: VerificationStatus::UnknownNeedsVerification,
            )],
        );

        self::assertNotEmpty((new FlashcardIntegrityRule())->check($content));
    }

    public function testAValidCoursePasses(): void
    {
        $item = ItemFactory::make();
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            courses: [self::course($item->id->value, 'A concise explanation.')],
        );

        self::assertSame([], (new CourseIntegrityRule())->check($content));
    }

    private static function content(string $courseBody, string $correctChoiceText): ContentSet
    {
        $item = ItemFactory::make();

        return new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            questions: [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'choices' => [
                    new Choice(Id::mint(EntityType::Choice), $correctChoiceText, true),
                    new Choice(Id::mint(EntityType::Choice), 'Something else', false, 'Wrong because.'),
                ],
            ])],
            courses: [self::course($item->id->value, $courseBody)],
        );
    }

    /**
     * @param list<SourceRef> $sources
     */
    private static function course(string $itemId, string $body, ?array $sources = null): Course
    {
        return new Course(
            id: Id::mint(EntityType::Course),
            officialItemId: $itemId,
            title: 'A course',
            contentLevel: ContentLevel::Standard,
            body: $body,
            officialSources: $sources ?? [new SourceRef(url: 'https://example.test/x', anchor: 'x', branch: '8.0')],
            language: Language::French,
            verificationStatus: VerificationStatus::Verified,
        );
    }

    private static function flashcard(
        string $itemId,
        string $front,
        VerificationStatus $status = VerificationStatus::Verified,
    ): Flashcard {
        return new Flashcard(
            id: Id::mint(EntityType::Flashcard),
            officialItemId: $itemId,
            front: $front,
            back: 'Back',
            explanation: 'Because.',
            memorizationJustification: 'Not derivable.',
            officialSources: [new SourceRef(url: 'https://example.test/x', anchor: 'x', branch: '8.0')],
            language: Language::French,
            verificationStatus: $status,
        );
    }
}
