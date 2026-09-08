<?php

declare(strict_types=1);

namespace CertPath\Domain;

use CertPath\Support\Id;

/**
 * Teaching content for one atomic official item (Master Plan §4.2: COURSE =
 * understanding).
 *
 * The body is Markdown authored under `content/courses/`. §4.3 lists the
 * sections a course *may* use; it is deliberately not a template to fill in,
 * because an empty "Common mistakes" heading costs revision time and teaches
 * nothing.
 */
final readonly class Course
{
    /**
     * @param list<SourceRef> $officialSources
     */
    public function __construct(
        public Id $id,
        public string $officialItemId,
        public string $title,
        public ContentLevel $contentLevel,
        public string $body,
        public array $officialSources,
        public Language $language,
        public VerificationStatus $verificationStatus,
        public ?string $reviewedAt = null,
    ) {
    }

    /**
     * §4.3: "Course pages must not reveal interactive exam answers."
     *
     * Checked by CI rather than trusted, because a course that quotes a
     * question's correct option silently destroys that question's value.
     */
    public function mentions(string $needle): bool
    {
        return str_contains(mb_strtolower($this->body), mb_strtolower($needle));
    }

    /**
     * Body words — the unit CLAUDE.md requires for course size, and the input
     * to the revision budget REV-001.
     *
     * Counted as whitespace-separated tokens, not with `str_word_count()`:
     * that function's default character class excludes accented letters, so it
     * splits "défaut" into two words and over-counts a French corpus. On this
     * project's first course it reported 387 where the body holds 354 tokens —
     * a 9% inflation applied unevenly, since the error scales with how many
     * accents a page happens to contain.
     */
    public function wordCount(): int
    {
        $tokens = preg_split('/\s+/u', trim($this->body), -1, \PREG_SPLIT_NO_EMPTY);

        return false === $tokens ? 0 : \count($tokens);
    }
}
