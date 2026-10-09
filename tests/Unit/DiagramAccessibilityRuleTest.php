<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\ContentLevel;
use CertPath\Domain\Course;
use CertPath\Domain\Language;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Domain\VerificationStatus;
use CertPath\Support\EntityType;
use CertPath\Support\Id;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule\DiagramAccessibilityRule;
use CertPath\Validation\Severity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * DIA-001 (ADR-0009). One accepted shape, then one test per defect: a rule
 * that has only ever been silent is not passing (§16).
 */
#[CoversClass(DiagramAccessibilityRule::class)]
final class DiagramAccessibilityRuleTest extends TestCase
{
    private const string VALID = <<<'MD'
        Texte.

        ```mermaid
        ---
        title: Le trajet
        ---
        flowchart TD
          accTitle: Le trajet d'une requête
          accDescr: kernel.request puis kernel.response.
          A["kernel.request"] --> B["kernel.response"]
        ```
        MD;

    public function testAnAccessibleDiagramWithItsFrontMatterIsAccepted(): void
    {
        self::assertSame([], $this->check(self::VALID));
    }

    public function testACourseWithoutDiagramIsAccepted(): void
    {
        self::assertSame([], $this->check("Texte.\n\n```php\n\$a = 1;\n```\n"));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function defects(): iterable
    {
        yield 'no accTitle' => [
            str_replace("  accTitle: Le trajet d'une requête\n", '', self::VALID),
            'no accessible title',
        ];
        yield 'empty accTitle' => [
            str_replace("accTitle: Le trajet d'une requête", 'accTitle:', self::VALID),
            'no accessible title',
        ];
        yield 'no accDescr' => [
            str_replace("  accDescr: kernel.request puis kernel.response.\n", '', self::VALID),
            'no accessible description',
        ];
        yield 'prose instead of a diagram type' => [
            str_replace('flowchart TD', 'Voici un long paragraphe qui échappe au budget.', self::VALID),
            'does not open with a known diagram type',
        ];
        yield 'unclosed fence' => [
            "Texte.\n\n```mermaid\nflowchart TD\n  accTitle: T\n  accDescr: D\n  A --> B\n",
            'never closed',
        ];
        yield 'tilde fence' => [
            "Texte.\n\n~~~mermaid\nflowchart TD\n  A --> B\n~~~\n",
            'fenced with tildes',
        ];
    }

    #[DataProvider('defects')]
    public function testEachDefectIsAnError(string $body, string $expected): void
    {
        $violations = $this->check($body);

        self::assertNotEmpty($violations);
        self::assertSame('DIA-001', $violations[0]->ruleId);
        self::assertSame(Severity::Error, $violations[0]->severity);
        self::assertStringContainsString($expected, implode("\n", array_map(
            static fn ($violation): string => $violation->message,
            $violations,
        )));
    }

    /**
     * @return list<\CertPath\Validation\Violation>
     */
    private function check(string $body): array
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

        return (new DiagramAccessibilityRule())->check(new ContentSet(
            matrix: new SyllabusMatrix([]),
            questions: [],
            courses: [$course],
        ));
    }
}
