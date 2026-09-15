<?php

declare(strict_types=1);

namespace CertPath\Domain;

use CertPath\Support\SourceUrl;

/**
 * Reproducible evidence, Master Plan §2.4.
 *
 * A documentation homepage is explicitly not sufficient evidence for a precise
 * technical claim, so an anchor is required whenever the reference points at a
 * documentation page rather than a pinned source file.
 */
final readonly class SourceRef
{
    /**
     * The same object, spelled as a rendered GitHub page.
     *
     * `url` is the form this project VERIFIES — raw bytes at a pinned ref.
     * `readableUrl` is the form it SHOWS, because a learner following a citation
     * wants a rendered file with line numbers, not reStructuredText source.
     *
     * Never null. A citation that does not carry one is given the derivation,
     * here, in the constructor — so every SourceRef in the system has a usable
     * link whether it came from YAML, a fixture or a test. A value that IS
     * supplied is kept as supplied and checked by SRC-002, because silently
     * correcting a wrong one would hide the defect the rule exists to report.
     */
    public ?string $readableUrl;

    public function __construct(
        public string $url,
        ?string $readableUrl = null,
        public ?string $anchor = null,
        public ?string $repository = null,
        public ?string $branch = null,
        public ?string $commitSha = null,
        public ?string $file = null,
        public ?string $symbolOrLines = null,
        public ?string $verifiedAt = null,
        public ?string $verifiedBy = null,
    ) {
        $this->readableUrl = null !== $readableUrl && '' !== $readableUrl
            ? $readableUrl
            : SourceUrl::readable($url);
    }

    /**
     * §2.3 forbids `/current/` as a primary source, and §2.2 pins the technical
     * authority to Symfony 8.0.
     */
    public function isVersionAnchored(): bool
    {
        if (str_contains($this->url, '/current/')) {
            return false;
        }

        if (null !== $this->commitSha && '' !== $this->commitSha) {
            return true;
        }

        return null !== $this->branch && '' !== $this->branch;
    }

    /**
     * What the site links to: the readable form when the citation carries one,
     * the raw URL otherwise. A citation is never rendered as a dead end.
     */
    public function displayUrl(): string
    {
        return null !== $this->readableUrl && '' !== $this->readableUrl
            ? $this->readableUrl
            : $this->url;
    }

    public function hasAnchor(): bool
    {
        return (null !== $this->anchor && '' !== $this->anchor)
            || (null !== $this->symbolOrLines && '' !== $this->symbolOrLines);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $string = static fn (string $key): ?string => isset($data[$key]) && \is_scalar($data[$key])
            ? (string) $data[$key]
            : null;

        return new self(
            url: $string('url') ?? '',
            readableUrl: $string('readable_url'),
            anchor: $string('anchor'),
            repository: $string('repository'),
            branch: $string('branch'),
            commitSha: $string('commit_sha'),
            file: $string('file'),
            symbolOrLines: $string('symbol_or_lines'),
            verifiedAt: $string('verified_at'),
            verifiedBy: $string('verified_by'),
        );
    }
}
