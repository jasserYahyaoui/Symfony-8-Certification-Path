<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\ContentLevel;
use CertPath\Domain\Course;
use CertPath\Domain\Language;
use CertPath\Domain\SourceRef;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Domain\VerificationStatus;
use CertPath\Schema\Migrations\QuestionBankReadableUrl;
use CertPath\Schema\SchemaRegistry;
use CertPath\Support\EntityType;
use CertPath\Support\Id;
use CertPath\Support\SourceUrl;
use CertPath\Tests\Support\ItemFactory;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule\ReadableSourceUrlRule;
use CertPath\Validation\Severity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A citation carries two spellings of one object, and this is what keeps them
 * one object: the derivation, the rule that refuses drift, and the migration
 * that fills the field in for a document written before it existed.
 *
 * The defect these guard against is not a malformed URL — it is a PLAUSIBLE
 * one. `blob/6.4/` instead of `blob/8.0/` reads correctly to a human, passes
 * every other gate, and sends the learner to a different version of the page
 * the claim was verified against.
 */
final class ReadableSourceUrlTest extends TestCase
{
    private const string RAW = 'https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst';
    private const string BLOB = 'https://github.com/symfony/symfony-docs/blob/8.0/routing.rst';

    public function testTheDerivationPreservesOwnerRepositoryRefAndPath(): void
    {
        self::assertSame(self::BLOB, SourceUrl::readable(self::RAW));
    }

    /**
     * A nested path is where a naive replacement breaks: everything after the
     * ref is the path and must survive intact, slashes included.
     */
    public function testANestedPathSurvivesWhole(): void
    {
        self::assertSame(
            'https://github.com/php/doc-en/blob/master/language/oop5/abstract.xml',
            SourceUrl::readable('https://raw.githubusercontent.com/php/doc-en/master/language/oop5/abstract.xml'),
        );
    }

    /**
     * A 40-hex commit is a ref like any other. Rewriting it to a branch would
     * silently unpin the strongest anchor this project has.
     */
    public function testACommitPinnedUrlStaysPinned(): void
    {
        $sha = '0c2fc141fcf9edb13b57b34c3843ed75e24ddcf5';

        self::assertSame(
            "https://github.com/php/php-src/blob/{$sha}/Zend/zend_exceptions.c",
            SourceUrl::readable("https://raw.githubusercontent.com/php/php-src/{$sha}/Zend/zend_exceptions.c"),
        );
    }

    #[DataProvider('notRawFileUrls')]
    public function testAnythingElseIsReturnedUnchanged(string $url): void
    {
        self::assertSame($url, SourceUrl::readable($url));
        self::assertFalse(SourceUrl::isRawFileUrl($url));
    }

    /** @return iterable<string, array{0: string}> */
    public static function notRawFileUrls(): iterable
    {
        yield 'an ordinary documentation page' => ['https://symfony.com/doc/8.0/routing.html'];
        yield 'a github blob url already' => [self::BLOB];
        // GitHub serves this form too, and its blob equivalent is spelled
        // differently. Mis-converting one is worse than leaving it alone.
        yield 'the refs/heads form' => [
            'https://raw.githubusercontent.com/symfony/symfony-docs/refs/heads/8.0/routing.rst',
        ];
        yield 'a raw url with no path at all' => ['https://raw.githubusercontent.com/symfony/symfony-docs/8.0/'];
        yield 'not a url' => ['routing.rst'];
    }

    public function testACoherentCitationPasses(): void
    {
        self::assertSame([], self::errors(new SourceRef(
            url: self::RAW,
            readableUrl: self::BLOB,
            anchor: 'routing',
            branch: '8.0',
        )));
    }

    public function testAReadableUrlOnAnotherRefIsRejected(): void
    {
        $errors = self::errors(new SourceRef(
            url: self::RAW,
            readableUrl: 'https://github.com/symfony/symfony-docs/blob/6.4/routing.rst',
            anchor: 'routing',
            branch: '8.0',
        ));

        self::assertCount(1, $errors);
        self::assertStringContainsString('does not match its url', $errors[0]);
    }

    public function testAReadableUrlOnAnotherFileIsRejected(): void
    {
        self::assertCount(1, self::errors(new SourceRef(
            url: self::RAW,
            readableUrl: 'https://github.com/symfony/symfony-docs/blob/8.0/security.rst',
            anchor: 'routing',
            branch: '8.0',
        )));
    }

    /**
     * The copy-paste mistake: both fields on the raw host. The citation still
     * verifies, and the learner is sent to unrendered bytes — which is the
     * thing the second field exists to prevent.
     */
    public function testAReadableUrlLeftOnTheRawHostIsRejected(): void
    {
        self::assertCount(1, self::errors(new SourceRef(
            url: self::RAW,
            readableUrl: self::RAW,
            anchor: 'routing',
            branch: '8.0',
        )));
    }

    /**
     * Absence is not a defect: it is derived. And it is derived in the
     * CONSTRUCTOR, not in fromArray — the first version of this put it in
     * fromArray alone, so every SourceRef built in code (fixtures, factories,
     * the matrix items this very rule iterates) came out with a null and the
     * rule reported the corpus against itself. These two assertions are what
     * caught it.
     */
    public function testAnAbsentReadableUrlIsDerivedWhereverTheCitationComesFrom(): void
    {
        $fromYaml = SourceRef::fromArray(['url' => self::RAW, 'anchor' => 'routing', 'branch' => '8.0']);
        $fromCode = new SourceRef(url: self::RAW, anchor: 'routing', branch: '8.0');

        self::assertSame(self::BLOB, $fromYaml->readableUrl);
        self::assertSame(self::BLOB, $fromCode->readableUrl);
        self::assertSame(self::BLOB, $fromCode->displayUrl());
        self::assertSame([], self::errors($fromYaml));
    }

    /**
     * A citation with no rendered equivalent is published as-is and SAID to be,
     * rather than passing in silence. Without this test the branch reporting it
     * would never have run: every citation in the corpus is a raw GitHub file.
     */
    public function testACitationWithNoRenderedEquivalentIsReported(): void
    {
        $content = new ContentSet(
            matrix: new SyllabusMatrix([ItemFactory::make()]),
            questions: [],
            courses: [self::course([new SourceRef(url: 'https://www.rfc-editor.org/rfc/rfc9110.html')])],
            flashcards: [],
        );

        $warnings = array_values(array_filter(
            (new ReadableSourceUrlRule())->check($content),
            static fn (object $v): bool => Severity::Warning === $v->severity,
        ));

        self::assertCount(1, $warnings);
        self::assertStringContainsString('published as-is', $warnings[0]->message);
    }

    /**
     * A value that IS written is taken as written. Correcting it here would
     * hide the mistake the rule exists to report.
     */
    public function testAWrittenValueIsNeverSilentlyCorrected(): void
    {
        $wrong = 'https://github.com/symfony/symfony-docs/blob/6.4/routing.rst';
        $source = SourceRef::fromArray(['url' => self::RAW, 'readable_url' => $wrong]);

        self::assertSame($wrong, $source->readableUrl);
        self::assertCount(1, self::errors($source));
    }

    public function testTheMigrationFillsEveryCitationItFinds(): void
    {
        $document = (new QuestionBankReadableUrl())->upgrade([
            'schema_version' => 1,
            'questions' => [
                ['id' => 'QST-a', 'official_sources' => [['url' => self::RAW, 'anchor' => 'routing']]],
                ['id' => 'QST-b', 'official_sources' => [['url' => 'https://example.test/x']]],
                ['id' => 'QST-c'],
            ],
        ]);

        self::assertSame(self::BLOB, $document['questions'][0]['official_sources'][0]['readable_url']);
        // Not a raw file URL: carried through unchanged rather than mangled.
        self::assertSame('https://example.test/x', $document['questions'][1]['official_sources'][0]['readable_url']);
        self::assertArrayNotHasKey('official_sources', $document['questions'][2]);
    }

    public function testTheMigrationLeavesAnExistingValueAlone(): void
    {
        $wrong = 'https://github.com/symfony/symfony-docs/blob/6.4/routing.rst';

        $document = (new QuestionBankReadableUrl())->upgrade([
            'schema_version' => 1,
            'questions' => [['id' => 'QST-a', 'official_sources' => [
                ['url' => self::RAW, 'readable_url' => $wrong],
            ]]],
        ]);

        self::assertSame($wrong, $document['questions'][0]['official_sources'][0]['readable_url'], 'a wrong value must reach SRC-002, not be silently repaired');
    }

    public function testBothCitationBanksDeclareVersionTwo(): void
    {
        self::assertSame(2, SchemaRegistry::currentVersion(SchemaRegistry::QUESTION_BANK));
        self::assertSame(2, SchemaRegistry::currentVersion(SchemaRegistry::FLASHCARD_DECK));
    }

    /** @return list<string> */
    private static function errors(SourceRef $source): array
    {
        $content = new ContentSet(
            matrix: new SyllabusMatrix([ItemFactory::make()]),
            questions: [],
            courses: [self::course([$source])],
            flashcards: [],
        );

        $violations = (new ReadableSourceUrlRule())->check($content);

        return array_values(array_map(
            static fn (object $v): string => $v->message,
            array_filter($violations, static fn (object $v): bool => Severity::Error === $v->severity),
        ));
    }

    /** @param list<SourceRef> $sources */
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
}
