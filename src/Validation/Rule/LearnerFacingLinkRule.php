<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Support\SourceUrl;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * CRS-003 — a link a learner clicks goes to the rendered page, not the raw file.
 *
 * WHY THIS EXISTS. The project already holds the principle and enforces it on
 * citations: `url` is the raw file this project fetches to check a claim, and
 * `readable_url` — rule SRC-002 — is the github.com/blob form the site sends a
 * learner to. The generator says so in as many words: "a citation they cannot
 * read is a citation they will not follow".
 *
 * That was enforced on the *citations* and on nothing else. The Markdown links
 * hand-written in a course body were never looked at, and 209 of them, across
 * 142 of the 163 courses, pointed at raw.githubusercontent.com — a learner
 * clicking "Sources officielles" got a wall of reStructuredText with no line
 * numbers, no file tree and no navigation. Every gate was green throughout.
 *
 * The check is deliberately narrow. It fires only on a Markdown link target
 * that `SourceUrl::readable()` knows how to convert, so a raw URL with no blob
 * equivalent — or one shown as evidence inside a code span rather than linked —
 * is left alone. It never touches the front matter: AUD-02 extracts each
 * citation's (repository, ref) from the raw form, and converting `url` itself
 * would make three contamination checks skip every source while reporting zero
 * findings.
 */
final class LearnerFacingLinkRule implements Rule
{
    /** A Markdown link target, captured for inspection. */
    private const string LINK = '/\]\(([^)\s]+)\)/';

    public function id(): string
    {
        return 'CRS-003';
    }

    public function description(): string
    {
        return 'A link in a course body sends the learner to a rendered page, not a raw file.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->courses as $course) {
            preg_match_all(self::LINK, $course->body, $matches);

            foreach ($matches[1] as $target) {
                $readable = SourceUrl::readable($target);
                if ($readable === $target) {
                    continue;
                }

                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf(
                        'Course links a learner to the raw file "%s"; use the rendered page "%s".',
                        $target,
                        $readable,
                    ),
                    $course->id->value,
                );
            }
        }

        return $violations;
    }
}
