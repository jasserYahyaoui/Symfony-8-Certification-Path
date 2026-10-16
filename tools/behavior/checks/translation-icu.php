<?php

declare(strict_types=1);

/*
 * Remediation P2.7 (Translation): ICU plural and select, nested, and several
 * catalogues for one key, executed on Translation 8.0 with the intl extension.
 * Plural categories come from ICU's data for each locale; the checks pin the
 * Symfony side — which locale formats the message, which catalogue wins, how
 * parameter keys are read — and each is proved by a mutation of the vendor
 * line carrying it (tools/audit/deep80/mutate.py).
 */

use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

$translator = static function (string $locale, array $resources): Translator {
    $translator = new Translator($locale);
    $translator->addLoader('array', new ArrayLoader());
    foreach ($resources as [$messages, $resourceLocale, $domain]) {
        $translator->addResource('array', $messages, $resourceLocale, $domain);
    }

    return $translator;
};

return [
    // Plural categories are the locale's: in French 0 and 1.5 are "one"; in English 0 is "other". =0 wins over a category.
    'translation.icu-plural-categories' => static function () use ($translator): ?string {
        $t = $translator('fr', [
            [['files' => '{n, plural, one {# fichier} other {# fichiers}}'], 'fr', 'messages+intl-icu'],
            [['files' => '{n, plural, one {# file} other {# files}}'], 'en', 'messages+intl-icu'],
            [['exact' => '{n, plural, =0 {aucun fichier} one {# fichier} other {# fichiers}}'], 'fr', 'messages+intl-icu'],
        ]);
        $got = [
            $t->trans('files', ['n' => 0]), $t->trans('files', ['n' => 1.5]), $t->trans('files', ['n' => 2]),
            $t->trans('files', ['n' => 0], null, 'en'), $t->trans('exact', ['n' => 0]), $t->trans('exact', ['n' => 1]),
        ];
        $want = ['0 fichier', '1,5 fichier', '2 fichiers', '0 files', 'aucun fichier', '1 fichier'];

        return $want === $got ? null : 'got '.json_encode($got, \JSON_UNESCAPED_UNICODE);
    },

    // select and plural nest; # is the number of the innermost plural; selectordinal picks English ordinals.
    'translation.icu-nested-select-plural' => static function () use ($translator): ?string {
        $t = $translator('en', [[[
            'inbox' => '{gender, select, female {{n, plural, =0 {She has no message} one {She has # message} other {She has # messages}}} other {{n, plural, one {# message} other {# messages}}}}',
            'inner' => '{a, plural, other {{b, plural, other {# inside}}}}',
            'rank' => '{n, selectordinal, one {#st} two {#nd} few {#rd} other {#th}}',
        ], 'en', 'messages+intl-icu']]);
        $got = [
            $t->trans('inbox', ['gender' => 'female', 'n' => 0]),
            $t->trans('inbox', ['gender' => 'female', 'n' => 1]),
            $t->trans('inbox', ['gender' => 'male', 'n' => 3]),
            $t->trans('inner', ['a' => 7, 'b' => 3]),
            implode(' ', array_map(static fn (int $n): string => $t->trans('rank', ['n' => $n]), [1, 2, 3, 4, 11, 21, 22])),
        ];
        $want = ['She has no message', 'She has 1 message', '3 messages', '3 inside', '1st 2nd 3rd 4th 11th 21st 22nd'];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // A select or plural without "other" is refused when the message is formatted, even for a matching value.
    'translation.icu-other-required' => static function () use ($translator): ?string {
        $t = $translator('en', [[['g' => '{g, select, female {She} male {He}}'], 'en', 'messages+intl-icu']]);
        $got = [];
        foreach (['female', 'x'] as $value) {
            try {
                $got[] = $t->trans('g', ['g' => $value]);
            } catch (InvalidArgumentException $e) {
                $got[] = str_contains($e->getMessage(), 'U_DEFAULT_KEYWORD_MISSING') ? 'U_DEFAULT_KEYWORD_MISSING' : $e->getMessage();
            }
        }
        $want = ['U_DEFAULT_KEYWORD_MISSING', 'U_DEFAULT_KEYWORD_MISSING'];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // An ICU message reads {name}; the parameter key may be name or %name%. %name% in an ICU message is plain text,
    // and a missing parameter leaves the placeholder.
    'translation.icu-parameter-keys' => static function () use ($translator): ?string {
        $t = $translator('en', [[['hi' => 'Hello {name}!', 'pct' => 'Hello %name%!'], 'en', 'messages+intl-icu']]);
        $got = [
            $t->trans('hi', ['name' => 'Ann']), $t->trans('hi', ['%name%' => 'Ann']),
            $t->trans('pct', ['%name%' => 'Ann']), $t->trans('hi'),
        ];
        $want = ['Hello Ann!', 'Hello Ann!', 'Hello %name%!', 'Hello {name}!'];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // Several catalogues: the +intl-icu domain wins over the plain one for a key in both; a later resource for the same
    // domain and locale wins key by key; a message found in a fallback locale is formatted with that locale's rules.
    'translation.several-catalogues' => static function () use ($translator): ?string {
        $t = $translator('fr', [
            [['k' => 'plain %x%'], 'en', 'messages'],
            [['k' => 'icu {x}'], 'en', 'messages+intl-icu'],
            [['a' => 'first', 'b' => 'only first'], 'en', 'admin'],
            [['a' => 'second'], 'en', 'admin'],
            [['files' => '{n, plural, one {# file} other {# files}}'], 'en', 'messages+intl-icu'],
        ]);
        $t->setFallbackLocales(['en']);
        $got = [
            $t->trans('k', ['x' => 'v'], null, 'en'),
            $t->trans('a', [], 'admin', 'en'), $t->trans('b', [], 'admin', 'en'),
            $t->trans('files', ['n' => 0]),
        ];
        $want = ['icu v', 'second', 'only first', '0 files'];

        return $want === $got ? null : 'got '.json_encode($got);
    },
];
