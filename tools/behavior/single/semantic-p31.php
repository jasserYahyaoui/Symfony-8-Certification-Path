<?php

declare(strict_types=1);

/*
 * Remediation P3.1 (2026-10-17): the single-answer LEARNING questions
 * rewritten because a learner who memorized another question's answer could
 * answer them without reasoning (tools/audit/semantic_repetition.py). One
 * boolean per choice, in content order, compared by run.php with the `correct`
 * flags of the YAML; exactly one is true. questions/ holds the multiple-answer
 * ones, which MultipleAnswerEvidenceTest counts.
 *
 * Not here, and why: QST-enxn0ad65n0w (303 See Other) is RFC 9110 text and
 * QST-nqeq65rj5m97 (what twig-bundle installs) is two composer.json files;
 * both are read, not executed. QST-ycpqw0ygx9cd is the central fact
 * console.alarm-event.
 */

namespace CertBehavior\P31;

use Symfony\Bundle\TwigBundle\DependencyInjection\Configuration as TwigConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Controller\ErrorController;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Strategy\PriorityStrategy;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Loader\ArrayLoader;

final class Busy extends \RuntimeException implements HttpExceptionInterface
{
    public function getStatusCode(): int
    {
        return 409;
    }

    public function getHeaders(): array
    {
        return [];
    }
}

final class Mailer
{
}

final class Report
{
}

/** A temporary directory, removed by the caller. */
function scratch(): string
{
    $dir = sys_get_temp_dir().'/cert-p31-'.bin2hex(random_bytes(4));
    mkdir($dir.'/config/services', 0o777, true);

    return $dir;
}

function remove(string $dir): void
{
    foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}

return [
    // setSharedMaxAge(600) then setPrivate(): not storable (private) | storable 600 s | storable, browser-only | s-maxage removed
    'QST-m8byv8zcsa8m' => static function (): array {
        $r = new Response('x');
        $r->setSharedMaxAge(600);
        $r->setPrivate();
        // isCacheable() is what Symfony's HttpCache asks before it stores.
        $storable = $r->isCacheable();
        $keepsSMaxAge = $r->headers->hasCacheControlDirective('s-maxage');
        $private = $r->headers->hasCacheControlDirective('private');

        return [
            !$storable && $private && $keepsSMaxAge,
            $storable && !$private,
            $storable,
            !$storable && !$keepsSMaxAge,
        ];
    },

    // strict_variables not configured, {{ user.nmae }}: error in debug, nothing otherwise | nothing either way | error either way | inverted
    'QST-k1qgrrevaw40' => static function (): array {
        // The default as TwigBundle's configuration tree gives it, for an empty config.
        $default = (new Processor())->processConfiguration(new TwigConfiguration(), [[]])['strict_variables'];
        $strict = static fn (bool $debug): bool => '%kernel.debug%' === $default ? $debug : (bool) $default;
        $user = new class {
            public string $name = 'Ada';
        };
        $outcome = static function (bool $strict) use ($user): string {
            $twig = new Environment(new ArrayLoader(['t' => '{{ user.nmae }}']), ['strict_variables' => $strict]);
            try {
                return '' === $twig->render('t', ['user' => $user]) ? 'nothing' : 'something';
            } catch (RuntimeError) {
                return 'error';
            }
        };
        [$dev, $prod] = [$outcome($strict(true)), $outcome($strict(false))];

        return [
            'error' === $dev && 'nothing' === $prod,
            'nothing' === $dev && 'nothing' === $prod,
            'error' === $dev && 'error' === $prod,
            'nothing' === $dev && 'error' === $prod,
        ];
    },

    // after routing: _controller string | _route names the method | `controller` | inside _route_params
    'QST-gbccz7ynvbd2' => static function (): array {
        $routes = new RouteCollection();
        $routes->add('blog_show', new Route('/blog/{slug}', ['_controller' => 'App\Controller\BlogController::show']));
        $request = Request::create('/blog/hello');
        $stack = new RequestStack();
        $stack->push($request);
        $kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                throw new \LogicException('not called');
            }
        };
        (new RouterListener(new UrlMatcher($routes, new RequestContext()), $stack))
            ->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $a = $request->attributes;

        return [
            'App\Controller\BlogController::show' === $a->get('_controller'),
            'App\Controller\BlogController::show' === $a->get('_route'),
            $a->has('controller'),
            \array_key_exists('_controller', $a->all('_route_params')),
        ];
    },

    // priority, votes abstain, deny, grant: denied by the first non-abstaining | granted, one grant | granted, tie | denied by all-abstain
    'QST-c8gpyvw5shmy' => static function (): array {
        $voter = static fn (int $vote): VoterInterface => new class($vote) implements VoterInterface {
            public function __construct(private int $vote)
            {
            }

            public function vote($token, mixed $subject, array $attributes, $v = null): int
            {
                return $this->vote;
            }
        };
        $decide = static fn (array $votes, bool $allowIfAllAbstain = false): bool => (new AccessDecisionManager(
            array_map($voter, $votes),
            new PriorityStrategy($allowIfAllAbstain),
        ))->decide(new NullToken(), ['EDIT']);
        [$abstain, $deny, $grant] = [VoterInterface::ACCESS_ABSTAIN, VoterInterface::ACCESS_DENIED, VoterInterface::ACCESS_GRANTED];
        $granted = $decide([$abstain, $deny, $grant]);
        // The first non-abstaining vote decides: swap deny and grant, and the decision follows.
        $firstDecides = $decide([$abstain, $grant, $deny]) && !$granted;
        // allow_if_all_abstain applies only if flipping it changes this decision.
        $allAbstainApplies = $decide([$abstain, $deny, $grant], true) !== $granted;

        return [!$granted && $firstDecides, $granted, $granted, !$granted && $allAbstainApplies];
    },

    // APP_ENV=prod, APP_GREETING in .env, .env.local, .env.prod: .env.prod | .env.local | .env | an error
    'QST-bftzbt0t3q8y' => static function (): array {
        $dir = scratch();
        file_put_contents("$dir/.env", "APP_ENV=prod\nAPP_GREETING=from-env\n");
        file_put_contents("$dir/.env.local", "APP_GREETING=from-local\n");
        file_put_contents("$dir/.env.prod", "APP_GREETING=from-prod\n");
        foreach (['APP_ENV', 'APP_GREETING', 'SYMFONY_DOTENV_VARS'] as $k) {
            unset($_SERVER[$k], $_ENV[$k]);
            putenv($k);
        }
        $error = false;
        try {
            (new Dotenv())->loadEnv("$dir/.env");
        } catch (\Throwable) {
            $error = true;
        }
        $value = $_SERVER['APP_GREETING'] ?? null;
        foreach (['APP_ENV', 'APP_GREETING', 'SYMFONY_DOTENV_VARS'] as $k) {
            unset($_SERVER[$k], $_ENV[$k]);
            putenv($k);
        }
        remove($dir);

        return ['from-prod' === $value, 'from-local' === $value, 'from-env' === $value, $error];
    },

    // _defaults in services.yaml, App\Report in an imported file: not autowired (scope) | inherited | on by default | never possible
    'QST-gfqb0xrb7tbx' => static function (): array {
        $dir = scratch();
        file_put_contents("$dir/config/services.yaml", "services:\n    _defaults:\n        autowire: true\n    CertBehavior\\P31\\Mailer: ~\nimports:\n    - { resource: services/admin.yaml }\n");
        file_put_contents("$dir/config/services/admin.yaml", "services:\n    CertBehavior\\P31\\Report: ~\n");
        file_put_contents("$dir/config/services/admin-own.yaml", "services:\n    _defaults:\n        autowire: true\n    CertBehavior\\P31\\Report: ~\n");
        $load = static function (string $file) use ($dir): ContainerBuilder {
            $c = new ContainerBuilder();
            (new YamlFileLoader($c, new FileLocator("$dir/config")))->load($file);

            return $c;
        };
        $c = $load('services.yaml');
        $mailer = $c->getDefinition(Mailer::class)->isAutowired();
        $report = $c->getDefinition(Report::class)->isAutowired();
        // An imported file with its own _defaults does autowire.
        $own = $load('services/admin-own.yaml')->getDefinition(Report::class)->isAutowired();
        $plain = (new ContainerBuilder())->register('x', Report::class)->isAutowired();
        remove($dir);

        return [$mailer && !$report, $report, $report && $plain, !$report && !$own];
    },

    // _format json, Content-Type text/csv: text/csv kept | application/json | two headers | 406
    'QST-af1e821vzx6r' => static function (): array {
        $request = Request::create('/report');
        $request->attributes->set('_format', 'json');
        $response = new Response('a,b', 200, ['Content-Type' => 'text/csv']);
        $response->prepare($request);
        $type = $response->headers->get('Content-Type');

        return [
            str_starts_with($type, 'text/csv') && str_contains($type, 'charset='),
            'application/json' === $type,
            \count($response->headers->all('content-type')) > 1,
            406 === $response->getStatusCode(),
        ];
    },

    // HttpExceptionInterface 409 + framework.exceptions 423: 423 | 409 | 500 | depends on the environment
    'QST-3bbtms3dk9ca' => static function (): array {
        $status = static function (bool $debug): int {
            $dispatcher = new EventDispatcher();
            $resolver = new class implements ControllerResolverInterface {
                public function getController(Request $request): callable|false
                {
                    return $request->attributes->get('_controller');
                }
            };
            $kernel = new HttpKernel($dispatcher, $resolver, new RequestStack(), new ArgumentResolver());
            $dispatcher->addSubscriber(new ErrorListener(
                new ErrorController($kernel, null, new HtmlErrorRenderer($debug)),
                null,
                $debug,
                [Busy::class => ['status_code' => 423, 'log_level' => null, 'log_channel' => null]],
            ));
            $request = Request::create('/');
            $request->attributes->set('_controller', static fn () => throw new Busy());

            return $kernel->handle($request)->getStatusCode();
        };
        $prod = $status(false);
        $dev = $status(true);

        return [423 === $prod && 423 === $dev, 409 === $prod, 500 === $prod, $dev !== $prod];
    },
];
