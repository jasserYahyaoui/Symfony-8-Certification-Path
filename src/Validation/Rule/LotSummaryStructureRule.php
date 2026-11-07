<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\LotSummary;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * ADR-0013, SUM-001: a lot summary has the shape a beginner can rely on.
 *
 * The owner asked for « une page de résumé de tout le lot pour les nuls avec
 * des exemples de vie réelle » (2026-11-07). « Tout le lot » is checked, not
 * trusted: every atomic item of the lot has its own notion, named exactly as
 * the syllabus names it, and nothing else does. « Exemples de vie réelle » is
 * checked the same way: each notion carries a labelled everyday example,
 * between what the notion is and what the exam asks of it.
 *
 * The summary carries no link: the generator writes, under each notion, the
 * link to its course, so a summary cannot send a beginner to a page that does
 * not exist. It carries no hand-written heading id either (`{#…}`): the
 * generator writes those, as for a course.
 *
 * Every lot carrying atomic items has its summary: « par lot » is checked as
 * well. The 26 summaries were delivered together, so no lot ever needed to be
 * tolerated without one, and the rule has no staging to remove later.
 */
final class LotSummaryStructureRule implements Rule
{

    private const int SENTENCE_MAX_WORDS = 60;
    private const int MISTAKES_MIN_POINTS = 2;
    private const int DIGEST_MIN_POINTS = 5;
    private const int DIGEST_MAX_POINTS = 10;

    public function id(): string
    {
        return 'SUM-001';
    }

    public function description(): string
    {
        return 'A lot summary covers every item of its lot, each with an everyday example, in the fixed shape of ADR-0013.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];
        $itemsByLot = [];
        foreach ($content->matrix->items as $item) {
            $itemsByLot[$item->lot][] = $item->officialItem;
        }

        $summarised = [];
        foreach ($content->summaries as $summary) {
            $summarised[$summary->lot] = true;
            foreach ($this->problems($summary, $itemsByLot[$summary->lot] ?? []) as $problem) {
                $violations[] = new Violation($this->id(), Severity::Error, $problem, $summary->path);
            }
        }

        foreach (array_keys($itemsByLot) as $lot) {
            if (!isset($summarised[$lot])) {
                $violations[] = new Violation($this->id(), Severity::Error, \sprintf('Lot %s has no summary (content/summaries/%s.md).', $lot, $lot), $lot);
            }
        }

        return $violations;
    }

    /**
     * @param list<string> $itemNames the official item names of the lot
     *
     * @return list<string>
     */
    private function problems(LotSummary $summary, array $itemNames): array
    {
        if ([] === $itemNames) {
            return [\sprintf('Summary of %s, a lot that carries no atomic item.', $summary->lot)];
        }

        $problems = [];

        // Sections: only the known ones, in the fixed order, the required ones present.
        $headings = $summary->sectionHeadings();
        foreach ($headings as $heading) {
            if (!\in_array($heading, LotSummary::ALLOWED_HEADINGS, true)) {
                $problems[] = \sprintf('Unknown section « %s »; a summary uses only: %s.', $heading, implode(', ', LotSummary::ALLOWED_HEADINGS));
            }
        }
        foreach (LotSummary::REQUIRED_HEADINGS as $required) {
            if (!\in_array($required, $headings, true)) {
                $problems[] = \sprintf('Missing section « %s ».', $required);
            }
        }
        $known = array_values(array_filter($headings, static fn (string $h): bool => \in_array($h, LotSummary::ALLOWED_HEADINGS, true)));
        $expected = array_values(array_filter(LotSummary::ALLOWED_HEADINGS, static fn (string $h): bool => \in_array($h, $known, true)));
        if ($known !== $expected) {
            $problems[] = 'Sections out of order; the order is: '.implode(', ', LotSummary::ALLOWED_HEADINGS).'.';
        }
        if (\count($headings) !== \count(array_unique($headings))) {
            $problems[] = 'A section appears twice.';
        }

        // « En une phrase »: a short paragraph, nothing else.
        $sentence = $summary->section(LotSummary::HEADING_SENTENCE);
        if (null !== $sentence) {
            $text = trim($sentence);
            $words = preg_split('/\s+/u', $text, -1, \PREG_SPLIT_NO_EMPTY) ?: [];
            if ('' === $text) {
                $problems[] = '« En une phrase » is empty.';
            } elseif (\count($words) > self::SENTENCE_MAX_WORDS) {
                $problems[] = \sprintf('« En une phrase » runs to %d words; it is at most %d.', \count($words), self::SENTENCE_MAX_WORDS);
            }
            if (1 === preg_match('/^(\s*[-*]\s|\s*\d+\.\s|```|\||#)/m', $sentence)) {
                $problems[] = '« En une phrase » is a paragraph: no list, code, table or sub-heading.';
            }
        }

        // « L'image de la vie réelle »: not empty.
        $image = $summary->section(LotSummary::HEADING_IMAGE);
        if (null !== $image && '' === trim($image)) {
            $problems[] = "« L'image de la vie réelle » is empty.";
        }

        // « Notion par notion »: exactly the items of the lot, each with its three labels.
        if (null !== $summary->section(LotSummary::HEADING_NOTIONS)) {
            $notions = $summary->notions();
            foreach ($itemNames as $name) {
                if (!isset($notions[$name])) {
                    $problems[] = \sprintf('No notion for the item « %s »: « Notion par notion » needs a `### %s` subsection.', $name, $name);
                }
            }
            foreach (array_keys($notions) as $heading) {
                if (!\in_array($heading, $itemNames, true)) {
                    $problems[] = \sprintf('Notion « %s » names no item of %s; each `### ` heading is an official item name, verbatim.', $heading, $summary->lot);
                }
            }
            foreach ($summary->repeatedNotions() as $heading) {
                $problems[] = \sprintf('Notion « %s » appears twice.', $heading);
            }
            foreach ($notions as $heading => $text) {
                foreach (LotSummary::NOTION_MARKERS as $marker) {
                    if (!str_contains($text, $marker)) {
                        $problems[] = \sprintf('Notion « %s » has no %s paragraph.', $heading, $marker);
                    }
                }
            }
        }

        // « Les erreurs de débutant » and « La fiche du lot »: lists of the right size.
        $mistakes = $summary->section(LotSummary::HEADING_MISTAKES);
        if (null !== $mistakes && self::points($mistakes) < self::MISTAKES_MIN_POINTS) {
            $problems[] = \sprintf('« Les erreurs de débutant » lists %d point(s); it needs at least %d.', self::points($mistakes), self::MISTAKES_MIN_POINTS);
        }
        $digest = $summary->section(LotSummary::HEADING_DIGEST);
        if (null !== $digest) {
            $n = self::points($digest);
            if ($n < self::DIGEST_MIN_POINTS || $n > self::DIGEST_MAX_POINTS) {
                $problems[] = \sprintf('« La fiche du lot » lists %d point(s); it holds %d to %d.', $n, self::DIGEST_MIN_POINTS, self::DIGEST_MAX_POINTS);
            }
        }

        // No link and no heading id: the generator writes both.
        if (1 === preg_match('/\]\([^)]*\)/', $summary->prose())) {
            $problems[] = 'A summary carries no link: the generator links each notion to its course.';
        }
        if (1 === preg_match('/^#{2,3} .*\{#/mu', $summary->prose())) {
            $problems[] = 'A heading carries a hand-written id; the generator writes the anchors.';
        }

        // MDX reads `{…}` as a JavaScript expression and `<` as a tag: outside
        // code, either breaks the page at build time (lot 06, 2026-11-07:
        // « #{prénom} » in prose stopped the site build). Code spans and
        // fenced blocks are where they belong.
        foreach (explode("\n", $summary->prose()) as $line) {
            $outsideCode = preg_replace('/`[^`]*`/', '', $line) ?? $line;
            if (1 === preg_match('/[{}<]/', $outsideCode)) {
                $problems[] = \sprintf('« %s » has a {, } or < outside code, which MDX cannot render; put it in a code span.', trim(mb_substr($line, 0, 60)));
            }
        }

        return $problems;
    }

    private static function points(string $section): int
    {
        return preg_match_all('/^- \S/m', $section);
    }
}
