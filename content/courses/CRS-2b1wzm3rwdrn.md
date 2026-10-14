---
id: CRS-2b1wzm3rwdrn
official_item: OIT-21m4pmtymygn
title: "Domain name matching"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "sub-domain-routing"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/RouteCompiler.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCompiler.php"
    symbol_or_lines: "compilePattern"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    symbol_or_lines: "doGenerate"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Tests/Matcher/UrlMatcherTest.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Tests/Matcher/UrlMatcherTest.php"
    symbol_or_lines: "testHostIsCaseInsensitive"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Tests/Generator/UrlGeneratorTest.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Tests/Generator/UrlGeneratorTest.php"
    symbol_or_lines: "testWithHostDifferentFromContext, testWithHostSameAsContext"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-wtvymxrjxf80: "L'option `host`"
  OUT-kz98mvtwphqc: "L'option `host`"
  OUT-ac8d2dvvjma9: "L'hôte accepte des parameters"
---

## Objectif

Faire dépendre une route du nom d'hôte de la Request.

## L'option `host`

Elle exige que l'hôte HTTP de la Request entrante corresponde à une valeur :

```php
#[Route('/', name: 'mobile_homepage', host: 'm.example.com')]
#[Route('/', name: 'homepage')]
```

Deux routes peuvent ainsi partager le **même path** et ne se distinguer que
par l'hôte. Sans `host`, une route accepte n'importe quel hôte.

La comparaison **ignore la casse** : le matcher met l'hôte de la Request en
minuscules, et l'expression régulière compilée pour l'hôte porte le drapeau
`i`. Une constraint `EN|FR|DE` accepte donc `en.example.com`, et l'attribute
vaut alors `en` (test `testHostIsCaseInsensitive`).

## L'hôte accepte des parameters

C'est ce qui rend l'option utile aux applications multi-locataires : l'hôte se
parameter comme un path, avec default values et constraints.

```php
#[Route(
    '/',
    name: 'mobile_homepage',
    host: '{subdomain}.example.com',
    defaults: ['subdomain' => 'm'],
    requirements: ['subdomain' => 'm|mobile'],
)]
```

`defaults` et `requirements` sont les mêmes options que pour un parameter de
path : il n'y a pas de key séparée pour l'hôte. Le parameter apparié est
disponible comme n'importe quel autre, en attribute de Request et en argument de
controller.

## Une default value d'hôte ne rend rien facultatif

C'est la différence avec le path. Dans `RouteCompiler`, le calcul du premier
parameter facultatif n'est fait **que pour le path** : un parameter d'hôte est
toujours exigé à l'appariement. Avec la route ci-dessus, une Request vers
`example.com` ne correspond pas, malgré la default value `m`.

La default value sert à la **génération** : la documentation la justifie
ainsi, sinon il faudrait fournir `subdomain` à chaque URL générée.

## Générer vers un autre hôte

Quand l'hôte de la route diffère de celui de la Request courante,
`UrlGenerator` ne peut pas produire un simple path. Il bascule alors de
`ABSOLUTE_PATH`, le type par défaut, vers `NETWORK_PATH` :

| Request courante | `path('mobile_homepage')` |
|---|---|
| `m.example.com` | `/` |
| `www.example.com` | `//m.example.com/` |

Le résultat est une URL **relative au schéma** — sans `http:` ni `https:` — et
non une URL absolue. `url()` produit, lui, toujours l'URL absolue complète. La
constraint de l'hôte est vérifiée à la génération comme celle d'un parameter de
path.

## Pièges d'examen

**Sans constraint d'hôte, une route accepte n'importe quel hôte.** L'absence de
l'option n'est pas une restriction implicite au domaine principal.

**Une default value d'hôte ne sert qu'à la génération.** Contrairement au
path, elle ne rend pas le parameter facultatif à l'appariement.

**`path()` vers un autre hôte donne `//hôte/chemin`**, pas `https://hôte/chemin`.

**L'hôte ignore la casse**, constraint comprise.

**Deux routes peuvent partager le même path** et ne se distinguer que par
l'hôte : la route sans `host` doit donc venir après celle qui en a un, sinon
elle capte toutes les Requests.

## Points clés

- `host` contraint le nom d'hôte ; sans elle, tout hôte correspond.
- Comparaison insensible à la casse.
- Parameters d'hôte : `defaults` et `requirements` ordinaires, mais toujours
  exigés à l'appariement.
- Hôte différent de la Request courante : `path()` produit un `NETWORK_PATH`.

## Sources officielles

- [Routing, section « Sub-Domain Routing »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`RouteCompiler::compilePattern()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCompiler.php)
- [`UrlGenerator::doGenerate()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php)
- [`UrlMatcherTest`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Tests/Matcher/UrlMatcherTest.php) et [`UrlGeneratorTest`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Tests/Generator/UrlGeneratorTest.php)
