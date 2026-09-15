<?php

declare(strict_types=1);

namespace CertPath\Schema\Migrations;

use CertPath\Schema\SchemaRegistry;

/** question-bank 1 -> 2: every citation gains its readable_url. */
final class QuestionBankReadableUrl extends CitationReadableUrl
{
    public function schemaName(): string
    {
        return SchemaRegistry::QUESTION_BANK;
    }

    protected function collection(): string
    {
        return 'questions';
    }
}
