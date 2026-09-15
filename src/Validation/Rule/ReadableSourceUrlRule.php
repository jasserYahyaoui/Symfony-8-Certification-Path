<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\SourceRef;
use CertPath\Support\SourceUrl;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * SRC-002 — the readable URL is the raw one, and cannot drift from it.
 *
 * A citation carries two spellings of one object: `url`, the raw file this
 * project fetches to verify the claim, and `readable_url`, the rendered GitHub
 * page the site sends a learner to. Two fields holding one fact is a standing
 * invitation to drift — somebody repoints one and not the other, and the
 * learner then reads a different file from the one the claim was checked
 * against, with every gate still green.
 *
 * This is the rule that makes the duplication safe. `readable_url` must be
 * exactly SourceUrl::readable(`url`): same owner, same repository, same ref,
 * same path. Not "a github.com URL", not "a URL mentioning the same file" —
 * the derivation, character for character.
 *
 * It says nothing about whether either URL resolves. Reachability is AUD-03's
 * question, answered against the network; this one is answered offline and
 * holds on every push.
 */
final class ReadableSourceUrlRule implements Rule
{
    public function id(): string
    {
        return 'SRC-002';
    }

    public function description(): string
    {
        return 'Every citation\'s readable_url is exactly the github.com/blob derivation '
            .'of its raw url, so the page a learner opens is the file the claim was verified against.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($this->citations($content) as [$ownerId, $source]) {
            $violations = [...$violations, ...$this->checkCitation($ownerId, $source)];
        }

        return $violations;
    }

    /**
     * Every citation this project carries, whoever owns it.
     *
     * The matrix items are included deliberately. SRC-001 spent the life of the
     * project checking them alone while the learner-facing citations went
     * unchecked; the opposite blind spot is just as easy to write.
     *
     * @return iterable<array{0: string, 1: SourceRef}>
     */
    private function citations(ContentSet $content): iterable
    {
        foreach ($content->matrix->officialItems() as $item) {
            foreach ($item->officialSources as $source) {
                yield [$item->id->value, $source];
            }
        }

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
        if ('' === $source->url) {
            // SRC-001 owns the empty citation. Reporting it twice would make
            // one defect look like two.
            return [];
        }

        // Never null: SourceRef derives it when the document omits it, so
        // "missing" is not a state this rule can observe and is not checked
        // for. What it CAN observe is a value someone wrote by hand.
        $readable = (string) $source->readableUrl;
        $expected = SourceUrl::readable($source->url);

        if ($readable !== $expected) {
            return [new Violation(
                $this->id(),
                Severity::Error,
                \sprintf(
                    'readable_url does not match its url. Expected %s, found %s. A learner '
                    .'would open a different file from the one this claim was verified against.',
                    $expected,
                    $readable,
                ),
                $ownerId,
            )];
        }

        // A raw url must actually convert. When it does not, readable() returns
        // it unchanged, the comparison above passes, and the citation ships
        // pointing at raw bytes — which is not wrong, but is worth naming
        // rather than discovering on the page.
        if (!SourceUrl::isRawFileUrl($source->url) && $readable === $source->url) {
            return [new Violation(
                $this->id(),
                Severity::Warning,
                'Citation is not a raw GitHub file URL, so it has no rendered equivalent and '
                .'is published as-is: '.$source->url,
                $ownerId,
            )];
        }

        return [];
    }
}
