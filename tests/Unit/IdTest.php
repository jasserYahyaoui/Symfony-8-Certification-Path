<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Support\EntityType;
use CertPath\Support\Id;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Id::class)]
#[CoversClass(EntityType::class)]
final class IdTest extends TestCase
{
    public function testMintedIdRoundTrips(): void
    {
        $id = Id::mint(EntityType::OfficialItem);

        self::assertTrue(Id::isValid($id->value));
        self::assertSame(EntityType::OfficialItem, Id::parse($id->value)->type);
        self::assertStringStartsWith('OIT-', $id->value);
    }

    /**
     * Master Plan §11: ids must not depend on file names or slugs, so minting
     * twice for identical input must not collide onto one identifier.
     */
    public function testMintingIsNotDerivedFromInput(): void
    {
        $first = Id::mint(EntityType::Question);
        $second = Id::mint(EntityType::Question);

        self::assertNotSame($first->value, $second->value);
    }

    public function testEveryEntityTypeHasAUniquePrefix(): void
    {
        $prefixes = array_map(static fn (EntityType $t): string => $t->prefix(), EntityType::cases());

        self::assertSame(\count($prefixes), \count(array_unique($prefixes)));
    }

    public function testMalformedIdsAreRejected(): void
    {
        foreach (['', 'OIT', 'OIT-', 'oit-3k9m2xq7bv4t', 'ZZZ-3k9m2xq7bv4t', 'OIT-3k9m2xq7bv4', 'OIT-3k9m2xq7bv4ti'] as $candidate) {
            self::assertFalse(Id::isValid($candidate), $candidate.' should be rejected');
        }
    }

    public function testAmbiguousCharactersAreExcluded(): void
    {
        for ($i = 0; $i < 200; ++$i) {
            $suffix = substr(Id::mint(EntityType::Course)->value, 4);
            self::assertSame(0, preg_match('/[ilou]/', $suffix), 'suffix must avoid ambiguous letters');
        }
    }

    public function testParsingAMalformedIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Id::parse('not-an-id');
    }
}
