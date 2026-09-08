<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

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
use CertPath\Validation\Rule\SourceAnchorRule;
use CertPath\Validation\Severity;
use PHPUnit\Framework\TestCase;

/**
 * SRC-6 regression suite.
 *
 * `SRC-001` used to inspect only the syllabus matrix items' own sources. The
 * ~900 citations a learner actually follows — on courses, flashcards and
 * questions — were never passed to it, and the anchor failure it did raise was
 * a Warning, which does not fail a build. AUD-03 found 105 unanchored
 * citations that way, and in 15 of them a source that did not prove its claim.
 *
 * Every test here asserts an Error, because a Warning is what let this stand.
 */
final class LearnerFacingCitationRuleTest extends TestCase
{
    private const PINNED = '0c2fc141fcf9edb13b57b34c3843ed75e24ddcf5';

    private static function content(array $parts = []): ContentSet
    {
        return new ContentSet(
            matrix: new SyllabusMatrix([ItemFactory::make()]),
            questions: $parts['questions'] ?? [],
            courses: $parts['courses'] ?? [],
            flashcards: $parts['flashcards'] ?? [],
        );
    }

    private static function course(array $sources): Course
    {
        return new Course(
            id: Id::mint(EntityType::Course),
            officialItemId: ItemFactory::make()->id->value,
            title: 'A course',
            contentLevel: ContentLevel::Standard,
            body: 'body',
            officialSources: $sources,
            language: Language::English,
            verificationStatus: VerificationStatus::Verified,
        );
    }

    private static function flashcard(array $sources): Flashcard
    {
        return new Flashcard(
            id: Id::mint(EntityType::Flashcard),
            officialItemId: ItemFactory::make()->id->value,
            front: 'front',
            back: 'back',
            explanation: 'why',
            memorizationJustification: 'worth memorising',
            officialSources: $sources,
            language: Language::English,
            verificationStatus: VerificationStatus::Verified,
        );
    }

    /**
     * @return list<string>
     */
    private static function errors(ContentSet $content): array
    {
        $out = [];
        foreach ((new SourceAnchorRule())->check($content) as $violation) {
            if (Severity::Error === $violation->severity) {
                $out[] = $violation->message;
            }
        }

        return $out;
    }

    private static function anchored(string $url = 'https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst'): SourceRef
    {
        return new SourceRef(url: $url, branch: '8.0', symbolOrLines: 'Routing — "the first matching route wins"');
    }

    // --- a learner-facing citation without an anchor ------------------------

    public function testCourseCitationWithoutAnAnchorIsAnError(): void
    {
        $content = self::content(['courses' => [self::course([
            new SourceRef(url: 'https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst', branch: '8.0'),
        ])]]);

        self::assertNotEmpty(self::errors($content), 'an unanchored course citation must fail the build');
    }

    public function testFlashcardCitationWithoutAnAnchorIsAnError(): void
    {
        $content = self::content(['flashcards' => [self::flashcard([
            new SourceRef(url: 'https://raw.githubusercontent.com/symfony/symfony-docs/8.0/cache.rst', branch: '8.0'),
        ])]]);

        self::assertNotEmpty(self::errors($content));
    }

    public function testQuestionCitationWithoutAnAnchorIsAnError(): void
    {
        $content = self::content(['questions' => [QuestionFactory::make([
            'officialSources' => [new SourceRef(url: 'https://raw.githubusercontent.com/php/doc-en/master/language/enumerations.xml', branch: 'master')],
        ])]]);

        self::assertNotEmpty(self::errors($content));
    }

    /** The severity is the point of SRC-6, so it is asserted directly. */
    public function testTheAnchorFailureIsAnErrorAndNotAWarning(): void
    {
        $content = self::content(['courses' => [self::course([
            new SourceRef(url: 'https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst', branch: '8.0'),
        ])]]);

        $violations = (new SourceAnchorRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame(Severity::Error, $violations[0]->severity);
    }

    // --- partial source coverage --------------------------------------------

    /**
     * A claim proved by two sources is fine; a second source that is itself
     * unanchored is not, and must not hide behind the anchored first one.
     */
    public function testASecondUnanchoredSourceIsStillAnError(): void
    {
        $content = self::content(['flashcards' => [self::flashcard([
            self::anchored(),
            new SourceRef(url: 'https://raw.githubusercontent.com/symfony/symfony-docs/8.0/http_cache.rst', branch: '8.0'),
        ])]]);

        self::assertCount(1, self::errors($content));
    }

    public function testTwoFullyAnchoredSourcesForOneClaimPass(): void
    {
        $content = self::content(['flashcards' => [self::flashcard([
            self::anchored(),
            self::anchored('https://raw.githubusercontent.com/symfony/symfony-docs/8.0/http_cache.rst'),
        ])]]);

        self::assertSame([], self::errors($content));
    }

    // --- citation shapes -----------------------------------------------------

    public function testABranchReferenceWithAnAnchorPasses(): void
    {
        self::assertSame([], self::errors(self::content(['courses' => [self::course([self::anchored()])]])));
    }

    public function testAnImmutableCommitPinPasses(): void
    {
        $content = self::content(['questions' => [QuestionFactory::make(['officialSources' => [new SourceRef(
            url: 'https://raw.githubusercontent.com/php/php-src/'.self::PINNED.'/Zend/zend_exceptions.c',
            branch: 'PHP-8.4',
            commitSha: self::PINNED,
            symbolOrLines: 'zend_implement_throwable()',
        )]])]]);

        self::assertSame([], self::errors($content));
    }

    public function testACommitPinWithoutCommitShaIsAnError(): void
    {
        $content = self::content(['questions' => [QuestionFactory::make(['officialSources' => [new SourceRef(
            url: 'https://raw.githubusercontent.com/php/php-src/'.self::PINNED.'/Zend/zend_exceptions.c',
            branch: 'PHP-8.4',
            symbolOrLines: 'zend_implement_throwable()',
        )]])]]);

        self::assertNotEmpty(self::errors($content));
    }

    public function testACommitPinWithoutAnAuthorisedBranchIsAnError(): void
    {
        $content = self::content(['questions' => [QuestionFactory::make(['officialSources' => [new SourceRef(
            url: 'https://raw.githubusercontent.com/php/php-src/'.self::PINNED.'/Zend/zend_exceptions.c',
            commitSha: self::PINNED,
            symbolOrLines: 'zend_implement_throwable()',
        )]])]]);

        self::assertNotEmpty(self::errors($content));
    }

    public function testACommitPinWhoseShaDisagreesWithItsUrlIsAnError(): void
    {
        $content = self::content(['questions' => [QuestionFactory::make(['officialSources' => [new SourceRef(
            url: 'https://raw.githubusercontent.com/php/php-src/'.self::PINNED.'/Zend/zend_exceptions.c',
            branch: 'PHP-8.4',
            commitSha: str_repeat('a', 40),
            symbolOrLines: 'zend_implement_throwable()',
        )]])]]);

        self::assertNotEmpty(self::errors($content));
    }

    // --- invalid shapes ------------------------------------------------------

    public function testACitationWithNoUrlIsAnError(): void
    {
        $content = self::content(['courses' => [self::course([new SourceRef(url: '', branch: '8.0', symbolOrLines: 'x')])]]);

        self::assertNotEmpty(self::errors($content));
    }

    public function testALearnerFacingCurrentDocsCitationIsAnError(): void
    {
        $content = self::content(['courses' => [self::course([
            new SourceRef(url: 'https://symfony.com/doc/current/routing.html', branch: '8.0', symbolOrLines: 'Routing'),
        ])]]);

        self::assertNotEmpty(self::errors($content));
    }

    public function testALearnerFacingCitationWithoutAVersionAnchorIsAnError(): void
    {
        $content = self::content(['flashcards' => [self::flashcard([
            new SourceRef(url: 'https://example.test/page', symbolOrLines: 'somewhere'),
        ])]]);

        self::assertNotEmpty(self::errors($content));
    }

    /** §2.4 accepts either field, so neither alone may be required. */
    public function testEitherAnchorFieldSatisfiesTheRequirement(): void
    {
        $viaAnchor = self::content(['courses' => [self::course([
            new SourceRef(url: 'https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst', anchor: 'Form events', branch: '8.0'),
        ])]]);
        $viaSymbol = self::content(['courses' => [self::course([self::anchored()])]]);

        self::assertSame([], self::errors($viaAnchor));
        self::assertSame([], self::errors($viaSymbol));
    }

    // --- reporting -----------------------------------------------------------

    /**
     * The whole corpus is inspected, not a sample: a defect in the last
     * flashcard must be reported as surely as one in the first course.
     */
    public function testEveryDefectIsReportedWithNoDisplayLimit(): void
    {
        $courses = [];
        for ($i = 0; $i < 40; ++$i) {
            $courses[] = self::course([new SourceRef(
                url: 'https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst',
                branch: '8.0',
            )]);
        }
        $flashcards = [self::flashcard([new SourceRef(
            url: 'https://raw.githubusercontent.com/symfony/symfony-docs/8.0/cache.rst',
            branch: '8.0',
        )])];

        $errors = self::errors(self::content(['courses' => $courses, 'flashcards' => $flashcards]));

        self::assertCount(41, $errors, 'no finding may be dropped or truncated');
    }

    public function testAllThreeContentKindsAreInspectedInOneRun(): void
    {
        $bare = static fn (string $u): SourceRef => new SourceRef(url: $u, branch: '8.0');
        $content = self::content([
            'courses' => [self::course([$bare('https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst')])],
            'flashcards' => [self::flashcard([$bare('https://raw.githubusercontent.com/symfony/symfony-docs/8.0/cache.rst')])],
            'questions' => [QuestionFactory::make(['officialSources' => [$bare('https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst')]])],
        ]);

        self::assertCount(3, self::errors($content));
    }

    /** The matrix half of the rule must keep working, only harder. */
    public function testTheMatrixItemCheckStillFiresAndIsNowAnError(): void
    {
        $content = new ContentSet(matrix: new SyllabusMatrix([ItemFactory::make([
            'officialSources' => [new SourceRef(url: 'https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst', branch: '8.0')],
        ])]));

        $errors = self::errors($content);

        self::assertNotEmpty($errors);
    }
}
