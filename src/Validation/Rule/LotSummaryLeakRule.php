<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\Pool;
use CertPath\Domain\Question;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * ADR-0013, SUM-002: a lot summary reveals no correct answer (§4.3).
 *
 * The same test as CRS-001, at the scale of a lot: a correct option of 25
 * characters or more, found verbatim. A summary may show, inside a fenced
 * block, the code an item of its own lot teaches; a correct answer belonging
 * to an item of another lot is a leak wherever it appears, fence or not.
 *
 * Two differences with CRS-001, both because a summary is read before
 * everything else of its lot:
 *
 * - the comprehension questions are matched too: the summary is read before
 *   the lot's « Contrôle de compréhension », and quoting its answers would
 *   empty it;
 * - a holdout question is matched but never named: the message withholds its
 *   id, as CMP-002 and QCM-001 do.
 */
final class LotSummaryLeakRule implements Rule
{
    private const int MIN_LEAK_LENGTH = 25;

    public function id(): string
    {
        return 'SUM-002';
    }

    public function description(): string
    {
        return 'A lot summary never reproduces the correct answer of an exam-bank or comprehension question.';
    }

    public function check(ContentSet $content): array
    {
        $lotOf = [];
        foreach ($content->matrix->items as $item) {
            $lotOf[$item->id->value] = $item->lot;
        }

        $violations = [];
        foreach ($content->summaries as $summary) {
            $prose = mb_strtolower($summary->prose());
            $whole = mb_strtolower($summary->body);
            foreach ([...$content->questions, ...$content->comprehension] as $question) {
                $ownLot = ($lotOf[$question->officialItemId] ?? null) === $summary->lot;
                if ($this->leaks($question, $ownLot ? $prose : $whole)) {
                    $violations[] = new Violation($this->id(), Severity::Error, $this->message($question), $summary->path);
                }
            }
        }

        return $violations;
    }

    private function leaks(Question $question, string $haystack): bool
    {
        foreach ($question->choices as $choice) {
            $text = trim($choice->text);
            if ($choice->correct && \strlen($text) >= self::MIN_LEAK_LENGTH && str_contains($haystack, mb_strtolower($text))) {
                return true;
            }
        }

        return false;
    }

    private function message(Question $question): string
    {
        if (Pool::Holdout === $question->pool) {
            return 'Summary reproduces the correct answer of a holdout question verbatim (§4.3; id withheld).';
        }

        return \sprintf('Summary reproduces the correct answer of question %s verbatim (§4.3).', $question->id->value);
    }
}
