---
id: CRS-h3q0qxnq8eq0
official_item: OIT-5g82spham3vm
title: "HTTP methods matching"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-08"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "matching-http-methods"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    symbol_or_lines: "getMethod"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    symbol_or_lines: "http_method_override, allowed_http_method_override"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig"
    symbol_or_lines: "form_start"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherTrait.php"
    symbol_or_lines: "doMatch"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-y5cganxr802r: "L'option `methods`"
  OUT-28bd4czsshkq: "L'option `methods`"
  OUT-1cfa6y0xkxt0: "La limite des forms HTML"
---

## Objectif

Restreindre une route à certaines methods HTTP, et contourner la limite des
forms HTML.

## L'option `methods`

**Par défaut une route accepte tous les verbes.** `methods` restreint :

```php
#[Route('/api/posts/{id}', methods: ['GET', 'HEAD'])]
public function show(int $id): Response {}

#[Route('/api/posts/{id}', methods: ['PUT'])]
public function edit(int $id): Response {}
```

Deux routes peuvent donc partager exactement le même path et ne se distinguer
que par la method — c'est le pattern REST habituel.

Écrire `HEAD` est d'ailleurs superflu : le matcher traite une Request `HEAD`
comme un `GET` quand il compare les methods, donc `methods: ['GET']` accepte
aussi `HEAD`.

## La limite des forms HTML

Un form HTML ne sait envoyer que `GET` et `POST`. Pour atteindre une route
en `PUT`, `PATCH` ou `DELETE` depuis un form, on ajoute un field caché
nommé `_method` :

```html
<input type="hidden" name="_method" value="PUT">
```

Symfony ne lit ce field que si l'option `framework.http_method_override` vaut
`true` ; elle vaut `false` par défaut. Le theme de form Twig ajoute le
field caché dès que la method du form n'est ni `GET` ni `POST`, que
l'option soit activée ou non.

## Ce que fait `Request::getMethod()` (8.0)

- Rien n'est remplacé si la method réelle n'est pas `POST`.
- Le header `X-HTTP-METHOD-OVERRIDE` est lu **en premier**, et il ne dépend pas
  de `http_method_override` : cette option ne gouverne que le parameter
  `_method`.
- Le parameter `_method` est cherché dans le body, puis dans la query
  string.
- Un remplacement vers `GET`, `HEAD`, `CONNECT` ou `TRACE` est ignoré.
- Le nom est d'abord mis en majuscules : `_method=put` est accepté et donne
  `PUT`. Seul un nom qui contient alors autre chose que des lettres — un
  chiffre, un tiret — lève une `SuspiciousOperationException` (exécuté).

`framework.allowed_http_method_override` restreint les methods simulables :
`null` (le défaut) les autorise toutes, une liste les limite, et un array vide
**désactive tout remplacement**, header compris. La configuration refuse d'y
inscrire `GET`, `HEAD`, `CONNECT` ou `TRACE`.

## Pièges d'examen

**Sans restriction de method, une route accepte tous les verbes.** L'absence de
l'option n'est pas un `GET` implicite.

**Le field caché ne suffit pas.** Simuler un verbe par `_method` exige
`http_method_override: true` ; désactivée — ce qui est le cas par défaut — le
field est ignoré et la Request reste un `POST`.

**Le header, lui, n'attend pas cette option.** Seul
`allowed_http_method_override: []` le neutralise.

**`methods: ['GET']` accepte `HEAD`.**

**Deux routes peuvent partager exactement le même path** et ne différer que
par la method : c'est le pattern REST, et il rend la lecture de la liste des
routes indispensable.

## Points clés

- Sans `methods`, une route accepte **tous** les verbes ; `GET` inclut `HEAD`.
- `_method` en field caché contourne la limite des forms HTML, si
  `framework.http_method_override: true`.
- Remplacement seulement depuis un `POST`, jamais vers `GET`, `HEAD`,
  `CONNECT` ou `TRACE`.
- `allowed_http_method_override` : `null` tout, liste restreinte, `[]` rien.

## Sources officielles

- [Routing, section « Matching HTTP Methods »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`Request::getMethod()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php)
- [FrameworkBundle, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php)
- [`form_div_layout.html.twig`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig)
- [`CompiledUrlMatcherTrait::doMatch()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherTrait.php)
