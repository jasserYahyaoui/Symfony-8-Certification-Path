<?php

declare(strict_types=1);

namespace CertPath\Domain;

use CertPath\Schema\SchemaException;
use CertPath\Schema\SchemaRegistry;
use CertPath\Schema\YamlLoader;
use CertPath\Support\Id;

final readonly class FlashcardLoader
{
    public function __construct(
        private YamlLoader $yaml = new YamlLoader(),
    ) {
    }

    /**
     * @return list<Flashcard>
     */
    public function loadDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = glob(rtrim($directory, '/').'/*.yml') ?: [];
        sort($files);

        $cards = [];
        foreach ($files as $file) {
            $document = $this->yaml->load($file, SchemaRegistry::FLASHCARD_DECK);

            foreach (array_values((array) ($document['flashcards'] ?? [])) as $index => $raw) {
                if (!\is_array($raw)) {
                    throw new SchemaException(\sprintf('%s: flashcard #%d is not a mapping.', basename($file), $index));
                }
                $cards[] = $this->hydrate($raw, \sprintf('%s card #%d', basename($file), $index));
            }
        }

        return $cards;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function hydrate(array $raw, string $ctx): Flashcard
    {
        $sources = [];
        foreach ((array) ($raw['official_sources'] ?? []) as $source) {
            $sources[] = \is_array($source) ? SourceRef::fromArray($source) : new SourceRef(url: (string) $source);
        }

        return new Flashcard(
            id: Id::parse($this->req($raw, 'id', $ctx)),
            officialItemId: $this->req($raw, 'official_item', $ctx),
            front: $this->req($raw, 'front', $ctx),
            back: $this->req($raw, 'back', $ctx),
            explanation: $this->req($raw, 'explanation', $ctx),
            memorizationJustification: $this->req($raw, 'memorization_justification', $ctx),
            officialSources: $sources,
            language: Language::from($this->req($raw, 'language', $ctx)),
            verificationStatus: VerificationStatus::from($this->req($raw, 'verification_status', $ctx)),
        );
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function req(array $raw, string $key, string $ctx): string
    {
        if (!isset($raw[$key]) || !\is_scalar($raw[$key]) || '' === trim((string) $raw[$key])) {
            throw new SchemaException(\sprintf('%s: missing required field `%s`.', $ctx, $key));
        }

        return (string) $raw[$key];
    }
}
