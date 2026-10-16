<?php

declare(strict_types=1);

/*
 * Remediation P2.7 (Console): signals, the alarm event, interaction and the
 * two testers, executed on Console 8.0. A signal or an alarm ends the PHP
 * process, so those checks run a small script in a child process and read its
 * stderr trace and exit status. They need pcntl and posix, and fail rather
 * than skip without them: a skipped check would read as a passed one.
 * Each check is proved by a mutation of the vendor line carrying it
 * (tools/audit/deep80/mutate.py).
 */

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleAlarmEvent;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

/*
 * Runs $body as a console command in a child PHP process. $body may use $app,
 * $dispatcher (null without one) and trace(); the result is the trace lines
 * and the exit status, or "signal N" when the process was killed by one.
 */
$child = static function (string $setup, string $command, bool $withDispatcher = true): array {
    if (!\function_exists('pcntl_signal') || !\function_exists('posix_kill')) {
        throw new LogicException('pcntl and posix are required to execute signal handling');
    }
    $autoload = var_export(dirname(__DIR__).'/vendor/autoload.php', true);
    $dispatcher = $withDispatcher ? 'new Symfony\Component\EventDispatcher\EventDispatcher()' : 'null';
    $script = <<<PHP
        <?php
        require $autoload;
        use Symfony\\Component\\Console\\Application;
        use Symfony\\Component\\Console\\Command\\Command;
        use Symfony\\Component\\Console\\Command\\SignalableCommandInterface;
        use Symfony\\Component\\Console\\ConsoleEvents;
        use Symfony\\Component\\Console\\Event\\ConsoleAlarmEvent;
        use Symfony\\Component\\Console\\Event\\ConsoleSignalEvent;
        use Symfony\\Component\\Console\\Event\\ConsoleTerminateEvent;
        use Symfony\\Component\\Console\\Input\\ArrayInput;
        use Symfony\\Component\\Console\\Input\\InputInterface;
        use Symfony\\Component\\Console\\Output\\NullOutput;
        use Symfony\\Component\\Console\\Output\\OutputInterface;
        function trace(string \$line): void { fwrite(STDERR, \$line."\\n"); }
        \$dispatcher = $dispatcher;
        \$app = new Application();
        \$app->setAutoExit(false);
        if (\$dispatcher) {
            \$dispatcher->addListener(ConsoleEvents::SIGNAL, fn (ConsoleSignalEvent \$e) => trace('signal '.\$e->getHandlingSignal()));
            \$dispatcher->addListener(ConsoleEvents::TERMINATE, fn (ConsoleTerminateEvent \$e) => trace('terminate '.\$e->getExitCode().' '.var_export(\$e->getInterruptingSignal(), true)));
            \$app->setDispatcher(\$dispatcher);
        }
        $setup
        \$app->addCommand($command);
        trace('run returned '.\$app->run(new ArrayInput(['command' => 't']), new NullOutput()));
        PHP;
    $file = tempnam(sys_get_temp_dir(), 'console-check-');
    file_put_contents($file, $script);
    try {
        $process = proc_open([\PHP_BINARY, $file], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        do {
            $status = proc_get_status($process);
            usleep(10000);
        } while ($status['running']);
        proc_close($process);
    } finally {
        unlink($file);
    }
    $lines = array_values(array_filter(explode("\n", $stderr), static fn (string $l): bool => '' !== $l));

    return [$lines, $status['signaled'] ? 'signal '.$status['termsig'] : $status['exitcode']];
};

// A command that sends itself SIGINT, then returns 7.
$selfSignal = static fn (string $interfaces = '', string $methods = ''): string => <<<PHP
    new class extends Command $interfaces {
        public function __construct() { parent::__construct('t'); }
        $methods
        protected function execute(InputInterface \$i, OutputInterface \$o): int {
            posix_kill(getmypid(), \\SIGINT);
            pcntl_signal_dispatch();
            trace('execution continued');
            return 7;
        }
    }
    PHP;

return [
    // With a dispatcher, Symfony handles SIGINT: console.signal, then console.terminate, then exit with code 0 by default.
    'console.signal-exits-zero-by-default' => static function () use ($child, $selfSignal): ?string {
        $sig = \defined('SIGINT') ? \SIGINT : 2;
        $got = [
            $child('', $selfSignal()),
            $child('$dispatcher->addListener(ConsoleEvents::SIGNAL, fn (ConsoleSignalEvent $e) => $e->abortExit());', $selfSignal()),
            $child('$dispatcher->addListener(ConsoleEvents::SIGNAL, fn (ConsoleSignalEvent $e) => $e->setExitCode(3));', $selfSignal()),
            $child('', $selfSignal(), false),
        ];
        $want = [
            [["signal $sig", "terminate 0 $sig"], 0],
            [["signal $sig", 'execution continued', 'terminate 7 NULL', 'run returned 7'], 0],
            [["signal $sig", "terminate 3 $sig"], 3],
            [[], "signal $sig"],
        ];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // A SignalableCommandInterface command: handleSignal() gets the exit code so far; an int exits with it, false continues.
    'console.signalable-command-return' => static function () use ($child, $selfSignal): ?string {
        $sig = \defined('SIGINT') ? \SIGINT : 2;
        $handler = static fn (string $return): string => "public function getSubscribedSignals(): array { return [\\SIGINT]; }
            public function handleSignal(int \$signal, int|false \$previousExitCode = 0): int|false { trace('handleSignal '.var_export(\$previousExitCode, true)); return $return; }";
        $got = [
            $child('', $selfSignal('implements SignalableCommandInterface', $handler('5'))),
            $child('', $selfSignal('implements SignalableCommandInterface', $handler('false'))),
            $child('', $selfSignal('implements SignalableCommandInterface', $handler('5')), false),
            $child('', $selfSignal('implements SignalableCommandInterface', $handler('false')), false),
        ];
        $want = [
            [["signal $sig", 'handleSignal 0', "terminate 5 $sig"], 5],
            [["signal $sig", 'handleSignal 0', 'execution continued', 'terminate 7 NULL', 'run returned 7'], 0],
            [['handleSignal 0'], 5],
            [['handleSignal 0', 'execution continued', 'run returned 7'], 0],
        ];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // setAlarmInterval(): each SIGALRM dispatches console.signal then ConsoleAlarmEvent, under its class name; without
    // abortExit() the first alarm ends the command with code 0. ConsoleEvents has no constant for it.
    'console.alarm-event' => static function () use ($child): ?string {
        $alrm = \defined('SIGALRM') ? \SIGALRM : 14;
        $command = 'new class extends Command {
            public function __construct() { parent::__construct(\'t\'); }
            protected function execute(InputInterface $i, OutputInterface $o): int {
                $this->getApplication()->setAlarmInterval(1);
                for ($n = 0; $n < 15; ++$n) { usleep(100000); }
                trace(\'loop finished\');
                return 4;
            }
        }';
        $listen = static fn (string $body): string => "\$dispatcher->addListener(ConsoleAlarmEvent::class, function (ConsoleAlarmEvent \$e) { trace('alarm'); $body });";
        $got = [
            $child($listen(''), $command),
            $child($listen('$e->abortExit();'), $command),
        ];
        $want = [
            [["signal $alrm", 'alarm', "terminate 0 $alrm"], 0],
            [["signal $alrm", 'alarm', 'loop finished', 'terminate 4 NULL', 'run returned 4'], 0],
        ];
        $constants = (new ReflectionClass(ConsoleEvents::class))->getConstants();
        unset($constants['ALIASES']);

        return match (true) {
            $want !== $got => 'got '.json_encode($got),
            ['COMMAND', 'SIGNAL', 'TERMINATE', 'ERROR'] !== array_keys($constants) => 'ConsoleEvents constants '.json_encode(array_keys($constants)),
            isset(ConsoleEvents::ALIASES[ConsoleAlarmEvent::class]) => 'ConsoleEvents::ALIASES now names ConsoleAlarmEvent',
            default => null,
        };
    },

    // CommandTester runs the command alone, without console events; ApplicationTester goes through the Application.
    'console.command-tester-dispatches-nothing' => static function (): ?string {
        $events = [];
        $dispatcher = new EventDispatcher();
        foreach ([ConsoleEvents::COMMAND, ConsoleEvents::TERMINATE] as $name) {
            $dispatcher->addListener($name, static function () use (&$events, $name): void { $events[] = $name; });
        }
        $app = new Application();
        $app->setAutoExit(false);
        $app->setDispatcher($dispatcher);
        $app->addCommand(new class extends Command {
            public function __construct() { parent::__construct('t'); }
            protected function execute(InputInterface $i, OutputInterface $o): int { return 0; }
        });

        (new CommandTester($app->find('t')))->execute([]);
        $commandTester = $events;
        $events = [];
        (new ApplicationTester($app))->run(['command' => 't']);
        $got = [$commandTester, $events];
        $want = [[], ['console.command', 'console.terminate']];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // initialize(), interact() only when interactive, then validation, then execute(): interact() can fill a missing
    // required argument; non-interactive, the same input fails validation.
    'console.interact-before-validation' => static function (): ?string {
        $log = [];
        $command = new class($log) extends Command {
            public function __construct(private array &$log) { parent::__construct('greet'); }
            protected function configure(): void { $this->addArgument('name', InputArgument::REQUIRED); }
            protected function initialize(InputInterface $i, OutputInterface $o): void { $this->log[] = 'initialize'; }
            protected function interact(InputInterface $i, OutputInterface $o): void
            {
                $this->log[] = 'interact';
                if (!$i->getArgument('name')) {
                    $i->setArgument('name', $this->getHelper('question')->ask($i, $o, new Question('Name? ', 'Anon')));
                }
            }
            protected function execute(InputInterface $i, OutputInterface $o): int
            {
                $this->log[] = 'execute '.$i->getArgument('name');

                return 0;
            }
        };
        $app = new Application();
        $app->addCommand($command);
        $tester = new CommandTester($app->find('greet'));
        $tester->setInputs(['Bob']);
        $tester->execute([]);
        $interactive = $log;
        $log = [];
        try {
            $tester->execute([], ['interactive' => false]);
            $refused = null;
        } catch (RuntimeException $e) {
            $refused = $e->getMessage();
        }

        return match (true) {
            ['initialize', 'interact', 'execute Bob'] !== $interactive => 'interactive '.json_encode($interactive),
            'Not enough arguments (missing: "name").' !== $refused || ['initialize'] !== $log => 'non-interactive '.json_encode([$refused, $log]),
            default => null,
        };
    },

    // capture_stderr_separately: getErrorStyle() and the question prompt go to getErrorOutput(); without it, to getDisplay().
    'console.tester-stderr-separately' => static function (): ?string {
        $command = new class extends Command {
            public function __construct() { parent::__construct('t'); }
            protected function execute(InputInterface $i, OutputInterface $o): int
            {
                (new SymfonyStyle($i, $o))->getErrorStyle()->writeln('diagnostic');
                $o->writeln('data');
                $o->writeln('answer '.$this->getHelper('question')->ask($i, $o, new Question('Name? ')));

                return 0;
            }
        };
        $app = new Application();
        $app->addCommand($command);
        $tester = new CommandTester($app->find('t'));
        $tester->setInputs(['Bob']);
        $tester->execute([], ['capture_stderr_separately' => true]);
        $separate = [$tester->getDisplay(), $tester->getErrorOutput()];
        $tester->execute([]);
        $got = [$separate, $tester->getDisplay()];
        $want = [["data\nanswer Bob\n", "diagnostic\nName? "], "diagnostic\ndata\nName? answer Bob\n"];

        return $want === $got ? null : 'got '.json_encode($got);
    },
];
