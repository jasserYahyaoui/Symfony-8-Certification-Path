<?php

declare(strict_types=1);

namespace CertPath\Domain;

use CertPath\Schema\SchemaException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads the lot summaries of ADR-0013 from `content/summaries/`, one Markdown
 * file per lot with a YAML front matter naming the lot.
 */
final readonly class LotSummaryLoader
{
    /**
     * @return list<LotSummary>
     */
    public function loadDirectory(string $directory, string $projectDir = ''): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = glob(rtrim($directory, '/').'/*.md') ?: [];
        sort($files);

        return array_map(fn (string $file): LotSummary => $this->loadFile($file, $projectDir), $files);
    }

    public function loadFile(string $path, string $projectDir = ''): LotSummary
    {
        $raw = file_get_contents($path);
        if (false === $raw) {
            throw new SchemaException(\sprintf('Cannot read lot summary "%s".', $path));
        }

        $ctx = basename($path);
        if (1 !== preg_match('/^---\R(.*?)\R---\R(.*)$/s', $raw, $matches)) {
            throw new SchemaException(\sprintf('Lot summary "%s" has no YAML front matter.', $ctx));
        }

        $meta = Yaml::parse($matches[1]);
        if (!\is_array($meta)) {
            throw new SchemaException(\sprintf('Lot summary "%s" has malformed front matter.', $ctx));
        }

        $lot = $meta['lot'] ?? null;
        if (!\is_string($lot) || 1 !== preg_match('/^lot-\d{2}$/', $lot)) {
            throw new SchemaException(\sprintf('Lot summary "%s": `lot` must be a lot id such as lot-02.', $ctx));
        }
        if ($ctx !== $lot.'.md') {
            throw new SchemaException(\sprintf('Lot summary "%s" must be named after its lot, %s.md.', $ctx, $lot));
        }

        $body = trim($matches[2]);
        if ('' === $body) {
            throw new SchemaException(\sprintf('Lot summary "%s" has an empty body.', $ctx));
        }

        $status = $meta['verification_status'] ?? null;
        if (!\is_string($status)) {
            throw new SchemaException(\sprintf('Lot summary "%s": missing `verification_status`.', $ctx));
        }

        $relative = '' !== $projectDir && str_starts_with($path, rtrim($projectDir, '/').'/')
            ? substr($path, \strlen(rtrim($projectDir, '/')) + 1)
            : 'content/summaries/'.$ctx;

        return new LotSummary(
            lot: $lot,
            body: $body,
            path: $relative,
            verificationStatus: VerificationStatus::from($status),
            reviewedAt: isset($meta['reviewed_at']) ? (string) $meta['reviewed_at'] : null,
        );
    }
}
