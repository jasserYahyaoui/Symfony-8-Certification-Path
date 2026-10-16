<?php

declare(strict_types=1);

/*
 * Remediation P2.7 (2026-10-16): the multiple-answer HttpClient retry
 * question, one boolean per choice in content order — whether
 * RetryableHttpClient with its default strategy sends the request again.
 */

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;

return [
    // POST 503 | GET 500 | POST 500 | GET 404 — retried?
    'QST-57h88pmcmram' => static function (): array {
        $retried = static function (string $method, int $code): bool {
            $mock = new MockHttpClient(static fn () => new MockResponse('', ['http_code' => $code]));
            $strategy = new GenericRetryStrategy(delayMs: 0, jitter: 0); // the default status codes; no wait
            (new RetryableHttpClient($mock, $strategy))->request($method, 'https://x.test/')->getStatusCode();

            return $mock->getRequestsCount() > 1;
        };

        return [$retried('POST', 503), $retried('GET', 500), $retried('POST', 500), $retried('GET', 404)];
    },
];
