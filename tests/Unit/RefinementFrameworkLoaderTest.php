<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\MatrixLoader;
use CertPath\Domain\QuestionArchetype;
use CertPath\Domain\QuestionLoader;
use CertPath\Schema\SchemaException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The parser half of refinement framework version 2 (ADR-0007).
 *
 * A malformed value must be rejected at the door rather than coerced. The
 * project has already been bitten by silent coercion twice: an unquoted `#`
 * truncating a canonical scalar, and an unquoted `:` turning a learning
 * outcome into a mapping that the loader then read as an empty string.
 */
#[CoversClass(MatrixLoader::class)]
#[CoversClass(QuestionLoader::class)]
final class RefinementFrameworkLoaderTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/certpath-loader-'.bin2hex(random_bytes(6));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    public function testABareStringOutcomeStillLoads(): void
    {
        $items = $this->loadMatrix("      - \"Attribuer une fonctionnalité à sa version\"\n");

        self::assertCount(1, $items[0]->learningOutcomes);
        self::assertSame('Attribuer une fonctionnalité à sa version', $items[0]->learningOutcomes[0]->text);
        self::assertFalse($items[0]->learningOutcomes[0]->isIdentified());
    }

    public function testAnIdentifiedOutcomeCarriesItsMintedId(): void
    {
        $items = $this->loadMatrix(
            "      - id: OUT-abcdefghjkmn\n        outcome: \"Attribuer une fonctionnalité à sa version\"\n"
        );

        self::assertSame('OUT-abcdefghjkmn', $items[0]->learningOutcomes[0]->idValue());
        self::assertSame(['OUT-abcdefghjkmn'], $items[0]->learningOutcomeIds());
        self::assertSame(['Attribuer une fonctionnalité à sa version'], $items[0]->learningOutcomeTexts());
    }

    public function testAMappingWithoutTextIsRejectedRatherThanDroppedSilently(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessageMatches('/non-empty `outcome`/');

        $this->loadMatrix("      - id: OUT-abcdefghjkmn\n");
    }

    public function testAMalformedOutcomeIdIsRejected(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessageMatches('/not a persistent identifier/');

        $this->loadMatrix("      - id: outcome-1\n        outcome: \"Something\"\n");
    }

    public function testAnUnknownArchetypeIsRejectedRatherThanPassedThroughAsAString(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessageMatches('/unknown question archetype "TRICK_QUESTION"/');

        $this->loadQuestion("    question_archetype: TRICK_QUESTION\n");
    }

    public function testAKnownArchetypeIsHydratedIntoTheEnum(): void
    {
        $questions = $this->loadQuestion("    question_archetype: CODE_DIAGNOSIS\n");

        self::assertSame(QuestionArchetype::CodeDiagnosis, $questions[0]->questionArchetype);
    }

    public function testAnOutcomeReferenceThatIsNotAnIdIsRejected(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessageMatches('/not a persistent identifier/');

        $this->loadQuestion("    assesses_outcomes:\n      - \"the first one\"\n");
    }

    public function testOutcomeReferencesAreHydrated(): void
    {
        $questions = $this->loadQuestion("    assesses_outcomes:\n      - OUT-abcdefghjkmn\n");

        self::assertTrue($questions[0]->assessesOutcome('OUT-abcdefghjkmn'));
        self::assertFalse($questions[0]->assessesOutcome('OUT-nmkjhgfedcba'));
    }

    /**
     * @return list<\CertPath\Domain\OfficialItem>
     */
    private function loadMatrix(string $outcomesBlock): array
    {
        $path = $this->dir.'/matrix.yml';
        file_put_contents($path, <<<YML
            schema_version: 1
            syllabus_revision: "test"
            syllabus_complete: true
            items:
              - id: OIT-abcdefghjkmn
                official_topic_order: 1
                official_topic: "PHP"
                official_item_order: 1
                official_item: "Item"
                official_wording: "Item"
                learning_domain: php
                lot: lot-01
                classification: OFFICIAL
                content_level: STANDARD
                content_level_justification: "Because."
                learning_outcomes:
            {$outcomesBlock}
                exclusion_boundaries: "None"
                version_constraints: "Symfony 8.0"
                status: EXAM_READY
                verification_status: VERIFIED
                exam_ready: true
            YML);

        return (new MatrixLoader())->load($path)->items;
    }

    /**
     * @return list<\CertPath\Domain\Question>
     */
    private function loadQuestion(string $extraFields): array
    {
        $path = $this->dir.'/questions.yml';
        file_put_contents($path, <<<YML
            schema_version: 1
            questions:
              - id: QST-abcdefghjkmn
                version: 1
                official_topic: "PHP"
                official_item: OIT-abcdefghjkmn
                domain: php
                language: en
                difficulty: medium
                cognitive_level: UNDERSTAND
                exam_skill: DISTINGUISH
                type: mcq
                answer_mode: single
                required_answer_count: 1
                question: "Which statement is true?"
                scoring_policy: all-or-nothing
                classification: OFFICIAL
                pool: LEARNING
                verification_status: VERIFIED
            {$extraFields}    choices:
                  - id: CHO-abcdefghjkmn
                    text: "The right one"
                    correct: true
                  - id: CHO-nmkjhgfedcba
                    text: "The wrong one"
                    correct: false
                    explanation: "It applies elsewhere."
            YML);

        return (new QuestionLoader())->loadFile($path);
    }
}
