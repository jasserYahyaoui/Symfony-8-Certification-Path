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
     * @param array<string, string> $wordingFingerprints itemId => sha256 of official wording
     * @param list<string>         $contentFiles       repository-relative paths
     */
    public function __construct(
        public SyllabusMatrix $matrix,
        public array $questions = [],
        public array $courses = [],
        public array $flashcards = [],
        public array $excludedTerms = [],
        public array $wordingFingerprints = [],
        public array $contentFiles = [],
        public string $projectDir = '.',
    ) {
    }
}
