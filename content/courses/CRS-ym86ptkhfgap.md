---
id: CRS-ym86ptkhfgap
official_item: OIT-tw3xaqbz8xjy
title: "Assets management"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst"
    anchor: "asset"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/AssetExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/AssetExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "AssetExtension::getAssetUrl(), getAssetVersion()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Asset/PathPackage.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/PathPackage.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "PathPackage::getUrl()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Asset/VersionStrategy/StaticVersionStrategy.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/VersionStrategy/StaticVersionStrategy.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "StaticVersionStrategy::applyVersion()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Asset/VersionStrategy/JsonManifestVersionStrategy.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/VersionStrategy/JsonManifestVersionStrategy.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "JsonManifestVersionStrategy::getManifestPath()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "addAssetsSection()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/configuration/framework.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/configuration/framework.rst"
    anchor: "version_format"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "percentage signs must be doubled; packages inherit the default strategy"
    verified_at: "2026-11-03"
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

## Avant la règle : le problème à résoudre

Une boutique est servie sous `/shop`, pas à la racine du domain. Son logo est
`public/images/logo.png`. Écrit en dur, `/images/logo.png` pointe hors de
l'application ; et quand la feuille de style change, les navigateurs gardent
l'ancienne en cache. Les avatars, eux, viennent d'un CDN.

La function `asset()` règle ces trois problèmes **par configuration** : le
préfixe de base, la version, le package. Tous les faits de cette page ont été
exécutés dans un kernel Symfony 8.0.15 démarré — Asset 8.0.8, la dernière
version 8.0 du component —, la page étant servie sous `/shop`.

## Le fil rouge : la boutique sous /shop

```yaml
# config/packages/framework.yaml
framework:
    assets:
        version: 'v2'
        packages:
            avatars:
                base_urls: ['https://cdn.example.com']
```

```twig
<link rel="stylesheet" href="{{ asset('css/app.css') }}">   {# /shop/css/app.css?v2 #}
<img src="{{ asset('u/42.png', 'avatars') }}">              {# https://cdn.example.com/u/42.png?v2 #}
<meta property="og:image" content="{{ absolute_url(asset('images/logo.png')) }}">
```

Le path est relatif à `public/`, sans barre initiale ; la version s'ajoute
seule ; le package `avatars` change la base, et hérite de la version par
défaut.

## Le cycle complet en un schéma

Ce que fait `asset()` d'un path, lu dans `PathPackage` et rejoué :

```mermaid
---
title: Ce que asset() fait d'un path
---
flowchart TD
  accTitle: Le traitement d'un path par la function asset du component Asset 8.0
  accDescr: Le second argument choisit le package ; un nom inconnu lève une erreur. Une URL absolue, commençant par un schéma ou par deux barres obliques, est rendue inchangée, sans version ni préfixe. Sinon la strategy de version s'applique, version statique ou manifeste JSON ; un path absent du manifeste passe inchangé, sauf en mode strict, qui lève une erreur. Un résultat qui commence par une barre oblique est rendu tel quel, sans préfixe de base. Sinon un PathPackage le préfixe par le base path de la Request et du package, et un UrlPackage par sa base URL.
  A["asset(path, package)"] --> PK{"package connu ?"}
  PK -->|"non"| E1(["erreur : There is no asset package"])
  PK -->|"oui"| ABS{"https://… ou //… ?"}
  ABS -->|"oui"| SAME(["rendu inchangé : ni version ni préfixe"])
  ABS -->|"non"| VER["version : ?v2, ou le path du manifeste"]
  VER --> MAN{"absent du manifeste ?"}
  MAN -->|"oui, strict_mode"| E2(["AssetNotFoundException"])
  MAN -->|"non, ou pas strict"| SL{"commence par / ?"}
  SL -->|"oui"| NOBASE(["rendu tel quel : pas de préfixe de base"])
  SL -->|"non"| BASE(["préfixé : /shop/… ou https://cdn/…"])
```

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

**Rejoué**, la page étant servie sous `/shop` :

| Appel | Résultat |
|---|---|
| `asset('images/logo.png')` | `/shop/images/logo.png` |
| `asset('/images/logo.png')` | `/images/logo.png` — le préfixe est perdu |
| `absolute_url(asset('images/logo.png'))` | `http://example.com/shop/images/logo.png` |
| le même, sans Request — une command | `http://localhost/images/logo.png` |

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

**Rejoué** avec `version: 'v2'` : `css/app.css` donne `/shop/css/app.css?v2`,
`/css/app.css` donne `/css/app.css?v2`, et `https://cdn.example.com/app.js`
comme `//cdn.example.com/app.js` ressortent inchangés. Avec `base_path:
'/static'`, `images/logo.png` donne `/shop/static/images/logo.png` : la base du
package s'ajoute à celle de la Request. Déclarer `base_path` et `base_urls` sur
un même package empêche le kernel de démarrer : « An asset package cannot have
base URLs and base paths. »

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

**Rejoué :**

| Configuration | Résultat |
|---|---|
| `version: 'v2'` et `json_manifest_path` | refusée : « You cannot use both "version" and "json_manifest_path" at the same time under "assets". » |
| `version_format: '%s?version=%s'` | le kernel ne démarre pas : `%s?version=%` est lu comme un parameter |
| `version_format: '%%s?version=%%s'` | `/shop/css/app.css?version=v2` |
| `version_format: 'v/%%2$s/%%1$s'` | `/shop/v/v2/css/app.css` |
| manifeste, `build/app.js` | `/shop/build/app.3f2a1c.js` ; `asset_version()` rend `build/app.3f2a1c.js` |
| manifeste, `build/missing.js` | `/shop/build/missing.js`, sans erreur |
| manifeste, `strict_mode: true`, `build/ap.js` | « Asset "build/ap.js" not found in manifest "…". Did you mean one of these? "build/app.js", "build/app.css". » |

La documentation de `version_format` le dit : tous les `%` du format se
**doublent**, sinon ils sont pris pour des parameters du container.

## Les packages

`framework.assets.packages` déclare des packages nommés, chacun avec sa base et
sa version. Le second argument de `asset()` choisit le package :

```html
{{ asset('logo.png', 'images') }}
```

Sans second argument, c'est le package par défaut.

Pour une URL absolue — un e-mail, un tag de partage —, on combine avec
`absolute_url()` : `absolute_url(asset('images/logo.png'))`.

**Rejoué** avec une version par défaut `v2` :

| Package | `asset('…', package)` |
|---|---|
| `avatars`, `base_urls: ['https://cdn.example.com']` | `https://cdn.example.com/u/42.png?v2` — la version par défaut est héritée |
| le même, avec `'/u/42.png'` | identique : `UrlPackage` gère la barre |
| `images`, `base_path: '/img'`, `version: 'i9'` | `/shop/img/logo.png?i9` |
| `foo`, `version: ~` | `/shop/f/a.png`, sans version |
| un nom non déclaré, `'nope'` | erreur : « There is no "nope" asset package. » |

## Concrètement dans ton application

- Toute ressource de `public/` : `{{ asset('…') }}`, sans barre initiale.
- Un cache à casser à chaque deployment : `version`, ou le manifeste de l'outil
  de build ; une seule strategy.
- Un format de version personnalisé : chaque `%` doublé.
- Un CDN pour une partie des files : un package à `base_urls`, appelé par le
  second argument.
- Un lien dans un email : `absolute_url(asset(…))` — hors Request, le host est
  celui du contexte du router, `localhost` par défaut.

## À comprendre

- **Pourquoi une barre initiale fait-elle perdre le préfixe ?** `PathPackage`
  traite un path qui commence par `/` comme déjà complet ; il ne peut pas savoir
  qu'il manque `/shop`.
- **Pourquoi une URL absolue n'est-elle pas versionnée ?** Elle pointe peut-être
  ailleurs que chez soi ; la version ne concerne que ses propres files.
- **Pourquoi doubler les `%` ?** La configuration Symfony lit `%nom%` comme un
  parameter du container avant même que le component Asset ne voie le format.

## À mémoriser

- **`asset(path, packageName = null)`**, path relatif à `public/`.
- **Barre initiale** : version gardée, **préfixe perdu**.
- **URL absolue** : rendue **inchangée**.
- **`version`, `version_strategy`, `json_manifest_path`** : un seul.
- **`version_format`** : `%%s?%%s`, les `%` doublés.
- Manifeste : absent → inchangé ; **`strict_mode`** → exception avec suggestions.
- Un **package** hérite de la version par défaut ; `version: ~` l'en retire.
- Moyen mnémotechnique : **« pas de barre, pas de dur, un seul versionnage »**.

## Pièges d'examen

**Une barre en tête supprime le préfixe de base**, pas la version.

**Une URL absolue traverse `asset()` sans version.**

**`version`, `version_strategy` et `json_manifest_path` ne se combinent pas.**

**Absent du manifeste, un path passe inchangé**, sauf en mode strict.

**Les outils de construction d'assets sont hors périmètre** de l'examen.

## Application : trois scénarios

**Scénario 1 — Les images cassées en préproduction.** En local, servi à la
racine, tout marche ; en préproduction, sous `/shop`, les images pointent vers
`/images/…`. *Solution.* Les templates écrivent `asset('/images/…')` : retirer
la barre initiale, pour que le préfixe de base s'applique.

**Scénario 2 — Le deployment qui ne démarre plus.** Après l'ajout de
`version_format: '%s?version=%s'`, le kernel lève une
`ParameterNotFoundException`. *Solution.* Doubler les pourcents :
`'%%s?version=%%s'`.

**Scénario 3 — Le file oublié du build.** Un template référence
`build/chekout.js`, mal orthographié ; la page charge un 404 sans erreur.
*Solution.* `strict_mode: true` sur le manifeste : le rendu échoue et propose
les noms proches.

## QCM d'entraînement

Cinq questions sur les assets. Choisis avant d'ouvrir la correction.

**Question 1.** La configuration contient `version: 'v2'` et
`version_format: '%s?version=%s'`, écrit tel quel. Que se passe-t-il ?

- **A.** Les URLs prennent la forme `css/app.css?version=v2`
- **B.** La version est ignorée, le format étant invalide
- **C.** Le kernel ne démarre pas
- **D.** Le format est accepté, mais seulement en `prod`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Rejoué : `%s?version=%` est lu comme un parameter du
container, et la `ParameterNotFoundException` arrête le chargement. La
documentation demande de doubler chaque `%`.

A est le résultat avec `'%%s?version=%%s'`. B et D sont faux.

</details>

**Question 2.** La version par défaut est `v2`. Le package `avatars` ne déclare
que `base_urls: ['https://cdn.example.com']`. Que rend
`{{ asset('u/42.png', 'avatars') }}` ?

- **A.** `https://cdn.example.com/u/42.png`, sans version
- **B.** `https://cdn.example.com/u/42.png?v2`
- **C.** `/shop/u/42.png?v2`
- **D.** Une erreur : le package n'a pas de version

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Rejoué : un package sans version propre hérite de la
strategy par défaut, comme la documentation l'indique ; `version: ~` l'en
retirerait.

A suppose qu'un package repart sans version. C ignore `base_urls`. D est faux.

</details>

**Question 3.** Le manifeste JSON contient `build/app.js` et `build/app.css`,
avec `strict_mode: true`. Que fait `{{ asset('build/ap.js') }}` ?

- **A.** Il rend `build/ap.js` tel quel, préfixé
- **B.** Il rend `build/app.js`, le nom le plus proche
- **C.** Il rend une string vide
- **D.** Il échoue, en proposant des noms proches

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Rejoué : « Asset "build/ap.js" not found in manifest …
Did you mean one of these? ». Sans `strict_mode`, A.

B suppose une correction automatique, qui n'existe pas. C est faux.

</details>

**Question 4.** Que fait `{{ asset('x.png', 'nope') }}` quand aucun package
`nope` n'est déclaré ?

- **A.** Il utilise le package par défaut
- **B.** Il échoue : package inconnu
- **C.** Il rend `nope/x.png`
- **D.** Il rend `x.png`, sans version ni préfixe de base

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Rejoué : le second argument doit nommer un package
déclaré sous `framework.assets.packages`.

A, C et D supposent un repli qui n'existe pas.

</details>

**Question 5.** Une command, sans Request, rend un email avec
`{{ absolute_url(asset('images/logo.png')) }}` ; la version est `v2`, aucun
`default_uri` n'est configuré. Que contient l'email ?

- **A.** `/images/logo.png?v2`
- **B.** Une erreur : aucune Request
- **C.** `http://localhost/images/logo.png?v2`
- **D.** `images/logo.png`, faute de Request pour la base

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Rejoué dans un kernel 8.0 sans Request : `asset()` rend
`/images/logo.png?v2`, puis `absolute_url()` prend le host du contexte du
router, `localhost` par défaut.

A est le résultat d'`asset()` seul. B et D sont faux.

</details>

## Fiche express

**Si tu dois retenir seulement 8 choses :**

- **`asset('images/logo.png')`** : relatif à `public/`, **sans barre**.
- **Barre initiale** : préfixe perdu, version gardée.
- **URL absolue** : inchangée.
- **Une seule** strategy : `version`, `version_strategy` ou
  `json_manifest_path`.
- **`version_format`** : chaque `%` **doublé**.
- Manifeste : absent → inchangé ; **`strict_mode`** → erreur avec suggestions.
- Second argument : le **package** ; il hérite de la version par défaut.
- **`absolute_url(asset(…))`** pour un email — `localhost` sans Request.

**En une phrase, pour tout reconstruire :**

```text
asset(path relatif à public/, package) ; URL absolue inchangée ; version puis base ; / en tête = pas de base ;
une seule strategy ; %% dans version_format ; manifeste strict = erreur ; package hérite de la version
```

## Sources officielles

- [Symfony Twig Reference, `asset` et `asset_version`](https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst)
- [Symfony, configuration `framework.assets`](https://github.com/symfony/symfony-docs/blob/8.0/reference/configuration/framework.rst)
- [Asset 8.0, `PathPackage`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/PathPackage.php), [`StaticVersionStrategy`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/VersionStrategy/StaticVersionStrategy.php) et [`JsonManifestVersionStrategy`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Asset/VersionStrategy/JsonManifestVersionStrategy.php)
- [FrameworkBundle 8.0, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php)
