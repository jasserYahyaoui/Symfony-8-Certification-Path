<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Support\Project;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * The content side of docs/facts/central-facts.yml.
 *
 * tools/behavior/run.php executes each fact against Symfony 8.0; this test
 * checks that the content still carries it: every linked id exists, every
 * fact names a check that exists, and no canonical file states the corrected
 * claim again. It runs in the main suite, without Symfony 8.0 installed.
 */
#[CoversNothing]
final class CentralFactsTest extends TestCase
{
    /** @return array<string, array<string, mixed>> */
    private static function facts(): array
    {
        $data = Yaml::parseFile(Project::locate()->path('docs/facts/central-facts.yml'));

        return $data['facts'];
    }

    /** @return array<string, string> path => contents */
    private static function canonicalFiles(): array
    {
        $project = Project::locate();
        $files = [$project->matrixPath() => file_get_contents($project->matrixPath())];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($project->path('content'), \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (\in_array($file->getExtension(), ['md', 'yml'], true)) {
                $files[$file->getPathname()] = file_get_contents($file->getPathname());
            }
        }
        ksort($files);

        return $files;
    }

    public function testEveryFactNamesAnExistingBehaviorCheck(): void
    {
        $declared = [];
        foreach (glob(Project::locate()->path('tools/behavior/checks/*.php')) as $file) {
            preg_match_all("/^    '([a-z0-9.-]+)' => static function/m", file_get_contents($file), $m);
            $declared = [...$declared, ...$m[1]];
        }

        foreach (self::facts() as $id => $fact) {
            self::assertContains($fact['behavior_check'], $declared, "Fact $id names a check that tools/behavior does not define.");
        }
        self::assertSameSize(array_unique($declared), $declared, 'A behavior check id is declared twice.');
    }

    public function testEveryLinkedContentIdExists(): void
    {
        $all = implode("\n", self::canonicalFiles());
        foreach (self::facts() as $id => $fact) {
            self::assertNotEmpty($fact['content'], "Fact $id links no content.");
            foreach ($fact['content'] as $contentId) {
                // assertTrue, not assertMatchesRegularExpression: a failure must
                // print the id, never the corpus it was searched in.
                self::assertTrue(1 === preg_match('/^ *(- )?id: "?'.preg_quote($contentId, '/').'"?$/m', $all), "Fact $id links $contentId, which no canonical file defines.");
            }
        }
    }

    public function testNoCanonicalFileStatesACorrectedClaimAgain(): void
    {
        $found = [];
        foreach (self::facts() as $id => $fact) {
            foreach ($fact['forbidden'] as $pattern) {
                foreach (self::canonicalFiles() as $path => $text) {
                    if (1 === preg_match($pattern, $text, $m, \PREG_OFFSET_CAPTURE)) {
                        $found[] = \sprintf('%s: %s matches %s', $id, self::locate($path, $text, $m[0][1]), $pattern);
                    }
                }
            }
        }

        self::assertSame([], $found, "A corrected claim is back:\n".implode("\n", $found));
    }

    /** Names the entity around an offset, withholding a HOLDOUT id. */
    private static function locate(string $path, string $text, int $offset): string
    {
        $relative = substr($path, \strlen(Project::locate()->rootDir) + 1);
        $before = substr($text, 0, $offset);
        if (0 === preg_match_all('/\n *- id: /', $before, $all, \PREG_OFFSET_CAPTURE)) {
            return $relative;
        }
        $start = end($all[0])[1];
        $next = preg_match('/\n *- id: /', $text, $n, \PREG_OFFSET_CAPTURE, $start + 1) ? $n[0][1] : \strlen($text);
        $entity = substr($text, $start, $next - $start);
        if (str_contains($entity, 'pool: HOLDOUT')) {
            return $relative.' (a HOLDOUT question, id withheld)';
        }
        preg_match('/- id: "?([^"\s]+)/', $entity, $id);

        return $relative.' '.$id[1];
    }
}
