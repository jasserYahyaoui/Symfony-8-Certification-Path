<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\Course;
use CertPath\Domain\LotSummary;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * DIA-002: a « Le schéma » section is drawn, not written.
 *
 * The owner, 2026-11-08: « Refais voir tous les schémas, ils doivent être
 * visibles en tant qu'image comme mermaid, pas un code ni texte ». Until then
 * the course policy let a ```text block stand for a diagram of a linear cycle,
 * and 104 of the 175 schema sections were text in a fence: the learner saw
 * code. The section now holds at least one ```mermaid block — which DIA-001
 * then holds to a declared type, an accessible title and description — and no
 * other fenced block. Prose may surround the drawing.
 *
 * A fence of another language is refused rather than tolerated beside a
 * diagram: the failure the owner saw was a text fence shown as code, and a
 * schema that is half drawing, half fence, shows the same thing.
 *
 * What it cannot see: whether the browser draws the block. That is
 * `website/tools/verify-diagrams-drawn.mjs`, on the built site.
 */
final class SchemaDrawnRule implements Rule
{
    public const string HEADING = 'Le schéma';

    public function id(): string
    {
        return 'DIA-002';
    }

    public function description(): string
    {
        return 'A « Le schéma » section draws at least one Mermaid diagram and holds no other fenced block.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->courses as $course) {
            foreach ($this->problems($course->section(self::HEADING)) as $problem) {
                $violations[] = new Violation($this->id(), Severity::Error, $problem, $course->id->value);
            }
        }

        // ADR-0013: a lot summary's schema is optional, and drawn when present.
        foreach ($content->summaries as $summary) {
            foreach ($this->problems($summary->section(LotSummary::HEADING_DIAGRAM)) as $problem) {
                $violations[] = new Violation($this->id(), Severity::Error, $problem, $summary->path);
            }
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    private function problems(?string $section): array
    {
        if (null === $section) {
            return [];
        }

        $problems = [];
        if ([] === Course::mermaidBlocks($section)) {
            $problems[] = \sprintf('« %s » draws no diagram: it needs a ```mermaid block, not text or prose alone (owner, 2026-11-08).', self::HEADING);
        }

        preg_match_all('/^```+[ \t]*([^\s`]*)/m', $section, $fences);
        // Every other match is a closing fence: the openings are at even positions.
        $openings = array_values(array_filter($fences[1], static fn (int $i): bool => 0 === $i % 2, \ARRAY_FILTER_USE_KEY));
        foreach ($openings as $language) {
            if ('mermaid' !== $language) {
                $problems[] = \sprintf(
                    '« %s » holds a ```%s block, shown as code: draw it in Mermaid instead.',
                    self::HEADING,
                    '' === $language ? '' : $language,
                );
            }
        }

        return $problems;
    }
}
