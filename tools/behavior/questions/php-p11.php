<?php

declare(strict_types=1);

/*
 * Remediation P1.1 (2026-10-16): the multiple-answer PHP comparison question,
 * one boolean per choice in content order, compared by run.php with the
 * `correct` flags of the YAML.
 */

return [
    // '1' == '01' | '10' == '1e1' | 0 == 'a' | in_array('abc', [0])
    'QST-f2m90kz2ya28' => static function (): array {
        return ['1' == '01', '10' == '1e1', 0 == 'a', in_array('abc', [0])];
    },
];
