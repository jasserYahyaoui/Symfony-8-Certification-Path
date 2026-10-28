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

## Avant la règle : le problème à résoudre

Trois situations reviennent dans toute application, et à l'examen :

- **la mauvaise page s'affiche** : tu appelles `/blog/new`, et c'est la page
  d'un article qui répond, avec un article nommé « new » introuvable ;
- **une 404 inexplicable** : la route existe, tu la vois dans ton controller,
  et pourtant le path répond « No route found » ;
- **une 405** : la route existe, mais pas pour cette method HTTP.

Dans les trois cas, lire les attributes `#[Route]` un par un ne suffit pas : ce
qui compte, c'est l'ensemble des routes **tel que le router le voit**, dans
l'**ordre** où il les essaie, avec le **contexte** de l'appel (method, host,
schéma). Deux commands de FrameworkBundle répondent exactement à cela :
`debug:router` montre l'ensemble, `router:match` rejoue un appel.

## Le fil rouge : un blog et son API

Toute la page utilise ces quatre routes, déclarées dans cet ordre :

| Nom | Path | Restrictions |
|---|---|---|
| `blog_list` | `/blog` | aucune |
| `blog_show` | `/blog/{slug}` | method GET |
| `blog_new` | `/blog/new` | methods GET et POST |
| `api_post_edit` | `/api/posts/{id}` | method PUT, host `api.example.com`, schéma `https`, `id` numérique |

Regarde bien les lignes 2 et 3 : `/blog/{slug}` est déclaré **avant**
`/blog/new`. Le path `/blog/new` correspond aux deux. Garde ce détail en tête :
c'est le bug que les deux commands vont révéler.

## Le cycle complet en un schéma

```text
APPEL HTTP        PUT https://api.example.com/api/posts/1
                    │
                    ▼
CONTEXTE          path info : /api/posts/1
                  method PUT · host api.example.com · scheme https
                    │
                    ▼
LES ROUTES, UNE PAR UNE, DANS L'ORDRE D'ÉVALUATION   ◄── ce que montre debug:router
  1. blog_list       path différent            → ne correspond pas
  2. blog_show       path différent            → ne correspond pas
  3. blog_new        path différent            → ne correspond pas
  4. api_post_edit   path, host, scheme, method → CORRESPOND : on s'arrête
                    │
                    ▼
CONTROLLER        celui d'api_post_edit
                                                      ◄── ce que rejoue router:match
```

Deux idées tiennent tout le chapitre :

1. les routes sont essayées **dans un ordre**, et la **première** qui
   correspond gagne ;
2. une route peut correspondre par son **path** et être écartée par son
   **contexte** (method, host, schéma, condition) : elle « correspond presque ».

## `debug:router` : la liste, dans l'ordre d'évaluation

```bash
php bin/console debug:router
```

La documentation le dit en une phrase : la command « lists all your
application routes in the same order in which Symfony evaluates them ».
Exécuté sur le fil rouge (FrameworkBundle 8.0.15) :

```text
  Name            Method     Scheme   Host              Path
  blog_list       ANY        ANY      ANY               /blog
  blog_show       GET        ANY      ANY               /blog/{slug}
  blog_new        GET|POST   ANY      ANY               /blog/new
  api_post_edit   PUT        https    api.example.com   /api/posts/{id}
```

Ce n'est **pas** un ordre alphabétique (`api_post_edit` serait premier), ni un
classement par file : c'est l'ordre dans lequel le router va essayer les
routes. Et c'est ce qui en fait un outil de **diagnostic** : `blog_show` est
au-dessus de `blog_new`, donc pour le path `/blog/new`, `blog_show` passera
d'abord. Le bug du fil rouge se lit dans la liste.

Les colonnes `Scheme` et `Host` n'apparaissent que si au moins une route en
restreint un (lu dans `TextDescriptor` ; exécuté : sans la route d'API, la
table n'a que `Name`, `Method` et `Path`). `ANY` veut dire « aucune
restriction ».

### D'où vient l'ordre : la déclaration, puis la `priority`

Par défaut, l'ordre est celui de la **déclaration**. Une route peut passer
devant les autres avec le parameter `priority`, que la documentation décrit
ainsi : « Routes with higher priority are sorted before routes with lower
priority. The default value when it is not defined is 0. »

```php
class BlogController
{
    #[Route('/blog/{slug}', name: 'blog_show')]
    public function show(string $slug): Response { /* … */ }

    #[Route('/blog/list', name: 'blog_list', priority: 2)]
    public function list(): Response { /* … */ }
}
```

Exécuté : bien que déclarée **après**, `blog_list` apparaît **en premier**
dans `debug:router`, et `router:match /blog/list` répond `blog_list`. Lu dans
`RouteCollection::all()` : tri par priority décroissante, puis par ordre
d'insertion à priority égale. `debug:router` affiche donc toujours l'ordre
**réel**, priority comprise.

## Les options de `debug:router`

Lues dans `RouterDebugCommand::configure()` (8.0), et chacune exécutée :

| Écriture | Effet |
|---|---|
| `debug:router` | toutes les routes, dans l'ordre d'évaluation |
| `debug:router <nom>` | le **détail** d'une route : path, path regex, host, schéma, method, requirements, class, defaults (dont le controller), options |
| `--show-controllers` | ajoute une colonne `Controller` à la liste |
| `--show-aliases` | ajoute une colonne `Aliases` : les alias de route qui pointent vers chaque route |
| `--method=GET` | ne garde que les routes qui acceptent GET… **et celles sans restriction** |
| `--method=ANY` | ne garde que les routes **sans** restriction de method |
| `--format=json` | change le format : `txt` (défaut), `xml`, `json`, `md` ; tout autre valeur échoue (« Unsupported format "…" ») |
| `--raw` | sortie brute, sans mise en forme |

### `--method` : le filter qui garde les routes « ANY »

Le filter est lu dans `Descriptor::filterRoutesByHttpMethod()` : une route
n'est retirée que si elle **a** des methods **et** que la method demandée n'en
fait pas partie. Une route sans restriction accepte tous les verbes, elle reste
donc. Exécuté : `--method=post` (la valeur est mise en majuscules) garde
`blog_list` (ANY) et `blog_new` (GET|POST), et retire `blog_show` (GET) et
`api_post_edit` (PUT).

La documentation signale la valeur spéciale **`ANY`** : elle n'affiche que les
routes qui acceptent n'importe quelle method. Exécuté : seules les routes sans
restriction restent.

### L'argument : un nom exact… ou partiel

Lu dans `RouterDebugCommand::execute()` et exécuté :

| Ce que tu passes | Ce que fait la command |
|---|---|
| un nom **exact** (`blog_show`) | affiche le détail de cette route |
| un **morceau** de nom, qui ne correspond qu'à une route (`show`) | affiche le détail de cette route (en mode interactif, après te l'avoir proposée comme choix par défaut) |
| un morceau qui correspond à **plusieurs** routes (`BLOG`) | en mode interactif, te demande de choisir ; en mode non interactif, affiche la liste des routes trouvées |
| un nom qui ne correspond à rien (`nope`) | erreur « The route "nope" does not exist. », exit code 1 |

La recherche partielle **ignore la casse** (`stripos`) : `BLOG` trouve
`blog_list`, `blog_show` et `blog_new`. La documentation le résume : « Pass the
name (or part of the name) of some route ».

## `router:match` : quelle route répondrait

`debug:router` montre la liste ; il faut encore la lire et en déduire
laquelle répond. `router:match` **pose directement la question** : pour ce path, et ce
contexte, quelle route répond ? La documentation : « It's useful to find out
why some URL is not executing the controller action that you expect ».

```bash
php bin/console router:match /blog/new
php bin/console router:match /api/posts/1 --method=PUT --host=api.example.com --scheme=https
php bin/console router:match /nothing -v
```

### L'argument : un path, pas une URL

L'argument s'appelle `path_info` : c'est le **path**, sans schéma ni host
ni query string. Le reste du contexte passe par trois options, qui modifient le
contexte du router avant l'appariement : `--method`, `--host`, `--scheme`.

Sans ces options, le contexte par défaut s'applique. Exécuté : la method vaut
**GET** et le host vaut **`localhost`**. C'est un piège concret : une route
restreinte à un host ou à POST ne répondra jamais à un `router:match` sans
option.

### Ce que la command affiche

Lu dans `RouterMatchCommand::execute()`, qui s'appuie sur `TraceableUrlMatcher`
et ses trois niveaux de trace (ne correspond pas, correspond presque,
correspond) :

| Situation | Affichage |
|---|---|
| une route **correspond presque** | « Route "…" almost matches but … », avec la raison : requirement, host, schéma, method ou condition |
| une route **correspond** | « [OK] Route "…" matches », puis le détail de la route (la command relance `debug:router` sur son nom) ; exit code 0 |
| **aucune** route ne correspond | « [ERROR] None of the routes match the path "…" » ; exit code **1** |
| avec `-v` (verbeux) | en plus, chaque route qui **ne** correspond **pas**, avec la raison |

Les traces s'arrêtent à la première route qui correspond : les routes suivantes
ne sont même pas essayées, exactement comme pendant un vrai appel.

### Le fil rouge, rejoué

Exécuté avec FrameworkBundle 8.0.15 :

```text
router:match /blog/new
  [OK] Route "blog_show" matches                 ← le bug : /blog/{slug} passe avant /blog/new

router:match /blog/new --method=POST
  Route "blog_show" almost matches but method "POST" does not match any of the required methods (GET)
  [OK] Route "blog_new" matches                  ← en POST, blog_show est écartée

router:match /api/posts/1
  Route "api_post_edit" almost matches but host "localhost" does not match the requirement ("api.example.com")
  [ERROR] None of the routes match the path "/api/posts/1"        (exit code 1)

router:match /api/posts/1 --method=PUT --host=api.example.com --scheme=https
  [OK] Route "api_post_edit" matches

router:match /nothing -v
  Route "blog_list" does not match: Path "/blog" does not match
  … une ligne par route …
  [ERROR] None of the routes match the path "/nothing"
```

Le premier résultat est le diagnostic de la page : `/blog/new` en GET exécute
le controller d'un article. Deux corrections possibles : déclarer `blog_new`
**avant** `blog_show`, ou lui donner une `priority` plus haute (on peut aussi
restreindre `slug` par un requirement qui exclut `new`).

## Concrètement dans ton application

| Symptôme | Command | Ce que tu cherches |
|---|---|---|
| « No route found » sur un path que tu crois déclaré | `debug:router` (avec un morceau du nom) | la route est-elle **chargée** ? son path est-il celui que tu crois ? |
| la mauvaise action s'exécute | `router:match <path>` | quelle route répond, et quelle route **plus haute** l'intercepte |
| 405, ou route restreinte | `router:match <path> --method=… --host=… --scheme=…` | la ligne « almost matches » et sa raison |
| savoir quel controller sert une route | `debug:router --show-controllers` ou `debug:router <nom>` | la colonne `Controller`, ou `_controller` dans les defaults |
| un script de deployment doit vérifier une route | `router:match <path>` | l'exit code : 0 si une route répond, 1 sinon |

Pour corriger un ordre, deux leviers : l'ordre de déclaration (dans un même
controller, l'ordre des methods ; entre files, l'ordre des imports) et la
`priority` de la route.

## À comprendre

- **Pourquoi l'ordre compte-t-il ?** Parce que le router s'arrête à la première
  route qui correspond. Deux routes dont les paths se recouvrent ne sont pas une
  erreur : c'est l'ordre qui décide, et `debug:router` le montre tel quel.
- **Pourquoi deux commands ?** Parce qu'elles répondent à deux questions :
  « quelles routes existent, et dans quel ordre ? » et « pour cet appel précis,
  laquelle répond ? ». La seconde évite de faire l'appariement de tête.
- **Pourquoi un path et des options, plutôt qu'une URL ?** Parce que le router
  apparie un *path info* dans un *contexte* : method, host, schéma ne font pas
  partie du path, ce sont des conditions séparées, et chacune peut écarter une
  route.
- **Pourquoi « almost matches » est-il précieux ?** Parce qu'il dit **pourquoi**
  la route attendue n'a pas répondu : le path était bon, mais la method, le host,
  le schéma, un requirement ou une condition l'a écartée.

## À mémoriser

- `debug:router` : routes **dans l'ordre d'évaluation** (déclaration, puis
  `priority`, défaut 0, la plus haute d'abord).
- Options : `--show-controllers`, `--show-aliases`, `--method`, `--format`
  (`txt` par défaut, `xml`, `json`, `md`), `--raw`.
- `--method=X` garde aussi les routes **ANY** ; `--method=ANY` ne garde **que**
  celles-là.
- Argument de `debug:router` : nom exact ou **partiel**, casse ignorée ;
  plusieurs résultats → choix, ou liste en non interactif ; aucun → « does not
  exist », exit code 1.
- `router:match <path>` : un **path**, pas une URL ; `--method`, `--host`,
  `--scheme` ; par défaut **GET** sur **`localhost`**.
- Sortie : « almost matches but … », « [OK] … matches » + détail, `-v` pour
  les non-correspondances ; **exit code 1** si rien ne répond.
- Moyen mnémotechnique : **« debug LISTE, match TESTE »**.

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

## Application : quatre scénarios

**Scénario 1 — La page « nouvel article » affiche un article introuvable.**
`GET /blog/new` exécute `show()` avec `$slug = 'new'`.

*Diagnostic.* `router:match /blog/new` répond `blog_show` : `/blog/{slug}` est
évalué avant `/blog/new`. *Correction.* Déclarer `blog_new` avant `blog_show`,
ou lui donner `priority: 1`.

**Scénario 2 — L'API répond 404 en test manuel, mais la route existe.**

*Diagnostic.* `router:match /api/posts/1` affiche « almost matches but host
"localhost" does not match the requirement ». *Correction.* Rejouer avec le bon
contexte : `--host=api.example.com --scheme=https --method=PUT`. Si cela
correspond, la route est bonne ; c'est l'appel qui n'avait pas le bon host.

**Scénario 3 — Retrouver vite une route dont on connaît un morceau du nom.**

*Solution.* `php bin/console debug:router edit` : la recherche partielle trouve
`api_post_edit` ; si plusieurs routes contiennent `edit`, la command propose de
choisir.

**Scénario 4 — Un contrôle de deployment.** Le script doit échouer si la route
de paiement n'est plus servie.

*Solution.* `php bin/console router:match /checkout/pay --method=POST` : exit
code 0 si une route répond, 1 sinon. Le script teste le code de retour.

## QCM d'entraînement

Six questions sur les commands de routage ; décide avant d'ouvrir chaque
correction.

**Question 1.** Une application déclare, dans cet ordre, `shop_product` sur `/shop/{ref}` puis `shop_cart` sur `/shop/cart`, sans priority. Que répond `router:match /shop/cart` ?

- **A.** `shop_cart`, parce qu'un path sans parameter est toujours préféré
- **B.** `shop_product`, parce qu'elle est évaluée en premier et que son path correspond
- **C.** Une erreur de routage, parce que deux routes correspondent au même path et que Symfony refuse l'ambiguïté
- **D.** `shop_cart`, parce que `debug:router` trie les routes par nom

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le router essaie les routes dans l'ordre d'évaluation et
s'arrête à la première qui correspond ; `/shop/{ref}` accepte `cart` comme
valeur de `ref`.

A est le piège : il n'existe aucune préférence automatique pour les paths fixes,
c'est l'ordre (déclaration, puis priority) qui décide. C invente une détection
de conflit : deux routes qui se recouvrent sont légales. D confond l'ordre
d'affichage, qui est l'ordre d'évaluation, avec un tri par nom.

</details>

**Question 2.** Quelle command liste uniquement les routes qui acceptent n'importe quelle method HTTP ?

- **A.** `debug:router --method=GET`
- **B.** `debug:router --show-controllers`
- **C.** `debug:router --method=ANY`
- **D.** `router:match --method=ANY /`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La documentation signale la valeur spéciale `ANY` ;
exécuté, seules les routes sans restriction de method restent.

A est le piège : `--method=GET` garde les routes GET **et** les routes sans
restriction, la liste est donc plus large. B ajoute une colonne sans rien
filtrer. D teste un appel sur le path `/`, il ne liste rien.

</details>

**Question 3.** Une route `admin_report` n'accepte que `POST` sur le host `admin.example.org`. Que montre `php bin/console router:match /admin/report`, sans option ?

- **A.** « [OK] Route "admin_report" matches », car le path correspond
- **B.** Une ligne « almost matches » pour `admin_report`, puis une erreur et l'exit code 1
- **C.** Rien : `router:match` exige l'option `--host` dès qu'une des routes de l'application en restreint un
- **D.** La liste complète des routes, triée par host

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Sans option, le contexte par défaut s'applique : GET sur
`localhost` (exécuté). La route correspond par son path mais est écartée par
son contexte : « almost matches but … », puis « None of the routes match », et
l'exit code 1.

A oublie que method et host font partie de l'appariement. C invente une option
obligatoire : seul `path_info` est requis. D décrit `debug:router`, et encore,
sans tri par host.

</details>

**Question 4.** `debug:router USER` est lancé en mode non interactif. Aucune route ne s'appelle exactement ainsi, mais `user_edit` et `admin_user_list` existent. Que se passe-t-il ?

- **A.** La command liste les deux routes, car la recherche partielle ignore la casse
- **B.** La command répond « The route "USER" does not exist. »
- **C.** La command affiche le détail de `admin_user_list`, première par ordre alphabétique
- **D.** La command échoue, car un route name doit être en minuscules

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Sans nom exact, la command cherche les routes dont le nom
**contient** l'argument, sans tenir compte de la casse (`stripos`). Avec
plusieurs résultats et sans interaction, elle les liste ; en interactif, elle
demanderait de choisir.

B est le piège : l'erreur n'arrive que si **aucune** route ne contient
l'argument. C invente un choix automatique : avec un seul résultat, elle
afficherait son détail, avec plusieurs, elle n'en choisit aucun. D invente une
règle de casse.

</details>

**Question 5.** Un script de deployment exécute `router:match /legacy/export` et aucune route ne correspond. Quel exit code reçoit-il ?

- **A.** 0, avec une table vide, puisque la command a bien pu lire toutes les routes
- **B.** 404, comme le status code HTTP correspondant
- **C.** 1, après le message « None of the routes match the path »
- **D.** 2, le code réservé aux arguments invalides

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Lu dans `RouterMatchCommand::execute()` et exécuté :
`return 1` quand aucune trace n'est une correspondance.

A est le piège d'un outil de liste : `router:match` n'est pas une liste, il
échoue. B mélange code HTTP et exit code de process, qui ne dépasse pas 255 et
n'a rien à voir avec HTTP. D invente une convention que la command n'utilise
pas.

</details>

**Question 6.** Deux routes sont déclarées dans cet ordre : `doc_page` sur `/doc/{page}`, puis `doc_index` sur `/doc/index` avec `priority: 5`. Dans quel ordre `debug:router` les affiche-t-il ?

- **A.** `doc_page` puis `doc_index`, l'ordre des files
- **B.** `doc_index` puis `doc_page`, car la priority la plus haute passe d'abord
- **C.** Par ordre alphabétique : `doc_index` puis `doc_page`, sans lien avec la priority
- **D.** `doc_page` seule, car `doc_index` n'est jamais atteinte et n'est pas listée

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La documentation : « Routes with higher priority are
sorted before routes with lower priority », et le défaut est 0.
`debug:router` affiche l'ordre réel d'évaluation (exécuté).

A est le piège : sans priority, ce serait vrai ; avec `priority: 5`, l'ordre de
déclaration ne suffit plus. C donne le bon ordre pour une mauvaise raison, et
serait faux avec d'autres noms. D invente un filter : `debug:router` liste
toutes les routes, même celles qu'aucun appel n'atteindrait.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **debug LISTE, match TESTE** : `debug:router` montre les routes,
  `router:match` dit laquelle répond à un path.
- `debug:router` liste dans l'**ordre d'évaluation** : déclaration, puis
  `priority` (défaut 0, la plus haute d'abord). **La première qui correspond
  gagne.**
- Options : `--show-controllers`, `--show-aliases`, `--method` (garde les routes
  ANY ; `ANY` ne garde qu'elles), `--format` (`txt`, `xml`, `json`, `md`),
  `--raw`.
- Argument de `debug:router` : nom exact ou **partiel**, casse ignorée.
- `router:match` prend un **path** ; `--method`, `--host`, `--scheme` ; défaut
  **GET** sur **localhost**.
- « almost matches » donne la raison ; `-v` montre les non-correspondances ;
  aucune route : **exit code 1**.

**En une phrase, pour tout reconstruire :**

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
