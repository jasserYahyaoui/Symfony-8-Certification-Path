<?php

declare(strict_types=1);

use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
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

final class BehaviorMoney
{
    public function __construct(public int $cents = 0, public string $currency = 'EUR')
    {
    }
}

final class BehaviorOrder
{
    public ?string $note = null;
    public int $qty = 1;
    public ?BehaviorMoney $price = null;
    public ?DateTimeImmutable $at = null;
}

final class BehaviorMoneyNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        return sprintf('%.2f %s', $data->cents / 100, $data->currency);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof BehaviorMoney;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [BehaviorMoney::class => true];
    }
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
    // P1.5: the first normalizer that supports the value wins, in the order given.
    'serializer.first-supporting-normalizer-wins' => static function (): ?string {
        $order = new BehaviorOrder();
        $order->price = new BehaviorMoney(1250);
        $specialFirst = new Serializer([new BehaviorMoneyNormalizer(), new ObjectNormalizer()], [new JsonEncoder()]);
        $objectFirst = new Serializer([new ObjectNormalizer(), new BehaviorMoneyNormalizer(), new DateTimeNormalizer()], [new JsonEncoder()]);
        $order->at = new DateTimeImmutable('2026-01-02T03:04:05+00:00');
        $a = json_decode($specialFirst->serialize($order, 'json'), true)['price'];
        $b = json_decode($objectFirst->serialize($order, 'json'), true)['price'];

        return match (true) {
            '12.50 EUR' !== $a => 'the custom normalizer listed first did not produce "12.50 EUR"',
            ['cents' => 1250, 'currency' => 'EUR'] !== $b => 'ObjectNormalizer listed first did not take the Money object',
            ['timezone', 'offset', 'timestamp', 'microsecond'] !== array_keys(json_decode($objectFirst->serialize($order, 'json'), true)['at'])
                => 'ObjectNormalizer listed first did not expose the date getters',
            default => null,
        };
    },
    // P1.5: type checks on denormalization come from a type extractor; without
    // one, PHP's own property coercion applies.
    'serializer.denormalize-types-need-extractor' => static function (): ?string {
        $bare = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $typed = new Serializer([new ObjectNormalizer(null, null, null, new PropertyInfoExtractor([], [new ReflectionExtractor()]))], [new JsonEncoder()]);
        if (7 !== $bare->deserialize('{"qty":"7"}', BehaviorOrder::class, 'json')->qty) {
            return 'without an extractor "7" was not coerced to 7';
        }
        try {
            $typed->deserialize('{"qty":"7"}', BehaviorOrder::class, 'json');

            return 'with an extractor "7" was accepted for an int';
        } catch (NotNormalizableValueException) {
        }
        try {
            $typed->deserialize('{"qty":"x","note":5}', BehaviorOrder::class, 'json', [DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true]);

            return 'no PartialDenormalizationException with errors collected';
        } catch (PartialDenormalizationException $e) {
            $paths = array_map(static fn ($v) => $v->getPath(), $e->getErrors());

            return ['qty', 'note'] === $paths ? null : 'collected paths '.implode(',', $paths);
        }
    },
    // P1.5: OBJECT_TO_POPULATE fills the given object; absent keys keep their value.
    'serializer.object-to-populate' => static function (): ?string {
        $existing = new BehaviorOrder();
        $existing->note = 'keep';
        $existing->qty = 5;
        $result = (new Serializer([new ObjectNormalizer()], [new JsonEncoder()]))
            ->deserialize('{"qty":7}', BehaviorOrder::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $existing]);

        return $result === $existing && 'keep' === $result->note && 7 === $result->qty
            ? null
            : 'the existing object was not the one populated, or an absent key was reset';
    },
    // P1.5: context options change the output of one call.
    'serializer.context-options' => static function () use ($serializer): ?string {
        $s = $serializer();
        $order = new BehaviorOrder();
        $order->at = new DateTimeImmutable('2026-01-02T03:04:05+00:00');
        $skip = $s->serialize(new BehaviorOrder(), 'json', [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]);
        $dated = json_decode($s->serialize($order, 'json', [DateTimeNormalizer::FORMAT_KEY => 'Y-m-d']), true)['at'];

        return match (true) {
            '{"qty":1}' !== $skip => "skip_null_values gave $skip",
            '2026-01-02' !== $dated => "datetime_format gave $dated",
            default => null,
        };
    },
];
