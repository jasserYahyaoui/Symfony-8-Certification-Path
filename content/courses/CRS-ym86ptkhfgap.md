---
id: CRS-ym86ptkhfgap
official_item: OIT-tw3xaqbz8xjy
title: "Assets management"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-24"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst"
    anchor: "asset"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/AssetExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/AssetExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "AssetExtension::getAssetUrl(), getAssetVersion()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Asset/PathPackage.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/PathPackage.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "PathPackage::getUrl()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Asset/VersionStrategy/StaticVersionStrategy.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/VersionStrategy/StaticVersionStrategy.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "StaticVersionStrategy::applyVersion()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Asset/VersionStrategy/JsonManifestVersionStrategy.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/VersionStrategy/JsonManifestVersionStrategy.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "JsonManifestVersionStrategy::getManifestPath()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "addAssetsSection()"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-pw46yjz3zfeh: "La function"
  OUT-1c5dnwnne01z: "La function"
  OUT-k668ynwpjgby: "Les packages"
---

## Objectif

Référencer une ressource publique depuis un template, et savoir ce que le
component Asset fait du path.

## Limite de périmètre

Les outils de construction d'assets — AssetMapper, Webpack Encore — sont
**exclus du périmètre de l'examen** (`docs/syllabus/exclusions.yml`). Ce qui
reste ici est la function Twig et la configuration du component Asset.

## La function

```html
<img src="{{ asset('images/logo.png') }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
```

Sa signature est `asset(path, packageName = null)` (`AssetExtension`, Twig
Bridge 8.0). Le path est relatif à `public/`, sans barre oblique initiale.

Elle n'est pas cosmétique : elle applique le préfixe de base de l'application —
utile lorsque le site n'est pas servi à la racine du domain, ce qui est le cas
de ce projet même — et la strategy de version configurée.

## Ce que fait le package, dans l'ordre

Sans `base_urls`, le package est un `PathPackage` — `base_path` et `base_urls`
ne se combinent pas. Sa method `getUrl()` :

1. rend **inchangée** une URL absolue (`https://…`, `//…`) — ni préfixe ni
   version ;
2. applique la strategy de version ;
3. rend le résultat tel quel s'il commence par `/` : **pas de préfixe de
   base** ;
4. sinon, préfixe le path par la base.

D'où le piège : `asset('/css/app.css')` garde la version mais perd le préfixe.
Un package à `base_urls` (`UrlPackage`) ajoute lui-même la barre, et n'a donc pas
ce problème.

## La version

Trois réglages s'excluent mutuellement dans `framework.assets` : `version`,
`version_strategy` et `json_manifest_path` — la configuration refuse d'en
combiner deux.

- `version: 'v2'` : `StaticVersionStrategy`, format par défaut `%s?%s` —
  `css/app.css?v2`, modifiable par `version_format`.
- `json_manifest_path` : le path est cherché dans un manifeste JSON. Absent du
  manifeste, il est rendu tel quel — sauf avec `strict_mode: true`, qui lève une
  `AssetNotFoundException` en proposant des alternatives.

`asset_version(path, packageName = null)` retourne la version appliquée. Le
versionnement sert à casser le cache du navigateur quand une ressource change ;
il est configuré, pas calculé dans le template.

## Les packages

`framework.assets.packages` déclare des packages nommés, chacun avec sa base et
sa version. Le second argument de `asset()` choisit le package :

```html
{{ asset('logo.png', 'images') }}
```

Sans second argument, c'est le package par défaut.

Pour une URL absolue — un e-mail, un tag de partage —, on combine avec
`absolute_url()` : `absolute_url(asset('images/logo.png'))`.

## Pièges d'examen

**Une barre en tête supprime le préfixe de base**, pas la version.

**Une URL absolue traverse `asset()` sans version.**

**`version`, `version_strategy` et `json_manifest_path` ne se combinent pas.**

**Absent du manifeste, un path passe inchangé**, sauf en mode strict.

**Les outils de construction d'assets sont hors périmètre** de l'examen.

## Points clés

- Écrire `{{ asset('…') }}`, jamais un path en dur.
- Path relatif à `public/`, sans barre initiale.
- `asset()` applique la version puis le préfixe de base.
- Second argument facultatif : le nom du package.
- Version statique, strategy ou manifeste — une seule à la fois.

## Sources officielles

- [Symfony Twig Reference, `asset` et `asset_version`](https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst)
- [Asset 8.0, `PathPackage`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/PathPackage.php), [`StaticVersionStrategy`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/VersionStrategy/StaticVersionStrategy.php) et [`JsonManifestVersionStrategy`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/VersionStrategy/JsonManifestVersionStrategy.php)
- [FrameworkBundle 8.0, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php)
