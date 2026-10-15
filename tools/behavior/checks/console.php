<?php

declare(strict_types=1);

/*
 * Deep audit (2026-10-15): the Console note of docs/whats-new/04-console.md,
 * executed on Console 8.0. A closure given to Command::setCode() is accepted
 * as is; the 8.0 constraint applies when the command runs.
 */

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/** Runs a one-command closure; returns the exit code, or « ShortClass: message » of what it threw. */
$run = static function (callable $code): int|string {
    try {
        $command = (new Command('probe'))->setCode($code);

        return $command->run(new ArrayInput([]), new BufferedOutput());
    } catch (Throwable $e) {
        // Class and message: three guards raise a TypeError for a non-int result (Symfony's
        // is_int() check, then the `: int` return types of __invoke() and run()); only the
        // message tells Symfony's apart.
        return (new ReflectionClass($e))->getShortName().': '.$e->getMessage();
    }
};

return [
    'console.setcode-closure-typed-and-int' => static function () use ($run): ?string {
        $untyped = $run(static function ($input, $output) { return 0; });
        $noInt = $run(static function (InputInterface $input, OutputInterface $output) { });
        $typed = $run(static function (InputInterface $input, OutputInterface $output) { return 0; });

        return match (true) {
            !str_starts_with((string) $untyped, 'LogicException: The parameter "$input" must have a named type') => "untyped parameters gave $untyped",
            !str_contains((string) $noInt, 'TypeError: The command "probe" must return an integer value') => "a closure returning no int gave $noInt",
            0 !== $typed => "a typed closure returning 0 gave $typed",
            default => null,
        };
    },
];
