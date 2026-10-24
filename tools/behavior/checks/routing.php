<?php

declare(strict_types=1);

use Symfony\Component\Routing\Route;

return [
    // Pitfall audit, 2026-10-24: a Unicode property in a requirement needs the
    // "utf8" option; without it, compiling the route throws a LogicException.
    // The course said « les properties Unicode fonctionnent », unqualified.
    'routing.unicode-requirement-needs-utf8' => static function (): ?string {
        try {
            (new Route('/x/{v}', [], ['v' => '\p{Lu}+']))->compile();

            return 'a \p{Lu} requirement compiled without the "utf8" option';
        } catch (LogicException $e) {
            if (!str_contains($e->getMessage(), 'without setting the "utf8" option')) {
                return 'unexpected LogicException: '.$e->getMessage();
            }
        }
        (new Route('/x/{v}', [], ['v' => '\p{Lu}+'], ['utf8' => true]))->compile();

        return null;
    },
];
