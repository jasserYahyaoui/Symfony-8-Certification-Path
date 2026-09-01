---
id: CRS-222wq64mqxye
official_item: OIT-d3nyk9z0q2pd
title: "HTTP request"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "public bag properties lines 94-130, getClientIp line 821"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/InputBag.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "InputBag::get, BadRequestException on non-scalar"
    verified_at: "2026-09-01"
---

## Objectif

Choisir le bon sac de la `Request` et connaître la contrainte que `InputBag`
impose — c'est là que se situe le piège.

## Les sacs

`Request` expose sept propriétés publiques typées, chacune un sac spécialisé :

| Propriété | Type | Contenu |
|---|---|---|
| `$query` | `InputBag` | Paramètres d'URL (`$_GET`) |
| `$request` | `InputBag` | Corps de formulaire (`$_POST`) |
| `$cookies` | `InputBag` | Cookies |
| `$attributes` | `ParameterBag` | Données internes à l'application (route, `_route`, `_controller`) |
| `$headers` | `HeaderBag` | En-têtes |
| `$server` | `ServerBag` | `$_SERVER` |
| `$files` | `FileBag` | Fichiers téléversés |

Le nom `$request` désigne le **corps POST**, pas la requête entière : c'est une
source de confusion classique.

## InputBag n'accepte que des scalaires

C'est la différence de fond avec `ParameterBag`.

```php
// URL : /search?tags[]=php&tags[]=http
$request->query->get('tags');   // BadRequestException : valeur non scalaire
$request->query->all('tags');   // ['php', 'http'] — correct
```

`InputBag::get()` lève une `BadRequestException` si la valeur n'est pas un
scalaire. La raison est défensive : les données utilisateur sont hostiles, et un
tableau reçu là où un scalaire est attendu doit échouer bruyamment plutôt que de
se propager. Pour une valeur multiple, `all()` est l'accesseur prévu.

`$attributes` est un `ParameterBag` sans cette restriction, parce que son
contenu vient de l'application, pas du client.

## Corps brut et méthode

```php
$request->getContent();          // corps brut, utile pour du JSON
$request->getPayload();          // ParameterBag depuis JSON ou form-data
$request->isMethod('POST');      // comparaison insensible à la casse
$request->getMethod();           // méthode effective
```

## Client et proxys

```php
$request->getClientIp();
```

Derrière un reverse proxy, cette valeur n'est fiable **que si** les proxys de
confiance sont déclarés (`Request::setTrustedProxies()` ou la configuration
`trusted_proxies` du framework). Sans cela, Symfony ignore délibérément
`X-Forwarded-For`, car un client peut le falsifier.

## Pièges d'examen

**`$request->request` n'est pas la requête** : c'est le corps POST.

**`get()` sur un tableau lève.** Ce n'est pas `null`, ni le premier élément.

**`getClientIp()` sans trusted proxies renvoie l'IP du proxy**, pas celle du
client — et c'est le comportement sûr.

## Points clés

- Sept sacs typés ; `$request` = corps POST, `$attributes` = données internes.
- `InputBag::get()` exige un scalaire et lève sinon ; `all()` pour un tableau.
- `getPayload()` lit JSON comme form-data.
- `getClientIp()` n'est fiable qu'avec des trusted proxies déclarés.

## Sources officielles

- `Symfony\Component\HttpFoundation\Request`, `InputBag` (branche 8.0, `6f841c0`)
