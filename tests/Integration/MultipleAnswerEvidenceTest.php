<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Domain\AnswerMode;
use CertPath\Domain\Pool;
use CertPath\Support\Project;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Remediation item P1.6 (owner, 2026-10-14): every multiple-answer question of
 * the VALIDATION pool, and every question of content/questions/
 * multiple-answer-learning.yml, is either executed choice by choice on Symfony
 * 8.0 by tools/behavior/run.php (questions/*.php), or named below as resting on
 * a document, with the reason.
 *
 * An all-or-nothing question with one wrong distractor costs the learner the
 * whole point, so a distractor is a claim like any other. This test keeps the
 * evidence from silently thinning: a new multiple-answer VALIDATION question
 * with neither an execution nor a stated reason fails here.
 */
final class MultipleAnswerEvidenceTest extends TestCase
{
    /**
     * Verified by reading the cited source; nothing in tools/behavior can run
     * them.
     */
    private const array DOCUMENTARY = [
        'QST-b7n04h0xsdrb' => 'RFC 9110, 307 and 308 keep the method: a specification, not code',
        'QST-h13r88e3t040' => 'release process: a policy document',
        'QST-szaz294k89ed' => 'special routing parameters: the list the routing documentation gives',
        'QST-sws8a7kgyd2f' => 'AbstractController signatures: FrameworkBundle is not installed in tools/behavior',
    ];

    /** @return array<string, true> */
    private static function executed(): array
    {
        $ids = [];
        foreach (glob(Project::locate()->path('tools/behavior/questions/*.php')) ?: [] as $file) {
            preg_match_all("/^\\s*'(QST-[a-z0-9]{12})' =>/m", (string) file_get_contents($file), $m);
            foreach ($m[1] as $id) {
                $ids[$id] = true;
            }
        }

        return $ids;
    }

    public function testEveryMultipleAnswerValidationQuestionHasEvidence(): void
    {
        $executed = self::executed();
        $learningFile = [];
        foreach (Yaml::parseFile(Project::locate()->path('content/questions/multiple-answer-learning.yml'))['questions'] as $q) {
            $learningFile[$q['id']] = true;
        }
        $missing = [];
        $count = 0;
        foreach (Project::locate()->loadContentSet()->questions as $q) {
            $inScope = AnswerMode::Multiple === $q->answerMode
                && (Pool::Validation === $q->pool || isset($learningFile[$q->id->value]));
            if (!$inScope) {
                continue;
            }
            ++$count;
            if (!isset($executed[$q->id->value]) && !isset(self::DOCUMENTARY[$q->id->value])) {
                $missing[] = $q->id->value;
            }
        }

        self::assertSame([], $missing, 'multiple-answer questions with neither an execution nor a stated reason');
        self::assertGreaterThanOrEqual(30, \count($learningFile));
        self::assertGreaterThanOrEqual(\count($learningFile) + 3, $count);
    }

    public function testEveryExecutedIdIsAMultipleAnswerQuestionOutsideTheHoldout(): void
    {
        $questions = [];
        foreach (Project::locate()->loadContentSet()->questions as $q) {
            $questions[$q->id->value] = $q;
        }
        foreach (array_keys(self::executed() + self::DOCUMENTARY) as $id) {
            self::assertArrayHasKey($id, $questions, "$id names no question");
            self::assertNotSame(Pool::Holdout, $questions[$id]->pool, "$id is a holdout question");
            self::assertSame(AnswerMode::Multiple, $questions[$id]->answerMode, "$id is not multiple-answer");
        }
        self::assertSame([], array_intersect_key(self::executed(), self::DOCUMENTARY), 'an executed question is also listed as documentary');
    }
}
