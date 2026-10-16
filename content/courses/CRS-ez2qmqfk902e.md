---
id: CRS-ez2qmqfk902e
official_item: OIT-9kh5cqz5qtrb
title: "Symfony HttpClient component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-16"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/http_client.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/http_client.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "section Processing Responses, asynchronous by default"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Contracts/HttpClient/HttpClientInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/HttpClient/HttpClientInterface.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "request line 85, stream line 93, withOptions line 98"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Contracts/HttpClient/Exception/HttpExceptionInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/HttpClient/Exception/HttpExceptionInterface.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "getResponse() ligne 23 ; interfaces voisines du repertoire Exception/"
    verified_at: "2026-09-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Contracts/HttpClient/ResponseInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/HttpClient/ResponseInterface.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "@throws de getStatusCode(), getHeaders(), getContent(), toArray()"
    verified_at: "2026-09-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpClient/RetryableHttpClient.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpClient/RetryableHttpClient.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "$maxRetries = 3 ; getDelayFromHeader() lit Retry-After ; passthru() une fois les retries épuisés"
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpClient/Retry/GenericRetryStrategy.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpClient/Retry/GenericRetryStrategy.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "DEFAULT_RETRY_STATUS_CODES, IDEMPOTENT_METHODS ; getDelay() : delayMs * multiplier ** retry_count"
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpClient/Response/TransportResponseTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpClient/Response/TransportResponseTrait.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "doDestruct() appelle checkStatusCode() ; stream() : « Ensure the HTTP status code is always checked »"
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpClient/Chunk/ErrorChunk.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpClient/Chunk/ErrorChunk.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "isFirst(), isLast(), getContent() lèvent ; isTimeout() ; __destruct()"
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/http_client.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/http_client.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "Retry Failed Requests ; Handling Exceptions ; Dealing with Network Timeouts ; Dealing with Network Errors"
    verified_at: "2026-10-16"
outcome_sections:
  OUT-9vyq57j7gj6b: "L'interface"
  OUT-81pzmq0sf6k9: "Le point central : les Responses sont lazy"
  OUT-t602yfbqy21b: "Le point central : les Responses sont lazy"
  OUT-9bcphcvp1dp0: "La Response"
  OUT-a0z9e1zqpyxy: "La hiérarchie d'exceptions"
  OUT-bhzv4dsk1jbr: "Une Response jamais lue lève quand même"
  OUT-5rm06598zbw5: "Rejouer les échecs : RetryableHttpClient"
  OUT-67xk171zj659: "Un stream dont une Response échoue"
---

## Objectif

Émettre une Request sortante avec `HttpClientInterface` et comprendre **quand**
elle s'exécute réellement — c'est tout l'enjeu de cet item.

## L'interface

```php
public function request(string $method, string $url, array $options = []): ResponseInterface;
public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface;
public function withOptions(array $options): static;
```

Trois methods, injectées par autowiring via `HttpClientInterface`.

## Le point central : les Responses sont lazy

**Le client est asynchrone par défaut.** `request()` rend la main avant que la
Response soit arrivée ; rien n'est attendu tant qu'on ne la consulte pas :

```php
$response = $client->request('GET', 'https://example.com/api');  // ne bloque pas

$status  = $response->getStatusCode();  // bloque ici
$content = $response->getContent();     // ou ici
$data    = $response->toArray();        // ou ici
```

D'où le parallélisme : lancer les Requests d'abord, les lire ensuite.

```php
$responses = [];
foreach ($urls as $url) {
    $responses[] = $client->request('GET', $url);   // toutes lancées en parallèle
}

foreach ($client->stream($responses) as $response => $chunk) {
    if ($chunk->isLast()) {
        // cette réponse est complète
    }
}
```

Lire chaque Response dans la loop qui l'émet rend l'ensemble séquentiel, sans
erreur visible.

## La Response

```php
$response->getStatusCode();
$response->getHeaders();      // lève sur 3xx/4xx/5xx, sauf getHeaders(false)
$response->getContent();      // idem
$response->toArray();         // décode le JSON ; lève si ce n'en est pas
$response->getInfo('debug');
$response->cancel();
```

`getHeaders()`, `getContent()` et `toArray()` **lèvent** sur un statut 3xx-5xx,
sauf avec `$throw = false`. **`getStatusCode()` ne lève pas** sur un statut :
son contrat ne déclare qu'une `TransportExceptionInterface`. C'est l'appel qui
inspecte un 404 sans rien attraper.

## La hiérarchie d'exceptions

Tout descend d'une seule interface, et la branche dit **où** la chose a échoué :

```text
ExceptionInterface
├── TransportExceptionInterface      le réseau — rien n'est arrivé
│   └── TimeoutExceptionInterface    délai d'inactivité dépassé
├── HttpExceptionInterface           une réponse est arrivée, son statut fâche
│   ├── ClientExceptionInterface     4xx
│   ├── ServerExceptionInterface     5xx
│   └── RedirectionExceptionInterface 3xx, une fois `max_redirects` atteint
└── DecodingExceptionInterface       le corps ne se décode pas
```

La ligne de partage est `HttpExceptionInterface` : elle seule expose
`getResponse()`, parce qu'elle seule a une Response à montrer. Un échec de
transport n'en a aucune.

Attraper `HttpExceptionInterface` couvre 3xx, 4xx et 5xx d'un coup ;
`TransportExceptionInterface` couvre le délai dépassé, dont
`TimeoutExceptionInterface` hérite.

## Options utiles

```php
$client->request('POST', $url, [
    'json' => ['name' => 'value'],      // encode et pose le Content-Type
    'headers' => ['Authorization' => 'Bearer '.$token],
    'query' => ['page' => 2],
    'timeout' => 5,
]);
```

`json` et `body` sont exclusifs. Un **client scopé** applique des options aux
URL d'un pattern.

## Tests

`MockHttpClient` remplace le transport sans réseau :

```php
$client = new MockHttpClient(new MockResponse('{"ok":true}'));
```

`timeout` est le délai **d'inactivité**, pas la durée totale, qui s'écrit
`max_duration` (`0`, illimité, par défaut). Le client suit les redirects
jusqu'à `max_redirects`, qui vaut `20`.

## Une Response jamais lue lève quand même

Si rien n'a lu le statut, le **destructor** de la Response lève sur un 3xx-5xx. Une Request non assignée — `$client->request('POST',
$url);` — devient donc synchrone et lève sur un 500. `getStatusCode()`,
`getHeaders(false)` ou `cancel()` désactivent ce filet ; `getInfo('http_code')`
ne le désactive pas — exécuté.

## Rejouer les échecs : RetryableHttpClient

| Échec | Rejoué |
|---|---|
| 423, 425, 429, 502, 503 | pour toute method |
| 500, 504, 507, 510, erreur de transport | pour les methods idempotentes seulement (`GET`, `PUT`, `DELETE`…) |
| autre statut, dont 404 | jamais |

Un échec de résolution DNS est rejoué pour toute method. Trois retries
par défaut (`max_retries`), donc quatre Requests au plus ; délai de 1 s doublé
à chaque essai, jitter de 10 % ; un header `Retry-After` remplace ce délai.
Les retries épuisés, la dernière Response est rendue telle quelle : un 503 dont
`getContent()` lève une `ServerException`. Dans une application : l'option
`retry_failed`.

## Un stream dont une Response échoue

Dans `stream()`, l'échec d'une Response est levé par les methods de **son
chunk** (`isFirst()`, `isLast()`, `getContent()`). Non attrapée, la
`TransportExceptionInterface` termine la loop pour toutes les Responses : le
`try`/`catch` va **dans** la loop. `getError()` lit le message sans
traiter l'erreur, et la loop lève quand même.

Le statut se lit au premier chunk : sans `getStatusCode()` à `isFirst()`, un
404 fait lever une `ClientException` au stream dès qu'il reprend.

Un `timeout` passé à `stream()` produit un chunk dont `isTimeout()` vaut
`true`, et ce stream s'arrête ; `isLast()` sur ce chunk lève une
`TimeoutException`. Un nouveau `stream()` reprend la Response.

## Pièges d'examen

**`toArray()` n'est pas `json_decode()`** : sur un body invalide, il lève une
`DecodingExceptionInterface`, pas une `ClientExceptionInterface`.

**Un POST en 500 n'est pas rejoué**, un POST en 503 l'est.

## Tips d'examen

**`$throw = false` ne désactive que le statut.** `toArray(false)` lève encore
sur un body indécodable, et une panne réseau lève toujours.

**Deux zéros, deux sens.** `max_duration = 0` veut dire illimité ;
`max_redirects = 0` veut dire aucune redirect suivie.

## Points clés

- `request()` est asynchrone ; émettre toutes les Requests, puis lire.
- Un statut jamais lu lève à la destruction de la Response.
- Dans `stream()`, attraper l'erreur dans la loop ; lire le statut au premier chunk.

## Aller lire la source

- [Component HttpClient](https://github.com/symfony/symfony-docs/blob/8.0/http_client.rst) — *Processing Responses*, Requests asynchrones
- [`HttpClientInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/HttpClient/HttpClientInterface.php) — `request()`, `stream()`,
  `withOptions()` (branche 8.0, `6f841c0`)
- [`HttpExceptionInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/HttpClient/Exception/HttpExceptionInterface.php) — `getResponse()`, et les interfaces
  voisines du même directory `Exception/` (branche 8.0)
- [`RetryableHttpClient`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpClient/RetryableHttpClient.php), [`GenericRetryStrategy`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpClient/Retry/GenericRetryStrategy.php)
- [`ResponseInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/HttpClient/ResponseInterface.php) — les `@throws` de `getStatusCode()`,
  `getHeaders()`, `getContent()` et `toArray()` (branche 8.0)
