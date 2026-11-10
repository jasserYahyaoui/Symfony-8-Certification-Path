<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\ContentLevel;
use CertPath\Domain\Course;
use CertPath\Domain\Language;
use CertPath\Domain\LotSummary;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Domain\VerificationStatus;
use CertPath\Support\EntityType;
use CertPath\Support\Id;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule\SchemaDrawnRule;
use CertPath\Validation\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * DIA-002 (owner, 2026-11-08): « Le schéma » is drawn. The accepted shapes
 * first, then one test per defect the rule exists to catch: a rule that has
 * only ever been silent is not passing (§16).
 */
#[CoversClass(SchemaDrawnRule::class)]
final class SchemaDrawnRuleTest extends TestCase
{
    private const string DIAGRAM = <<<'MD'
        ```mermaid
        flowchart TD
          accTitle: Le trajet
          accDescr: A puis B.
          A["A"] --> B["B"]
        ```
        MD;

    public function testADrawnSchemaIsAccepted(): void
    {
        self::assertSame([], $this->checkCourse($this->body(self::DIAGRAM)));
    }

    public function testProseAroundTheDrawingIsAccepted(): void
    {
        self::assertSame([], $this->checkCourse($this->body("Avant.\n\n".self::DIAGRAM."\n\nAprès.")));
    }

    public function testTwoDrawingsAreAccepted(): void
    {
        self::assertSame([], $this->checkCourse($this->body(self::DIAGRAM."\n\n".self::DIAGRAM)));
    }

    public function testACourseWithoutSchemaSectionIsAccepted(): void
    {
        self::assertSame([], $this->checkCourse("## L'essentiel\n\nTexte.\n\n```text\nA -> B\n```\n"));
    }

    public function testAFenceOutsideTheSchemaSectionIsAccepted(): void
    {
        $body = $this->body(self::DIAGRAM)."\n## Fiche express\n\n```text\nmémo\n```\n";

        self::assertSame([], $this->checkCourse($body));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function defects(): iterable
    {
        yield 'a text fence' => ["```text\nA ──▶ B\n```", 'draws no diagram'];
        yield 'a fence without language' => ["```\nA -> B\n```", 'draws no diagram'];
        yield 'prose alone' => ['A, puis B.', 'draws no diagram'];
        yield 'a text fence beside a drawing' => [self::DIAGRAM."\n\n```text\nA -> B\n```", 'shown as code'];
        yield 'a php fence beside a drawing' => [self::DIAGRAM."\n\n```php\n\$a = 1;\n```", 'shown as code'];
    }

    #[DataProvider('defects')]
    public function testEachDefectFails(string $schema, string $message): void
    {
        $violations = $this->checkCourse($this->body($schema));

        self::assertNotSame([], $violations);
        self::assertSame('DIA-002', $violations[0]->ruleId);
        self::assertStringContainsString($message, implode("\n", array_map(static fn (Violation $v): string => $v->message, $violations)));
    }

    public function testASummarySchemaWrittenAsTextFails(): void
    {
        $summary = new LotSummary('lot-07', "## En une phrase\n\nUn lot.\n\n## Le schéma\n\n```text\nA -> B\n```\n\n## Notion par notion\n", 'content/summaries/lot-07.md', VerificationStatus::Verified);

        $violations = (new SchemaDrawnRule())->check(new ContentSet(matrix: new SyllabusMatrix([]), summaries: [$summary]));

        self::assertCount(2, $violations);
        self::assertSame('content/summaries/lot-07.md', $violations[0]->subject);
    }

    private function body(string $schema): string
    {
        return "## L'essentiel\n\nTexte.\n\n## Le schéma\n\n".$schema."\n\n## L'exemple\n\n```php\n\$x = 1;\n```\n";
    }

    /**
     * @return list<Violation>
     */
    private function checkCourse(string $body): array
    {
        $course = new Course(
            id: Id::mint(EntityType::Course),
            officialItemId: 'OIT-000000000001',
            title: 'Course',
            contentLevel: ContentLevel::Standard,
            body: $body,
            officialSources: [],
            language: Language::French,
            verificationStatus: VerificationStatus::Verified,
        );

        return (new SchemaDrawnRule())->check(new ContentSet(
            matrix: new SyllabusMatrix([]),
            questions: [],
            courses: [$course],
        ));
    }
}
