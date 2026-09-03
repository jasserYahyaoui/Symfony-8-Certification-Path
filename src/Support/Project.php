<?php

declare(strict_types=1);

namespace CertPath\Support;

use CertPath\Domain\CourseLoader;
use CertPath\Domain\FlashcardLoader;
use CertPath\Domain\LotRegistry;
use CertPath\Domain\MatrixLoader;
use CertPath\Domain\QuestionLoader;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Schema\SchemaRegistry;
use CertPath\Schema\YamlLoader;
use CertPath\Validation\ContentSet;

/**
 * Canonical paths and the one place that assembles a ContentSet, so the
 * validator, the coverage engine and the site build all read exactly the same
 * data (Master Plan §11: one canonical source per entity).
 */
final readonly class Project
{
    public function __construct(
        public string $rootDir,
    ) {
    }

    public static function locate(): self
    {
        return new self(\dirname(__DIR__, 2));
    }

    public function path(string $relative): string
    {
        return $this->rootDir.'/'.ltrim($relative, '/');
    }

    public function matrixPath(): string
    {
        return $this->path('docs/syllabus/syllabus-matrix.yml');
    }

    public function exclusionsPath(): string
    {
        return $this->path('docs/syllabus/exclusions.yml');
    }

    public function glossaryPath(): string
    {
        return $this->path('docs/syllabus/glossary.yml');
    }

    public function lotsPath(): string
    {
        return $this->path('docs/syllabus/lots.yml');
    }

    public function wordingLockPath(): string
    {
        return $this->path('docs/syllabus/wording.lock.yml');
    }

    public function questionsDir(): string
    {
        return $this->path('content/questions');
    }

    public function coursesDir(): string
    {
        return $this->path('content/courses');
    }

    public function flashcardsDir(): string
    {
        return $this->path('content/flashcards');
    }

    public function websiteDir(): string
    {
        return $this->path('website');
    }

    /** Rendered static site, produced by Docusaurus from the generated tree. */
    public function buildDir(): string
    {
        return $this->path('website/build');
    }

    public function loadMatrix(): SyllabusMatrix
    {
        return (new MatrixLoader())->load($this->matrixPath());
    }

    /**
     * The learner-facing lot names (Master Plan §14).
     *
     * A missing file yields an empty registry rather than an error: the
     * generator then falls back to the lot id, which is visibly wrong and
     * therefore fixable, instead of inventing a name.
     */
    public function loadLotRegistry(): LotRegistry
    {
        if (!is_file($this->lotsPath())) {
            return LotRegistry::empty();
        }

        return LotRegistry::fromArray((new YamlLoader())->load($this->lotsPath(), SchemaRegistry::SYLLABUS_MATRIX));
    }

    public function loadContentSet(): ContentSet
    {
        return new ContentSet(
            matrix: $this->loadMatrix(),
            questions: (new QuestionLoader())->loadDirectory($this->questionsDir()),
            courses: (new CourseLoader())->loadDirectory($this->coursesDir()),
            flashcards: (new FlashcardLoader())->loadDirectory($this->flashcardsDir()),
            excludedTerms: $this->loadExcludedTerms(),
            wordingFingerprints: $this->loadWordingFingerprints(),
            contentFiles: $this->markdownFiles(),
            projectDir: $this->rootDir,
        );
    }

    /**
     * @return list<string>
     */
    public function loadExcludedTerms(): array
    {
        $document = (new YamlLoader())->load($this->exclusionsPath(), SchemaRegistry::EXCLUSIONS);

        $terms = [];
        foreach ((array) ($document['excluded_topics'] ?? []) as $entry) {
            if (\is_array($entry)) {
                foreach ((array) ($entry['match_terms'] ?? []) as $term) {
                    $terms[] = (string) $term;
                }
            } elseif (\is_scalar($entry)) {
                $terms[] = (string) $entry;
            }
        }

        return array_values(array_unique($terms));
    }

    /**
     * Master Plan §5 — the French-to-English certification glossary.
     *
     * @return list<array{en: string, fr: string, topic: string, see: string, note?: string}>
     */
    public function loadGlossary(): array
    {
        if (!is_file($this->glossaryPath())) {
            return [];
        }

        $document = (new YamlLoader())->load($this->glossaryPath(), SchemaRegistry::GLOSSARY);

        $entries = [];
        foreach ((array) ($document['entries'] ?? []) as $entry) {
            if (!\is_array($entry)) {
                continue;
            }

            $row = [
                'en' => (string) ($entry['en'] ?? ''),
                'fr' => (string) ($entry['fr'] ?? ''),
                'topic' => (string) ($entry['topic'] ?? ''),
                'see' => (string) ($entry['see'] ?? ''),
            ];
            if (isset($entry['note'])) {
                $row['note'] = (string) $entry['note'];
            }

            $entries[] = $row;
        }

        return $entries;
    }

    /**
     * @return array<string, string>
     */
    public function loadWordingFingerprints(): array
    {
        if (!is_file($this->wordingLockPath())) {
            return [];
        }

        $document = (new YamlLoader())->load($this->wordingLockPath(), SchemaRegistry::SYLLABUS_MATRIX);

        $fingerprints = [];
        foreach ((array) ($document['fingerprints'] ?? []) as $id => $hash) {
            $fingerprints[(string) $id] = (string) $hash;
        }

        return $fingerprints;
    }

    /**
     * @return list<string>
     */
    public function markdownFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($this->rootDir, \FilesystemIterator::SKIP_DOTS),
                static function (\SplFileInfo $file): bool {
                    $name = $file->getFilename();

                    return !\in_array($name, ['vendor', 'build', '.git', 'node_modules', 'website'], true);
                },
            ),
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && 'md' === $file->getExtension()) {
                $files[] = substr($file->getPathname(), \strlen($this->rootDir) + 1);
            }
        }

        sort($files);

        return $files;
    }
}
