<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\Pool;
use CertPath\Domain\QuestionLoader;
use CertPath\Schema\SchemaException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * ADR-0010 isolates the comprehension pool by construction: the file decides
 * the pool and the pool decides the file. A question in the wrong place is
 * refused at load time, before any rule or payload could see it.
 */
#[CoversClass(QuestionLoader::class)]
final class ComprehensionLoaderTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/certpath-comprehension-'.bin2hex(random_bytes(6));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    public function testAComprehensionBankLoadsItsQuestionsWithTheirRelatedItems(): void
    {
        $questions = QuestionLoader::comprehension()->loadFile(
            $this->write('COMPREHENSION', "    related_items: [OIT-nmkjhgfedcba]\n"),
        );

        self::assertCount(1, $questions);
        self::assertSame(Pool::Comprehension, $questions[0]->pool);
        self::assertSame(['OIT-nmkjhgfedcba'], $questions[0]->relatedItems);
    }

    public function testAnExamPoolQuestionIsRefusedInAComprehensionBank(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessageMatches('/COMPREHENSION questions only, found LEARNING/');

        QuestionLoader::comprehension()->loadFile($this->write('LEARNING'));
    }

    /**
     * The holdout is the case that matters most: a comprehension page is
     * public study material.
     */
    public function testAHoldoutQuestionIsRefusedInAComprehensionBank(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessageMatches('/found HOLDOUT/');

        QuestionLoader::comprehension()->loadFile($this->write('HOLDOUT'));
    }

    public function testAComprehensionQuestionIsRefusedAmongTheExamBanks(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessageMatches('/belongs in content\/comprehension/');

        (new QuestionLoader())->loadFile($this->write('COMPREHENSION'));
    }

    public function testRelatedItemsAreRefusedOnAnExamQuestion(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessageMatches('/exists only on comprehension questions/');

        (new QuestionLoader())->loadFile($this->write('LEARNING', "    related_items: [OIT-nmkjhgfedcba]\n"));
    }

    public function testARelatedItemThatIsNotAnIdentifierIsRefused(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessageMatches('/is not a persistent identifier/');

        QuestionLoader::comprehension()->loadFile($this->write('COMPREHENSION', "    related_items: [console-commands]\n"));
    }

    private function write(string $pool, string $extraFields = ''): string
    {
        $path = $this->dir.'/bank.yml';
        file_put_contents($path, <<<YML
            schema_version: 1
            questions:
              - id: QST-abcdefghjkmn
                version: 1
                official_topic: "Console"
                official_item: OIT-abcdefghjkmn
                domain: symfony
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
                pool: {$pool}
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

        return $path;
    }
}
