<?php

declare(strict_types=1);

/*
 * Second batch of P1.6 (2026-10-15): multiple-answer questions on PHP and
 * Automated Tests, one boolean per choice in content order, compared by run.php
 * with the `correct` flags of the YAML (see multiple-answer.php).
 *
 * Several PHP choices are compile-time errors (a parse error, "Cannot use
 * $this as lexical variable"), which no try/catch can observe from inside the
 * same process. They are run in a child PHP process; a choice holds when that
 * process exits 0 and prints "ok".
 *
 * QST-fvx988xhq7gw (the WebTestCase assertions) rests on FrameworkBundle's
 * assertion traits, which tools/behavior does not install; it is listed in
 * MultipleAnswerEvidenceTest::DOCUMENTARY.
 */

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/** Runs $code in a child PHP process: true when it exits 0 and prints "ok". */
$compilesAndRuns = static function (string $code): bool {
    $file = tempnam(sys_get_temp_dir(), 'cert-p17-');
    file_put_contents($file, "<?php\n".$code);
    exec(escapeshellarg(PHP_BINARY).' -d display_errors=stderr '.escapeshellarg($file).' 2>/dev/null', $out, $status);
    unlink($file);

    return 0 === $status && 'ok' === trim(implode('', $out));
};

/** A kernel answering /a with a 302 to /b, and anything else with a page echoing X-Api-Key. */
$kernel = static fn (): HttpKernelInterface => new class implements HttpKernelInterface {
    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
    {
        return '/a' === $request->getPathInfo()
            ? new RedirectResponse('/b')
            : new Response('<html><body>'.$request->headers->get('x-api-key', '').'</body></html>');
    }
};

return [
    // readonly initialized by a child; promotion declares | new self() returns the child; private redeclared changes the call
    'QST-m4m81mfwc4ww' => static fn (): array => array_map($compilesAndRuns, [
        'class P { public readonly int $x; } class C extends P { public function __construct() { $this->x = 1; } } echo 1 === (new C())->x ? "ok" : "no";',
        'class P { public function __construct(public int $a = 3) {} } echo 3 === (new P())->a && property_exists(P::class, "a") ? "ok" : "no";',
        'class P { public static function make() { return new self(); } } class Child extends P {} echo Child::make() instanceof Child ? "ok" : "no";',
        'class P { private function h() { return "P"; } public function call() { return $this->h(); } } class C extends P { private function h() { return "C"; } } echo "C" === (new C())->call() ? "ok" : "no";',
    ]),

    // interface extends two; class implements two | interface implements; class extends two
    'QST-xcwt5f53w57p' => static fn (): array => array_map(
        static fn (string $decl): bool => $compilesAndRuns('interface A {} interface B {} '.$decl.' echo "ok";'),
        ['interface I extends A, B {}', 'class C implements A, B {}', 'interface I implements A {}', 'class C extends A, B {}'],
    ),

    // tryFrom('x'), from('h') | from('x'), writing ->value: complete without throwing
    'QST-1qg0yxxx9jxj' => static fn (): array => array_map(
        static fn (string $call): bool => $compilesAndRuns('enum Suit: string { case Hearts = "h"; } '.$call.' echo "ok";'),
        ['Suit::tryFrom("x");', 'Suit::from("h");', 'Suit::from("x");', '$c = Suit::Hearts; $c->value = "z";'],
    ),

    // cases(), ->name | ->value, from(): available on a pure enum
    'QST-nyr4h6qq8ayk' => static fn (): array => array_map(
        // Strict: a warning counts as unavailable, since reading ->value on a
        // pure enum only warns "Undefined property" and yields null.
        static fn (string $use): bool => $compilesAndRuns('set_error_handler(static function (int $n, string $m): never { throw new ErrorException($m); }); enum Status { case Draft; } '.$use.' echo "ok";'),
        ['Status::cases();', 'Status::Draft->name;', 'Status::Draft->value;', 'Status::from("Draft");'],
    ),

    // 302 kept; followRedirect() loads /b | already on /b; isFollowingRedirects() true
    'QST-et0r585d9ra3' => static function () use ($kernel): array {
        $client = new HttpKernelBrowser($kernel());
        $client->request('GET', '/a');
        $status = $client->getResponse()->getStatusCode();
        $onB = '/b' === $client->getRequest()->getPathInfo();
        $following = $client->isFollowingRedirects();
        $client->followRedirect();

        return [302 === $status, '/b' === $client->getRequest()->getPathInfo(), $onB, $following];
    },

    // extract(), each() | text(), attr(): something for both links
    'QST-0z642aefqqc9' => static function (): array {
        $links = (new Crawler('<html><body><a title="A">one</a><a title="B">two</a></body></html>'))->filterXPath('//a');
        $both = static fn (mixed $v): bool => is_array($v) && 2 === count($v);

        return [$both($links->extract(['_text', 'title'])), $both($links->each(static fn (Crawler $n): string => $n->text())), $both($links->text()), $both($links->attr('title'))];
    },

    // HTTP_X_API_KEY: first request, second request | 'X-Api-Key' works; only the first request
    'QST-e6fsny5640vm' => static function () use ($kernel): array {
        $client = new HttpKernelBrowser($kernel(), ['HTTP_X_API_KEY' => 'k']);
        $client->request('GET', '/one');
        $first = 'k' === $client->getRequest()->headers->get('x-api-key');
        $client->request('GET', '/two');
        $second = 'k' === $client->getRequest()->headers->get('x-api-key');
        $other = new HttpKernelBrowser($kernel(), ['X-Api-Key' => 'k']);
        $other->request('GET', '/one');

        return [$first, $second, 'k' === $other->getRequest()->headers->get('x-api-key'), !$second];
    },

    // E_USER_DEPRECATED; "Since acme/lib 1.2: …" | throws; printed without a handler
    'QST-rs3tsp2rj4ka' => static function () use ($compilesAndRuns): array {
        $seen = [];
        set_error_handler(static function (int $no, string $msg) use (&$seen): bool {
            $seen[] = [$no, $msg];

            return true;
        });
        $threw = false;
        try {
            trigger_deprecation('acme/lib', '1.2', 'Old thing is deprecated');
        } catch (Throwable) {
            $threw = true;
        } finally {
            restore_error_handler();
        }
        $autoload = var_export(__DIR__.'/../vendor/autoload.php', true);
        $printed = !$compilesAndRuns('require '.$autoload.'; ob_start(); trigger_deprecation("acme/lib", "1.2", "Old thing is deprecated"); echo "" === ob_get_clean() ? "ok" : "no";');

        return [
            [E_USER_DEPRECATED] === array_column($seen, 0),
            'Since acme/lib 1.2: Old thing is deprecated' === ($seen[0][1] ?? null),
            $threw,
            $printed,
        ];
    },

    // getResponse(), getInternalResponse() | Request, Crawler: give the status code
    'QST-4a1ch0v8dv9d' => static function () use ($kernel): array {
        $client = new HttpKernelBrowser($kernel());
        $crawler = $client->request('GET', '/b');

        return [
            200 === $client->getResponse()->getStatusCode(),
            200 === $client->getInternalResponse()->getStatusCode(),
            method_exists($client->getRequest(), 'getStatusCode'),
            method_exists($crawler, 'getStatusCode'),
        ];
    },
];
