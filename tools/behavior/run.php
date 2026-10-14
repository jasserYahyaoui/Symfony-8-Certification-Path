<?php

declare(strict_types=1);

/*
 * Executes the claims the course teaches, against the exam versions.
 *
 * Each file under checks/ returns a map of fact id => callable. A callable
 * returns null when the behaviour matches what the canonical content now
 * teaches, or a message describing the mismatch. The fact ids are the keys of
 * docs/facts/central-facts.yml, which links each fact to the content that
 * states it; tests/Content/CentralFactsTest.php checks the other direction.
 *
 * Usage: php tools/behavior/run.php [--list]
 */

require __DIR__.'/vendor/autoload.php';

$checks = [];
// checks/ holds the central facts (docs/facts/central-facts.yml); questions/
// holds questions re-executed choice by choice, keyed by question id.
foreach ([...glob(__DIR__.'/checks/*.php'), ...glob(__DIR__.'/questions/*.php')] as $file) {
    foreach (require $file as $id => $check) {
        if (isset($checks[$id])) {
            fwrite(\STDERR, "Duplicate fact id $id in $file\n");
            exit(2);
        }
        $checks[$id] = $check;
    }
}
ksort($checks);

if (in_array('--list', $argv, true)) {
    echo implode("\n", array_keys($checks)), "\n";
    exit(0);
}

printf("PHP %s, symfony/http-kernel %s\n", PHP_VERSION, Composer\InstalledVersions::getPrettyVersion('symfony/http-kernel'));

// The `correct` flags of every question a check in questions/ names. The
// holdout file is never opened: no check may name a holdout question.
$flags = [];
foreach (glob(dirname(__DIR__, 2).'/content/questions/*.yml') as $file) {
    if (str_contains(basename($file), 'holdout')) {
        continue;
    }
    foreach (Symfony\Component\Yaml\Yaml::parseFile($file)['questions'] ?? [] as $question) {
        if (isset($checks[$question['id']])) {
            $flags[$question['id']] = array_map(static fn (array $c): bool => (bool) $c['correct'], $question['choices']);
        }
    }
}

$failed = 0;
foreach ($checks as $id => $check) {
    try {
        $error = $check();
        if (str_starts_with($id, 'QST-')) {
            // A question check returns, choice by choice, whether its statement
            // holds; it must equal what the content marks as correct.
            $error = match (true) {
                !isset($flags[$id]) => 'no such question outside the holdout',
                $error === $flags[$id] => null,
                default => 'the content marks '.json_encode($flags[$id]).', Symfony 8.0 gives '.json_encode($error),
            };
        }
    } catch (Throwable $e) {
        $error = $e::class.': '.$e->getMessage();
    }
    if (null === $error) {
        echo "PASS $id\n";
    } else {
        echo "FAIL $id — $error\n";
        ++$failed;
    }
}

printf("%d fact(s), %d failed\n", count($checks), $failed);
exit($failed > 0 ? 1 : 0);
