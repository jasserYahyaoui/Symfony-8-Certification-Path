<?php

declare(strict_types=1);

namespace CertPath\Build;

use CertPath\Coverage\CoverageCalculator;
use CertPath\Coverage\CoverageReport;
use CertPath\Support\Project;
use CertPath\Validation\ContentSet;

/**
 * Generates the static site deployed to GitHub Pages (ADR-0001).
 *
 * The deployed artefact is plain HTML/CSS/JS plus JSON payloads: no server
 * runtime, no account, no secret in client code (§13).
 */
final readonly class SiteBuilder
{
    public function __construct(
        private Project $project,
        private PayloadBuilder $payloads = new PayloadBuilder(),
        private CoverageCalculator $coverage = new CoverageCalculator(),
    ) {
    }

    /**
     * @return list<string> generated files, relative to the build directory
     */
    public function build(ContentSet $content): array
    {
        $buildDir = $this->project->buildDir();
        $this->reset($buildDir);

        $written = [];

        $practice = $this->payloads->practicePayload($content);
        PayloadBuilder::assertNoHoldoutLeak($practice, $content);

        $written[] = $this->writeJson($buildDir.'/data/practice.json', $practice);
        $written[] = $this->writeJson($buildDir.'/data/exam.json', $this->payloads->examPayload($content));

        $report = $this->coverage->calculate($content->matrix);
        $written[] = $this->writeJson($buildDir.'/data/coverage.json', [
            'generated_at' => gmdate('c'),
            'denominator_established' => $report->isDenominatorEstablished(),
            'total_official_items' => $report->totalOfficialItems,
            'exam_ready_items' => $report->examReadyItems,
            'percentage' => $report->percentage(),
            'by_lot' => $report->byLot,
            'by_topic' => $report->byTopic,
        ]);

        foreach (['css/app.css', 'js/app.js'] as $asset) {
            $source = $this->project->path('assets/'.$asset);
            if (is_file($source)) {
                $target = $buildDir.'/assets/'.$asset;
                $this->ensureDir(\dirname($target));
                copy($source, $target);
                $written[] = 'assets/'.$asset;
            }
        }

        foreach ($this->pages($report) as $file => $html) {
            file_put_contents($buildDir.'/'.$file, $html);
            $written[] = $file;
        }

        // GitHub Pages would otherwise run the output through Jekyll.
        file_put_contents($buildDir.'/.nojekyll', '');
        $written[] = '.nojekyll';

        sort($written);

        return $written;
    }

    /**
     * @return array<string, string>
     */
    private function pages(CoverageReport $report): array
    {
        $layout = $this->template('layout.html');

        return [
            'index.html' => $this->render($layout, 'Accueil', 'home', strtr($this->template('home.html'), [
                '{{coverage_state}}' => $report->isDenominatorEstablished()
                    ? \sprintf('%s%% (%d / %d items officiels)', $report->percentage(), $report->examReadyItems, $report->totalOfficialItems)
                    : 'Non établie — le syllabus officiel n\'a pas encore été importé',
            ])),
            'practice.html' => $this->render($layout, 'Practice Mode', 'practice', $this->template('practice.html')),
            'exam.html' => $this->render($layout, 'Exam Mode', 'exam', $this->template('exam.html')),
        ];
    }

    private function render(string $layout, string $title, string $page, string $body): string
    {
        return strtr($layout, [
            '{{title}}' => htmlspecialchars($title, \ENT_QUOTES),
            '{{page}}' => htmlspecialchars($page, \ENT_QUOTES),
            '{{content}}' => $body,
            '{{year}}' => gmdate('Y'),
        ]);
    }

    private function template(string $name): string
    {
        $path = $this->project->path('assets/templates/'.$name);
        $contents = is_file($path) ? file_get_contents($path) : false;

        if (false === $contents) {
            throw new \RuntimeException(\sprintf('Missing template "%s".', $name));
        }

        return $contents;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeJson(string $path, array $data): string
    {
        $this->ensureDir(\dirname($path));
        file_put_contents($path, json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));

        return substr($path, \strlen($this->project->buildDir()) + 1);
    }

    private function reset(string $dir): void
    {
        if (is_dir($dir)) {
            $this->removeRecursively($dir);
        }
        $this->ensureDir($dir);
    }

    private function removeRecursively(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item instanceof \SplFileInfo) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0o775, true) && !is_dir($dir)) {
            throw new \RuntimeException(\sprintf('Cannot create directory "%s".', $dir));
        }
    }
}
