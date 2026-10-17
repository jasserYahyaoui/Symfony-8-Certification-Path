<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Domain\AnswerMode;
use CertPath\Domain\Pool;
use CertPath\Support\Project;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * tools/behavior/single/ executes single-answer questions choice by choice
 * (remediation P3.1), as questions/ does for multiple-answer ones. run.php
 * compares each result with the content's `correct` flags; this holds the
 * directory to its shape: every id is a question, none is HOLDOUT, each is
 * single-answer, and none is executed twice.
 */
#[CoversNothing]
final class SingleAnswerExecutionTest extends TestCase
{
    public function testEveryExecutedIdIsASingleAnswerQuestionOutsideTheHoldout(): void
    {
        $questions = [];
        foreach (Project::locate()->loadContentSet()->questions as $q) {
            $questions[$q->id->value] = $q;
        }
        $seen = [];
        foreach (glob(Project::locate()->path('tools/behavior/single/*.php')) ?: [] as $file) {
            preg_match_all("/^\\s*'(QST-[a-z0-9]{12})' =>/m", (string) file_get_contents($file), $m);
            foreach ($m[1] as $id) {
                self::assertArrayHasKey($id, $questions, "$id names no question");
                // assertTrue: a failure names the file, never a holdout id's content.
                self::assertTrue(Pool::Holdout !== $questions[$id]->pool, "$id is a holdout question");
                self::assertSame(AnswerMode::Single, $questions[$id]->answerMode, "$id is not single-answer");
                self::assertArrayNotHasKey($id, $seen, "$id is executed twice");
                $seen[$id] = true;
            }
        }
        self::assertNotEmpty($seen, 'tools/behavior/single/ executes nothing');
    }
}
