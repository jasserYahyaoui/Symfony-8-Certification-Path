<?php

declare(strict_types=1);

/*
 * Remediation P2.7 (HttpClient): retries, the response lifecycle and a stream
 * in which one response fails, executed on HttpClient 8.0 with MockHttpClient.
 * Each check is proved by a mutation of the vendor line carrying it
 * (tools/audit/deep80/mutate.py).
 */

use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;

return [
    // A response whose status is never read throws from its destructor on 3xx-5xx; getStatusCode() opts out, getInfo() does not.
    'http-client.unchecked-status-throws-on-destruct' => static function (): ?string {
        $thrown = static function (callable $use, int $code): ?string {
            $client = new MockHttpClient(new MockResponse('', ['http_code' => $code]));
            try {
                $use($client);
            } catch (Throwable $e) {
                return $e::class;
            }

            return null;
        };
        $got = [
            $thrown(static fn ($c) => $c->request('POST', 'https://x.test/'), 500),
            $thrown(static function ($c) { $r = $c->request('GET', 'https://x.test/'); unset($r); }, 404),
            $thrown(static function ($c) { $r = $c->request('GET', 'https://x.test/'); $r->getStatusCode(); unset($r); }, 404),
            $thrown(static function ($c) { $r = $c->request('GET', 'https://x.test/'); $r->getInfo('http_code'); unset($r); }, 500),
            $thrown(static function ($c) { $r = $c->request('GET', 'https://x.test/'); $r->getHeaders(false); unset($r); }, 500),
            $thrown(static function ($c) { $r = $c->request('GET', 'https://x.test/'); $r->cancel(); unset($r); }, 500),
            $thrown(static fn ($c) => $c->request('GET', 'https://x.test/'), 200),
        ];
        $want = [ServerException::class, ClientException::class, null, ServerException::class, null, null, null];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // RetryableHttpClient: 3 retries by default, then the last response is returned as it is.
    'http-client.retry-count-and-last-response' => static function (): ?string {
        $strategy = new GenericRetryStrategy(delayMs: 0, jitter: 0);
        $mock = new MockHttpClient([new MockResponse('', ['http_code' => 503]), new MockResponse('', ['http_code' => 503]), new MockResponse('ok')]);
        $recovered = (new RetryableHttpClient($mock, $strategy))->request('GET', 'https://x.test/');
        $recoveredGot = [$recovered->getStatusCode(), $recovered->getContent(), $mock->getRequestsCount(), $recovered->getInfo('retry_count')];

        $always = new MockHttpClient(static fn () => new MockResponse('', ['http_code' => 503]));
        $exhausted = (new RetryableHttpClient($always, $strategy))->request('GET', 'https://x.test/');
        $exhaustedStatus = $exhausted->getStatusCode();
        try {
            $exhausted->getContent();
            $exhaustedThrew = null;
        } catch (Throwable $e) {
            $exhaustedThrew = $e::class;
        }

        $once = new MockHttpClient(static fn () => new MockResponse('', ['http_code' => 503]));
        (new RetryableHttpClient($once, $strategy))->request('GET', 'https://x.test/', ['max_retries' => 1])->getStatusCode();

        $got = [$recoveredGot, [$exhaustedStatus, $always->getRequestsCount(), $exhaustedThrew], $once->getRequestsCount()];
        $want = [[200, 'ok', 3, 2], [503, 4, ServerException::class], 2];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // 423, 425, 429, 502, 503 are retried for every method; 500, 504, 507, 510 and transport errors for idempotent methods only,
    // except a failed DNS resolution, retried for every method.
    'http-client.retry-by-status-and-method' => static function (): ?string {
        $calls = static function (string $method, array $info): int {
            $mock = new MockHttpClient(static fn () => new MockResponse('', $info));
            $response = (new RetryableHttpClient($mock, new GenericRetryStrategy(delayMs: 0, jitter: 0)))->request($method, 'https://x.test/');
            try {
                $response->getStatusCode();
            } catch (TransportException) {
            }

            return $mock->getRequestsCount();
        };
        $got = [];
        foreach ([429, 503, 500, 504, 404] as $code) {
            $got["POST $code"] = $calls('POST', ['http_code' => $code]);
            $got["GET $code"] = $calls('GET', ['http_code' => $code]);
        }
        $got['POST transport'] = $calls('POST', ['error' => 'reset']);
        $got['GET transport'] = $calls('GET', ['error' => 'reset']);
        $got['POST dns'] = $calls('POST', ['error' => 'dns', 'primary_ip' => '']); // no address resolved: always retried
        $want = [
            'POST 429' => 4, 'GET 429' => 4, 'POST 503' => 4, 'GET 503' => 4, 'POST 500' => 1, 'GET 500' => 4,
            'POST 504' => 1, 'GET 504' => 4, 'POST 404' => 1, 'GET 404' => 1, 'POST transport' => 1, 'GET transport' => 4,
            'POST dns' => 4,
        ];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // The delay doubles from 1 s (jitter 10 %); a Retry-After header replaces the computed delay.
    'http-client.retry-delay-and-retry-after' => static function (): ?string {
        $defaults = [];
        foreach ((new ReflectionMethod(GenericRetryStrategy::class, '__construct'))->getParameters() as $parameter) {
            $defaults[$parameter->getName()] = $parameter->getDefaultValue();
        }
        unset($defaults['statusCodes']);

        $mock = new MockHttpClient(static fn () => new MockResponse('', ['http_code' => 503]));
        $start = microtime(true);
        (new RetryableHttpClient($mock, new GenericRetryStrategy(delayMs: 50, multiplier: 2, jitter: 0)))->request('GET', 'https://x.test/')->getStatusCode();
        $doubling = microtime(true) - $start; // 50 + 100 + 200 ms

        $mock = new MockHttpClient([new MockResponse('', ['http_code' => 503, 'response_headers' => ['retry-after: 0.3']]), new MockResponse('ok')]);
        $start = microtime(true);
        (new RetryableHttpClient($mock, new GenericRetryStrategy(delayMs: 0, jitter: 0)))->request('GET', 'https://x.test/')->getContent();
        $header = microtime(true) - $start;

        return match (true) {
            ['delayMs' => 1000, 'multiplier' => 2.0, 'maxDelayMs' => 0, 'jitter' => 0.1] !== $defaults => 'defaults '.json_encode($defaults),
            $doubling < 0.33 || $doubling > 1.0 => sprintf('three retries from 50 ms took %.3f s, not about 0.35 s', $doubling),
            $header < 0.28 || $header > 1.0 => sprintf('Retry-After 0.3 gave %.3f s', $header),
            default => null,
        };
    },

    // In stream(), a transport error is raised by the chunk's methods; caught, the other responses complete.
    'http-client.stream-error-chunk' => static function (): ?string {
        $client = new MockHttpClient([new MockResponse('aaa'), new MockResponse('', ['error' => 'reset']), new MockResponse('ccc')]);
        $responses = [];
        foreach (['a', 'b', 'c'] as $key) {
            $responses[] = $client->request('GET', "https://x.test/$key", ['user_data' => $key]);
        }
        $events = [];
        foreach ($client->stream($responses) as $response => $chunk) {
            try {
                if ($chunk->isLast()) {
                    $events[] = $response->getInfo('user_data').':last';
                }
            } catch (TransportException) {
                $events[] = $response->getInfo('user_data').':error '.$chunk->getError();
            }
        }
        sort($events);

        // getError() reads the message without handling it: the stream throws anyway.
        $client = new MockHttpClient([new MockResponse('', ['error' => 'reset'])]);
        try {
            foreach ($client->stream([$client->request('GET', 'https://x.test/')]) as $chunk) {
                $chunk->getError();
            }
            $ignored = null;
        } catch (TransportException) {
            $ignored = TransportException::class;
        }

        return match (true) {
            ['a:last', 'b:error reset', 'c:last'] !== $events => 'events '.json_encode($events),
            TransportException::class !== $ignored => 'an error chunk read with getError() only did not throw',
            default => null,
        };
    },

    // In stream(), the status must be read at the first chunk; otherwise the stream throws as it resumes.
    'http-client.stream-status-at-first-chunk' => static function (): ?string {
        $run = static function (bool $check): array {
            $client = new MockHttpClient([new MockResponse('x', ['http_code' => 404]), new MockResponse('ok')]);
            $responses = [$client->request('GET', 'https://x.test/a'), $client->request('GET', 'https://x.test/b')];
            $done = 0;
            try {
                foreach ($client->stream($responses) as $response => $chunk) {
                    if ($check && $chunk->isFirst()) {
                        $response->getStatusCode();
                    }
                    $done += $chunk->isLast() ? 1 : 0;
                }
            } catch (Throwable $e) {
                return [$done, $e::class];
            }

            return [$done, null];
        };
        $got = [$run(false), $run(true)];
        $want = [[0, ClientException::class], [2, null]];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // A stream timeout yields a chunk whose isTimeout() is true and ends that stream; isLast() on it throws TimeoutException; a new stream() resumes.
    'http-client.stream-timeout-chunk' => static function (): ?string {
        $body = static function (): Generator { yield ''; yield 'x'; };
        $client = new MockHttpClient([new MockResponse($body())]);
        $response = $client->request('GET', 'https://x.test/');
        $seen = [];
        foreach ([0.0, null] as $timeout) { // the first stream ends on its timeout chunk; streaming again resumes
            foreach ($client->stream($response, $timeout) as $chunk) {
                if ($chunk->isTimeout()) {
                    $seen[] = 'timeout';
                    continue;
                }
                if ($chunk->isLast()) {
                    $seen[] = 'last';
                }
            }
        }

        $client = new MockHttpClient([new MockResponse($body())]);
        try {
            foreach ($client->stream($client->request('GET', 'https://x.test/'), 0.0) as $chunk) {
                $chunk->isLast();
            }
            $unread = null;
        } catch (Throwable $e) {
            $unread = $e::class;
        }

        return match (true) {
            ['timeout', 'last'] !== $seen || 'x' !== $response->getContent() => 'seen '.json_encode($seen),
            TimeoutException::class !== $unread => 'isLast() on a timeout chunk gave '.var_export($unread, true),
            default => null,
        };
    },
];
