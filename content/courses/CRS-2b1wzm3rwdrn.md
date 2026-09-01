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
    anchor: "sub-domain-routing"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Faire dépendre une route du nom d'hôte de la requête.

## L'option `host`

Elle exige que l'hôte HTTP de la requête entrante corresponde à une valeur :

```php
#[Route('/', name: 'mobile_homepage', host: 'm.example.com')]
#[Route('/', name: 'homepage')]
```

Deux routes peuvent ainsi partager le **même chemin** et ne se distinguer que
par l'hôte. Sans `host`, une route accepte n'importe quel hôte.

## L'hôte accepte des paramètres

C'est ce qui rend l'option utile aux applications multi-locataires : l'hôte se
paramètre comme un chemin, avec valeurs par défaut et contraintes.

```php
#[Route(
    '/',
    name: 'mobile_homepage',
    host: '{subdomain}.example.com',
    defaults: ['subdomain' => 'm'],
    requirements: ['subdomain' => 'm|mobile'],
)]
```

`defaults` et `requirements` sont les mêmes options que pour un paramètre de
chemin : il n'y a pas de mécanisme séparé pour l'hôte. Le paramètre apparié est
disponible comme n'importe quel autre.

## Points clés

- `host` contraint le nom d'hôte ; sans elle, tout hôte correspond.
- Deux routes peuvent partager un chemin et différer par l'hôte.
- L'hôte accepte des paramètres, avec `defaults` et `requirements` ordinaires.

## Sources officielles

- [Routing, section « Sub-Domain Routing »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst)
