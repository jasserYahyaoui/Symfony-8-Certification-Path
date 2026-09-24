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
---

## Objectif

Référencer une ressource publique depuis un gabarit, et savoir ce que le
composant Asset fait du chemin.

## Limite de périmètre

Les outils de construction d'assets — AssetMapper, Webpack Encore — sont
**exclus du périmètre de l'examen** (`docs/syllabus/exclusions.yml`). Ce qui
reste ici est la fonction Twig et la configuration du composant Asset.

## La fonction

```html
<img src="{{ asset('images/logo.png') }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
```

Sa signature est `asset(path, packageName = null)` (`AssetExtension`, Twig
Bridge 8.0). Le chemin est relatif à `public/`, sans barre oblique initiale.

Elle n'est pas cosmétique : elle applique le préfixe de base de l'application —
utile lorsque le site n'est pas servi à la racine du domaine, ce qui est le cas
de ce projet même — et la stratégie de version configurée.

## Ce que fait le paquet, dans l'ordre

Sans `base_urls`, le paquet est un `PathPackage` — `base_path` et `base_urls`
ne se combinent pas. Sa méthode `getUrl()` :

1. rend **inchangée** une URL absolue (`https://…`, `//…`) — ni préfixe ni
   version ;
2. applique la stratégie de version ;
3. rend le résultat tel quel s'il commence par `/` : **pas de préfixe de
   base** ;
4. sinon, préfixe le chemin par la base.

D'où le piège : `asset('/css/app.css')` garde la version mais perd le préfixe.
Un paquet à `base_urls` (`UrlPackage`) ajoute lui-même la barre, et n'a donc pas
ce problème.

## La version

Trois réglages s'excluent mutuellement dans `framework.assets` : `version`,
`version_strategy` et `json_manifest_path` — la configuration refuse d'en
combiner deux.

- `version: 'v2'` : `StaticVersionStrategy`, format par défaut `%s?%s` —
  `css/app.css?v2`, modifiable par `version_format`.
- `json_manifest_path` : le chemin est cherché dans un manifeste JSON. Absent du
  manifeste, il est rendu tel quel — sauf avec `strict_mode: true`, qui lève une
  `AssetNotFoundException` en proposant des alternatives.

`asset_version(path, packageName = null)` retourne la version appliquée. Le
versionnement sert à casser le cache du navigateur quand une ressource change ;
il est configuré, pas calculé dans le gabarit.

## Les paquets

`framework.assets.packages` déclare des paquets nommés, chacun avec sa base et
sa version. Le second argument de `asset()` choisit le paquet :

```html
{{ asset('logo.png', 'images') }}
```

Sans second argument, c'est le paquet par défaut.

Pour une URL absolue — un e-mail, une balise de partage —, on combine avec
`absolute_url()` : `absolute_url(asset('images/logo.png'))`.

## Pièges d'examen

**Une barre en tête supprime le préfixe de base**, pas la version.

**Une URL absolue traverse `asset()` sans version.**

**`version`, `version_strategy` et `json_manifest_path` ne se combinent pas.**

**Absent du manifeste, un chemin passe inchangé**, sauf en mode strict.

**Les outils de construction d'assets sont hors périmètre** de l'examen.

## Points clés

- Écrire `{{ asset('…') }}`, jamais un chemin en dur.
- Chemin relatif à `public/`, sans barre initiale.
- `asset()` applique la version puis le préfixe de base.
- Second argument facultatif : le nom du paquet.
- Version statique, stratégie ou manifeste — une seule à la fois.

## Sources officielles

- [Symfony Twig Reference, `asset` et `asset_version`](https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst)
- [Asset 8.0, `PathPackage`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/PathPackage.php), [`StaticVersionStrategy`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/VersionStrategy/StaticVersionStrategy.php) et [`JsonManifestVersionStrategy`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/VersionStrategy/JsonManifestVersionStrategy.php)
- [FrameworkBundle 8.0, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php)
