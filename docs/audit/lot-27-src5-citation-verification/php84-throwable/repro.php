<?php

declare(strict_types=1);

// Controlled reproduction for QST-fhrga35d77wa.
//
// The question's answer key says a userland class becomes throwable by
// extending Exception *or Error*. The PHP manual's Throwable page says such a
// class "must instead extend Exception". This exercises the Error branch and
// the direct-implementation prohibition, so the disagreement is settled by the
// engine rather than by prose.

// 1. A userland class deriving from Error.
class ApplicationThrowable extends Error
{
}

$throwable = new ApplicationThrowable('test');

assert($throwable instanceof Error);
assert($throwable instanceof Throwable);

try {
    throw $throwable;
} catch (Throwable $caught) {
    assert($caught === $throwable);
}

// 2. The same for Exception, so the claim's other half is exercised too.
class ApplicationException extends Exception
{
}

$e = new ApplicationException('test');
assert($e instanceof Exception);
assert($e instanceof Throwable);

try {
    throw $e;
} catch (Throwable $caught) {
    assert($caught === $e);
}

// 3. catch (Exception) must NOT catch the Error-derived one — the sibling
//    branches the question's distractors depend on.
$caughtByException = false;
try {
    throw new ApplicationThrowable('sibling');
} catch (Exception) {
    $caughtByException = true;
} catch (Error) {
    // expected
}
assert($caughtByException === false);

// 4. Direct userland implementation of Throwable must be rejected. This is a
//    compile-time fatal, not a catchable exception, so it is exercised in a
//    child process and the engine's own message is captured verbatim.
$probe = tempnam(sys_get_temp_dir(), 'thr').'.php';
file_put_contents($probe, "<?php class DirectThrowable implements Throwable {}\n");
exec(PHP_BINARY.' '.escapeshellarg($probe).' 2>&1', $out, $code);
unlink($probe);
$rejection = trim(implode(' ', $out));
assert($code !== 0);
assert(str_contains($rejection, 'cannot implement interface Throwable'));

echo 'PHP                            : ', PHP_VERSION, PHP_EOL;
echo 'zend_assertions                : ', ini_get('zend.assertions'), PHP_EOL;
echo 'ApplicationThrowable extends   : ', get_parent_class('ApplicationThrowable'), PHP_EOL;
echo 'instanceof Throwable           : ', var_export($throwable instanceof Throwable, true), PHP_EOL;
echo 'thrown and caught as Throwable : yes', PHP_EOL;
echo 'caught by catch (Exception)    : ', var_export($caughtByException, true), PHP_EOL;
echo 'direct implements Throwable    : rejected', PHP_EOL;
echo 'rejection                      : ', $rejection, PHP_EOL;
echo 'ALL ASSERTIONS PASSED', PHP_EOL;
