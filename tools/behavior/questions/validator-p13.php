<?php

declare(strict_types=1);

/*
 * Remediation P1.3 (2026-10-15): the multiple-answer Validator questions, one
 * boolean per choice in content order, compared by run.php with the `correct`
 * flags of the YAML.
 *
 * Slug, SlugOnClass and SlugValidator come from checks/validator.php, which
 * run.php requires before this directory.
 */

use CertBehavior\Validator\Slug;
use CertBehavior\Validator\SlugOnClass;
use CertBehavior\Validator\SlugValidator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validation;

$validator = static fn () => Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

return [
    // Length(min: 3), Choice(choices: ['a']) | Email(), Regex: a violation on ''
    'QST-axp7pjs0gkx9' => static fn (): array => array_map(
        static fn ($constraint): bool => \count($validator()->validate('', $constraint)) > 0,
        [new Assert\Length(min: 3), new Assert\Choice(choices: ['a']), new Assert\Email(), new Assert\Regex(pattern: '/^\d+$/')],
    ),

    // SlugValidator used; runs for null | usable on a class; UnexpectedValueException aborts validate()
    'QST-v6s83rdh77nq' => static function () use ($validator): array {
        SlugValidator::$seen = [];
        $v = $validator();
        $v->validate(null, new Slug());
        try {
            $v->validate(new SlugOnClass());
            $onClass = true;
        } catch (ConstraintDefinitionException) {
            $onClass = false;
        }
        try {
            $v->validate(['x'], new Slug());
            $aborts = false;
        } catch (Throwable) {
            $aborts = true;
        }

        return [
            SlugValidator::class === (new Slug())->validatedBy(),
            [null] === array_slice(SlugValidator::$seen, 0, 1),
            $onClass,
            $aborts,
        ];
    },
];
