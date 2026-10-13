<?php

declare(strict_types=1);

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

enum BehaviorSuit: string
{
    case Hearts = 'H';
}

final class BehaviorPerson
{
    public string $name = 'Ada';
}

$serializer = static fn (): Serializer => new Serializer(
    [new DateTimeNormalizer(), new BackedEnumNormalizer(), new ObjectNormalizer()],
    [new JsonEncoder()],
);

return [
    // F1: the normalized form is an array only for an object.
    'serializer.normalized-form-not-always-array' => static function () use ($serializer): ?string {
        $s = $serializer();
        $cases = [
            'object' => [$s->normalize(new BehaviorPerson()), 'array', '{"name":"Ada"}', new BehaviorPerson()],
            'datetime' => [$s->normalize(new DateTimeImmutable('2026-01-02T03:04:05+00:00')), 'string', '"2026-01-02T03:04:05+00:00"', new DateTimeImmutable('2026-01-02T03:04:05+00:00')],
            'enum' => [$s->normalize(BehaviorSuit::Hearts), 'string', '"H"', BehaviorSuit::Hearts],
            'int' => [$s->normalize(42), 'int', '42', 42],
        ];
        foreach ($cases as $label => [$normalized, $type, $json, $value]) {
            if (get_debug_type($normalized) !== $type) {
                return "$label normalized to ".get_debug_type($normalized).", expected $type";
            }
            if ($s->serialize($value, 'json') !== $json) {
                return "$label serialized to ".$s->serialize($value, 'json').", expected $json";
            }
        }

        return null;
    },
    // F1, reverse direction: the decoder hands the denormalizer what the format holds.
    'serializer.decode-follows-format' => static function () use ($serializer): ?string {
        $s = $serializer();
        $decoded = (new JsonEncoder())->decode('{"name":"Ada"}', 'json');
        if (!is_array($decoded)) {
            return 'a JSON object did not decode to an array';
        }
        if ('string' !== get_debug_type((new JsonEncoder())->decode('"H"', 'json'))) {
            return 'a JSON string did not decode to a string';
        }
        if (BehaviorSuit::Hearts !== $s->deserialize('"H"', BehaviorSuit::class, 'json')) {
            return '"H" did not deserialize to BehaviorSuit::Hearts';
        }
        if (!$s->deserialize('{"name":"Ada"}', BehaviorPerson::class, 'json') instanceof BehaviorPerson) {
            return 'the JSON object did not deserialize to the class';
        }

        return null;
    },
];
