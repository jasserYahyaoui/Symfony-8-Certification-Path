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
        /**
         * Where each learning outcome of the item is taught: outcome id =>
         * the text of a `## ` heading of the body. A wrong answer in a mock
         * links to that section rather than to the top of a page of a
         * thousand words. Rule SEC-001 keeps every entry pointing at a
         * heading that exists.
         *
         * @var array<string, string>
         */
        public array $outcomeSections = [],
    ) {
    }

    /**
     * The `## ` headings of the body, in order, fenced code excluded.
     *
     * @return list<string>
     */
    public function sectionHeadings(): array
    {
        return self::headingsOf($this->body);
    }

    /**
     * @return list<string>
     */
    public static function headingsOf(string $markdown): array
    {
        $headings = [];
        $inFence = false;
        foreach (explode("\n", $markdown) as $line) {
            if (str_starts_with(ltrim($line), '```')) {
                $inFence = !$inFence;

                continue;
            }
            if (!$inFence && 1 === preg_match('/^## (.+?)\s*$/u', $line, $m)) {
                $headings[] = $m[1];
            }
        }

        return $headings;
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
     * A fenced ```mermaid block, from its opening line to its closing fence.
     *
     * An unclosed block does not match, so its text stays counted: the
     * failure mode of a malformed diagram is a heavier page, never a lighter
     * one. DIA-001 reports the unclosed fence itself.
     */
    public const string MERMAID_BLOCK = '/^```mermaid\b[^\n]*\n.*?^```[ \t]*$/msu';

    /**
     * The ```mermaid blocks of a Markdown body, fences included.
     *
     * @return list<string>
     */
    public static function mermaidBlocks(string $markdown): array
    {
        preg_match_all(self::MERMAID_BLOCK, $markdown, $matches);

        return $matches[0];
    }

    /**
     * Body words — the unit CLAUDE.md requires for course size, and the input
     * to the revision budget REV-001.
     *
     * A ```mermaid block is not counted (ADR-0009, the owner's decision of
     * 2026-10-09). Its source is node identifiers, arrows and labels, not prose
     * read at 250 words a minute, and counting it would make a diagram compete
     * with the explanation it replaces. DIA-001 keeps the exemption from
     * becoming a hiding place: every block must declare a diagram type, an
     * accessible title and an accessible description.
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
        return self::tokens(preg_replace(self::MERMAID_BLOCK, ' ', $this->body) ?? $this->body);
    }

    /**
     * The `## Objectif` section, heading included, up to the next `## `
     * heading (ADR-0011). A `## Objectifs …` heading does not match: the
     * pattern requires the line to end after the word.
     */
    public const string OBJECTIVE_SECTION = '/^## Objectif[ \t]*(?:\n|\z).*?(?=^## |\z)/msu';

    /**
     * The `## Objectif` sections of a Markdown body. A course has one; OBJ-001
     * reports a second rather than let both leave the budget unnoticed.
     *
     * @return list<string>
     */
    public static function objectiveSections(string $markdown): array
    {
        preg_match_all(self::OBJECTIVE_SECTION, $markdown, $matches);

        return $matches[0];
    }

    /**
     * Words the revision budget REV-001 and readiness criterion R14 count:
     * body words without the `## Objectif` section.
     *
     * The owner's decision of 2026-10-24 (ADR-0011): the page's preamble is
     * not counted. Four of its five parts — the table, « Objectifs
     * d'apprentissage », « Justification du niveau », « Limites de
     * périmètre » — are written by the generator from the matrix and were
     * never in a course body; the objective is, and it leaves the budget here.
     * OBJ-001 keeps it to a short statement of the goal, so that the exemption
     * cannot shelter teaching.
     *
     * `wordCount()` is unchanged: course size, as CLAUDE.md reports it, and the
     * revision roadmap still count the objective, because a learner reads it.
     */
    public function budgetWordCount(): int
    {
        return self::tokens(preg_replace(
            [self::MERMAID_BLOCK, self::OBJECTIVE_SECTION],
            ' ',
            $this->body,
        ) ?? $this->body);
    }

    private static function tokens(string $prose): int
    {
        $tokens = preg_split('/\s+/u', trim($prose), -1, \PREG_SPLIT_NO_EMPTY);

        return false === $tokens ? 0 : \count($tokens);
    }
}
