<?php

declare(strict_types=1);

/*
 * Remediation P3.1 (2026-10-17): the multiple-answer LEARNING question on
 * traits rewritten because it repeated a VALIDATION question
 * (tools/audit/semantic_repetition.py). One boolean per choice, in content
 * order, compared by run.php with the `correct` flags of the YAML.
 */

namespace CertBehavior\P31Traits;

trait Hello
{
    public function hello(): string
    {
        return 'A';
    }
}

trait Greeting
{
    public function hello(): string
    {
        return 'B';
    }
}

final class Both
{
    use Hello, Greeting {
        Hello::hello insteadof Greeting;
        Greeting::hello as helloB;
    }
}

return [
    // use A, B { A::hello insteadof B; B::hello as helloB; }: hello() is A | helloB() is B | hello() is B | does not compile
    'QST-gpz2ww98k670' => static function (): array {
        $o = new Both();

        // The fourth choice says the class cannot compile; it was just instantiated.
        return ['A' === $o->hello(), 'B' === $o->helloB(), 'B' === $o->hello(), !class_exists(Both::class)];
    },
];
