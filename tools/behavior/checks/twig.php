<?php

declare(strict_types=1);

/*
 * Pedagogical rewrite of lot 06 (ADR-0012), executed against Twig 3.22.
 *
 * relative_path(): the reference documentation shows
 * relative_path('http://example.com/human.txt') giving ../human.txt; the 8.0
 * UrlHelper returns any argument containing :// unchanged, and only a path
 * becomes relative.
 *
 * loop.last and its three siblings: ForNode sets them for an array or any
 * Countable, an ArrayIterator included; a generator or a non-Countable
 * iterator lacks them, while loop.index and loop.first always exist.
 */

use Symfony\Bridge\Twig\Extension\HttpFoundationExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper;

return [
    'twig.relative-path-leaves-full-url' => static function (): ?string {
        $stack = new RequestStack();
        $stack->push(Request::create('http://example.com/products/hover-board'));
        $twig = new Twig\Environment(new Twig\Loader\ArrayLoader([
            'full' => "{{ relative_path('http://example.com/human.txt') }}",
            'path' => "{{ relative_path('/human.txt') }}",
        ]));
        $twig->addExtension(new HttpFoundationExtension(new UrlHelper($stack)));

        $full = $twig->render('full');
        if ('http://example.com/human.txt' !== $full) {
            return 'relative_path() of a full URL gave '.$full;
        }
        $path = $twig->render('path');

        return '../human.txt' === $path ? null : 'relative_path() of /human.txt gave '.$path;
    },

    'twig.loop-last-needs-countable' => static function (): ?string {
        $twig = new Twig\Environment(new Twig\Loader\ArrayLoader([
            'last' => "{% for v in s %}{{ loop.last ? 'L' : '-' }}{% endfor %}",
            'first' => "{% for v in s %}{{ loop.index }}{{ loop.first ? 'F' : '-' }}{% endfor %}",
        ]), ['strict_variables' => true]);
        $generator = static function (): Generator {
            yield 'a';
            yield 'b';
        };

        if ('-L' !== $out = $twig->render('last', ['s' => new ArrayIterator(['a', 'b'])])) {
            return 'loop.last on an ArrayIterator gave '.$out;
        }
        try {
            $twig->render('last', ['s' => $generator()]);

            return 'loop.last exists on a generator';
        } catch (Twig\Error\RuntimeError $e) {
            if (!str_contains($e->getMessage(), 'Key "last"')) {
                return 'unexpected RuntimeError: '.$e->getMessage();
            }
        }

        return '1F2-' === ($out = $twig->render('first', ['s' => $generator()])) ? null : 'loop.index and loop.first on a generator gave '.$out;
    },
];
