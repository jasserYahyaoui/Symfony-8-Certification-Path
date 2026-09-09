<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\Question;
use CertPath\Domain\QuestionArchetype;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * ADR-0007: a question declares its structural form, and the declaration has
 * to agree with the question actually written.
 *
 * The rule has two halves, and the second is the one that matters. Requiring
 * the field is cheap and a lazy author satisfies it by writing the same value
 * on every question. What stops that is the consistency half: an archetype
 * that contradicts the question's own observable properties is rejected, so a
 * label cannot be applied for the sake of the gate.
 *
 * The field is required only for questions belonging to a lot recorded as
 * refined in docs/progress/refinement-log.yml. The 550 questions written
 * before the axis existed carry none; assigning archetypes to them by
 * guesswork would fabricate data (§19), and each lot's refinement pass brings
 * its own in.
 */
final class QuestionArchetypeRule implements Rule
{
    /** A version-attribution question has to name a version. */
    private const string VERSION_PATTERN = '/\d+\.\d+/';

    public function id(): string
    {
        return 'ARC-001';
    }

    public function description(): string
    {
        return 'Questions in a refined lot declare a structural archetype consistent with the question written.';
    }

    public function check(ContentSet $content): array
    {
        $lotOfItem = [];
        foreach ($content->matrix->officialItems() as $item) {
            $lotOfItem[$item->id->value] = $item->lot;
        }

        $violations = [];

        foreach ($content->questions as $question) {
            $lot = $lotOfItem[$question->officialItemId] ?? null;
            $refined = null !== $lot && \in_array($lot, $content->frameworkRefinedLots, true);
            $archetype = $question->questionArchetype;

            if (null === $archetype) {
                if ($refined) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf('Question in refined lot "%s" declares no question_archetype.', $lot),
                        $question->id->value,
                    );
                }

                continue;
            }

            foreach ($this->inconsistencies($question, $archetype) as $message) {
                $violations[] = new Violation($this->id(), Severity::Error, $message, $question->id->value);
            }
        }

        return $violations;
    }

    /**
     * Every check here compares the declared archetype with something else
     * already recorded about the question, so none of them can pass by
     * comparing a value with itself.
     *
     * @return list<string>
     */
    private function inconsistencies(Question $question, QuestionArchetype $archetype): array
    {
        $problems = [];

        if ($archetype->requiresCode() && null === $question->codeLanguage) {
            $problems[] = \sprintf(
                'Archetype %s puts code in front of the candidate but the question declares no code_language.',
                $archetype->value,
            );
        }

        if ($archetype->forbidsCode() && null !== $question->codeLanguage) {
            $problems[] = \sprintf(
                'Archetype %s describes a behaviour rather than showing it, but the question ships code.',
                $archetype->value,
            );
        }

        if ($archetype->requiresDiagnosis() && 'DIAGNOSE' !== $question->examSkill) {
            $problems[] = \sprintf(
                'Archetype %s asks why something misbehaves, but exam_skill is "%s".',
                $archetype->value,
                $question->examSkill,
            );
        }

        if (QuestionArchetype::ConceptDistinction === $archetype && 'RECOGNIZE' === $question->examSkill) {
            $problems[] = 'Archetype CONCEPT_DISTINCTION separates two mechanisms; exam_skill RECOGNIZE only asks for one.';
        }

        if ($archetype->isRecall() && 'APPLY' === $question->cognitiveLevel) {
            $problems[] = 'Archetype DEFINITION_RECALL is recall; cognitive_level APPLY contradicts it (§4.1).';
        }

        if (
            QuestionArchetype::VersionAttribution === $archetype
            && 1 !== preg_match(self::VERSION_PATTERN, $question->question)
        ) {
            $problems[] = 'Archetype VERSION_ATTRIBUTION names no version in the question stem.';
        }

        return $problems;
    }
}
