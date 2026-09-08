<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\ContentLevel;
use CertPath\Domain\Course;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * ADR-0007: an item's teaching content stays inside the revision budget for
 * its content level.
 *
 * The measure is REVISION COST, not progress. CLAUDE.md forbids counting
 * files or lines as progress, and this rule does the opposite of that: more
 * words is worse, not better. Budgets are per content level because a MINIMAL
 * item that costs as much to revise as a DEEP one has mis-declared one of the
 * two.
 *
 * Honest statement of what this rule catches today: measured on the corpus at
 * the time it was written, it flags ONE item of 163. It is installed before
 * the refinement passes that will add scenarios, rationale and targeted
 * confusions to every course — a budget that arrives after the spending is not
 * a budget. A green REV-001 is therefore not evidence that revision load has
 * been audited under pressure; see docs/policy/revision-budget.md.
 *
 * Outside the refined lots the finding is a WARNING: the ceiling is a
 * constraint on refinement work, and failing the build over a course whose
 * refinement pass has not happened yet would only invite the ceiling to be
 * raised.
 */
final class RevisionBudgetRule implements Rule
{
    /**
     * Body words per item. Derived in docs/policy/revision-budget.md from the
     * observed p90 of each level with headroom for refinement, and bounded by
     * a full-corpus revision pass of roughly one long day at 250 words/minute.
     *
     * @var array<string, int>
     */
    private const array BUDGET = [
        ContentLevel::Minimal->value => 400,
        ContentLevel::Standard->value => 900,
        ContentLevel::Deep->value => 1200,
    ];

    public function id(): string
    {
        return 'REV-001';
    }

    public function description(): string
    {
        return 'An item\'s course stays inside the revision budget for its content level.';
    }

    public function check(ContentSet $content): array
    {
        $wordsByItem = [];
        foreach ($content->courses as $course) {
            $wordsByItem[$course->officialItemId] = ($wordsByItem[$course->officialItemId] ?? 0) + $course->wordCount();
        }

        $violations = [];

        foreach ($content->matrix->officialItems() as $item) {
            $level = $item->contentLevel;
            if (null === $level) {
                continue;
            }

            $budget = self::BUDGET[$level->value] ?? null;
            if (null === $budget) {
                continue;
            }

            $words = $wordsByItem[$item->id->value] ?? 0;
            if ($words <= $budget) {
                continue;
            }

            $violations[] = new Violation(
                $this->id(),
                \in_array($item->lot, $content->frameworkRefinedLots, true) ? Severity::Error : Severity::Warning,
                \sprintf(
                    'Revision cost %d body words exceeds the %s budget of %d; either the content is doing '
                    .'more than the level claims, or the level is wrong.',
                    $words,
                    $level->value,
                    $budget,
                ),
                $item->id->value,
            );
        }

        return $violations;
    }

    /**
     * @return array<string, int>
     */
    public static function budgets(): array
    {
        return self::BUDGET;
    }

    /**
     * The ceiling the budgets imply for a corpus with the given number of
     * items at each level, so the policy document and the rule cannot drift
     * apart without a test noticing.
     */
    public static function corpusCeiling(int $minimal, int $standard, int $deep): int
    {
        return $minimal * self::BUDGET[ContentLevel::Minimal->value]
            + $standard * self::BUDGET[ContentLevel::Standard->value]
            + $deep * self::BUDGET[ContentLevel::Deep->value];
    }
}
