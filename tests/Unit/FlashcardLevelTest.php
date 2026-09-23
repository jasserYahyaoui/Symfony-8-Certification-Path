<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Build\DocsGenerator;
use CertPath\Domain\Flashcard;
use CertPath\Domain\FlashcardLevel;
use CertPath\Domain\FlashcardLoader;
use CertPath\Domain\Language;
use CertPath\Domain\SourceRef;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Domain\VerificationStatus;
use CertPath\Schema\SchemaException;
use CertPath\Support\EntityType;
use CertPath\Support\Id;
use CertPath\Support\Project;
use CertPath\Tests\Support\ItemFactory;
use CertPath\Validation\ContentSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The flashcard level axis (§6): a deck declares what kind of work each card
 * does, so that a deck made entirely of recall can be seen to be one.
 */
#[CoversClass(FlashcardLevel::class)]
#[CoversClass(FlashcardLoader::class)]
#[CoversClass(DocsGenerator::class)]
final class FlashcardLevelTest extends TestCase
{
    public function testAMissingLevelIsNullRatherThanADefault(): void
    {
        $cards = $this->load("- id: FLC-000000000001\n  official_item: OIT-000000000001\n  language: fr\n  front: F\n  back: B\n  explanation: E\n  memorization_justification: J\n  official_sources:\n    - url: https://example.invalid/x\n  verification_status: VERIFIED\n");

        self::assertNull($cards[0]->level, 'an unlabelled card must not be given a level it was never read for');
    }

    public function testADeclaredLevelIsHydrated(): void
    {
        $cards = $this->load("- id: FLC-000000000001\n  official_item: OIT-000000000001\n  language: fr\n  level: TRAP\n  front: F\n  back: B\n  explanation: E\n  memorization_justification: J\n  official_sources:\n    - url: https://example.invalid/x\n  verification_status: VERIFIED\n");

        self::assertSame(FlashcardLevel::Trap, $cards[0]->level);
    }

    /**
     * A typo must not degrade into "no level": that would silently drop the
     * card out of every grouping while looking like a deliberate omission.
     */
    public function testAnUnknownLevelIsRejected(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessageMatches('/unknown flashcard level "PIEGE"/');

        $this->load("- id: FLC-000000000001\n  official_item: OIT-000000000001\n  language: fr\n  level: PIEGE\n  front: F\n  back: B\n  explanation: E\n  memorization_justification: J\n  official_sources:\n    - url: https://example.invalid/x\n  verification_status: VERIFIED\n");
    }

    /**
     * The rendered page carries one heading per level that has a card, in the
     * declared order, and no heading for a level that has none.
     */
    public function testTheGeneratedPageGroupsCardsByLevelAndSkipsEmptyLevels(): void
    {
        $item = ItemFactory::make(['officialItem' => 'Flashcard level fixture']);
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            flashcards: [
                $this->card($item->id->value, null, 'Carte sans niveau'),
                $this->card($item->id->value, FlashcardLevel::Trap, 'Carte piège'),
                $this->card($item->id->value, FlashcardLevel::Recall, 'Carte mémorisation'),
            ],
        );

        $project = Project::locate();
        (new DocsGenerator($project))->generate($content);

        $page = $this->pageMentioning($project, 'Carte sans niveau');

        self::assertStringContainsString('### Mémorisation', $page);
        self::assertStringContainsString('### Pièges', $page);
        self::assertStringNotContainsString('### Application', $page, 'a level with no card must not get a heading');
        self::assertStringNotContainsString('### Compréhension', $page);

        // Order: unlabelled first, then the declared order of the enum.
        self::assertLessThan(strpos($page, '### Mémorisation'), strpos($page, 'Carte sans niveau'));
        self::assertLessThan(strpos($page, '### Pièges'), strpos($page, '### Mémorisation'));
    }

    /**
     * A code span is rendered verbatim by MDX, so HTML-escaping the front and
     * back put `&gt;` and `&#039;` in front of the learner on every card whose
     * text held `->` or a quote. Outside a code span the browser decodes the
     * entity and the damage is invisible, which is how it survived on 123
     * pages until a reader reported it.
     */
    public function testCodeSpansInACardAreNotHtmlEscaped(): void
    {
        $item = ItemFactory::make(['officialItem' => 'Flashcard escaping fixture']);
        $front = "Symfony : que renvoie `\$request->get('id')` ?";
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            flashcards: [$this->card($item->id->value, FlashcardLevel::Recall, $front)],
        );

        $project = Project::locate();
        (new DocsGenerator($project))->generate($content);

        $page = $this->pageMentioning($project, 'Flashcard escaping fixture');

        self::assertStringContainsString($front, $page, 'the card front must reach the page unescaped');
        self::assertStringNotContainsString('&gt;', $page);
        self::assertStringNotContainsString('&#039;', $page);
    }

    /**
     * What the escaping was there for must still hold OUTSIDE a code span:
     * `<` would open a JSX tag and `{` a JS expression, and either breaks the
     * site build. Inside a code span, both must survive untouched — MDX
     * renders that span verbatim, so an entity there is published as itself.
     */
    public function testMdxEscapingAppliesOutsideCodeSpansAndNotInside(): void
    {
        $item = ItemFactory::make(['officialItem' => 'Flashcard MDX fixture']);
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            flashcards: [$this->card(
                $item->id->value,
                FlashcardLevel::Recall,
                'Flashcard MDX fixture : `{motif}i` puis /{page}/blog et <Foo>',
            )],
        );

        $project = Project::locate();
        (new DocsGenerator($project))->generate($content);

        $page = $this->pageMentioning($project, 'Flashcard MDX fixture');

        self::assertStringContainsString('`{motif}i`', $page, 'a code span must reach the page verbatim');
        self::assertStringContainsString('/&#123;page&#125;/blog', $page, '{ outside code opens a JS expression');
        self::assertStringContainsString('&lt;Foo', $page, '< outside code opens a JSX tag');
    }

    private function card(string $itemId, ?FlashcardLevel $level, string $front): Flashcard
    {
        return new Flashcard(
            id: Id::mint(EntityType::Flashcard),
            officialItemId: $itemId,
            front: $front,
            back: 'Réponse',
            explanation: 'Explication',
            memorizationJustification: 'Justification',
            officialSources: [new SourceRef(url: 'https://example.invalid/x')],
            language: Language::French,
            verificationStatus: VerificationStatus::Verified,
            level: $level,
        );
    }

    /**
     * @return list<Flashcard>
     */
    private function load(string $cardsYaml): array
    {
        $dir = sys_get_temp_dir().'/certpath-flc-'.bin2hex(random_bytes(6));
        mkdir($dir);
        file_put_contents($dir.'/deck.yml', "schema_version: 2\nflashcards:\n".preg_replace('/^/m', '  ', $cardsYaml));

        try {
            return (new FlashcardLoader())->loadDirectory($dir);
        } finally {
            array_map('unlink', glob($dir.'/*') ?: []);
            rmdir($dir);
        }
    }

    private function pageMentioning(Project $project, string $needle): string
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $project->path('website/docs'),
            \FilesystemIterator::SKIP_DOTS,
        ));

        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo || 'md' !== $file->getExtension()) {
                continue;
            }

            // The lot index lists every item by name, so it matches the needle
            // too — and it carries no flashcard. Returning it made the outcome
            // depend on the order `RecursiveDirectoryIterator` happens to yield
            // on the machine running the suite: green locally, red on CI, with
            // nothing wrong in the generated page the test is about. Skipping
            // the listing narrows the lookup to the item page these assertions
            // were always written against.
            if ('index.md' === $file->getFilename()) {
                continue;
            }

            $body = (string) file_get_contents($file->getPathname());
            if (str_contains($body, $needle)) {
                return $body;
            }
        }

        self::fail('no generated page carries the fixture card');
    }
}
