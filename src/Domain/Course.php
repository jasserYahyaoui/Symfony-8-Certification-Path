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
        /**
         * `pedagogical` for a page written in the revision-course format of
         * ADR-0012, null otherwise. The owner lifted the word budget for that
         * format (2026-10-27); FMT-001 makes the format earn the exemption.
         */
        public ?string $format = null,
    ) {
    }

    /** The front-matter value of a course in the ADR-0012 format. */
    public const string FORMAT_PEDAGOGICAL = 'pedagogical';

    /** The section holding the course's own multiple-choice questions. */
    public const string QUIZ_HEADING = "QCM d'entraînement";

    /** The compact revision sheet closing a page in the ADR-0012 format. */
    public const string DIGEST_HEADING = 'Fiche express';

    public function isPedagogical(): bool
    {
        return self::FORMAT_PEDAGOGICAL === $this->format;
    }

    /**
     * The text under `## $heading`, up to the next `## ` heading, or null
     * when the course has no such section. Fenced code is skipped when looking
     * for the end, so a `## ` line inside a block does not cut the section.
     */
    public function section(string $heading): ?string
    {
        $lines = explode("\n", $this->body);
        $inFence = false;
        $collected = null;
        foreach ($lines as $line) {
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
     * The questions of the « QCM d'entraînement » section, in page order.
     *
     * The format is fixed so that a rule can read it:
     *
     *     **Question 1.** The prompt, which may continue on following lines
     *     and hold a fenced code block.
     *
     *     - **A.** first choice
     *     - **B.** second choice
     *
     *     <details>
     *     <summary>Correction</summary>
     *
     *     **Bonne réponse : B.** Why B, and why the others are not.
     *
     *     </details>
     *
     * « Bonnes réponses : A et C. » announces a question with several correct
     * choices. Nothing is thrown: each question lists its own problems.
     *
     * @return list<CourseQuizQuestion>
     */
    public function quizQuestions(): array
    {
        $section = $this->section(self::QUIZ_HEADING);
        if (null === $section) {
            return [];
        }

        $blocks = [];
        $current = null;
        $inFence = false;
        foreach (explode("\n", $section) as $line) {
            if (str_starts_with(ltrim($line), '```')) {
                $inFence = !$inFence;
            }
            if (!$inFence && 1 === preg_match('/^\*\*Question (\d+)\.\*\*\s*(.*)$/u', $line, $m)) {
                if (null !== $current) {
                    $blocks[] = $current;
                }
                $current = ['number' => (int) $m[1], 'lines' => [$m[2]]];

                continue;
            }
            if (null !== $current) {
                $current['lines'][] = $line;
            }
        }
        if (null !== $current) {
            $blocks[] = $current;
        }

        $questions = [];
        foreach ($blocks as $index => $block) {
            $questions[] = self::parseQuizBlock($block['number'], $block['lines'], $index + 1);
        }

        return $questions;
    }

    /**
     * @param list<string> $lines
     */
    private static function parseQuizBlock(int $number, array $lines, int $expectedNumber): CourseQuizQuestion
    {
        $problems = [];
        if ($number !== $expectedNumber) {
            $problems[] = \sprintf('numbered %d where %d was expected', $number, $expectedNumber);
        }

        $prompt = [];
        $choices = [];
        $details = null;
        $inFence = false;
        $stage = 'prompt';
        foreach ($lines as $line) {
            if (str_starts_with(ltrim($line), '```')) {
                $inFence = !$inFence;
            }
            if ('details' === $stage) {
                if (!$inFence && str_starts_with(trim($line), '</details>')) {
                    $stage = 'after';

                    continue;
                }
                $details[] = $line;

                continue;
            }
            if (!$inFence && 'after' !== $stage && str_starts_with(trim($line), '<details>')) {
                $stage = 'details';
                $details = [];

                continue;
            }
            if (!$inFence && 1 === preg_match('/^- \*\*([A-Z])\.\*\*\s+(.+)$/u', $line, $m)) {
                if ('prompt' !== $stage && 'choices' !== $stage) {
                    $problems[] = 'a choice appears after the correction';
                }
                $stage = 'choices';
                if (isset($choices[$m[1]])) {
                    $problems[] = \sprintf('choice %s is listed twice', $m[1]);
                }
                $choices[$m[1]] = trim($m[2]);

                continue;
            }
            if ('prompt' === $stage) {
                $prompt[] = $line;
            } elseif ('choices' === $stage && '' !== trim($line)) {
                $problems[] = 'text between the choices and the correction';
            } elseif ('after' === $stage && '' !== trim($line)) {
                $problems[] = 'text after the correction';
            }
        }

        $letters = array_keys($choices);
        $count = \count($letters);
        if ($count < 3 || $count > 5) {
            $problems[] = \sprintf('%d choice(s), where 3 to 5 are expected', $count);
        }
        if ($letters !== \array_slice(range('A', 'Z'), 0, $count)) {
            $problems[] = 'choices are not lettered A, B, C… in order';
        }

        $answers = [];
        $correction = '';
        if (null === $details) {
            $problems[] = 'no <details> correction';
        } else {
            $body = implode("\n", $details);
            if (1 !== preg_match('/^\s*<summary>[^<]+<\/summary>\s*$/mu', $body)) {
                $problems[] = 'the correction has no <summary>';
            }
            if (1 === preg_match('/^\*\*(Bonnes? réponses?) : (.+?)\.\*\*/mu', $body, $m)) {
                preg_match_all('/\b([A-Z])\b/u', $m[2], $found);
                $answers = array_values(array_unique($found[1]));
                $plural = 'Bonnes réponses' === $m[1];
                if ('Bonne réponse' !== $m[1] && !$plural) {
                    $problems[] = \sprintf('« %s » is neither « Bonne réponse » nor « Bonnes réponses »', $m[1]);
                }
                if (!$plural && 1 !== \count($answers)) {
                    $problems[] = '« Bonne réponse » must name exactly one letter';
                }
                if ($plural && \count($answers) < 2) {
                    $problems[] = '« Bonnes réponses » must name at least two letters';
                }
                foreach ($answers as $answer) {
                    if (!isset($choices[$answer])) {
                        $problems[] = \sprintf('the correction names %s, which is not a choice', $answer);
                    }
                }
                $explanation = trim(preg_replace(['/^\s*<summary>.*?<\/summary>\s*$/mu', '/^\*\*Bonnes? réponses? : .+?\.\*\*/mu'], '', $body) ?? '');
                $correction = $explanation;
                if (self::tokens($explanation) < 12) {
                    $problems[] = 'the correction explains nothing (under 12 words besides the answer)';
                }
            } else {
                $problems[] = 'the correction does not start with « **Bonne réponse : X.** »';
            }
        }

        $promptText = trim(implode("\n", $prompt));
        if ('' === $promptText) {
            $problems[] = 'empty prompt';
        }

        return new CourseQuizQuestion($number, $promptText, $choices, $answers, $correction, $problems);
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
