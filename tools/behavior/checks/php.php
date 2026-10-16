<?php

declare(strict_types=1);

/*
 * Remediation P1.1 (F8, part): the PHP standard API a candidate meets in an
 * exam question, executed on PHP 8.4. These are engine behaviours: no vendor
 * line carries them, so tools/audit/deep80/mutate.py cannot prove them by
 * mutation; each check instead pins several values that a wrong rule would get
 * wrong.
 */

return [
    // array_merge renumbers integer keys and lets a later string key win; + keeps the left-hand key.
    'php.array-merge-vs-union' => static function (): ?string {
        $got = [
            array_merge(['a'], ['b']),
            ['a'] + ['b'],
            array_merge(['k' => 1], ['k' => 2]),
            ['k' => 1] + ['k' => 2],
            array_merge([5 => 'x'], [9 => 'y']),
        ];
        $want = [['a', 'b'], ['a'], ['k' => 2], ['k' => 1], ['x', 'y']];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // array_filter keeps keys; array_map keeps them for one array only; sort() returns true and works in place.
    'php.array-functions-and-keys' => static function (): ?string {
        $filtered = array_filter([1, 0, 2, null, '0', 'a']);
        $mapped = array_map(static fn (int $v): int => $v * 2, ['k' => 1, 'j' => 2]);
        $zipped = array_map(static fn (string $a, string $b): string => $a.$b, ['k' => 'a'], ['j' => 'b']);
        $list = [3, 1, 2];
        $sorted = sort($list);

        return match (true) {
            [0 => 1, 2 => 2, 5 => 'a'] !== $filtered => 'array_filter gave '.json_encode($filtered),
            array_is_list($filtered) => 'array_filter result is a list',
            ['k' => 2, 'j' => 4] !== $mapped => 'array_map on one array gave '.json_encode($mapped),
            ['ab'] !== $zipped => 'array_map on two arrays gave '.json_encode($zipped),
            true !== $sorted || [1, 2, 3] !== $list => 'sort() did not return true and sort in place',
            default => null,
        };
    },

    // PHP 8 compares a number and a non-numeric string as strings.
    'php.string-number-comparison' => static function (): ?string {
        $got = [
            0 == 'a', '1' == '01', '10' == '1e1', 0 == '', null == false,
            in_array('abc', [0]), in_array('1e1', ['10']), in_array('1e1', ['10'], true),
        ];
        $want = [false, true, true, false, true, false, true, false];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // PHP 8.4: array_find, array_find_key, array_any, array_all; the callback receives the value then the key.
    'php.php84-array-find-any-all' => static function (): ?string {
        $greater = static fn (int $v): bool => $v > 2;
        $got = [
            array_find([1, 2, 3, 4], $greater),
            array_find_key([1, 2, 3, 4], $greater),
            array_find([1], $greater),
            array_any([1, 2], static fn (int $v): bool => $v > 1),
            array_all([1, 2], static fn (int $v): bool => $v > 1),
            array_all([], static fn (): bool => false),
            array_any([], static fn (): bool => true),
            array_find(['a' => 1, 'b' => 2], static fn (int $v, string $k): bool => 'b' === $k),
        ];
        $want = [3, 2, null, true, false, true, false, 2];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // json_decode gives a stdClass by default, null on invalid input unless JSON_THROW_ON_ERROR; json_validate (8.3).
    'php.json-decode-defaults' => static function (): ?string {
        $object = json_decode('{"a":1}');
        $array = json_decode('{"a":1}', true);
        $invalid = json_decode('{');
        try {
            json_decode('{', flags: JSON_THROW_ON_ERROR);
            $threw = false;
        } catch (JsonException) {
            $threw = true;
        }

        return match (true) {
            !$object instanceof stdClass => 'json_decode did not give a stdClass',
            ['a' => 1] !== $array => 'json_decode(..., true) gave '.json_encode($array),
            null !== $invalid => 'invalid JSON did not give null',
            !$threw => 'JSON_THROW_ON_ERROR did not throw',
            false !== json_validate('{') || true !== json_validate('{"a":1}') => 'json_validate disagrees',
            default => null,
        };
    },
];
