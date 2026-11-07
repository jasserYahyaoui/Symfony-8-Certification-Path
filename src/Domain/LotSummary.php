<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * ADR-0013: the beginner's summary of one whole lot, « pour les nuls ».
 *
 * One Markdown file per lot under `content/summaries/`, keyed by the lot id in
 * its front matter. It teaches no fact of its own: every statement restates
 * what a course of the lot teaches, with an everyday example around it, and
 * the generator links each notion back to that course.
 *
 * The body follows a fixed shape so that a rule can read it (SUM-001):
 *
 *     ## En une phrase
 *     ## L'image de la vie réelle
 *     ## Le schéma                 (optional, a Mermaid diagram)
 *     ## Notion par notion
 *     ### <official item name>     one per item of the lot, in any order
 *     **C'est quoi ?** …
 *     **Dans la vraie vie** …
 *     **Pour l'examen** …
 *     ## Les erreurs de débutant
 *     ## La fiche du lot
 */
final readonly class LotSummary
{
    public const string HEADING_SENTENCE = 'En une phrase';
    public const string HEADING_IMAGE = "L'image de la vie réelle";
    public const string HEADING_DIAGRAM = 'Le schéma';
    public const string HEADING_NOTIONS = 'Notion par notion';
    public const string HEADING_MISTAKES = 'Les erreurs de débutant';
    public const string HEADING_DIGEST = 'La fiche du lot';

    /** The `## ` sections, in the order they must appear; the diagram is optional. */
    public const array REQUIRED_HEADINGS = [
        self::HEADING_SENTENCE,
        self::HEADING_IMAGE,
        self::HEADING_NOTIONS,
        self::HEADING_MISTAKES,
        self::HEADING_DIGEST,
    ];

    public const array ALLOWED_HEADINGS = [
        self::HEADING_SENTENCE,
        self::HEADING_IMAGE,
        self::HEADING_DIAGRAM,
        self::HEADING_NOTIONS,
        self::HEADING_MISTAKES,
        self::HEADING_DIGEST,
    ];

    /** The three labelled paragraphs every notion carries. */
    public const array NOTION_MARKERS = ["**C'est quoi ?**", '**Dans la vraie vie**', "**Pour l'examen**"];

    public function __construct(
        public string $lot,
        public string $body,
        public string $path,
        public VerificationStatus $verificationStatus,
        public ?string $reviewedAt = null,
    ) {
    }

    /**
     * @return list<string> the `## ` headings, fenced code skipped
     */
    public function sectionHeadings(): array
    {
        return Course::headingsOf($this->body);
    }

    /**
     * The text under `## $heading`, up to the next `## ` heading, or null
     * when the summary has no such section. Fenced code is skipped when
     * looking for the end.
     */
    public function section(string $heading): ?string
    {
        $inFence = false;
        $collected = null;
        foreach (explode("\n", $this->body) as $line) {
            if (str_starts_with(ltrim($line), '```')) {
                $inFence = !$inFence;
            } elseif (!$inFence && 1 === preg_match('/^## (.+?)\s*$/u', $line, $m)) {
                if (null !== $collected) {
                    break;
                }
                if ($m[1] === $heading) {
                    $collected = [];

                    continue;
                }
            }
            if (null !== $collected) {
                $collected[] = $line;
            }
        }

        return null === $collected ? null : implode("\n", $collected);
    }

    /**
     * The `### ` subsections of « Notion par notion », heading => text.
     *
     * @return array<string, string>
     */
    public function notions(): array
    {
        $section = $this->section(self::HEADING_NOTIONS);
        if (null === $section) {
            return [];
        }

        $notions = [];
        $current = null;
        $inFence = false;
        foreach (explode("\n", $section) as $line) {
            if (str_starts_with(ltrim($line), '```')) {
                $inFence = !$inFence;
            } elseif (!$inFence && 1 === preg_match('/^### (.+?)\s*$/u', $line, $m)) {
                $current = $m[1];
                $notions[$current] = $notions[$current] ?? '';

                continue;
            }
            if (null !== $current) {
                $notions[$current] .= $line."\n";
            }
        }

        return $notions;
    }

    /**
     * Headings of « Notion par notion » that appear more than once.
     *
     * @return list<string>
     */
    public function repeatedNotions(): array
    {
        $section = $this->section(self::HEADING_NOTIONS) ?? '';
        $seen = [];
        $inFence = false;
        foreach (explode("\n", $section) as $line) {
            if (str_starts_with(ltrim($line), '```')) {
                $inFence = !$inFence;
            } elseif (!$inFence && 1 === preg_match('/^### (.+?)\s*$/u', $line, $m)) {
                $seen[$m[1]] = ($seen[$m[1]] ?? 0) + 1;
            }
        }

        return array_keys(array_filter($seen, static fn (int $n): bool => $n > 1));
    }

    /**
     * Words a reader reads: Mermaid blocks excluded, as for a course
     * (ADR-0009). The front matter is not part of the body.
     */
    public function wordCount(): int
    {
        $prose = preg_replace(Course::MERMAID_BLOCK, ' ', $this->body) ?? $this->body;
        $tokens = preg_split('/\s+/u', trim($prose), -1, \PREG_SPLIT_NO_EMPTY);

        return false === $tokens ? 0 : \count($tokens);
    }

    /**
     * The body without fenced code, for the answer-leak check (SUM-002).
     */
    public function prose(): string
    {
        return preg_replace('/^```.*?^```/ms', '', $this->body) ?? $this->body;
    }
}
