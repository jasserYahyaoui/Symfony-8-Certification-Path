<?php

declare(strict_types=1);

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Runs one request through a bare HttpKernel and returns the kernel events in
 * dispatch order, short names (request, view, ...).
 *
 * @param callable(EventDispatcher): void $configure
 *
 * @return list<string>
 */
$trace = static function (callable $controller, callable $configure, bool $terminate): array {
    $dispatcher = new EventDispatcher();
    $seen = [];
    $names = (new ReflectionClass(KernelEvents::class))->getConstants();
    foreach ($names as $name) {
        if (!is_string($name) || !str_starts_with($name, 'kernel.')) {
            continue;
        }
        // Highest priority: setResponse() stops propagation, so a recorder
        // placed after the acting listener would miss the event.
        $dispatcher->addListener($name, static function () use (&$seen, $name): void {
            $seen[] = substr($name, 7);
        }, 2048);
    }
    $configure($dispatcher);
    $resolver = new class($controller) implements ControllerResolverInterface {
        public function __construct(private $controller)
        {
        }

        public function getController(Request $request): callable|false
        {
            return $this->controller;
        }
    };
    $kernel = new HttpKernel($dispatcher, $resolver, new RequestStack(), new ArgumentResolver());
    $request = Request::create('/');
    $response = $kernel->handle($request);
    if ($terminate) {
        $kernel->terminate($request, $response);
    }

    return $seen;
};

$expect = static fn (array $actual, array $expected): ?string => $actual === $expected
    ? null
    : 'got '.implode(' → ', $actual).', expected '.implode(' → ', $expected);

return [
    // F3: a controller returning a Response never triggers kernel.view.
    'http-kernel.response-controller-skips-view' => static function () use ($trace, $expect): ?string {
        $events = $trace(static fn (): Response => new Response('ok'), static function (): void {}, true);

        return $expect($events, ['request', 'controller', 'controller_arguments', 'response', 'finish_request', 'terminate']);
    },
    // F3: kernel.view is dispatched only when the controller returns something else.
    'http-kernel.view-only-for-non-response' => static function () use ($trace, $expect): ?string {
        $events = $trace(static fn (): array => ['ok' => true], static function (EventDispatcher $d): void {
            $d->addListener(KernelEvents::VIEW, static function (ViewEvent $e): void {
                $e->setResponse(new Response(json_encode($e->getControllerResult())));
            });
        }, false);

        return $expect($events, ['request', 'controller', 'controller_arguments', 'view', 'response', 'finish_request']);
    },
    // F3: no listener converts the value: handle() throws a LogicException.
    'http-kernel.unconverted-view-throws' => static function () use ($trace): ?string {
        try {
            $trace(static fn (): array => [], static function (): void {}, false);
        } catch (Symfony\Component\HttpKernel\Exception\ControllerDoesNotReturnResponseException) {
            return null;
        }

        return 'no exception although nothing converted the controller result';
    },
    // F3: a response set on kernel.request short-circuits controller and view.
    'http-kernel.request-short-circuit' => static function () use ($trace, $expect): ?string {
        $events = $trace(static fn (): Response => new Response('never'), static function (EventDispatcher $d): void {
            $d->addListener(KernelEvents::REQUEST, static fn (RequestEvent $e) => $e->setResponse(new Response('early')));
        }, false);

        return $expect($events, ['request', 'response', 'finish_request']);
    },
];
