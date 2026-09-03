<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §7: a question's cognitive level and its exam skill are two different axes.
 *
 * `cognitive_level` says how deeply the candidate must process the material;
 * `exam_skill` says what the question asks them to do with it. Nothing enforced
 * the first, so it was declared as a bare string and 39 questions written before
 * the taxonomy settled carried a *skill* value in the *level* field — in 38 of
 * them the two fields held the same word. Every gate passed, because no rule
 * looked. This rule closes that gap.
 */
final class CognitiveLevelRule implements Rule
{
    private const LEVELS = ['KNOW', 'UNDERSTAND', 'APPLY'];

    public function id(): string
    {
        return 'COG-001';
    }

    public function description(): string
    {
        return 'Every question declares a cognitive level drawn from the taxonomy, distinct from its exam skill.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->questions as $question) {
            $level = $question->cognitiveLevel;

            if (!\in_array($level, self::LEVELS, true)) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf(
                        'Cognitive level "%s" is not one of %s.',
                        $level,
                        implode(', ', self::LEVELS),
                    ),
                    $question->id->value,
                );
            }
        }

        return $violations;
    }
}
