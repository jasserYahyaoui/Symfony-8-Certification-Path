<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\Choice;
use CertPath\Domain\LotSummary;
use CertPath\Domain\LotSummaryLoader;
use CertPath\Domain\OfficialItem;
use CertPath\Domain\Pool;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Domain\VerificationStatus;
use CertPath\Schema\SchemaException;
use CertPath\Support\EntityType;
use CertPath\Support\Id;
use CertPath\Tests\Support\ItemFactory;
use CertPath\Tests\Support\QuestionFactory;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule\DiagramAccessibilityRule;
use CertPath\Validation\Rule\LotSummaryLeakRule;
use CertPath\Validation\Rule\LotSummaryStructureRule;
use CertPath\Validation\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * ADR-0013: the lot summaries « pour les nuls ». Each rule has an accepted
 * case beside each defect it exists to catch: a rule that has only ever been
 * silent is not passing (§16).
 */
#[CoversClass(LotSummary::class)]
#[CoversClass(LotSummaryLoader::class)]
#[CoversClass(LotSummaryStructureRule::class)]
#[CoversClass(LotSummaryLeakRule::class)]
final class LotSummaryTest extends TestCase
{
    private const string ANSWER = 'the lazy response waits for the first read';

    // ---- loader -------------------------------------------------------------

    public function testTheLoaderReadsTheLotAndTheBody(): void
    {
        $summary = (new LotSummaryLoader())->loadFile($this->file('lot-07', $this->body()));

        self::assertSame('lot-07', $summary->lot);
        self::assertSame(VerificationStatus::Verified, $summary->verificationStatus);
        self::assertSame(['Alpha', 'Beta'], array_keys($summary->notions()));
    }

    public function testTheLoaderRefusesAFileNotNamedAfterItsLot(): void
    {
        $path = $this->file('lot-07', $this->body());
        $renamed = \dirname($path).'/lot-08.md';
        rename($path, $renamed);

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('must be named after its lot');
        (new LotSummaryLoader())->loadFile($renamed);
    }

    // ---- SUM-001 ------------------------------------------------------------

    public function testAWellFormedSummaryPasses(): void
    {
        self::assertSame([], $this->structure($this->body()));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function defects(): iterable
    {
        yield 'an item has no notion' => ["### Beta\n", "### Gamma\n"];
        yield 'a notion has no everyday example' => ["**Dans la vraie vie** A beta example.\n", ''];
        yield 'a required section is missing' => ["## Les erreurs de débutant\n\n- one\n- two\n\n", ''];
        yield 'an unknown section' => ['## La fiche du lot', "## Quiz\n\nNothing.\n\n## La fiche du lot"];
        yield 'the sentence is a list' => ["## En une phrase\n\nThe lot in one line.\n", "## En une phrase\n\n- The lot in one line.\n"];
        yield 'the sentence is too long' => ['The lot in one line.', str_repeat('word ', 61)];
        yield 'the digest is too short' => ["- three\n- four\n- five\n", ''];
        yield 'a hand-written link' => ['An alpha example.', 'An [alpha](./alpha.md) example.'];
        yield 'a hand-written heading id' => ['## La fiche du lot', '## La fiche du lot {#digest}'];
        yield 'sections out of order' => [
            "## L'image de la vie réelle\n\nA picture.\n\n## Notion par notion",
            "## Notion par notion",
        ];
    }

    #[DataProvider('defects')]
    public function testEachDefectFails(string $from, string $to): void
    {
        $body = $this->body();
        self::assertStringContainsString($from, $body, 'the fixture must contain the string the defect replaces');
        $body = str_replace($from, $to, $body);
        if ("## Notion par notion" === $to) {
            // Out of order: the image section moves after the notions.
            $body = str_replace("## Les erreurs de débutant", "## L'image de la vie réelle\n\nA picture.\n\n## Les erreurs de débutant", $body);
        }

        self::assertNotSame([], $this->structure($body));
    }

    public function testALotWithoutASummaryFails(): void
    {
        $other = ItemFactory::make(['lot' => 'lot-09', 'officialItem' => 'Elsewhere']);
        $content = new ContentSet(
            matrix: new SyllabusMatrix([...$this->items(), $other]),
            summaries: [$this->summary($this->body())],
        );

        $violations = (new LotSummaryStructureRule())->check($content);

        self::assertCount(1, $violations);
        self::assertSame('lot-09', $violations[0]->subject);
        self::assertStringContainsString('has no summary', $violations[0]->message);
    }

    public function testASummaryOfALotWithoutItemsFails(): void
    {
        $content = new ContentSet(
            matrix: new SyllabusMatrix([]),
            summaries: [$this->summary($this->body())],
        );

        self::assertCount(1, (new LotSummaryStructureRule())->check($content));
    }

    // ---- SUM-002 ------------------------------------------------------------

    public function testAnOwnLotAnswerInProseIsALeak(): void
    {
        $body = str_replace('An alpha example.', 'Remember: '.self::ANSWER.'.', $this->body());

        $violations = $this->leaks($body, $this->items()[0]);

        self::assertCount(1, $violations);
        self::assertSame('SUM-002', $violations[0]->ruleId);
    }

    public function testAnOwnLotAnswerInAFenceIsAllowed(): void
    {
        $body = str_replace('An alpha example.', "An alpha example.\n\n```text\n".self::ANSWER."\n```", $this->body());

        self::assertSame([], $this->leaks($body, $this->items()[0]));
    }

    public function testAnotherLotsAnswerIsALeakEvenInAFence(): void
    {
        $other = ItemFactory::make(['lot' => 'lot-09', 'officialItem' => 'Elsewhere']);
        $body = str_replace('An alpha example.', "An alpha example.\n\n```text\n".self::ANSWER."\n```", $this->body());

        self::assertCount(1, $this->leaks($body, $other));
    }

    public function testAHoldoutLeakWithholdsTheQuestionId(): void
    {
        $body = str_replace('An alpha example.', 'Remember: '.self::ANSWER.'.', $this->body());

        $violations = $this->leaks($body, $this->items()[0], Pool::Holdout);

        self::assertCount(1, $violations);
        self::assertStringContainsString('id withheld', $violations[0]->message);
        self::assertStringNotContainsString('QST-', $violations[0]->message);
    }

    // ---- DIA-001 ------------------------------------------------------------

    public function testASummaryDiagramWithoutAccessibleTitleFails(): void
    {
        $body = str_replace("## Notion par notion", "## Le schéma\n\n```mermaid\nflowchart LR\n  accDescr: A to B.\n  A --> B\n```\n\n## Notion par notion", $this->body());
        $content = new ContentSet(matrix: new SyllabusMatrix($this->items()), summaries: [$this->summary($body)]);

        $violations = (new DiagramAccessibilityRule())->check($content);

        self::assertCount(1, $violations);
        self::assertStringContainsString('accessible title', $violations[0]->message);
    }

    // ---- helpers ------------------------------------------------------------

    private function body(): string
    {
        return <<<'MD'
            ## En une phrase

            The lot in one line.

            ## L'image de la vie réelle

            A picture.

            ## Notion par notion

            ### Alpha

            **C'est quoi ?** Alpha is a thing.

            **Dans la vraie vie** An alpha example.

            **Pour l'examen** Alpha's rule.

            ### Beta

            **C'est quoi ?** Beta is a thing.

            **Dans la vraie vie** A beta example.

            **Pour l'examen** Beta's rule.

            ## Les erreurs de débutant

            - one
            - two

            ## La fiche du lot

            - one
            - two
            - three
            - four
            - five

            MD;
    }

    /**
     * @return list<OfficialItem>
     */
    private function items(): array
    {
        return [
            ItemFactory::make(['lot' => 'lot-07', 'officialItem' => 'Alpha', 'officialItemOrder' => 1]),
            ItemFactory::make(['lot' => 'lot-07', 'officialItem' => 'Beta', 'officialItemOrder' => 2]),
        ];
    }

    private function summary(string $body): LotSummary
    {
        return new LotSummary('lot-07', trim($body), 'content/summaries/lot-07.md', VerificationStatus::Verified);
    }

    /**
     * @return list<Violation>
     */
    private function structure(string $body): array
    {
        return (new LotSummaryStructureRule())->check(new ContentSet(
            matrix: new SyllabusMatrix($this->items()),
            summaries: [$this->summary($body)],
        ));
    }

    /**
     * @return list<Violation>
     */
    private function leaks(string $body, OfficialItem $itemOfQuestion, Pool $pool = Pool::Learning): array
    {
        $items = $this->items();
        if (!\in_array($itemOfQuestion, $items, true)) {
            $items[] = $itemOfQuestion;
        }
        $question = QuestionFactory::make([
            'officialItemId' => $itemOfQuestion->id->value,
            'pool' => $pool,
            'choices' => [
                new Choice(Id::mint(EntityType::Choice), self::ANSWER, true),
                new Choice(Id::mint(EntityType::Choice), 'A plausible distractor', false, 'Not this one.'),
            ],
        ]);

        return (new LotSummaryLeakRule())->check(new ContentSet(
            matrix: new SyllabusMatrix($items),
            questions: [$question],
            summaries: [$this->summary($body)],
        ));
    }

    private function file(string $lot, string $body): string
    {
        $dir = sys_get_temp_dir().'/cert-summary-'.bin2hex(random_bytes(6));
        mkdir($dir);
        $path = $dir.'/'.$lot.'.md';
        file_put_contents($path, "---\nlot: {$lot}\nverification_status: VERIFIED\n---\n\n".$body);

        return $path;
    }
}
