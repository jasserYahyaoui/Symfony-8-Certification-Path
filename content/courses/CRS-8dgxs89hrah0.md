---
id: CRS-8dgxs89hrah0
official_item: OIT-8sr74a2wnb3r
title: "Router debugging"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-27"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "debugging-routes"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Debugging Routes — "lists all your application routes in the same order in which Symfony evaluates them"; --show-aliases, --show-controllers, --method=GET, --method=ANY; router:match; Route priority — "Routes with higher priority are sorted before routes with lower priority"'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterDebugCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterDebugCommand.php"
    symbol_or_lines: "configure() — argument name; options show-controllers, show-aliases, format (default txt), raw, method; execute(); findRouteNameContaining() — stripos"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterMatchCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterMatchCommand.php"
    symbol_or_lines: "configure() — argument path_info; options method, scheme, host; execute() — almost matches, verbose, debug:router on success, return 1"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Console/Descriptor/Descriptor.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Console/Descriptor/Descriptor.php"
    symbol_or_lines: "filterRoutesByHttpMethod() — a route without methods is kept"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Matcher/TraceableUrlMatcher.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/TraceableUrlMatcher.php"
    symbol_or_lines: "ROUTE_DOES_NOT_MATCH, ROUTE_ALMOST_MATCHES, ROUTE_MATCHES"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/RouteCollection.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCollection.php"
    symbol_or_lines: "all() — sorted by priority, then by insertion order"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-10-27"
outcome_sections:
  OUT-ef2j62bxp9fd: "`debug:router` : la liste, dans l'ordre d'évaluation"
  OUT-qnzwvqtmb7v5: "Les options de `debug:router`"
  OUT-w0yjsy54z7s9: "`router:match` : quelle route répondrait"
---

## Objectif

Savoir diagnostiquer un problème de routage avec les deux commands prévues
pour cela : lire la liste des routes dans l'ordre où Symfony les essaie, en
filtrer ou en détailler une, et demander directement quelle route répondrait à
un path donné, avec sa method, son host et son schéma.

## L'essentiel

Deux commands de FrameworkBundle diagnostiquent le routage : **`debug:router`**
liste les routes **dans l'ordre où Symfony les essaie**, et **`router:match`**
rejoue un appel pour dire laquelle répond. La **première** route qui
correspond gagne ; une route peut correspondre par son path et être écartée
par sa method, son host ou son schéma.

## Le schéma

```text
appel : PUT https://api.example.com/api/posts/1
  → contexte : path info /api/posts/1 · method PUT · host · scheme
  → routes essayées une par une, dans l'ordre d'évaluation   ◄ debug:router
      ne correspond pas · correspond presque (contexte) · CORRESPOND → stop
  → controller de la route trouvée                             ◄ router:match
```

## L'exemple

| Nom | Path | Restrictions |
|---|---|---|
| `blog_list` | `/blog` | aucune |
| `blog_show` | `/blog/{slug}` | method GET |
| `blog_new` | `/blog/new` | methods GET et POST |
| `api_post_edit` | `/api/posts/{id}` | method PUT, host `api.example.com`, schéma `https` |

`/blog/{slug}` est déclaré **avant** `/blog/new` : en GET, `/blog/new` est servi
par `blog_show`, avec `$slug = 'new'`.

## `debug:router` : la liste, dans l'ordre d'évaluation

La command « lists all your application routes in the same order in which
Symfony evaluates them » — ni alphabétique ni par file. Sur l'exemple,
`blog_show` est listée au-dessus de `blog_new` : le bug se lit dans la liste.

- L'ordre est celui de la **déclaration**, puis la **`priority`** : « Routes
  with higher priority are sorted before routes with lower priority », défaut
  0. Une route déclarée en dernier avec `priority: 2` est listée et évaluée en
  premier.
- `ANY` veut dire « aucune restriction » ; les colonnes `Scheme` et `Host`
  n'apparaissent que si une route en restreint un.

## Les options de `debug:router`

| Écriture | Effet |
|---|---|
| `debug:router <nom>` | le **détail** d'une route : path, regex, host, schéma, methods, requirements, defaults, options |
| `--show-controllers` | une colonne `Controller` |
| `--show-aliases` | une colonne `Aliases` |
| `--method=GET` | les routes GET… **et celles sans restriction** |
| `--method=ANY` | **seulement** les routes sans restriction de method |
| `--format=json` | `txt` (défaut), `xml`, `json`, `md` ; sinon « Unsupported format "…" » |
| `--raw` | sortie brute |

L'argument peut être un nom **partiel**, casse ignorée : un seul résultat
affiche son détail ; plusieurs, un choix (ou la liste en non interactif) ;
aucun, « The route "…" does not exist. » et l'exit code 1.

## `router:match` : quelle route répondrait

- L'argument est un **path** (`path_info`), pas une URL : method, host et
  schéma passent par `--method`, `--host`, `--scheme`.
- Sans option : un **GET** sur **`localhost`**.
- `RouterMatchCommand` s'appuie sur `TraceableUrlMatcher` et ses trois niveaux
  de trace :

| Situation | Affichage |
|---|---|
| correspond presque | « Route "…" almost matches but … », avec la raison |
| correspond | « [OK] Route "…" matches », puis le détail ; exit code 0 |
| aucune route | « [ERROR] None of the routes match the path "…" » ; exit code **1** |
| `-v` | en plus, chaque route qui ne correspond pas, avec la raison |

`router:match /blog/new` répond `blog_show` : déclarer `blog_new` avant, ou
lui donner une `priority` plus haute.

## Pièges d'examen

**L'ordre de `debug:router` n'est pas alphabétique.** C'est l'ordre dans lequel
Symfony évalue les routes, et donc l'explication de « pourquoi est-ce l'autre
route qui répond ? ». Un énoncé qui parle de tri par nom ou par path se trompe.

**Une route paramétrée déclarée plus haut intercepte un path fixe.** Avec
`/blog/{slug}` avant `/blog/new`, le path `/blog/new` en GET est servi par la
route de l'article : la première correspondance gagne, même si une route plus
précise existe plus bas.

**La `priority` change l'ordre affiché.** Une route déclarée en dernier mais avec
`priority: 2` est listée et évaluée avant les routes de priority 0 ;
`debug:router` montre cet ordre réel, pas l'ordre des files.

**`--method=POST` n'affiche pas que les routes POST.** Les routes sans
restriction de method acceptent tout verbe et restent dans la liste filtrée ;
`--method=ANY`, à l'inverse, n'affiche que celles-là.

**`router:match` prend un path, pas une URL.** Host, schéma et method passent
par `--host`, `--scheme` et `--method`.

**Sans options, `router:match` teste un GET sur `localhost`.** Une route
restreinte à un host ou à une autre method y apparaît au mieux en « almost
matches », et la command finit en erreur.

**Aucune correspondance, c'est l'exit code 1.** Ni 0 avec une table vide, ni un
code calqué sur HTTP : un script peut s'appuyer dessus.

**Un nom partiel suffit à `debug:router`, et la casse est ignorée.** Avec
plusieurs résultats, la command demande de choisir, ou liste les routes en mode
non interactif.

## Application

- L'API répond 404 en test manuel, mais la route existe →
  `router:match /api/posts/1` montre « almost matches but host "localhost" » :
  rejouer avec `--host`, `--scheme` et `--method`.
- Un script de deployment doit vérifier une route → `router:match <path>` et
  son exit code, 0 ou 1.

## QCM d'entraînement

**Question 1.** Une application déclare, dans cet ordre, `shop_product` sur
`/shop/{ref}` puis `shop_cart` sur `/shop/cart`, sans priority. Que répond
`router:match /shop/cart` ?

- **A.** `shop_product`, évaluée en premier, et son path correspond
- **B.** `shop_cart`, parce qu'un path sans parameter est toujours préféré
- **C.** Une erreur : deux routes correspondent au même path
- **D.** `shop_cart`, parce que les routes sont triées par nom

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Le router s'arrête à la première route qui correspond, et
`/shop/{ref}` accepte `cart` ; aucune préférence automatique pour un path fixe.

</details>

**Question 2.** Quelle command liste uniquement les routes qui acceptent
n'importe quelle method HTTP ?

- **A.** `debug:router --method=GET`
- **B.** `debug:router --show-controllers`
- **C.** `debug:router --method=ANY`
- **D.** `router:match --method=ANY /`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `ANY` est une valeur spéciale ; `--method=GET` garde
aussi les routes sans restriction, et sa liste est donc plus large.

</details>

**Question 3.** Une route `admin_report` n'accepte que `POST` sur le host
`admin.example.org`. Que montre `router:match /admin/report`, sans option ?

- **A.** « [OK] Route "admin_report" matches », car le path correspond
- **B.** Une ligne « almost matches », puis une erreur et l'exit code 1
- **C.** Rien : `--host` est obligatoire dès qu'une route restreint un host
- **D.** La liste complète des routes, triée par host

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Sans option, la command teste un GET sur `localhost` :
le path correspond, mais le contexte écarte la route.

</details>

**Question 4.** Deux routes sont déclarées dans cet ordre : `doc_page` sur
`/doc/{page}`, puis `doc_index` sur `/doc/index` avec `priority: 5`. Dans quel
ordre `debug:router` les affiche-t-il ?

- **A.** `doc_page` puis `doc_index`, l'ordre des files
- **B.** Par ordre alphabétique, sans lien avec la priority
- **C.** `doc_page` seule : `doc_index` n'est jamais atteinte
- **D.** `doc_index` puis `doc_page`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** La priority la plus haute passe d'abord, et
`debug:router` affiche l'ordre réel d'évaluation, priority comprise.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **`debug:router`** : les routes dans l'**ordre d'évaluation**.
- Ordre : déclaration, puis **`priority`** (défaut 0) ; **la première qui
  correspond gagne**.
- **`--method=X`** garde aussi les routes ANY ; **`--method=ANY`** ne garde
  qu'elles.
- Nom **partiel**, casse ignorée.
- **`router:match`** : un **path** ; défaut **GET** sur **localhost**.
- « almost matches » + raison ; aucune route : **exit code 1**.

Moyen mnémotechnique : **« debug LISTE, match TESTE »**.

```text
ROUTES ESSAYÉES DANS L'ORDRE (déclaration, puis priority) → LA PREMIÈRE QUI CORRESPOND GAGNE
  debug:router  → montre cet ordre (filtres : --method, détail : <nom partiel>)
  router:match  → rejoue un path dans un contexte (--method --host --scheme)
                  → almost matches + raison · OK + détail · sinon exit 1
```

## Sources officielles

- [Routing, « Debugging Routes » et « Priority Parameter »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [FrameworkBundle 8.0, `RouterDebugCommand`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterDebugCommand.php)
- [FrameworkBundle 8.0, `RouterMatchCommand`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterMatchCommand.php)
- [FrameworkBundle 8.0, `Descriptor::filterRoutesByHttpMethod()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Console/Descriptor/Descriptor.php)
- [Routing 8.0, `TraceableUrlMatcher`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/TraceableUrlMatcher.php)
- [Routing 8.0, `RouteCollection::all()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCollection.php)

