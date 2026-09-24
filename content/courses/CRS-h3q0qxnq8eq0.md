---
id: CRS-h3q0qxnq8eq0
official_item: OIT-5g82spham3vm
title: "HTTP methods matching"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
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
---

## Objectif

Restreindre une route à certaines méthodes HTTP, et contourner la limite des
formulaires HTML.

## L'option `methods`

**Par défaut une route accepte tous les verbes.** `methods` restreint :

```php
#[Route('/api/posts/{id}', methods: ['GET', 'HEAD'])]
public function show(int $id): Response {}

#[Route('/api/posts/{id}', methods: ['PUT'])]
public function edit(int $id): Response {}
```

Deux routes peuvent donc partager exactement le même chemin et ne se distinguer
que par la méthode — c'est le motif REST habituel.

Écrire `HEAD` est d'ailleurs superflu : le matcher traite une requête `HEAD`
comme un `GET` quand il compare les méthodes, donc `methods: ['GET']` accepte
aussi `HEAD`.

## La limite des formulaires HTML

Un formulaire HTML ne sait envoyer que `GET` et `POST`. Pour atteindre une route
en `PUT`, `PATCH` ou `DELETE` depuis un formulaire, on ajoute un champ caché
nommé `_method` :

```html
<input type="hidden" name="_method" value="PUT">
```

Symfony ne lit ce champ que si l'option `framework.http_method_override` vaut
`true` ; elle vaut `false` par défaut. Le thème de formulaire Twig ajoute le
champ caché dès que la méthode du formulaire n'est ni `GET` ni `POST`, que
l'option soit activée ou non.

## Ce que fait `Request::getMethod()` (8.0)

- Rien n'est remplacé si la méthode réelle n'est pas `POST`.
- L'en-tête `X-HTTP-METHOD-OVERRIDE` est lu **en premier**, et il ne dépend pas
  de `http_method_override` : cette option ne gouverne que le paramètre
  `_method`.
- Le paramètre `_method` est cherché dans le corps, puis dans la chaîne de
  requête.
- Un remplacement vers `GET`, `HEAD`, `CONNECT` ou `TRACE` est ignoré.
- Un nom qui contient autre chose que des lettres majuscules lève une
  `SuspiciousOperationException`.

`framework.allowed_http_method_override` restreint les méthodes simulables :
`null` (le défaut) les autorise toutes, une liste les limite, et un tableau vide
**désactive tout remplacement**, en-tête compris. La configuration refuse d'y
inscrire `GET`, `HEAD`, `CONNECT` ou `TRACE`.

## Pièges d'examen

**Sans restriction de méthode, une route accepte tous les verbes.** L'absence de
l'option n'est pas un `GET` implicite.

**Le champ caché ne suffit pas.** Simuler un verbe par `_method` exige
`http_method_override: true` ; désactivée — ce qui est le cas par défaut — le
champ est ignoré et la requête reste un `POST`.

**L'en-tête, lui, n'attend pas cette option.** Seul
`allowed_http_method_override: []` le neutralise.

**`methods: ['GET']` accepte `HEAD`.**

**Deux routes peuvent partager exactement le même chemin** et ne différer que
par la méthode : c'est le motif REST, et il rend la lecture de la liste des
routes indispensable.

## Points clés

- Sans `methods`, une route accepte **tous** les verbes ; `GET` inclut `HEAD`.
- `_method` en champ caché contourne la limite des formulaires HTML, si
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
