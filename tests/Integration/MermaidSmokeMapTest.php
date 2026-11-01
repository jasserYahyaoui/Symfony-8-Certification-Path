<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Domain\Course;
use CertPath\Support\CourseUrl;
use CertPath\Support\Project;
use PHPUnit\Framework\TestCase;

/**
 * The production smoke test checks « every Mermaid diagram » (ADR-0009) against
 * a map written by hand in .github/workflows/pages.yml: page => diagrams the
 * course writes. A map nobody updates covers fewer pages each time a course
 * gains a diagram, while the step keeps printing « ok »: on 2026-11-01 it held
 * 10 of the 38 pages that draw one. This test derives the map from the corpus,
 * with the slug rule the generator itself uses, and requires the workflow's map
 * to be exactly that.
 */
final class MermaidSmokeMapTest extends TestCase
{
    public function testTheSmokeMapListsEveryPageThatDrawsADiagram(): void
    {
        $project = Project::locate();
        $content = $project->loadContentSet();

        $expected = [];
        foreach ($content->courses as $course) {
            $diagrams = \count(Course::mermaidBlocks($course->body));
            if (0 === $diagrams) {
                continue;
            }
            $item = $content->matrix->findById($course->officialItemId);
            self::assertNotNull($item, $course->id.' names an item the matrix does not hold');
            $expected[substr(CourseUrl::pagePath($item), \strlen('courses/'))] = $diagrams;
        }
        ksort($expected);

        $workflow = (string) file_get_contents($project->rootDir.'/.github/workflows/pages.yml');
        self::assertSame(1, preg_match('/^\s*PAGES = \{\n(.*?)\n\s*\}$/ms', $workflow, $block), 'the PAGES map of the Mermaid smoke check is no longer where this test reads it');
        preg_match_all("/^\\s*'([^']+)': (\\d+),\$/m", $block[1], $entries, \PREG_SET_ORDER);
        $actual = [];
        foreach ($entries as [, $page, $count]) {
            $actual[$page] = (int) $count;
        }
        ksort($actual);

        self::assertGreaterThan(0, \count($expected), 'no course draws a diagram: the comparison below would prove nothing');
        self::assertSame(
            $expected,
            $actual,
            'The PAGES map of the Mermaid check in .github/workflows/pages.yml must list every course page that draws a diagram, with its count.',
        );
    }
}
