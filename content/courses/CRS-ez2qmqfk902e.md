---
id: CRS-ez2qmqfk902e
official_item: OIT-9kh5cqz5qtrb
title: "Symfony HttpClient component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/http_client.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "section Processing Responses, asynchronous by default"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Contracts/HttpClient/HttpClientInterface.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "request line 85, stream line 93, withOptions line 98"
    verified_at: "2026-09-01"
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

Par défaut ces méthodes **lèvent une exception** sur un statut non réussi. Le
paramètre `$throw = false` désactive ce comportement lorsqu'on veut inspecter
une réponse d'erreur.

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

## Pièges d'examen

**`request()` ne déclenche pas l'attente.** L'erreur classique est de croire
que la requête est terminée à son retour.

**Lire dans la boucle d'émission supprime le parallélisme.** Le code
fonctionne, il est juste séquentiel.

**`getContent()` lève sur 404.** Il faut `getContent(false)` pour inspecter le
corps d'une erreur.

**`toArray()` n'est pas `json_decode()`** : il lève si la réponse n'est pas du
JSON valide.

## Points clés

- `request()` est asynchrone ; l'attente survient à la première lecture.
- Émettre toutes les requêtes, puis lire — sinon pas de parallélisme.
- Les accesseurs lèvent sur statut d'erreur, sauf avec `false`.
- `MockHttpClient` pour tester sans réseau.

## Sources officielles

- `http_client.rst` (symfony-docs, branche 8.0, `eea05cb`)
- `Symfony\Contracts\HttpClient\HttpClientInterface` (branche 8.0, `6f841c0`)
