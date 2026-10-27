<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\ContentLevel;
use CertPath\Domain\Course;
use CertPath\Domain\CourseLoader;
use CertPath\Domain\Language;
use CertPath\Domain\Pool;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Domain\VerificationStatus;
use CertPath\Readiness\ReadinessCalculator;
use CertPath\Schema\SchemaException;
use CertPath\Support\EntityType;
use CertPath\Support\Id;
use CertPath\Tests\Support\ItemFactory;
use CertPath\Tests\Support\QuestionFactory;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule\CourseQuizIndependenceRule;
use CertPath\Validation\Rule\PedagogicalFormatRule;
use CertPath\Validation\Rule\RevisionBudgetRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * ADR-0012: the pedagogical course format, its quiz, and the word budget it
 * leaves.
 *
 * Every rule is tested in both directions — it fires on the defect and stays
 * silent on the sound case — because a rule that only ever passes has not been
 * shown to be capable of failing.
 */
#[CoversClass(Course::class)]
#[CoversClass(PedagogicalFormatRule::class)]
#[CoversClass(CourseQuizIndependenceRule::class)]
#[CoversClass(RevisionBudgetRule::class)]
final class PedagogicalFormatTest extends TestCase
{
    private const string QUESTION = <<<'MD'
        **Question %d.** Que désigne le deuxième argument de la function ?

        - **A.** La version de suppression
        - **B.** La version d'introduction
        - **C.** La version de PHP
        - **D.** La version installée

        <details>
        <summary>Correction</summary>

        **Bonne réponse : B.** Le docblock de la function le dit : la version du package
        qui a introduit la deprecation, jamais celle de sa suppression.

        </details>
        MD;

    // ---- the quiz parser ---------------------------------------------------

    public function testAWellFormedQuizIsReadQuestionByQuestion(): void
    {
        $questions = $this->course($this->quiz(2))->quizQuestions();

        self::assertCount(2, $questions);
        self::assertSame(1, $questions[0]->number);
        self::assertSame(['A', 'B', 'C', 'D'], array_keys($questions[0]->choices));
        self::assertSame(['B'], $questions[0]->answers);
        self::assertSame([], $questions[0]->problems);
        self::assertStringContainsString('jamais celle de sa suppression', $questions[0]->correction);
    }

    public function testSeveralCorrectAnswersAreAnnouncedInThePlural(): void
    {
        $quiz = str_replace('**Bonne réponse : B.**', '**Bonnes réponses : A et B.**', $this->quiz(1));

        $question = $this->course($quiz)->quizQuestions()[0];

        self::assertSame(['A', 'B'], $question->answers);
        self::assertSame([], $question->problems);
    }

    public function testCodeInsideAPromptDoesNotEndTheQuestion(): void
    {
        $quiz = str_replace(
            'de la function ?',
            "de la function ?\n\n```php\n**Question 9.** not a marker inside a fence\n```",
            $this->quiz(1),
        );

        $questions = $this->course($quiz)->quizQuestions();

        self::assertCount(1, $questions);
        self::assertStringContainsString('not a marker inside a fence', $questions[0]->prompt);
        self::assertSame([], $questions[0]->problems);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function malformedQuestions(): iterable
    {
        yield 'answer that is not a choice' => ['**Bonne réponse : B.**', '**Bonne réponse : E.**', 'not a choice'];
        yield 'singular with two letters' => ['**Bonne réponse : B.**', '**Bonne réponse : B et C.**', 'exactly one letter'];
        yield 'plural with one letter' => ['**Bonne réponse : B.**', '**Bonnes réponses : B.**', 'at least two letters'];
        yield 'no answer line' => ['**Bonne réponse : B.**', 'B, évidemment.', 'does not start with'];
        yield 'letters out of order' => ['- **C.** La version de PHP', '- **E.** La version de PHP', 'lettered A, B, C'];
        yield 'no correction' => ['<details>', '<div>', 'no <details> correction'];
        yield 'correction that explains nothing' => [
            "Le docblock de la function le dit : la version du package\nqui a introduit la deprecation, jamais celle de sa suppression.",
            'Voir le cours.',
            'explains nothing',
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('malformedQuestions')]
    public function testAMalformedQuestionCarriesItsProblem(string $search, string $replace, string $expected): void
    {
        $question = $this->course(str_replace($search, $replace, $this->quiz(1)))->quizQuestions()[0];

        self::assertNotSame([], $question->problems);
        self::assertStringContainsString($expected, implode(' | ', $question->problems));
    }

    // ---- FMT-001 -----------------------------------------------------------

    public function testACompletePedagogicalCoursePasses(): void
    {
        $course = $this->course($this->pedagogicalBody(), format: Course::FORMAT_PEDAGOGICAL);

        self::assertSame([], (new PedagogicalFormatRule())->check($this->content([$course])));
    }

    public function testAPedagogicalCourseWithoutItsDigestFails(): void
    {
        $body = preg_replace('/## Fiche express.*?(?=## QCM)/s', '', $this->pedagogicalBody());
        $course = $this->course((string) $body, format: Course::FORMAT_PEDAGOGICAL);

        $violations = (new PedagogicalFormatRule())->check($this->content([$course]));

        self::assertCount(1, $violations);
        self::assertSame('FMT-001', $violations[0]->ruleId);
        self::assertStringContainsString('Fiche express', $violations[0]->message);
    }

    public function testADigestOfFourPointsIsTooShort(): void
    {
        $body = str_replace("- point cinq\n", '', $this->pedagogicalBody());
        $course = $this->course($body, format: Course::FORMAT_PEDAGOGICAL);

        $violations = (new PedagogicalFormatRule())->check($this->content([$course]));

        self::assertCount(1, $violations);
        self::assertStringContainsString('holds 4 point(s)', $violations[0]->message);
    }

    public function testAQuizOfThreeQuestionsIsTooShort(): void
    {
        $body = str_replace($this->quiz(4), $this->quiz(3), $this->pedagogicalBody());
        $course = $this->course($body, format: Course::FORMAT_PEDAGOGICAL);

        $violations = (new PedagogicalFormatRule())->check($this->content([$course]));

        self::assertCount(1, $violations);
        self::assertStringContainsString('holds 3 question(s)', $violations[0]->message);
    }

    public function testTheSameBodyWithoutTheFlagIsNotHeldToTheFormat(): void
    {
        $body = (string) preg_replace('/## Fiche express.*?(?=## QCM)/s', '', $this->pedagogicalBody());

        self::assertSame([], (new PedagogicalFormatRule())->check($this->content([$this->course($body)])));
    }

    public function testAMalformedQuizQuestionFailsWhateverTheFormat(): void
    {
        $quiz = str_replace('**Bonne réponse : B.**', '**Bonne réponse : E.**', $this->quiz(1));

        $violations = (new PedagogicalFormatRule())->check($this->content([$this->course($quiz)]));

        self::assertCount(1, $violations);
        self::assertStringContainsString('Quiz question 1', $violations[0]->message);
    }

    public function testTheLoaderRefusesAnUnknownFormat(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'crs');
        file_put_contents((string) $file, "---\nid: CRS-000000000001\nofficial_item: OIT-000000000001\ntitle: T\ncontent_level: MINIMAL\nlanguage: fr\nverification_status: VERIFIED\nformat: long\n---\n\n## Objectif\n\nBody.\n");

        try {
            $this->expectException(SchemaException::class);
            (new CourseLoader())->loadFile((string) $file);
        } finally {
            unlink((string) $file);
        }
    }

    // ---- QCM-001 -----------------------------------------------------------

    public function testAQuizPromptCopiedFromTheBanksFails(): void
    {
        $bank = QuestionFactory::make(['question' => 'Que désigne le deuxième argument de la function ?']);

        $violations = (new CourseQuizIndependenceRule())->check(
            $this->content([$this->course($this->quiz(1))], [$bank]),
        );

        self::assertCount(1, $violations);
        self::assertSame('QCM-001', $violations[0]->ruleId);
        self::assertStringContainsString($bank->id->value, $violations[0]->message);
    }

    public function testAHoldoutMatchIsReportedWithoutItsId(): void
    {
        $holdout = QuestionFactory::make([
            'question' => 'Que désigne le deuxième argument de la function ?',
            'pool' => Pool::Holdout,
        ]);

        $violations = (new CourseQuizIndependenceRule())->check(
            $this->content([$this->course($this->quiz(1))], [$holdout]),
        );

        self::assertCount(1, $violations);
        self::assertStringContainsString('HOLDOUT question (id withheld)', $violations[0]->message);
        self::assertStringNotContainsString($holdout->id->value, $violations[0]->message);
    }

    public function testAQuizPromptCopiedFromAComprehensionCheckFails(): void
    {
        $comprehension = QuestionFactory::make([
            'question' => 'Que désigne le deuxième argument de la function ?',
            'pool' => Pool::Comprehension,
        ]);

        $content = new ContentSet(
            matrix: new SyllabusMatrix([]),
            questions: [],
            courses: [$this->course($this->quiz(1))],
            comprehension: [$comprehension],
        );

        self::assertCount(1, (new CourseQuizIndependenceRule())->check($content));
    }

    public function testAQuizWrittenApartPasses(): void
    {
        $bank = QuestionFactory::make(['question' => 'Which package provides the function that triggers deprecations?']);

        self::assertSame([], (new CourseQuizIndependenceRule())->check(
            $this->content([$this->course($this->quiz(1))], [$bank]),
        ));
    }

    // ---- REV-001 and R14 ---------------------------------------------------

    public function testAPedagogicalCourseHasNoWordBudget(): void
    {
        $item = ItemFactory::make(['contentLevel' => ContentLevel::Minimal]);
        $words = RevisionBudgetRule::budgets()[ContentLevel::Minimal->value] + 1;
        $body = trim(str_repeat('mot ', $words));

        $flagged = $this->course($body, $item->id->value, Course::FORMAT_PEDAGOGICAL);
        $plain = $this->course($body, $item->id->value);

        self::assertSame([], (new RevisionBudgetRule())->check($this->content([$flagged], [], [$item])));
        self::assertCount(1, (new RevisionBudgetRule())->check($this->content([$plain], [], [$item])));
    }

    public function testReadinessCriterionR14HoldsForAPedagogicalCourse(): void
    {
        $item = ItemFactory::make(['contentLevel' => ContentLevel::Minimal]);
        $body = trim(str_repeat('mot ', RevisionBudgetRule::budgets()[ContentLevel::Minimal->value] + 1));

        $flagged = $this->readiness($item, $this->course($body, $item->id->value, Course::FORMAT_PEDAGOGICAL));
        $plain = $this->readiness($item, $this->course($body, $item->id->value));

        self::assertTrue($flagged['R14_revision_budget']);
        self::assertFalse($plain['R14_revision_budget']);
    }

    // ---- fixtures ----------------------------------------------------------

    private function quiz(int $count): string
    {
        $questions = [];
        for ($i = 1; $i <= $count; ++$i) {
            $questions[] = \sprintf(self::QUESTION, $i);
        }

        return "## QCM d'entraînement\n\nRéponds avant d'ouvrir la correction.\n\n".implode("\n\n", $questions)."\n";
    }

    private function pedagogicalBody(): string
    {
        return "## Objectif\n\nComprendre la fenêtre de deprecation.\n\n"
            ."## Pièges d'examen\n\n**Le deuxième argument est la version d'introduction.** Jamais celle de suppression.\n\n"
            ."## Fiche express\n\n- point un\n- point deux\n- point trois\n- point quatre\n- point cinq\n\n"
            .$this->quiz(4);
    }

    private function course(string $body, string $itemId = 'OIT-000000000001', ?string $format = null): Course
    {
        return new Course(
            id: Id::mint(EntityType::Course),
            officialItemId: $itemId,
            title: 'Course',
            contentLevel: ContentLevel::Minimal,
            body: $body,
            officialSources: [],
            language: Language::French,
            verificationStatus: VerificationStatus::Verified,
            format: $format,
        );
    }

    /**
     * @param list<Course>                        $courses
     * @param list<\CertPath\Domain\Question>     $questions
     * @param list<\CertPath\Domain\OfficialItem> $items
     */
    private function content(array $courses, array $questions = [], array $items = []): ContentSet
    {
        return new ContentSet(
            matrix: new SyllabusMatrix($items),
            questions: $questions,
            courses: $courses,
        );
    }

    /**
     * @return array<string, bool>
     */
    private function readiness(\CertPath\Domain\OfficialItem $item, Course $course): array
    {
        $content = new ContentSet(
            matrix: new SyllabusMatrix(items: [$item], syllabusRevision: 'test', syllabusComplete: true),
            questions: [],
            courses: [$course],
        );

        return (new ReadinessCalculator([$item->lot]))->calculate($content)->assessments[0]->criteria;
    }
}
