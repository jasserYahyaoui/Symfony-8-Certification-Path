<?php

declare(strict_types=1);

/*
 * Remediation P2.7 (Runtime): a minimal entry point, executed, and its cycle
 * traced. The Runtime includes the entry script and ends the process, so each
 * check writes a front controller to a temporary directory and runs it in a
 * fresh PHP process against tools/behavior/vendor/autoload_runtime.php, which
 * the symfony/runtime Composer plugin generates. The runtime is GenericRuntime
 * (APP_RUNTIME), because SymfonyRuntime needs a .env file. Each check is
 * proved by a mutation of the vendor line carrying it
 * (tools/audit/deep80/mutate.py).
 */

$entry = static function (string $script, array $support = [], string $runtimeClass = 'Symfony\Component\Runtime\GenericRuntime'): array {
    $runtime = dirname(__DIR__).'/vendor/autoload_runtime.php';
    if (!is_file($runtime)) {
        throw new LogicException('vendor/autoload_runtime.php is missing: the symfony/runtime Composer plugin did not run');
    }
    $dir = sys_get_temp_dir().'/cert-runtime-'.bin2hex(random_bytes(6));
    mkdir($dir.'/public', 0777, true);
    file_put_contents($dir.'/trace.log', '');
    foreach ($support as $name => $code) {
        file_put_contents($dir.'/'.$name, $code);
    }
    $script = strtr($script, ['%runtime%' => var_export($runtime, true), '%trace%' => var_export($dir.'/trace.log', true), '%dir%' => var_export($dir, true)]);
    file_put_contents($dir.'/public/index.php', $script);
    $env = ['APP_RUNTIME' => $runtimeClass, 'PATH' => getenv('PATH') ?: '/usr/bin:/bin'];
    $process = proc_open([\PHP_BINARY, $dir.'/public/index.php'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $dir, $env);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    $code = proc_close($process);
    $trace = array_values(array_filter(explode("\n", (string) file_get_contents($dir.'/trace.log'))));
    array_map('unlink', glob($dir.'/public/*') ?: []);
    array_map('unlink', glob($dir.'/*.*') ?: []);
    rmdir($dir.'/public');
    rmdir($dir);

    return [$trace, $code, trim($stdout.$stderr)];
};

$trace = 'function trace(string $line): void { file_put_contents(%trace%, $line."\n", FILE_APPEND); }';

return [
    // The entry script returns a closure; the runtime includes it again, so what precedes the require_once runs twice;
    // order: script returns, runtime constructed, closure called, application run.
    'runtime.entry-script-runs-twice' => static function () use ($entry, $trace): ?string {
        $loggingRuntime = '<?php
            final class LoggingRuntime extends Symfony\Component\Runtime\GenericRuntime
            {
                public function __construct(array $options = []) { trace("runtime constructed"); parent::__construct($options); }
            }';
        [$lines, $code] = $entry(<<<PHP
            <?php
            if (!function_exists('trace')) { $trace }
            trace('above the require_once');
            require_once %runtime%;
            trace('below the require_once');
            require_once %dir%.'/LoggingRuntime.php';
            return static function (array \$context): Closure {
                trace('closure called');
                return static function (): int { trace('application run'); return 0; };
            };
            PHP, ['LoggingRuntime.php' => $loggingRuntime], 'LoggingRuntime');
        $want = ['above the require_once', 'above the require_once', 'below the require_once', 'runtime constructed', 'closure called', 'application run'];

        return [$want, 0] === [$lines, $code] ? null : 'got '.json_encode([$lines, $code]);
    },

    // What the script returns is checked: not a callable object, or a closure returning no object, is a TypeError.
    'runtime.return-value-checked' => static function () use ($entry): ?string {
        [, $integer, $integerOut] = $entry("<?php\nrequire_once %runtime%;\nreturn 42;\n");
        [, $string, $stringOut] = $entry("<?php\nrequire_once %runtime%;\nreturn static fn (): string => 'hello';\n");

        return match (true) {
            0 === $integer || !str_contains($integerOut, 'TypeError') || !str_contains($integerOut, 'callable object expected') => 'returning 42 gave '.$integer.' '.$integerOut,
            0 === $string || !str_contains($stringOut, 'TypeError') => 'a closure returning a string gave '.$string.' '.$stringOut,
            default => null,
        };
    },

    // vendor/autoload.php loaded first: autoload_runtime.php returns at once, the script runs to its end, nothing else.
    'runtime.autoload-first-disables-it' => static function () use ($entry, $trace): ?string {
        [$lines, $code] = $entry(<<<PHP
            <?php
            $trace
            require_once dirname(%runtime%).'/autoload.php';
            require_once %runtime%;
            trace('end of script');
            return static function (): Closure { trace('closure called'); return static fn (): int => 7; };
            PHP);

        return [['end of script'], 0] === [$lines, $code] ? null : 'got '.json_encode([$lines, $code]);
    },

    // Arguments are resolved by type and name: "array $context" works, "array $ctx" is refused.
    'runtime.argument-type-and-name' => static function () use ($entry, $trace): ?string {
        [$lines, $code] = $entry(<<<PHP
            <?php
            if (!function_exists('trace')) { $trace }
            require_once %runtime%;
            return static function (array \$context): Closure {
                trace('APP_RUNTIME in context: '.(isset(\$context['APP_RUNTIME']) ? 'yes' : 'no'));
                return static fn (): int => 0;
            };
            PHP);
        [, $refused, $output] = $entry("<?php\nrequire_once %runtime%;\nreturn static fn (array \$ctx): Closure => static fn (): int => 0;\n");

        return match (true) {
            [['APP_RUNTIME in context: yes'], 0] !== [$lines, $code] => 'array $context gave '.json_encode([$lines, $code]),
            0 === $refused || !str_contains($output, 'Cannot resolve argument "array $ctx"') => 'array $ctx gave '.$refused.' '.$output,
            default => null,
        };
    },
];
