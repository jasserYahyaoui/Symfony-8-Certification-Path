<?php

declare(strict_types=1);

namespace CertPath\Validation;

use CertPath\Domain\Course;
use CertPath\Domain\Flashcard;
use CertPath\Domain\Question;
use CertPath\Domain\SyllabusMatrix;

/**
 * Everything the validation rules operate on, assembled once per run.
 */
final readonly class ContentSet
{
    /**
     * @param list<Question>       $questions
     * @param list<Course>         $courses
     * @param list<Flashcard>      $flashcards
     * @param list<string>         $excludedTerms      §1.5 prohibited expansion
     * @param list<array{id: string, official_topic: string, transport_terms: list<string>}> $contextualExclusions
     *        §1.5 exclusions that bite only inside a stated context, so that a
     *        term legitimate elsewhere is not rejected wherever it appears.
     * @param array<string, string> $wordingFingerprints itemId => sha256 of official wording
     * @param list<string>         $contentFiles       repository-relative paths
     */
    public function __construct(
        public SyllabusMatrix $matrix,
        public array $questions = [],
        public array $courses = [],
        public array $flashcards = [],
        public array $excludedTerms = [],
        public array $contextualExclusions = [],
        public array $wordingFingerprints = [],
        public array $contentFiles = [],
        public string $projectDir = '.',
    ) {
    }
}
