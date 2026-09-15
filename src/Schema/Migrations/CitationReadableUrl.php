<?php

declare(strict_types=1);

namespace CertPath\Schema\Migrations;

use CertPath\Schema\Migration;
use CertPath\Support\SourceUrl;

/**
 * Version 1 -> 2 for every bank that carries citations: each one gains
 * `readable_url`, the github.com/blob spelling of its raw `url`.
 *
 * Unlike SyllabusMatrixOutcomeIdentity, this migration DOES convert data, and
 * the difference is worth stating because it looks like an inconsistency. That
 * one refused to act because an outcome id cannot be derived from anything —
 * inventing one is exactly what ADR-0002 forbids. A readable URL is the
 * opposite case: it is a pure function of the raw URL, owner, repository, ref
 * and path unchanged, so deriving it invents nothing and two people running
 * this migration get the same bytes.
 *
 * It never overwrites a value already written. A citation whose `readable_url`
 * is wrong is a defect SRC-002 reports by name; silently correcting it here
 * would hide the mistake and, if the raw URL were the wrong one of the pair,
 * would propagate it.
 */
abstract class CitationReadableUrl implements Migration
{
    public function fromVersion(): int
    {
        return 1;
    }

    /**
     * The document key holding the records that carry citations.
     */
    abstract protected function collection(): string;

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    public function upgrade(array $document): array
    {
        $key = $this->collection();
        $records = $document[$key] ?? null;

        if (!\is_array($records)) {
            return $document;
        }

        foreach ($records as $index => $record) {
            if (!\is_array($record) || !\is_array($record['official_sources'] ?? null)) {
                continue;
            }

            foreach ($record['official_sources'] as $position => $source) {
                if (!\is_array($source)) {
                    continue;
                }

                $existing = $source['readable_url'] ?? null;
                if (\is_string($existing) && '' !== $existing) {
                    continue;
                }

                $url = $source['url'] ?? null;
                if (!\is_string($url) || '' === $url) {
                    continue;
                }

                $records[$index]['official_sources'][$position]['readable_url'] = SourceUrl::readable($url);
            }
        }

        $document[$key] = $records;

        return $document;
    }
}
