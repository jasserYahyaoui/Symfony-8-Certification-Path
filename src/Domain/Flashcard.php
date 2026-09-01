<?php

declare(strict_types=1);

namespace CertPath\Domain;

use CertPath\Support\Id;

/**
 * Master Plan §6: FLASHCARD = durable memorization.
 *
 * A flashcard must test one idea, hide its answer before reveal, cite an
 * anchored official source and add genuine memorization value. §6 is explicit
 * that a fact already retained through application is not worth a card.
 */
final readonly class Flashcard
{
    /**
     * @param list<SourceRef> $officialSources
     */
    public function __construct(
        public Id $id,
        public string $officialItemId,
        public string $front,
        public string $back,
        public string $explanation,
        public string $memorizationJustification,
        public array $officialSources,
        public Language $language,
        public VerificationStatus $verificationStatus,
    ) {
    }
}
