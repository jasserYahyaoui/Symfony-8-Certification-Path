<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * Reproducible evidence, Master Plan §2.4.
 *
 * A documentation homepage is explicitly not sufficient evidence for a precise
 * technical claim, so an anchor is required whenever the reference points at a
 * documentation page rather than a pinned source file.
 */
final readonly class SourceRef
{
    public function __construct(
        public string $url,
        public ?string $anchor = null,
        public ?string $repository = null,
        public ?string $branch = null,
        public ?string $commitSha = null,
        public ?string $file = null,
        public ?string $symbolOrLines = null,
        public ?string $verifiedAt = null,
        public ?string $verifiedBy = null,
    ) {
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
