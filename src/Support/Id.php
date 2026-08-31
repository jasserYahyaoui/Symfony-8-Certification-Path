<?php

declare(strict_types=1);

namespace CertPath\Support;

/**
 * Persistent identifier.
 *
 * Master Plan §11: "Persistent IDs must not depend on file names or slugs."
 * An ID is therefore minted from randomness once, recorded in the ID registry,
 * and never regenerated. Renaming a file, retitling a chapter or rewording an
 * official item leaves every cross-reference intact.
 *
 * Format: `<PREFIX>-<12 chars of Crockford base32>`, e.g. `OIT-3k9m2xq7bv4t`.
 */
final readonly class Id implements \Stringable
{
    /** Crockford base32 minus I, L, O, U to stay unambiguous when read aloud. */
    private const string ALPHABET = '0123456789abcdefghjkmnpqrstvwxyz';

    private const int SUFFIX_LENGTH = 12;

    private function __construct(
        public EntityType $type,
        public string $value,
    ) {
    }

    /**
     * Mint a brand-new identifier. Randomness only: never a hash of the
     * content, the title or the file path, so that content may be rewritten
     * without changing identity.
     */
    public static function mint(EntityType $type): self
    {
        $alphabetMax = \strlen(self::ALPHABET) - 1;
        $suffix = '';
        for ($i = 0; $i < self::SUFFIX_LENGTH; ++$i) {
            $suffix .= self::ALPHABET[random_int(0, $alphabetMax)];
        }

        return new self($type, $type->prefix().'-'.$suffix);
    }

    public static function parse(string $value): self
    {
        $type = self::tryType($value);
        if (null === $type) {
            throw new \InvalidArgumentException(\sprintf('Malformed persistent id "%s".', $value));
        }

        return new self($type, $value);
    }

    public static function isValid(string $value): bool
    {
        return null !== self::tryType($value);
    }

    private static function tryType(string $value): ?EntityType
    {
        $pattern = '/^([A-Z]{3})-(['.self::ALPHABET.']{'.self::SUFFIX_LENGTH.'})$/';
        if (1 !== preg_match($pattern, $value, $matches)) {
            return null;
        }

        return EntityType::fromPrefix($matches[1]);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
