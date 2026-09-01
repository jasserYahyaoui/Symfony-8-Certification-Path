---
id: CRS-ym86ptkhfgap
official_item: OIT-tw3xaqbz8xjy
title: "Assets management"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst"
    anchor: "asset"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Référencer une ressource publique depuis un gabarit.

## Limite de périmètre

Les outils de construction d'assets — AssetMapper, Webpack Encore — sont
**exclus du périmètre de l'examen** (`docs/syllabus/exclusions.yml`). Ce qui
reste ici est la fonction Twig et la configuration du composant Asset.

## La fonction

```html
<img src="{{ asset('images/logo.png') }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
```

Sa signature est `asset(path, packageName = null)`. Le chemin est relatif à
`public/`, sans barre oblique initiale.

Elle n'est pas cosmétique : elle applique le préfixe de base de l'application —
utile lorsque le site n'est pas servi à la racine du domaine, ce qui est le cas
de ce projet même — et la stratégie de version configurée.

## Les paquets

`framework.assets` permet de déclarer plusieurs *paquets*, chacun avec son URL
de base et sa version. Le second argument de `asset()` choisit le paquet :

```html
{{ asset('logo.png', 'images') }}
```

Sans second argument, c'est le paquet par défaut.

## La version

`asset_version(path, packageName = null)` retourne la version courante d'un
paquet. Le versionnement sert à casser le cache du navigateur quand une
ressource change ; il est configuré, pas calculé dans le gabarit.

## Points clés

- Écrire `{{ asset('…') }}`, jamais un chemin en dur.
- Chemin relatif à `public/`, sans barre initiale.
- `asset()` applique le préfixe de base et la version configurée.
- Second argument facultatif : le nom du paquet.
- Les outils de *build* d'assets sont hors périmètre d'examen.

## Sources officielles

- [Symfony Twig Reference, `asset` et `asset_version`](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst)
