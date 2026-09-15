<?php

declare(strict_types=1);

namespace CertPath\Schema\Migrations;

use CertPath\Schema\SchemaRegistry;

/** flashcard-deck 1 -> 2: every citation gains its readable_url. */
final class FlashcardDeckReadableUrl extends CitationReadableUrl
{
    public function schemaName(): string
    {
        return SchemaRegistry::FLASHCARD_DECK;
    }

    protected function collection(): string
    {
        return 'flashcards';
    }
}
