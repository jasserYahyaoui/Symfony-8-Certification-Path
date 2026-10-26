<?php

declare(strict_types=1);

/*
 * Third refinement pass, 2026-10-26: Request::getLanguages() keeps a language,
 * a four-letter script and a two-letter region, and drops whatever follows —
 * the course said a value outside that mould came back unchanged. Only a value
 * whose beginning does not fit (x-klingon, *) does.
 */

use Symfony\Component\HttpFoundation\Request;

return [
    'http-foundation.language-tail-dropped' => static function (): ?string {
        $expected = [
            'de-DE-1996' => 'de_DE',
            'es-419' => 'es',
            'zh-Hant-TW-x-private' => 'zh_Hant_TW',
            'i-cherokee' => 'cherokee',
            'x-klingon' => 'x-klingon',
            '*' => '*',
        ];
        foreach ($expected as $header => $language) {
            $request = Request::create('/');
            $request->headers->set('Accept-Language', $header);
            if ([$language] !== $request->getLanguages()) {
                return "$header gave ".json_encode($request->getLanguages());
            }
        }

        return null;
    },
];
