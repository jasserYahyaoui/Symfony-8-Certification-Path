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
---

## Objectif

Émettre une requête sortante avec `HttpClientInterface` et comprendre **quand**
elle s'exécute réellement — c'est tout l'enjeu de cet item.

## L'interface

```php
public function request(string $method, string $url, array $options = []): ResponseInterface;
public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface;
public function withOptions(array $options): static;
```

Trois méthodes, injectées par autowiring via `HttpClientInterface`.

## Le point central : les réponses sont paresseuses

**Le client est asynchrone par défaut.** `request()` rend la main
immédiatement, avant que la réponse ne soit arrivée. Rien n'est attendu tant
qu'on ne consulte pas la réponse :

```php
$response = $client->request('GET', 'https://example.com/api');  // ne bloque pas

$status  = $response->getStatusCode();  // bloque ici
$content = $response->getContent();     // ou ici
$data    = $response->toArray();        // ou ici
```

C'est ce qui rend le parallélisme gratuit : lancer les requêtes d'abord, les
lire ensuite.

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

Inverser l'ordre — lire chaque réponse dans la même boucle qui l'émet — rend
l'ensemble séquentiel, sans aucune erreur visible.

## La réponse

```php
$response->getStatusCode();
$response->getHeaders();      // lève sur 3xx/4xx/5xx, sauf getHeaders(false)
$response->getContent();      // idem
$response->toArray();         // décode le JSON ; lève si ce n'en est pas
$response->getInfo('debug');
$response->cancel();
```

`getHeaders()`, `getContent()` et `toArray()` **lèvent** par défaut sur un
statut non réussi ; leur paramètre `$throw = false` le désactive.
**`getStatusCode()` ne lève pas** : sa signature ne porte aucun `$throw` et son
contrat ne déclare qu'une `TransportExceptionInterface` en cas d'erreur réseau.
C'est donc l'appel par lequel on inspecte un 404 sans rien attraper.

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
`getResponse()`, parce qu'elle seule a une réponse à montrer. Un échec de
transport n'en a aucune.

```php
try {
    $data = $client->request('GET', $url)->toArray();
} catch (ClientExceptionInterface $e) {
    $body = $e->getResponse()->getContent(false);   // le corps du 4xx
} catch (TransportExceptionInterface $e) {
    // pas de getResponse() ici : rien n'est revenu
}
```

Attraper `HttpExceptionInterface` couvre donc 3xx, 4xx et 5xx d'un coup, et
`TransportExceptionInterface` couvre le délai dépassé — `TimeoutExceptionInterface`
en hérite.

## Options utiles

```php
$client->request('POST', $url, [
    'json' => ['name' => 'value'],      // encode et pose le Content-Type
    'headers' => ['Authorization' => 'Bearer '.$token],
    'query' => ['page' => 2],
    'timeout' => 5,
]);
```

`json` et `body` sont exclusifs. Les **clients scopés** appliquent
automatiquement des options aux URL correspondant à un motif, ce qui évite de
répéter une base URL et un jeton.

## Tests

`MockHttpClient` remplace le transport sans réseau :

```php
$client = new MockHttpClient(new MockResponse('{"ok":true}'));
```

`timeout` est le délai **d'inactivité**, pas la durée totale : une requête qui
progresse lentement ne l'atteint jamais. La durée totale s'écrit
`max_duration`, qui vaut `0` — illimité — par défaut. Le client suit par
ailleurs les redirections, jusqu'à `max_redirects`, qui vaut `20`.

## Pièges d'examen

**`request()` ne déclenche pas l'attente.** L'erreur classique est de croire
que la requête est terminée à son retour.

**Lire dans la boucle d'émission supprime le parallélisme.** Le code
fonctionne, il est juste séquentiel.

**`getContent()` lève sur 404.** Il faut `getContent(false)` pour inspecter le
corps d'une erreur.

**`toArray()` n'est pas `json_decode()`** : il lève si la réponse n'est pas du
JSON valide — et l'exception est une `DecodingExceptionInterface`, pas une
`ClientExceptionInterface`.

**Seule `HttpExceptionInterface` porte `getResponse()`.** Sur une panne réseau
il n'y a pas de réponse à inspecter ; chercher `getResponse()` sur une
`TransportExceptionInterface` est l'erreur attendue.

## Points clés

- `request()` est asynchrone ; l'attente survient à la première lecture.
- Émettre toutes les requêtes, puis lire — sinon pas de parallélisme.
- Les accesseurs lèvent sur statut d'erreur, sauf avec `false`.
- `MockHttpClient` pour tester sans réseau.
- Deux branches d'exceptions : transport (rien n'est arrivé) et HTTP (une
  réponse est là) ; seule la seconde expose `getResponse()`.

## Aller lire la source

- [Composant HttpClient](https://github.com/symfony/symfony-docs/blob/8.0/http_client.rst) — *Processing Responses*, requêtes asynchrones
- [`HttpClientInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/HttpClient/HttpClientInterface.php) — `request()`, `stream()`,
  `withOptions()` (branche 8.0, `6f841c0`)
- [`HttpExceptionInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/HttpClient/Exception/HttpExceptionInterface.php) — `getResponse()`, et les interfaces
  voisines du même répertoire `Exception/` (branche 8.0)
- [`ResponseInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/HttpClient/ResponseInterface.php) — les `@throws` de `getStatusCode()`,
  `getHeaders()`, `getContent()` et `toArray()` (branche 8.0)
