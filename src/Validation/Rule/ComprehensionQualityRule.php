<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\Language;
use CertPath\Domain\Pool;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * ADR-0010: a comprehension question meets the bar every exam question meets,
 * plus the three constraints of its own kind.
 *
 * The question-level rules written for the exam banks — integrity, cognitive
 * level, archetype, source anchoring, readable URLs, references, scope,
 * duplicates — are run again on a content set whose questions are the
 * comprehension ones. Several of them also walk the matrix, and against a set
 * holding none of the exam questions they would report every matrix reference
 * to one as missing; so only violations whose subject is a comprehension
 * question are kept. The wrapped rules keep their own ids in the report, so a
 * failure names the check that caught it.
 *
 * Its own constraints: the question is in English (the owner's choice), its
 * related items exist, belong to the same lot and differ from its own item,
 * and every outcome it claims belongs to one of the items it covers.
 */
final class ComprehensionQualityRule implements Rule
{
    public function id(): string
    {
        return 'CMP-003';
    }

    public function description(): string
    {
        return 'Comprehension questions pass the question rules of the exam banks, are in English, and relate only items of their own lot.';
    }

    public function check(ContentSet $content): array
    {
        if ([] === $content->comprehension) {
            return [];
        }

        $violations = [...$this->ownRules($content), ...$this->wrappedRules($content)];

        return $violations;
    }

    /**
     * @return list<Violation>
     */
    private function ownRules(ContentSet $content): array
    {
        $violations = [];

        // An id is minted once (ADR-0002). A comprehension question sharing
        // one with an exam question would make every payload check that
        // works by id unreliable; when the other question is a holdout one,
        // the shared id is the holdout's, so it is not printed.
        $examIds = [];
        foreach ($content->questions as $question) {
            $examIds[$question->id->value] = Pool::Holdout === $question->pool;
        }

        foreach ($content->comprehension as $question) {
            $subject = $question->id->value;

            if (isset($examIds[$subject])) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    $examIds[$subject]
                        ? 'A comprehension question shares its id with a HOLDOUT question (id withheld).'
                        : 'A comprehension question shares its id with a question of the exam banks.',
                    $examIds[$subject] ? null : $subject,
                );
                continue;
            }

            $fail = function (string $message) use (&$violations, $subject): void {
                $violations[] = new Violation($this->id(), Severity::Error, $message, $subject);
            };

            if (Pool::Comprehension !== $question->pool) {
                $fail(\sprintf('A comprehension question declares pool %s.', $question->pool->value));
            }

            if (Language::English !== $question->language) {
                $fail('A comprehension question is written in English (owner decision, ADR-0010).');
            }

            $item = $content->matrix->findById($question->officialItemId);
            if (null === $item) {
                // QST-001 and REF-001, wrapped below, report the missing item.
                continue;
            }

            $owned = array_fill_keys($item->learningOutcomeIds(), true);
            $seen = [];

            foreach ($question->relatedItems as $relatedId) {
                if ($relatedId === $item->id->value) {
                    $fail('A question lists its own item among its related items.');
                    continue;
                }

                if (isset($seen[$relatedId])) {
                    $fail(\sprintf('Related item "%s" is listed twice.', $relatedId));
                    continue;
                }
                $seen[$relatedId] = true;

                $related = $content->matrix->findById($relatedId);
                if (null === $related) {
                    $fail(\sprintf('Related item "%s" does not exist.', $relatedId));
                    continue;
                }

                if ($related->lot !== $item->lot) {
                    $fail(\sprintf(
                        'Related item "%s" belongs to %s; a comprehension question stays inside its lot, %s.',
                        $relatedId,
                        $related->lot,
                        $item->lot,
                    ));
                    continue;
                }

                $owned += array_fill_keys($related->learningOutcomeIds(), true);
            }

            if ([] === $question->assessesOutcomes) {
                $fail('A comprehension question assesses no learning outcome; CMP-001 could not count it.');
            }

            foreach ($question->assessesOutcomes as $outcome) {
                if (!isset($owned[$outcome])) {
                    $fail(\sprintf(
                        'Outcome %s belongs to none of the items this question covers.',
                        $outcome,
                    ));
                }
            }
        }

        return $violations;
    }

    /**
     * @return list<Violation>
     */
    private function wrappedRules(ContentSet $content): array
    {
        $comprehensionSet = new ContentSet(
            matrix: $content->matrix,
            questions: $content->comprehension,
            excludedTerms: $content->excludedTerms,
            contextualExclusions: $content->contextualExclusions,
            wordingFingerprints: $content->wordingFingerprints,
            projectDir: $content->projectDir,
        );

        $ids = [];
        foreach ($content->comprehension as $question) {
            $ids[$question->id->value] = true;
        }

        $rules = [
            new QuestionIntegrityRule(),
            new CognitiveLevelRule(),
            new QuestionArchetypeRule(),
            new SourceAnchorRule(),
            new ReadableSourceUrlRule(),
            new ReferentialIntegrityRule(),
            new OutOfScopeContaminationRule(),
            new DuplicateQuestionRule(),
        ];

        $violations = [];
        foreach ($rules as $rule) {
            foreach ($rule->check($comprehensionSet) as $violation) {
                if (null !== $violation->subject && isset($ids[$violation->subject])) {
                    $violations[] = new Violation(
                        $violation->ruleId,
                        $violation->severity,
                        'comprehension bank: '.$violation->message,
                        $violation->subject,
                    );
                }
            }
        }

        return $violations;
    }
}
