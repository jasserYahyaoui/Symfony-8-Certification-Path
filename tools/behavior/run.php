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
foreach (glob(__DIR__.'/checks/*.php') as $file) {
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

$failed = 0;
foreach ($checks as $id => $check) {
    try {
        $error = $check();
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
