<?php

declare(strict_types=1);

/*
 * Remediation P2.7 (Profiler): a custom data collector end to end — its name,
 * what collect() and lateCollect() store, what the profile keeps and what the
 * profiler refuses — executed on HttpKernel and FrameworkBundle 8.0, then read
 * back from a functional test. Each check is proved by a mutation of the vendor
 * line carrying it (tools/audit/deep80/mutate.py).
 */

namespace CertBehavior\Profiler;

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class Meter
{
    public int $queries = 0;
}

/** The documented shape: a service counts during the request, collect() picks the count up. */
final class QueryCollector extends AbstractDataCollector implements LateDataCollectorInterface
{
    public function __construct(private Meter $meter)
    {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = ['at_response' => $this->meter->queries];
    }

    public function lateCollect(): void
    {
        $this->data['at_terminate'] = $this->meter->queries;
    }

    public function getData(): array
    {
        return $this->data;
    }
}

final class ClosureCollector extends AbstractDataCollector
{
    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = ['callback' => static fn (): int => 1];
    }
}

final class HitCollector extends AbstractDataCollector
{
    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = ['path' => $request->getPathInfo()];
    }

    public function getName(): string
    {
        return 'app.hit';
    }

    public function getPath(): string
    {
        return $this->data['path'];
    }
}

final class ProfiledKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(private string $dir)
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle()];
    }

    public function getCacheDir(): string
    {
        return $this->dir.'/cache';
    }

    public function getLogDir(): string
    {
        return $this->dir.'/log';
    }

    public function getProjectDir(): string
    {
        return $this->dir;
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        // The test environment of the profiler recipe: enabled, collecting nothing by default.
        $container->extension('framework', ['test' => true, 'secret' => 's', 'profiler' => ['enabled' => true, 'collect' => false]]);
        $container->services()->set(HitCollector::class)->autoconfigure();
        $container->services()->set('logger', \Psr\Log\NullLogger::class);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('hello', '/hello')->controller([self::class, 'hello']);
    }

    public static function hello(): Response
    {
        return new Response('<html><body>hi</body></html>');
    }
}

$tempDir = static function (): string {
    $dir = sys_get_temp_dir().'/cert-profiler-'.bin2hex(random_bytes(6));
    mkdir($dir);

    return $dir;
};

return [
    // getName() defaults to the class name; collect() runs at the response, lateCollect() at saveProfile(); the profile
    // keeps a clone, and loadProfile() returns an unserialized copy with both values.
    'profiler.collector-lifecycle' => static function () use ($tempDir): ?string {
        $meter = new Meter();
        $collector = new QueryCollector($meter);
        $profiler = new Profiler(new FileProfilerStorage('file:'.$tempDir()));
        $profiler->add($collector);

        $meter->queries = 2;
        $profile = $profiler->collect(Request::create('/x'), new Response());
        $meter->queries = 5;
        $profiler->saveProfile($profile);
        $loaded = $profiler->loadProfile($profile->getToken())->getCollector(QueryCollector::class);
        try {
            $profile->getCollector('app.unknown');
            $unknown = null;
        } catch (\InvalidArgumentException $e) {
            $unknown = $e->getMessage();
        }

        return match (true) {
            QueryCollector::class !== $collector->getName() => 'getName() gave '.$collector->getName(),
            null !== QueryCollector::getTemplate() => 'getTemplate() is not null by default',
            $profile->getCollector(QueryCollector::class) === $collector => 'the profile holds the service itself, not a clone',
            ['at_response' => 2, 'at_terminate' => 5] !== $loaded->getData() => 'stored '.json_encode($loaded->getData()),
            ['at_response' => 2] !== $collector->getData() => 'the service instance was changed by lateCollect(): '.json_encode($collector->getData()),
            'Collector "app.unknown" does not exist.' !== $unknown => 'unknown collector gave '.var_export($unknown, true),
            default => null,
        };
    },

    // The profile is serialized: a Closure in $this->data makes saveProfile() throw. reset() empties the data;
    // a disabled profiler collects nothing.
    'profiler.serialized-reset-disabled' => static function () use ($tempDir): ?string {
        $profiler = new Profiler(new FileProfilerStorage('file:'.$tempDir()));
        $profiler->add(new ClosureCollector());
        try {
            $profiler->saveProfile($profiler->collect(Request::create('/x'), new Response()));
            $closure = null;
        } catch (\Exception $e) {
            $closure = $e->getMessage();
        }

        $collector = new QueryCollector(new Meter());
        $collector->collect(Request::create('/x'), new Response());
        $collector->reset();

        $disabled = new Profiler(new FileProfilerStorage('file:'.$tempDir()));
        $disabled->disable();

        return match (true) {
            "Serialization of 'Closure' is not allowed" !== $closure => 'a Closure in the data gave '.var_export($closure, true),
            [] !== $collector->getData() => 'reset() left '.json_encode($collector->getData()),
            null !== $disabled->collect(Request::create('/x'), new Response()) => 'a disabled profiler returned a profile',
            default => null,
        };
    },

    // An autoconfigured collector, read back in a functional test under its getName(), only after enableProfiler().
    'profiler.collector-in-functional-test' => static function () use ($tempDir): ?string {
        $kernel = new ProfiledKernel($tempDir());
        $client = new KernelBrowser($kernel);
        $client->request('GET', '/hello');
        $without = $client->getProfile();
        $client->enableProfiler();
        $client->request('GET', '/hello');
        $profile = $client->getProfile();
        $client->request('GET', '/hello');
        $next = $client->getProfile();

        return match (true) {
            null !== $without => 'collect: false still profiled the request',
            !$profile || !$profile->hasCollector('app.hit') => 'no app.hit collector after enableProfiler()',
            '/hello' !== $profile->getCollector('app.hit')->getPath() => 'app.hit read '.$profile->getCollector('app.hit')->getPath(),
            null !== $next => 'enableProfiler() lasted beyond the next request',
            default => null,
        };
    },
];
