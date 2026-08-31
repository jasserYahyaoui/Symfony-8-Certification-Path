<?php

declare(strict_types=1);

namespace CertPath\Domain;

use CertPath\Schema\SchemaException;
use CertPath\Schema\SchemaRegistry;
use CertPath\Schema\YamlLoader;
use CertPath\Support\Id;

/**
 * Parses `docs/syllabus/syllabus-matrix.yml` into typed OfficialItem objects.
 *
 * Every field of Master Plan §3.3 is mandatory; a missing field is an error
 * rather than a default, because a silently defaulted `content_level` or
 * `classification` would distort both coverage and scoring.
 */
final readonly class MatrixLoader
{
    public function __construct(
        private YamlLoader $yaml = new YamlLoader(),
    ) {
    }

    public function load(string $path): SyllabusMatrix
    {
        $document = $this->yaml->load($path, SchemaRegistry::SYLLABUS_MATRIX);

        $rawItems = $document['items'] ?? [];
        if (!\is_array($rawItems)) {
            throw new SchemaException('syllabus-matrix: `items` must be a list.');
        }

        $items = [];
        foreach (array_values($rawItems) as $index => $raw) {
            if (!\is_array($raw)) {
                throw new SchemaException(\sprintf('syllabus-matrix: item #%d is not a mapping.', $index));
            }
            $items[] = $this->hydrate($raw, $index);
        }

        $revision = $document['syllabus_revision'] ?? null;
        $note = $document['missing_topics_note'] ?? null;

        return new SyllabusMatrix(
            items: $items,
            syllabusRevision: \is_scalar($revision) ? (string) $revision : null,
            // Absent means incomplete. A partial import must never be able to
            // masquerade as a complete denominator by omitting a flag.
            syllabusComplete: true === ($document['syllabus_complete'] ?? false),
            missingTopicsNote: \is_scalar($note) ? (string) $note : null,
        );
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function hydrate(array $raw, int $index): OfficialItem
    {
        $ctx = \sprintf('syllabus-matrix item #%d', $index);

        $sources = [];
        foreach ($this->listOf($raw, 'official_sources', $ctx, allowEmpty: true) as $source) {
            $sources[] = \is_array($source)
                ? SourceRef::fromArray($source)
                : new SourceRef(url: (string) $source);
        }

        return new OfficialItem(
            id: Id::parse($this->string($raw, 'id', $ctx)),
            officialTopicOrder: $this->int($raw, 'official_topic_order', $ctx),
            officialTopic: $this->string($raw, 'official_topic', $ctx),
            officialItemOrder: $this->int($raw, 'official_item_order', $ctx),
            officialItem: $this->string($raw, 'official_item', $ctx),
            officialWording: $this->string($raw, 'official_wording', $ctx),
            learningDomain: $this->string($raw, 'learning_domain', $ctx),
            lot: $this->string($raw, 'lot', $ctx),
            chapter: $this->optionalString($raw, 'chapter'),
            classification: Classification::from($this->string($raw, 'classification', $ctx)),
            // Pedagogical fields are unknown until an item has been researched
            // and specified (§3.4). They are optional here and enforced by the
            // lifecycle-aware rule PED-001, not by the parser.
            contentLevel: ($level = $this->optionalString($raw, 'content_level')) !== null
                ? ContentLevel::from($level)
                : null,
            contentLevelJustification: $this->optionalString($raw, 'content_level_justification'),
            learningOutcomes: $this->stringList($raw, 'learning_outcomes', $ctx, allowEmpty: true),
            requiredAssessmentModes: $this->stringList($raw, 'required_assessment_modes', $ctx, allowEmpty: true),
            minimumEvidence: $this->optionalString($raw, 'minimum_evidence'),
            exclusionBoundaries: $this->string($raw, 'exclusion_boundaries', $ctx),
            versionConstraints: $this->string($raw, 'version_constraints', $ctx),
            officialSources: $sources,
            courseRefs: $this->stringList($raw, 'course_refs', $ctx, allowEmpty: true),
            flashcardRefs: $this->stringList($raw, 'flashcard_refs', $ctx, allowEmpty: true),
            questionRefs: $this->stringList($raw, 'question_refs', $ctx, allowEmpty: true),
            exerciseRefs: $this->stringList($raw, 'exercise_refs', $ctx, allowEmpty: true),
            examRefs: $this->stringList($raw, 'exam_refs', $ctx, allowEmpty: true),
            prerequisites: $this->stringList($raw, 'prerequisites', $ctx, allowEmpty: true),
            status: ItemStatus::from($this->string($raw, 'status', $ctx)),
            verificationStatus: VerificationStatus::from($this->string($raw, 'verification_status', $ctx)),
            examReady: (bool) ($raw['exam_ready'] ?? false),
            lastVerifiedAt: $this->optionalString($raw, 'last_verified_at'),
            reviewedBy: $this->optionalString($raw, 'reviewed_by'),
            notes: $this->optionalString($raw, 'notes'),
        );
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function string(array $raw, string $key, string $ctx): string
    {
        if (!isset($raw[$key]) || !\is_scalar($raw[$key]) || '' === trim((string) $raw[$key])) {
            throw new SchemaException(\sprintf('%s: missing required field `%s`.', $ctx, $key));
        }

        return (string) $raw[$key];
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function optionalString(array $raw, string $key): ?string
    {
        return isset($raw[$key]) && \is_scalar($raw[$key]) ? (string) $raw[$key] : null;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function int(array $raw, string $key, string $ctx): int
    {
        if (!isset($raw[$key]) || !is_numeric($raw[$key])) {
            throw new SchemaException(\sprintf('%s: field `%s` must be an integer.', $ctx, $key));
        }

        return (int) $raw[$key];
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return list<mixed>
     */
    private function listOf(array $raw, string $key, string $ctx, bool $allowEmpty = false): array
    {
        $value = $raw[$key] ?? [];
        if (!\is_array($value)) {
            throw new SchemaException(\sprintf('%s: field `%s` must be a list.', $ctx, $key));
        }
        if ([] === $value && !$allowEmpty) {
            throw new SchemaException(\sprintf('%s: field `%s` must not be empty.', $ctx, $key));
        }

        return array_values($value);
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return list<string>
     */
    private function stringList(array $raw, string $key, string $ctx, bool $allowEmpty = false): array
    {
        return array_map(
            static fn (mixed $v): string => (string) (\is_scalar($v) ? $v : ''),
            $this->listOf($raw, $key, $ctx, $allowEmpty),
        );
    }
}
