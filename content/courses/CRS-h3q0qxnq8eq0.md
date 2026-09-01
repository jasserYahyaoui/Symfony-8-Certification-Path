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
    anchor: "matching-http-methods"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
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

## La limite des formulaires HTML

Un formulaire HTML ne sait envoyer que `GET` et `POST`. Pour atteindre une route
en `PUT`, `PATCH` ou `DELETE` depuis un formulaire, on ajoute un champ caché
nommé `_method` :

```html
<input type="hidden" name="_method" value="PUT">
```

Symfony ne lit ce champ que si l'option `framework.http_method_override` vaut
`true`. Le composant Form pose le champ automatiquement quand c'est le cas.

Par sécurité, `framework.allowed_http_method_override` restreint les méthodes
qu'un client a le droit de simuler.

## Points clés

- Sans `methods`, une route accepte **tous** les verbes.
- Deux routes peuvent partager un chemin et différer par la méthode.
- `_method` en champ caché contourne la limite des formulaires HTML.
- Il faut `framework.http_method_override: true` ; la liste autorisée se
  restreint par `framework.allowed_http_method_override`.

## Sources officielles

- [Routing, section « Matching HTTP Methods »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst)
