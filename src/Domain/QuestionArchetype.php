<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * The STRUCTURAL form of a question — what the stem puts in front of the
 * candidate and what it asks them to do with it.
 *
 * This is a third axis, distinct from the two the bank already carries:
 *
 *   `cognitive_level` — how deeply the material must be processed;
 *   `exam_skill`      — what the candidate is asked to do;
 *   `question_archetype` — the SHAPE of the question itself.
 *
 * The axis was added because the bank's only structural field, `type`, holds
 * the single value `mcq` across all 550 questions, and an axis with one value
 * distinguishes nothing. Without it an item may be assessed four times by the
 * same mould and read as fully assessed.
 *
 * Every value is a form that can be verified BY READING THE QUESTION. None of
 * them is a claim about the official exam's composition: this project has no
 * evidence of that composition and does not pretend to (§19).
 */
enum QuestionArchetype: string
{
    /** The stem shows code and asks what it produces, returns or prints. */
    case CodeOutput = 'CODE_OUTPUT';

    /** The stem shows code that misbehaves and asks why. */
    case CodeDiagnosis = 'CODE_DIAGNOSIS';

    /** The stem shows configuration or attributes and asks the resulting behaviour. */
    case ConfigBehavior = 'CONFIG_BEHAVIOR';

    /** Asks which class, interface, method or option exists, or what its shape is. */
    case ApiSignature = 'API_SIGNATURE';

    /** Asks in which version something appeared, changed or was removed. */
    case VersionAttribution = 'VERSION_ATTRIBUTION';

    /** Asks the candidate to separate two mechanisms that are easily confused. */
    case ConceptDistinction = 'CONCEPT_DISTINCTION';

    /** Asks the order of a lifecycle, a pipeline or a resolution sequence. */
    case SequenceOrder = 'SEQUENCE_ORDER';

    /** Presents a situation and asks which approach is correct for it. */
    case ScenarioChoice = 'SCENARIO_CHOICE';

    /** Plain recall of a definition or a stated fact. */
    case DefinitionRecall = 'DEFINITION_RECALL';

    /**
     * Archetypes whose stem must actually contain code, so `code_language`
     * has to be declared. A "code output" question with no code is mislabelled.
     */
    public function requiresCode(): bool
    {
        return match ($this) {
            self::CodeOutput, self::CodeDiagnosis => true,
            default => false,
        };
    }

    /**
     * Archetypes that are pure recall. §4.1's warning against a definition
     * standing in for understanding applies here: a recall question declared
     * as APPLY is one of the two fields lying.
     */
    public function isRecall(): bool
    {
        return self::DefinitionRecall === $this;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
