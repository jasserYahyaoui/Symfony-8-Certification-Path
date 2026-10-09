<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\Course;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * ADR-0009: every Mermaid diagram in a course is a real diagram, with an
 * accessible title and description.
 *
 * Two reasons, one rule. A diagram is an image: a screen-reader user gets
 * nothing from it without `accTitle` and `accDescr`, which Mermaid renders as
 * the SVG's <title> and <desc>. And a ```mermaid block is outside the revision
 * budget REV-001, by the owner's decision of 2026-10-09: an exemption that
 * accepted any text inside the fence would be a place to put prose the budget
 * refuses. Requiring a declared diagram type closes the easy form of that; the
 * mermaid token count AUD-04 reports keeps the rest visible.
 *
 * What it cannot see: whether the arrows are true. A diagram is a claim like
 * any sentence of the course, and it is verified the same way, against the
 * Symfony 8.0 code.
 */
final class DiagramAccessibilityRule implements Rule
{
    /**
     * Diagram types a block may open with. Kept to the kinds a course could
     * plausibly use; an unlisted type fails until it is deliberately added.
     */
    private const array DIAGRAM_TYPES = [
        'flowchart', 'graph', 'sequenceDiagram', 'classDiagram', 'stateDiagram',
        'stateDiagram-v2', 'erDiagram', 'journey', 'gantt', 'pie', 'mindmap',
        'timeline', 'gitGraph', 'quadrantChart', 'requirementDiagram',
    ];

    public function id(): string
    {
        return 'DIA-001';
    }

    public function description(): string
    {
        return 'Every Mermaid diagram in a course declares its type, an accessible title and an accessible description.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->courses as $course) {
            foreach ($this->problems($course->body) as $problem) {
                $violations[] = new Violation($this->id(), Severity::Error, $problem, $course->id->value);
            }
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    private function problems(string $body): array
    {
        $problems = [];

        if (1 === preg_match('/^~~~+\s*mermaid\b/mi', $body)) {
            $problems[] = 'A Mermaid diagram is fenced with tildes: use a ```mermaid fence, the only form the budget '
                .'exemption and this rule recognise.';
        }

        $blocks = Course::mermaidBlocks($body);
        $openings = preg_match_all('/^```mermaid\b/m', $body);
        if ($openings > \count($blocks)) {
            $problems[] = 'A ```mermaid fence is never closed: the block renders nothing and its text counts against '
                .'the budget.';
        }

        foreach ($blocks as $index => $block) {
            $label = \sprintf('Mermaid diagram %d', $index + 1);
            $lines = $this->declarations($block);

            $type = strtok($lines[0] ?? '', " \t");
            if (!\in_array($type, self::DIAGRAM_TYPES, true)) {
                $problems[] = \sprintf(
                    '%s does not open with a known diagram type (found "%s"); the fence holds a diagram, not prose.',
                    $label,
                    $lines[0] ?? '',
                );
            }

            // [ \t], never \s: \s crosses the line break, and an empty `accTitle:`
            // would borrow the next line as its value.
            if (1 !== preg_match('/^[ \t]*accTitle[ \t]*:[ \t]*\S/m', $block)) {
                $problems[] = \sprintf('%s has no accessible title: add a non-empty `accTitle:` line.', $label);
            }

            if (1 !== preg_match('/^[ \t]*accDescr[ \t]*(:[ \t]*\S|\{)/m', $block)) {
                $problems[] = \sprintf('%s has no accessible description: add a non-empty `accDescr:` line.', $label);
            }
        }

        return $problems;
    }

    /**
     * The block's lines after its fence, its optional front matter and its
     * comments: the first of them is the diagram type.
     *
     * @return list<string>
     */
    private function declarations(string $block): array
    {
        $lines = explode("\n", $block);
        array_shift($lines);
        array_pop($lines);

        if ('---' === trim($lines[0] ?? '')) {
            $close = array_search('---', array_map('trim', \array_slice($lines, 1)), true);
            $lines = false === $close ? [] : \array_slice($lines, $close + 2);
        }

        return array_values(array_filter(
            array_map('trim', $lines),
            static fn (string $line): bool => '' !== $line && !str_starts_with($line, '%%'),
        ));
    }
}
