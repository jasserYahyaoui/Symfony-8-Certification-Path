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
use CertPath\Validation\Rule\ObjectiveSectionRule;
use CertPath\Validation\Severity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * OBJ-001 (ADR-0011). One accepted shape, then one test per defect: a rule
 * that has only ever been silent is not passing (§16).
 */
#[CoversClass(ObjectiveSectionRule::class)]
final class ObjectiveSectionRuleTest extends TestCase
{
    private const string VALID = <<<'MD'
        ## Objectif

        Contraindre la valeur d'un parameter de route, et savoir quand ce
        requirement s'applique.

        > **Périmètre.** Le reste appartient à un autre item.

        ## Le problème qu'elle résout

        - une liste ici est permise : la section n'est plus l'objectif.
        MD;

    public function testAShortObjectiveInProseIsAccepted(): void
    {
        self::assertSame([], $this->check(self::VALID));
    }

    public function testACourseWithoutObjectiveIsNotThisRulesConcern(): void
    {
        self::assertSame([], $this->check("## Règles\n\nTexte.\n"));
    }

    public function testSixtyWordsIsTheCeilingAndPasses(): void
    {
        $body = "## Objectif\n\n".trim(str_repeat('mot ', ObjectiveSectionRule::MAX_WORDS))."\n\n## Suite\n\nTexte.";

        self::assertSame([], $this->check($body));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function defects(): iterable
    {
        yield 'one word over the ceiling' => [
            "## Objectif\n\n".trim(str_repeat('mot ', ObjectiveSectionRule::MAX_WORDS + 1))."\n\n## Suite\n",
            'holds 61 words, above 60',
        ];
        yield 'a fenced block' => [
            str_replace("> **Périmètre.**", "```php\n\$a = 1;\n```\n\n> **Périmètre.**", self::VALID),
            'carries a fenced block',
        ];
        yield 'a table' => [
            str_replace("> **Périmètre.**", "| a | b |\n|---|---|\n\n> **Périmètre.**", self::VALID),
            'carries a table',
        ];
        yield 'a list' => [
            str_replace("> **Périmètre.**", "- un point\n- un autre\n\n> **Périmètre.**", self::VALID),
            'carries a list',
        ];
        yield 'a sub-heading' => [
            str_replace("> **Périmètre.**", "### Détail\n\n> **Périmètre.**", self::VALID),
            'carries a sub-heading',
        ];
        yield 'a second objective' => [
            self::VALID."\n\n## Objectif\n\nUn second objectif.\n",
            'has 2 « ## Objectif » sections',
        ];
    }

    #[DataProvider('defects')]
    public function testEachDefectIsAnError(string $body, string $expected): void
    {
        $violations = $this->check($body);

        self::assertNotEmpty($violations);
        self::assertSame('OBJ-001', $violations[0]->ruleId);
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

        return (new ObjectiveSectionRule())->check(new ContentSet(
            matrix: new SyllabusMatrix([]),
            questions: [],
            courses: [$course],
        ));
    }
}
