<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\ItemStatus;
use CertPath\Domain\SourceRef;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "missing or irrelevant sources" and
 *      "source without version anchor where required".
 *
 * §2.3 also forbids `/current/` as a primary source.
 */
final class SourceAnchorRule implements Rule
{
    public function id(): string
    {
        return 'SRC-001';
    }

    public function description(): string
    {
        return 'Every source, including every learner-facing citation, is present, '
            .'version-anchored, anchored to an exact passage, and never taken from /current/.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->matrix->officialItems() as $item) {
            if (!$item->status->isAtLeast(ItemStatus::SourceVerified)) {
                continue;
            }

            if ([] === $item->officialSources) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Item claims SOURCE_VERIFIED but declares no official source.',
                    $item->id->value,
                );
                continue;
            }

            foreach ($item->officialSources as $source) {
                if (str_contains($source->url, '/current/')) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        '/current/ is never a valid primary source (§2.3): '.$source->url,
                        $item->id->value,
                    );
                }

                if (!$source->isVersionAnchored()) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        'Source lacks a version anchor (branch or commit sha): '.$source->url,
                        $item->id->value,
                    );
                }

                if (!$source->hasAnchor()) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        'Source has no section anchor or line reference; a homepage is not evidence (§2.4): '.$source->url,
                        $item->id->value,
                    );
                }
            }
        }

        // SRC-6. Until 2026-09-08 this rule inspected only the matrix items'
        // own sources. The 900-odd citations a learner actually follows — on
        // courses, flashcards and questions — were never passed to it, so the
        // §2.4 anchor requirement went unchecked for the life of the project
        // while every gate stayed green. AUD-03 found 105 unanchored citations
        // and, in 15 of them, a source that did not prove its claim at all.
        foreach ($this->learnerFacingCitations($content) as [$ownerId, $source]) {
            $violations = [...$violations, ...$this->checkCitation($ownerId, $source)];
        }

        return $violations;
    }

    /**
     * Every citation a learner can follow, from every kind of content that
     * carries one. Exercises and exams reach the learner through the questions
     * they draw, so their citations are these citations.
     *
     * @return iterable<array{0: string, 1: \CertPath\Domain\SourceRef}>
     */
    private function learnerFacingCitations(ContentSet $content): iterable
    {
        foreach ($content->courses as $course) {
            foreach ($course->officialSources as $source) {
                yield [$course->id->value, $source];
            }
        }

        foreach ($content->flashcards as $flashcard) {
            foreach ($flashcard->officialSources as $source) {
                yield [$flashcard->id->value, $source];
            }
        }

        foreach ($content->questions as $question) {
            foreach ($question->officialSources as $source) {
                yield [$question->id->value, $source];
            }
        }
    }

    /**
     * @return list<Violation>
     */
    private function checkCitation(string $ownerId, SourceRef $source): array
    {
        $violations = [];

        if ('' === $source->url) {
            $violations[] = new Violation(
                $this->id(),
                Severity::Error,
                'Citation has no url.',
                $ownerId,
            );

            return $violations;
        }

        if (str_contains($source->url, '/current/')) {
            $violations[] = new Violation(
                $this->id(),
                Severity::Error,
                '/current/ is never a valid primary source (§2.3): '.$source->url,
                $ownerId,
            );
        }

        // Three citation shapes are supported and all three are version-anchored:
        // a branch reference, an immutable 40-hex commit pin, and a documentation
        // page carrying either. A pin is the stronger anchor, so it is accepted
        // wherever a branch is — but it must be recorded as a field, not left to
        // be read out of the URL.
        if (!$source->isVersionAnchored()) {
            $violations[] = new Violation(
                $this->id(),
                Severity::Error,
                'Source lacks a version anchor (branch or commit sha): '.$source->url,
                $ownerId,
            );
        }

        if (1 === preg_match('#/([0-9a-f]{40})/#', $source->url, $m)) {
            if ($m[1] !== $source->commitSha) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'URL is pinned to a commit but commit_sha does not record it: '.$source->url,
                    $ownerId,
                );
            }

            if (null === $source->branch || '' === $source->branch) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Commit-pinned source does not declare its authorised branch: '.$source->url,
                    $ownerId,
                );
            }
        }

        if (!$source->hasAnchor()) {
            $violations[] = new Violation(
                $this->id(),
                Severity::Error,
                'Learner-facing citation has no anchor; §2.4 requires the exact '
                .'section, and a page without one cannot be checked against its '
                .'claim: '.$source->url,
                $ownerId,
            );
        }

        return $violations;
    }
}
