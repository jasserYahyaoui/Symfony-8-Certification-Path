<?php

declare(strict_types=1);

namespace CertPath\Validation;

final readonly class Validator
{
    /**
     * @param list<Rule> $rules
     */
    public function __construct(
        private array $rules,
    ) {
    }

    /**
     * @return list<Violation>
     */
    public function run(ContentSet $content): array
    {
        $violations = [];
        foreach ($this->rules as $rule) {
            foreach ($rule->check($content) as $violation) {
                $violations[] = $violation;
            }
        }

        return $violations;
    }

    /**
     * @param list<Violation> $violations
     */
    public static function hasBlockingViolation(array $violations): bool
    {
        foreach ($violations as $violation) {
            if ($violation->severity->failsBuild()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Rule>
     */
    public function rules(): array
    {
        return $this->rules;
    }
}
