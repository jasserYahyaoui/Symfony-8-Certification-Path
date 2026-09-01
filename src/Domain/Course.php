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

    public function wordCount(): int
    {
        return str_word_count(strip_tags($this->body));
    }
}
