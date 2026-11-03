---
id: CRS-dpbpbgwjey5c
official_item: OIT-yh6zx9shv9vs
title: "URLs generation"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst"
    anchor: "path"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/RoutingExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/RoutingExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "RoutingExtension::getPath(), getUrl(), isUrlGenerationSafe()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/HttpFoundationExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/HttpFoundationExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "absolute_url, relative_path"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/UrlHelper.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/UrlHelper.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "UrlHelper::getAbsoluteUrl()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "UrlGenerator::doGenerate(), scheme and host widening"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "Generating URLs in Commands"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "default_uri; http://localhost/ without it"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-eqkt5vg15zqf: "Deux functions"
  OUT-5g7zrabez2b4: "Deux functions"
  OUT-b6ffjb05rkmr: "Jamais d'URL écrite à la main"
---

## Objectif

Générer une URL depuis un template. Le generator lui-même et ses quatre types de
référence sont traités dans le lot Routing.

## Avant la règle : le problème à résoudre

Un blog affiche des liens vers ses articles : dans le menu, dans un flux RSS,
dans l'email de la newsletter. Écrire `/blog/mon-article` à la main marche le
premier jour ; le jour où la route gagne un préfixe `/fr`, chaque lien casse
sans qu'un test ne le voie. Et l'email exige une URL complète, avec le host,
quand le menu se contente d'un path.

Twig répond avec **deux functions sur un route name** — `path()` et `url()` —
et **deux functions sur un path** — `absolute_url()` et `relative_path()`.
Tous les faits de cette page ont été exécutés avec Symfony 8.0.15 et Twig
3.22.2, depuis la page `http://example.com/products/hover-board`.

## Le fil rouge : les liens d'un article

```twig
{# templates/blog/show.html.twig #}
<a href="{{ path('blog_show', {slug: post.slug}) }}">Lien interne</a>
<link rel="canonical" href="{{ url('blog_show', {slug: post.slug}) }}">
<meta property="og:image" content="{{ absolute_url(asset('images/' ~ post.cover)) }}">
<a href="{{ path('blog_list', {page: 2, tag: 'symfony'}) }}">Page suivante</a>
```

Le lien du menu prend un path ; le canonical et l'image de partage, lus par
d'autres sites, prennent une URL complète ; le dernier lien montre un
parameter que la route ne déclare pas, `tag`, qui part en query string.

## Le cycle complet en un schéma

Quelle function prendre, et ce que le generator peut décider à sa place :

```mermaid
---
title: Générer une URL depuis un template Twig
---
flowchart TD
  accTitle: Les functions de génération d'URL du Twig Bridge et ce que le generator décide
  accDescr: Avec un route name, path rend un path absolu, ou relatif avec true en troisième argument, et url rend une URL absolue, ou sans schéma avec true. Le generator élargit la référence quand la route exige un autre schéma, ce qui donne une URL absolue, ou un autre host, ce qui donne une référence commençant par deux barres obliques. Avec un path au lieu d'un route name, absolute_url ajoute le schéma et le host, et relative_path rend un path relatif à la page courante, mais laisse inchangée une URL complète.
  START{"tu as un route name ?"} -->|"oui"| WHICH{"path() ou url() ?"}
  WHICH -->|"path()"| P["/blog/x — ou ../blog/x avec true"]
  WHICH -->|"url()"| U["http://host/blog/x — ou //host/blog/x avec true"]
  P --> GEN{"la route exige un autre schéma ou un autre host ?"}
  U --> GEN
  GEN -->|"schéma"| ABS(["URL absolue, même par path()"])
  GEN -->|"host"| NET(["//autre-host/…"])
  GEN -->|"non"| OK(["la référence demandée"])
  START -->|"non : un path"| PATHFN{"absolute_url() ou relative_path() ?"}
  PATHFN -->|"absolute_url()"| AU(["schéma + host + path"])
  PATHFN -->|"relative_path()"| RP{"path ou URL complète ?"}
  RP -->|"/human.txt"| REL(["../human.txt"])
  RP -->|"http://…"| SAME(["rendue telle quelle"])
```

## Deux functions

```html
<a href="{{ path('blog_show', {slug: post.slug}) }}">Lire</a>
<a href="{{ url('blog_show', {slug: post.slug}) }}">Lien absolu</a>
```

| Appel | Type de référence | Résultat |
|---|---|---|
| `path(nom, params)` | `ABSOLUTE_PATH` | `/blog/mon-article` |
| `path(nom, params, true)` | `RELATIVE_PATH` | `blog/mon-article`, relatif à la page courante |
| `url(nom, params)` | `ABSOLUTE_URL` | `https://example.com/blog/mon-article` |
| `url(nom, params, true)` | `NETWORK_PATH` | `//example.com/blog/mon-article` |

Les deux premiers arguments sont communs : le nom de la route, puis un array
de parameters. Le **troisième diffère** : `relative` pour `path()`,
`schemeRelative` pour `url()`. `RoutingExtension` (Twig Bridge 8.0) se borne à
traduire ce bool en type de référence avant d'appeler le generator.

**Rejoué** depuis `/products/hover-board`, route `post` sur `/post/{slug}` :

| Appel | Résultat |
|---|---|
| `path('post', {slug: 'mon-article'})` | `/post/mon-article` |
| `path('post', {slug: 'mon-article'}, true)` | `../post/mon-article` |
| `url('post', {slug: 'mon-article'})` | `http://example.com/post/mon-article` |
| `url('post', {slug: 'mon-article'}, true)` | `//example.com/post/mon-article` |

Le path relatif part du **dossier** de la page courante : depuis
`/products/hover-board`, il remonte d'un niveau. Les named arguments marchent
aussi : `path(name: 'post', parameters: {…}, relative: true)`.

## Ce que le generator décide seul

Le comportement du generator ne change pas parce qu'on l'appelle depuis un
template. `UrlGenerator` peut **élargir** le type demandé :

- une route dont le schéma exigé diffère de celui de la Request courante rend
  une URL absolue, même par `path()` ;
- une route liée à un autre host rend une référence `//hôte/chemin`.

Un parameter absent de la route devient une query string, et une route
inconnue lève `RouteNotFoundException`.

**Rejoué**, la page étant servie en `http` sur `example.com` :

| Route | `path()` rend |
|---|---|
| `/sec`, `schemes: [https]` | `https://example.com/sec` |
| `/o`, `host: api.example.com` | `//api.example.com/o` |
| `post` avec un `ref` en plus | `/post/x?ref=nl` |
| `/blog/{page}`, `page` à 1 par défaut, appelée avec `page: 1` | `/blog` |

Une route inconnue, ou un parameter obligatoire oublié, arrive dans le
template emballé : une `Twig\Error\RuntimeError` dont `getPrevious()` est la
`RouteNotFoundException` ou la `MissingMandatoryParametersException`.

Sans Request — dans une command ou un worker qui envoie un email — `url()` ne
connaît que le contexte du router, dont FrameworkBundle fixe le host à
`localhost` par défaut. La documentation du routing le dit, et c'est rejoué
dans un kernel 8.0 démarré sans Request : `url('post', {slug: 'z'})` rend
`http://localhost/post/z`. Avec `framework.router.default_uri:
'https://example.org/my/path/'`, le même appel rend
`https://example.org/my/path/post/z`.

## Deux functions voisines, sans route

`HttpFoundationExtension` ajoute deux functions qui prennent un **path**, pas
un route name :

- `absolute_url('/images/logo.png')` rend l'URL absolue du path, sur le host
  courant — la documentation la combine avec `asset()` ;
- `relative_path('/human.txt')` rend le path relatif à la page courante, ici
  `../human.txt` depuis `/products/hover-board`.

Sans Request disponible, `absolute_url()` se replie sur le contexte du router,
et rend le path inchangé si aucun host n'y est défini.

**Rejoué**, et lu dans `UrlHelper` (HttpFoundation 8.0) :

| Appel | Résultat |
|---|---|
| `absolute_url('/images/logo.png')` | `http://example.com/images/logo.png` |
| `absolute_url('images/logo.png')` | `http://example.com/products/images/logo.png` |
| `absolute_url('#top')` | l'URI courante, query comprise, suivie de `#top` |
| `absolute_url('?page=2')` | le path courant suivi de `?page=2` — l'ancienne query est remplacée |
| `relative_path('/products/other')` | `other` |
| `relative_path('http://example.com/human.txt')` | `http://example.com/human.txt`, **inchangée** |

Le dernier cas contredit l'exemple de la documentation de référence, qui
annonce `../human.txt`. Le code 8.0 tranche : `getRelativePath()` rend tel
quel tout argument qui contient `://` ou commence par `//`. Pour obtenir
`../human.txt`, on passe le path, `/human.txt`.

## L'escaping

`path()` et `url()` déclarent un `is_safe_callback` : leur output n'est
dispensé d'escaping HTML que si l'appel n'a pas de parameters, ou un seul
parameter à valeur littérale. Sinon il est échappé comme toute valeur — le
`&` d'une query string devient `&amp;`, ce qui est correct dans un
attribute HTML.

**Rejoué :** `path('post', {slug: 'x', ref: 'nl', utm: 'a'})` écrit
`/post/x?ref=nl&amp;utm=a` dans le source HTML ; le navigateur lit bien
`&utm=a`. Et une valeur contenant `&` ou une espace est encodée par le
generator — `a%26b%20c` — avant même l'escaping.

## Jamais d'URL écrite à la main

Une URL ne s'écrit jamais à la main dans un template : changer le path d'une
route mettrait alors les liens en défaut sans qu'aucun test ne le signale.

Le generator fait plus que concaténer : il applique le schéma et le host exigés
par la route, retire un parameter égal à sa default value, encode les valeurs
et refuse une route inconnue. Une string écrite à la main ne fait rien de cela.

## Concrètement dans ton application

- Un lien de navigation : `path()`.
- Un canonical, un lien dans un email, un tag de partage : `url()`, ou
  `absolute_url(asset(…))` pour un file public.
- Un email envoyé hors d'une Request : `framework.router.default_uri` —
  sans lui, `url()` rend une URL en `http://localhost/…`.
- Un parameter de filter ou de tracking : un parameter en plus, qui part en
  query string — attention aux fautes de frappe, elles ne lèvent rien.
- Un path relatif : `path(…, true)` pour une route, `relative_path('/…')` pour
  un path — jamais une URL complète.

## À comprendre

- **Pourquoi `path()` peut-il rendre une URL complète ?** Un path seul
  garderait le schéma courant ; si la route exige `https`, il faut l'écrire.
- **Pourquoi l'output est-il parfois échappé ?** Twig ne peut prouver qu'il est
  sûr que si les parameters sont connus à la compilation.
- **Pourquoi `relative_path()` ignore-t-il une URL complète ?** Elle peut
  pointer vers un autre host ; le code ne la touche pas, quel que soit son
  host.

## À mémoriser

- **`path()`** → `/post/x` ; **`url()`** → `http://host/post/x`.
- Troisième argument **`true`** : `../post/x` pour `path()`, `//host/post/x`
  pour `url()`.
- Autre **schéma** exigé → URL absolue ; autre **host** → `//host/…`.
- Parameter en plus → **query string** ; route inconnue → `RuntimeError`
  autour de `RouteNotFoundException`.
- **`absolute_url()`** et **`relative_path()`** prennent un **path**.
- `relative_path('http://…')` → **inchangée**.
- Moyen mnémotechnique : **« path pour la page, url pour l'extérieur, et
  jamais à la main »**.

## Pièges d'examen

**Le troisième argument n'a pas le même sens dans les deux functions** : path
relatif pour l'une, URL sans schéma pour l'autre.

**La function de path peut retourner une URL absolue**, quand la route exige
un autre schéma — ou une référence `//hôte/…` quand elle est liée à un autre
host.

**Un parameter absent de la route part en query string** — une faute de
frappe ne produit aucune erreur.

**`absolute_url()` prend un path, pas un route name.**

**`relative_path()` laisse une URL complète inchangée** : seul un path,
`/human.txt`, devient relatif — contrairement à l'exemple de la documentation
de référence.

## Application : trois scénarios

**Scénario 1 — Le lien mort de la newsletter.** Une command envoie la
newsletter ; les liens de l'email pointent vers `http://localhost/blog/…`.
*Solution.* Hors Request, `url()` ne connaît que le contexte du router, dont le
host par défaut est `localhost` : configurer `framework.router.default_uri`.

**Scénario 2 — Le lien vers l'API.** Un `path('api_status')` rend
`//api.example.com/status` au lieu d'un path. *Solution.* Rien n'est cassé :
la route est liée à un autre host, et un path seul pointerait vers le mauvais
site.

**Scénario 3 — Le path relatif qui ne l'est pas.** Un template écrit
`relative_path(url('download'))` et obtient l'URL complète. *Solution.*
`relative_path()` ne touche pas une URL complète : écrire
`path('download', {}, true)`.

## QCM d'entraînement

Cinq questions sur la génération d'URL. Choisis avant d'ouvrir la correction.

**Question 1.** Depuis `http://example.com/products/hover-board`, que rend
`{{ relative_path('http://example.com/human.txt') }}` sur Symfony 8.0 ?

- **A.** `../human.txt`
- **B.** `/human.txt`, le path extrait de l'URL
- **C.** `http://example.com/human.txt`
- **D.** `human.txt`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Rejoué avec HttpFoundation 8.0.15 :
`UrlHelper::getRelativePath()` rend tel quel tout argument qui contient `://`.

A est l'exemple de la documentation, que le code ne suit pas ; on l'obtient en
passant `/human.txt`. B et D sont faux.

</details>

**Question 2.** La route `post` est `/post/{slug}`. Que contient le **source
HTML** produit par `{{ path('post', {slug: 'x', ref: 'nl', utm: 'a'}) }}` ?

- **A.** `/post/x?ref=nl&utm=a`
- **B.** `/post/x?ref=nl&amp;utm=a`
- **C.** `/post/x`
- **D.** `/post/x/nl/a`, les valeurs ajoutées au path

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Rejoué : avec plus d'un parameter, l'`is_safe_callback`
de `RoutingExtension` ne dispense plus l'output d'escaping, et `&` devient
`&amp;` — ce que le navigateur relit comme `&`.

A est ce que le navigateur interprète, pas le source. C et D sont faux.

</details>

**Question 3.** La page est servie sur `example.com`. La route `status` est
liée au host `api.example.com`. Que rend `{{ path('status') }}` ?

- **A.** `/status`
- **B.** `http://api.example.com/status`
- **C.** Une erreur : host différent
- **D.** `//api.example.com/status`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Rejoué : le generator élargit la référence en
`NETWORK_PATH`, car un path seul pointerait vers `example.com`.

B serait le résultat d'un schéma différent, pas d'un host différent. A et C
sont faux.

</details>

**Question 4.** Depuis `/products/hover-board`, que rend
`{{ path('post', {slug: 'mon-article'}, true) }}` ?

- **A.** `../post/mon-article`
- **B.** `post/mon-article`
- **C.** `//example.com/post/mon-article`
- **D.** `/post/mon-article`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Rejoué : le path relatif part du dossier de la page
courante, `/products/`, et doit en remonter.

B vaudrait depuis une page à la racine. C est le `true` d'`url()`. D est
`path()` sans troisième argument.

</details>

**Question 5.** Une command Symfony 8.0, sans Request, rend un template
d'email avec `{{ url('post', {slug: 'z'}) }}`. `default_uri` n'est pas
configuré. Que contient l'email ?

- **A.** Une erreur : aucune Request à lire
- **B.** `/post/z`, sans host
- **C.** `//post/z`
- **D.** `http://localhost/post/z`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Rejoué dans un kernel 8.0 démarré sans Request :
FrameworkBundle donne au contexte du router le host `localhost`, ce que la
documentation du routing annonce. `framework.router.default_uri` le corrige.

A, B et C supposent un contexte vide, que FrameworkBundle ne laisse pas.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **`path()`** : path absolu ; **`url()`** : URL complète.
- **`true`** en troisième argument : relatif pour `path()`, sans schéma pour
  `url()`.
- **Autre schéma** → URL absolue ; **autre host** → `//host/…`.
- Parameter en plus → **query string**, sans erreur.
- Plus d'un parameter, ou une valeur variable → output **échappé** (`&amp;`).
- **`absolute_url()` / `relative_path()`** : un path ; `relative_path()` laisse
  une **URL complète inchangée**.
- Hors Request, **`url()`** rend **`http://localhost/…`** sans
  `framework.router.default_uri`.

**En une phrase, pour tout reconstruire :**

```text
path = /x, url = http://host/x ; true = relatif / sans schéma ; schéma ≠ → absolue, host ≠ → //host ;
extra → query ; >1 param → &amp; ; absolute_url/relative_path sur un path, URL complète inchangée
```

## Sources officielles

- [Symfony Twig Reference, `path`, `url`, `absolute_url`, `relative_path`](https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst)
- [Twig Bridge 8.0, `RoutingExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/RoutingExtension.php) et [`HttpFoundationExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/HttpFoundationExtension.php)
- [Symfony Routing, générer des URL dans une command](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [HttpFoundation 8.0, `UrlHelper`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/UrlHelper.php)
- [Routing 8.0, `UrlGenerator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php)
