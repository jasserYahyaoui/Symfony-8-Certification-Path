<?php

declare(strict_types=1);

/*
 * P1.3 (F8, part): Validator behaviours a course states, executed on
 * symfony/validator 8.0.
 *
 * The custom constraint is declared in this namespace on purpose: the default
 * Constraint::validatedBy() is static::class.'Validator', which an anonymous
 * class cannot satisfy.
 */

namespace CertBehavior\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[\Attribute]
final class Slug extends Constraint
{
    public string $message = 'The string "{{ value }}" is not a slug.';
}

final class SlugValidator extends ConstraintValidator
{
    /** @var list<mixed> every value the validator was called with */
    public static array $seen = [];

    public function validate(mixed $value, Constraint $constraint): void
    {
        self::$seen[] = $value;
        if (!$constraint instanceof Slug) {
            throw new UnexpectedTypeException($constraint, Slug::class);
        }
        if (null === $value || '' === $value) {
            return;
        }
        if (!\is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }
        if (!preg_match('/^[a-z0-9-]+$/', $value)) {
            $this->context->buildViolation($constraint->message)->setParameter('{{ value }}', $value)->addViolation();
        }
    }
}

/** Routed to SlugValidator, which refuses any constraint but Slug. */
final class Misrouted extends Constraint
{
    public function validatedBy(): string
    {
        return SlugValidator::class;
    }
}

#[Slug]
final class SlugOnClass
{
}

final class StrongPassword extends Assert\Compound
{
    protected function getConstraints(array $options): array
    {
        return [new Assert\NotBlank(), new Assert\Length(min: 8), new Assert\Regex('/\d/')];
    }
}

$validator = static fn (): ValidatorInterface => Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
/** @return list<string> property path => message, one entry per violation */
$messages = static function (ConstraintViolationListInterface $list): array {
    $out = [];
    foreach ($list as $violation) {
        $out[] = $violation->getPropertyPath().'|'.$violation->getMessage();
    }

    return $out;
};

return [
    // null is valid for every value constraint but NotNull and NotBlank.
    'validator.null-passes-value-constraints' => static function () use ($validator): ?string {
        $v = $validator();
        $constraints = [
            'Length' => new Assert\Length(min: 3), 'Email' => new Assert\Email(), 'Regex' => new Assert\Regex('/a/'),
            'Choice' => new Assert\Choice(choices: ['a']), 'Positive' => new Assert\Positive(), 'Count' => new Assert\Count(min: 1),
            'Url' => new Assert\Url(requireTld: true), 'Type' => new Assert\Type('string'),
        ];
        foreach ($constraints as $name => $constraint) {
            if (0 !== \count($v->validate(null, $constraint))) {
                return "$name raised a violation on null";
            }
        }
        if (1 !== \count($v->validate(null, new Assert\NotNull())) || 1 !== \count($v->validate(null, new Assert\NotBlank()))) {
            return 'NotNull or NotBlank accepted null';
        }

        return null;
    },
    // The empty string is not null: some constraints skip it, others judge it.
    'validator.empty-string-split' => static function () use ($validator): ?string {
        $v = $validator();
        foreach (['Email' => new Assert\Email(), 'Regex' => new Assert\Regex('/a/'), 'Url' => new Assert\Url(requireTld: true)] as $name => $c) {
            if (0 !== \count($v->validate('', $c))) {
                return "$name raised a violation on ''";
            }
        }
        foreach (['Length' => new Assert\Length(min: 3), 'Choice' => new Assert\Choice(choices: ['a']), 'Positive' => new Assert\Positive()] as $name => $c) {
            if (1 !== \count($v->validate('', $c))) {
                return "$name accepted ''";
            }
        }

        return null;
    },
    // Constraint options are named arguments; an options array is refused in 8.0.
    'validator.options-array-refused' => static function (): ?string {
        try {
            new Assert\Choice(['a']);
        } catch (\Symfony\Component\Validator\Exception\InvalidArgumentException $e) {
            return str_contains($e->getMessage(), 'no longer supported') ? null : 'unexpected message: '.$e->getMessage();
        }

        return 'new Choice([\'a\']) was accepted';
    },
    // 8.0 changed the default of Url's requireTld to true: a host without a TLD is refused.
    'validator.url-requires-tld-by-default' => static function () use ($validator): ?string {
        $v = $validator();
        if (1 !== \count($v->validate('http://localhost', new Assert\Url()))) {
            return 'http://localhost passed Url() with its defaults';
        }

        return 0 === \count($v->validate('http://localhost', new Assert\Url(requireTld: false))) ? null : 'requireTld: false still refused localhost';
    },
    // Collection: a missing key and an unexpected key each produce a violation at [key].
    'validator.collection-missing-and-extra' => static function () use ($validator, $messages): ?string {
        $v = $validator();
        $fields = ['name' => new Assert\NotBlank(), 'age' => new Assert\Positive()];
        $got = $messages($v->validate(['name' => 'x', 'zip' => 1], new Assert\Collection(fields: $fields)));
        $want = ['[age]|This field is missing.', '[zip]|This field was not expected.'];
        if ($got !== $want) {
            return 'got '.implode(' / ', $got);
        }
        if (0 !== \count($v->validate(['name' => 'x', 'age' => 1, 'zip' => 1], new Assert\Collection(fields: $fields, allowExtraFields: true)))
            || 0 !== \count($v->validate(['name' => 'x'], new Assert\Collection(fields: $fields, allowMissingFields: true)))) {
            return 'allowExtraFields or allowMissingFields did not silence its violation';
        }
        // Required means the key must be present, not that its value is non-null.
        if (0 !== \count($v->validate(['age' => null], new Assert\Collection(fields: ['age' => new Assert\Required([new Assert\Positive()])])))) {
            return 'a present key holding null failed Required([Positive])';
        }

        return null;
    },
    // A list runs every constraint; Sequentially stops at the first violation; Compound runs all of its own.
    'validator.list-sequentially-compound' => static function () use ($validator): ?string {
        $v = $validator();
        $three = [new Assert\NotBlank(), new Assert\Length(min: 3), new Assert\Email()];
        $list = \count($v->validate('ab', $three));
        $seq = \count($v->validate('ab', new Assert\Sequentially($three)));
        $compound = \count($v->validate('abc', new StrongPassword()));

        return match (true) {
            2 !== $list => "a plain list gave $list violation(s)",
            1 !== $seq => "Sequentially gave $seq violation(s)",
            2 !== $compound => "Compound gave $compound violation(s), expected both failing members",
            default => null,
        };
    },
    // A custom constraint: validatedBy(), default target, default group.
    'validator.custom-constraint-defaults' => static function () use ($validator): ?string {
        $slug = new Slug();
        if (SlugValidator::class !== $slug->validatedBy()) {
            return 'validatedBy() is '.$slug->validatedBy();
        }
        if (Constraint::PROPERTY_CONSTRAINT !== $slug->getTargets() || ['Default'] !== $slug->groups) {
            return 'unexpected default target or groups';
        }
        try {
            $validator()->validate(new SlugOnClass());
        } catch (ConstraintDefinitionException) {
            return null;
        }

        return 'a property constraint was accepted on a class';
    },
    // The validator runs on null too: skipping it is the validator's own job.
    'validator.custom-validator-sees-null' => static function () use ($validator, $messages): ?string {
        SlugValidator::$seen = [];
        $v = $validator();
        $none = \count($v->validate(null, new Slug()));
        $bad = $messages($v->validate('Hello World', new Slug()));

        return match (true) {
            [null, 'Hello World'] !== SlugValidator::$seen => 'the validator was not called with null',
            0 !== $none => 'null produced a violation',
            ['|The string "Hello World" is not a slug.'] !== $bad => 'got '.implode(' / ', $bad),
            default => null,
        };
    },
    // UnexpectedValueException becomes a violation; UnexpectedTypeException propagates.
    'validator.unexpected-value-vs-type' => static function () use ($validator, $messages): ?string {
        $v = $validator();
        $got = $messages($v->validate(['x'], new Slug()));
        if (['|This value should be of type string.'] !== $got) {
            return 'an array gave '.implode(' / ', $got);
        }
        try {
            $v->validate('x', new Misrouted());
        } catch (UnexpectedTypeException) {
            return null;
        }

        return 'UnexpectedTypeException did not propagate';
    },
];
