<?php

declare(strict_types=1);

/*
 * The multiple-answer VALIDATION questions of remediation item P1.6
 * (owner, 2026-10-14), executed on the exam versions.
 *
 * Each entry is keyed by the question id and returns one boolean per choice,
 * in the order the choices are written in content/questions/: whether the
 * statement of that choice holds on Symfony 8.0. run.php compares the list with
 * the `correct` flags of the canonical YAML, so a flag flipped in the content,
 * a choice added or removed, or a behaviour changed upstream each fail the run.
 * A multiple-answer question with one wrong distractor is a question whose
 * all-or-nothing score is unfair, so every choice is executed, not only the
 * correct ones.
 *
 * Four questions rest on documentation or on a class this project does not
 * install (RFC 9110 on 307/308, the release process, the special routing
 * parameters, AbstractController's signatures). They are verified against the
 * cited source and are not repeated here; tests/Integration/
 * MultipleAnswerEvidenceTest.php names them, so the gap is stated and not
 * hidden.
 */

use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\EventDispatcher\Event;

/** The class of what $fn throws, or null. */
$throws = static function (callable $fn): ?string {
    try {
        $fn();
    } catch (Throwable $e) {
        return $e::class;
    }

    return null;
};

$tempDir = static fn (): string => sys_get_temp_dir().'/cert-p16-'.bin2hex(random_bytes(4));

// The constructor arguments of the autowiring question, one class each.
final class P16MailerClient
{
}
interface P16NotifierInterface
{
}
final class P16WithService
{
    public function __construct(public P16MailerClient $client)
    {
    }
}
final class P16WithOptional
{
    public function __construct(public int $max = 10)
    {
    }
}
final class P16WithString
{
    public function __construct(public string $dir)
    {
    }
}
final class P16WithInterface
{
    public function __construct(public P16NotifierInterface $notifier)
    {
    }
}

$twig = static fn (): Twig\Environment => new Twig\Environment(new Twig\Loader\ArrayLoader([]));
$compiles = static fn (string $src): bool => null === $throws(static fn () => $twig()->createTemplate($src)->render(['x' => 1]));

return [
    // OPTIONS, HEAD | PUT, DELETE
    'QST-bbxhkwvjjhph' => static fn (): array => array_map(
        static fn (string $m): bool => Request::create('/', $m)->isMethodSafe(),
        ['OPTIONS', 'HEAD', 'PUT', 'DELETE'],
    ),

    // 201, 308 | 304, 300
    'QST-kj1dey8gs671' => static fn (): array => array_map(
        static fn (int $s): bool => (new Response('', $s))->isRedirect(),
        [201, 308, 304, 300],
    ),

    // application/json, text/html | image/png, text/plain
    'QST-vkzw1c93z3ct' => static function (): array {
        $h = AcceptHeader::fromString('application/json, text/html;q=0.5, image/png;q=0');

        return array_map(static fn (string $t): bool => $h->has($t) && $h->get($t)->getQuality() > 0, ['application/json', 'text/html', 'image/png', 'text/plain']);
    },

    // public, s-maxage=600 | private, max-age=600
    'QST-w2tv19e0bh82' => static function (): array {
        $r = new Response();
        $r->setSharedMaxAge(600);
        $h = $r->headers;

        return [$h->hasCacheControlDirective('public'), '600' === (string) $h->getCacheControlDirective('s-maxage'), $h->hasCacheControlDirective('private'), '600' === (string) $h->getCacheControlDirective('max-age')];
    },

    // getHeaders(), getContent(), toArray() | getStatusCode(), getContent(false): which throw ClientException
    'QST-z9qbypbt56bw' => static function () use ($throws): array {
        $calls = [
            static fn ($r) => $r->getHeaders(), static fn ($r) => $r->getContent(), static fn ($r) => $r->toArray(),
            static fn ($r) => $r->getStatusCode(), static fn ($r) => $r->getContent(false),
        ];
        $result = [];
        foreach ($calls as $call) {
            $response = (new MockHttpClient(new MockResponse('{"a":1}', ['http_code' => 404])))->request('GET', 'https://example.test/');
            $result[] = ClientException::class === $throws(static fn () => $call($response));
            $response->getContent(false); // consumed, so the destructor has nothing left to raise
        }

        return $result;
    },

    // kernel.request, kernel.controller, kernel.response | kernel.view, kernel.exception
    'QST-42fahb78tb66' => static function (): array {
        $names = [KernelEvents::REQUEST, KernelEvents::CONTROLLER, KernelEvents::RESPONSE, KernelEvents::VIEW, KernelEvents::EXCEPTION];
        $dispatcher = new EventDispatcher();
        $seen = [];
        foreach ($names as $name) {
            $dispatcher->addListener($name, static function () use (&$seen, $name): void { $seen[$name] = true; });
        }
        $resolver = new class implements ControllerResolverInterface {
            public function getController(Request $request): callable|false
            {
                return static fn (): Response => new Response('ok');
            }
        };
        (new HttpKernel($dispatcher, $resolver, new RequestStack(), new ArgumentResolver()))->handle(Request::create('/'));

        return array_map(static fn (string $n): bool => isset($seen[$n]), $names);
    },

    // 7.4 is LTS; 7.4 security fixes four years | 7.3 bug fixes three years; 7.0 is LTS. Documentary.
    // 307, 308 | 303, 302. Documentary (RFC 9110).

    // 10 ran; order.paid listeners run | 0 runs; dispatch() throws
    'QST-qy72c2rzdvrw' => static function () use ($throws): array {
        $d = new EventDispatcher();
        $log = [];
        $d->addListener('order.placed', static function () use (&$log): void { $log[] = 10; }, 10);
        $d->addListener('order.placed', static function (Event $e) use (&$log): void { $log[] = 5; $e->stopPropagation(); }, 5);
        $d->addListener('order.placed', static function () use (&$log): void { $log[] = 0; }, 0);
        $d->addListener('order.paid', static function () use (&$log): void { $log[] = 'paid'; });
        $threw = null !== $throws(static fn () => $d->dispatch(new Event(), 'order.placed'));
        $d->dispatch(new Event(), 'order.paid');

        return [in_array(10, $log, true), in_array('paid', $log, true), in_array(0, $log, true), $threw];
    },

    // ABSOLUTE_URL, NETWORK_PATH | ABSOLUTE_PATH, RELATIVE_PATH: output contains the host
    'QST-kcb8mz5b8cy9' => static function (): array {
        $routes = new RouteCollection();
        $routes->add('blog', new Route('/blog/{slug}'));
        $g = new UrlGenerator($routes, new RequestContext('', 'GET', 'example.com', 'https'));

        return array_map(
            static fn (int $t): bool => str_contains($g->generate('blog', ['slug' => 'a'], $t), 'example.com'),
            [UrlGeneratorInterface::ABSOLUTE_URL, UrlGeneratorInterface::NETWORK_PATH, UrlGeneratorInterface::ABSOLUTE_PATH, UrlGeneratorInterface::RELATIVE_PATH],
        );
    },

    // defined, empty | blank, numeric
    'QST-78tq6ays7vnp' => static fn (): array => array_map(
        static fn (string $t): bool => $compiles("{{ x is $t ? 1 : 0 }}"),
        ['defined', 'empty', 'blank', 'numeric'],
    ),

    // html_attr, css, url | xml, sql
    'QST-wbjj5skr4pen' => static fn (): array => array_map(
        static fn (string $s): bool => $compiles("{{ '<a b>'|e('$s') }}"),
        ['html_attr', 'css', 'url', 'xml', 'sql'],
    ),

    // ~, ??, starts with | &&, .=
    'QST-qq5n3a8927cw' => static fn (): array => array_map($compiles, [
        "{{ 'a' ~ 'b' }}", "{{ y ?? 'd' }}", "{{ 'abc' starts with 'a' ? 1 : 0 }}", '{{ true && false }}', "{% set s = 'a' %}{% set s .= 'b' %}",
    ]),

    // PRE_SUBMIT, SUBMIT, POST_SUBMIT | PRE_SET_DATA, POST_SET_DATA: dispatched by submit()
    'QST-nf32gcp2hzy8' => static function (): array {
        $events = [FormEvents::PRE_SUBMIT, FormEvents::SUBMIT, FormEvents::POST_SUBMIT, FormEvents::PRE_SET_DATA, FormEvents::POST_SET_DATA];
        $builder = Forms::createFormFactory()->createBuilder(FormType::class, ['name' => 'x'])->add('name');
        $log = [];
        foreach ($events as $e) {
            $builder->addEventListener($e, static function () use (&$log, $e): void { $log[$e] = true; });
        }
        $form = $builder->getForm();
        $log = [];
        $form->submit(['name' => 'y']);

        return array_map(static fn (string $e): bool => isset($log[$e]), $events);
    },

    // (false,false), (false,true) | (true,false), (true,true): renders a <select>
    'QST-dg4pvtdpr5q1' => static function (): array {
        $dir = dirname((new ReflectionClass(FormExtension::class))->getFileName(), 2).'/Resources/views/Form';
        $env = new Twig\Environment(new Twig\Loader\FilesystemLoader([$dir]));
        $engine = new TwigRendererEngine(['form_div_layout.html.twig'], $env);
        $env->addRuntimeLoader(new Twig\RuntimeLoader\FactoryRuntimeLoader([FormRenderer::class => static fn () => new FormRenderer($engine)]));
        $env->addExtension(new FormExtension());
        $env->addExtension(new TranslationExtension());
        $factory = Forms::createFormFactory();
        $select = static function (array $o) use ($factory, $env): bool {
            $view = $factory->createNamed('c', ChoiceType::class, null, ['choices' => ['A' => 'a', 'B' => 'b'], 'expanded' => $o[0], 'multiple' => $o[1]])->createView();

            return str_contains($env->createTemplate('{{ form_widget(f) }}')->render(['f' => $view]), '<select');
        };

        return array_map($select, [[false, false], [false, true], [true, false], [true, true]]);
    },

    // which resolve() calls throw
    'QST-5vhmxncenp53' => static function () use ($throws): array {
        $r = new OptionsResolver();
        $r->setDefaults(['a' => 1]);
        $r->setDefined('b');
        $r->setRequired('c');

        return array_map(static fn (array $in): bool => null !== $throws(static fn () => $r->resolve($in)), [['c' => 1, 'z' => 1], [], ['c' => 1], ['c' => 1, 'a' => 5]]);
    },

    // null, '', [] | '0', 0: NotBlank reports a violation
    'QST-4nh0cd7s8rrd' => static function (): array {
        $validator = Validation::createValidator();

        return array_map(static fn (mixed $v): bool => count($validator->validate($v, new NotBlank())) > 0, [null, '', [], '0', 0]);
    },

    // $a, $c | $b, $d: checked by validate() with no group
    'QST-xpm0gvpryn1e' => static function (): array {
        $object = new class {
            public string $a = '';
            public string $b = '';
            public string $c = '';
            public string $d = '';
        };
        $validator = Validation::createValidatorBuilder()->addLoader(new class($object::class) implements LoaderInterface {
            public function __construct(private string $class)
            {
            }

            public function loadClassMetadata(ClassMetadata $metadata): bool
            {
                if ($metadata->getClassName() !== $this->class) {
                    return false;
                }
                $metadata->addPropertyConstraint('a', new NotBlank());
                $metadata->addPropertyConstraint('b', new NotBlank(groups: ['registration']));
                $metadata->addPropertyConstraint('c', new NotBlank(groups: ['Default']));
                $metadata->addPropertyConstraint('d', new NotBlank(groups: ['Strict']));

                return true;
            }
        })->getValidator();
        $paths = array_map(static fn ($v): string => $v->getPropertyPath(), iterator_to_array($validator->validate($object)));

        return array_map(static fn (string $p): bool => in_array($p, $paths, true), ['a', 'c', 'b', 'd']);
    },

    // TYPE_BEFORE_REMOVING, TYPE_OPTIMIZE | TYPE_AFTER_COMPILATION, TYPE_BEFORE_LOADING
    'QST-ngwbd01akwev' => static fn (): array => array_map(
        static fn (string $c): bool => defined(PassConfig::class.'::'.$c),
        ['TYPE_BEFORE_REMOVING', 'TYPE_OPTIMIZE', 'TYPE_AFTER_COMPILATION', 'TYPE_BEFORE_LOADING'],
    ),

    // isPublic() false; isShared() true | isLazy() true; isAutowired() true
    'QST-ybpw4y25fj78' => static function (): array {
        $d = (new ContainerBuilder())->register('app.archiver', ArrayObject::class);

        return [!$d->isPublic(), $d->isShared(), $d->isLazy(), $d->isAutowired()];
    },

    // service class, optional int | string, interface without alias: filled by autowiring
    'QST-6ew0t2hfqnsb' => static function () use ($throws): array {
        $wires = static function (string $class) use ($throws): bool {
            $c = new ContainerBuilder();
            $c->register(P16MailerClient::class, P16MailerClient::class);
            $c->register($class, $class)->setAutowired(true)->setPublic(true);

            return null === $throws(static function () use ($c, $class): void {
                $c->compile();
                $c->get($class);
            });
        };

        return array_map($wires, [P16WithService::class, P16WithOptional::class, P16WithString::class, P16WithInterface::class]);
    },

    // IS_AUTHENTICATED_FULLY, PUBLIC_ACCESS | ROLE_USER, IS_ANONYMOUS: handled by AuthenticatedVoter
    'QST-rafgr5c0nmkg' => static function (): array {
        $voter = new AuthenticatedVoter(new AuthenticationTrustResolver());

        return array_map(static fn (string $a): bool => $voter->supportsAttribute($a), ['IS_AUTHENTICATED_FULLY', 'PUBLIC_ACCESS', 'ROLE_USER', 'IS_ANONYMOUS']);
    },

    // ROLE_ADMIN, ROLE_USER | ROLE_ALLOWED_TO_SWITCH, ROLE_SUPER_ADMIN: reachable from ROLE_ADMIN
    'QST-9xrwcaceyrnm' => static function (): array {
        $reachable = (new RoleHierarchy(['ROLE_ADMIN' => ['ROLE_USER'], 'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH']]))->getReachableRoleNames(['ROLE_ADMIN']);

        return array_map(static fn (string $r): bool => in_array($r, $reachable, true), ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH', 'ROLE_SUPER_ADMIN']);
    },

    // handled during dispatch; result "ok" | SentStamp; NoHandlerForMessageException
    'QST-m4xfyvvr6rnq' => static function () use ($throws): array {
        $ran = false;
        $envelope = null;
        $bus = new MessageBus([
            new SendMessageMiddleware(new SendersLocator([], new Container())),
            new HandleMessageMiddleware(new HandlersLocator([stdClass::class => [static function () use (&$ran): string {
                $ran = true;

                return 'ok';
            }]])),
        ]);
        $threw = null !== $throws(static function () use ($bus, &$envelope): void { $envelope = $bus->dispatch(new stdClass()); });

        return [$ran, 'ok' === $envelope?->last(HandledStamp::class)?->getResult(), null !== $envelope?->last(SentStamp::class), $threw];
    },

    // SUCCESS is 0; INVALID is 2 | FAILURE is -1; ERROR exists
    'QST-hpn0a2ge2ah2' => static fn (): array => [
        0 === Command::SUCCESS, 2 === Command::INVALID, -1 === Command::FAILURE, defined(Command::class.'::ERROR'),
    ],

    // VERBOSITY_SILENT, VERBOSITY_VERY_VERBOSE | VERBOSITY_NONE, VERBOSITY_TRACE
    'QST-xg0943vmrt8s' => static fn (): array => array_map(
        static fn (string $c): bool => defined(OutputInterface::class.'::'.$c),
        ['VERBOSITY_SILENT', 'VERBOSITY_VERY_VERBOSE', 'VERBOSITY_NONE', 'VERBOSITY_TRACE'],
    ),

    // SIGNAL, TERMINATE | EXCEPTION, FINISH
    'QST-6j988v4fwa7n' => static fn (): array => array_map(
        static fn (string $c): bool => defined(ConsoleEvents::class.'::'.$c),
        ['SIGNAL', 'TERMINATE', 'EXCEPTION', 'FINISH'],
    ),

    // copy() missing source, readFile() on a directory | mkdir() existing, remove() missing
    'QST-zpyjs8dgyskb' => static function () use ($tempDir): array {
        $fs = new Filesystem();
        $dir = $tempDir();
        $fs->mkdir($dir);
        $io = static function (callable $fn): bool {
            try {
                $fn();
            } catch (IOExceptionInterface) {
                return true;
            }

            return false;
        };
        $result = [
            $io(static fn () => $fs->copy("$dir/missing", "$dir/copy")),
            $io(static fn () => $fs->readFile($dir)),
            $io(static fn () => $fs->mkdir($dir)),
            $io(static fn () => $fs->remove("$dir/missing")),
        ];
        $fs->remove($dir);

        return $result;
    },

    // getTimeout() 60.0; run() returns 1 without throwing | getIdleTimeout() 60.0; run() throws
    'QST-htty0wd3bvec' => static function () use ($throws): array {
        $p = new Process(['false']);
        $timeout = $p->getTimeout();
        $idle = $p->getIdleTimeout();
        $code = null;
        $runThrew = null !== $throws(static function () use ($p, &$code): void { $code = $p->run(); });

        return [60.0 === $timeout, 1 === $code && !$runThrew, 60.0 === $idle, $runThrew];
    },

    // sub/deep.txt, ign.log | .hidden, .git/HEAD: returned by default
    'QST-8a3w8pct60kq' => static function () use ($tempDir): array {
        $fs = new Filesystem();
        $dir = $tempDir();
        foreach (['a.txt', 'sub/deep.txt', '.hidden', '.git/HEAD', 'ign.log'] as $f) {
            $fs->dumpFile("$dir/$f", 'x');
        }
        $fs->dumpFile("$dir/.gitignore", "ign.log\n");
        $names = [];
        foreach ((new Finder())->in($dir) as $file) {
            $names[] = $file->getRelativePathname();
        }
        $fs->remove($dir);

        return array_map(static fn (string $n): bool => in_array($n, $names, true), ['sub/deep.txt', 'ign.log', '.hidden', '.git/HEAD']);
    },

    // sleep(5), modify('+1 day') | usleep(), now() again: makes now() change
    'QST-99tq1nj7c9vx' => static function (): array {
        $clock = new MockClock('2026-01-01 00:00:00');
        $start = $clock->now();
        usleep(20000);
        $afterRealTime = $clock->now() != $start;
        $afterRead = $clock->now() != $start;
        $clock->sleep(5);
        $afterSleep = $clock->now() != $start;
        $before = $clock->now();
        $clock->modify('+1 day');

        return [$afterSleep, $clock->now() != $before, $afterRealTime, $afterRead];
    },
];
