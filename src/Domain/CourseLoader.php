<?php

declare(strict_types=1);

namespace CertPath\Domain;

use CertPath\Schema\SchemaException;
use CertPath\Support\Id;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads Markdown courses with YAML front matter from `content/courses/`.
 */
final readonly class CourseLoader
{
    /**
     * @return list<Course>
     */
    public function loadDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = glob(rtrim($directory, '/').'/*.md') ?: [];
        sort($files);

        return array_map($this->loadFile(...), $files);
    }

    public function loadFile(string $path): Course
    {
        $raw = file_get_contents($path);
        if (false === $raw) {
            throw new SchemaException(\sprintf('Cannot read course "%s".', $path));
        }

        if (1 !== preg_match('/^---\R(.*?)\R---\R(.*)$/s', $raw, $matches)) {
            throw new SchemaException(\sprintf('Course "%s" has no YAML front matter.', basename($path)));
        }

        $meta = Yaml::parse($matches[1]);
        if (!\is_array($meta)) {
            throw new SchemaException(\sprintf('Course "%s" has malformed front matter.', basename($path)));
        }

        $ctx = basename($path);
        $body = trim($matches[2]);

        if ('' === $body) {
            throw new SchemaException(\sprintf('Course "%s" has an empty body.', $ctx));
        }

        $sources = [];
        foreach ((array) ($meta['official_sources'] ?? []) as $source) {
            $sources[] = \is_array($source) ? SourceRef::fromArray($source) : new SourceRef(url: (string) $source);
        }

        return new Course(
            id: Id::parse($this->req($meta, 'id', $ctx)),
            officialItemId: $this->req($meta, 'official_item', $ctx),
            title: $this->req($meta, 'title', $ctx),
            contentLevel: ContentLevel::from($this->req($meta, 'content_level', $ctx)),
            body: $body,
            officialSources: $sources,
            language: Language::from($this->req($meta, 'language', $ctx)),
            verificationStatus: VerificationStatus::from($this->req($meta, 'verification_status', $ctx)),
            reviewedAt: isset($meta['reviewed_at']) ? (string) $meta['reviewed_at'] : null,
        );
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function req(array $meta, string $key, string $ctx): string
    {
        if (!isset($meta[$key]) || !\is_scalar($meta[$key]) || '' === trim((string) $meta[$key])) {
            throw new SchemaException(\sprintf('Course "%s": missing required front-matter key `%s`.', $ctx, $key));
        }

        return (string) $meta[$key];
    }
}
