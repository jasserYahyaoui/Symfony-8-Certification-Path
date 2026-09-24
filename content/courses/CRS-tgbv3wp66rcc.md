---
id: CRS-tgbv3wp66rcc
official_item: OIT-d51jbkfs21pt
title: "Trigger redirects"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "redirecting-urls-with-trailing-slashes"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "routing-force-https"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Routing/RedirectableCompiledUrlMatcher.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Routing/RedirectableCompiledUrlMatcher.php"
    symbol_or_lines: "redirect()"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherTrait.php"
    symbol_or_lines: "match(), doMatch()"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/RedirectController.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/RedirectController.php"
    symbol_or_lines: "urlRedirectAction()"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Tests/Matcher/RedirectableUrlMatcherTest.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Tests/Matcher/RedirectableUrlMatcherTest.php"
    symbol_or_lines: "testRedirectWhenNoSlashForNonSafeMethod, testSchemeRedirectRedirectsToFirstScheme, testFallbackPage, testMissingTrailingSlashAndScheme"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/httpwg/httpwg.github.io/master/specs/rfc9110.html"
    readable_url: "https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html"
    anchor: "status.301"
    branch: "master"
    verified_at: "2026-09-24"
---

## Objectif

Connaître les redirections que **le routage** déclenche de lui-même. Les
redirections émises par un contrôleur — `redirect()`, `redirectToRoute()` — et
les options de `RedirectController` appartiennent au lot Controllers.

## Qui redirige, et comment

Le `UrlMatcher` du composant Routing ne redirige jamais : il lève
`ResourceNotFoundException`, qui devient un 404. La redirection n'existe que
si le matcher implémente `RedirectableUrlMatcherInterface`. Le composant n'en
fournit qu'une classe abstraite, dont la méthode `redirect()` reste à écrire ;
l'implémentation concrète est celle de FrameworkBundle,
`RedirectableCompiledUrlMatcher`.

Quand il faut rediriger, sa méthode `redirect()` ne construit pas de réponse :
elle retourne des attributs de requête qui désignent `RedirectController`
comme contrôleur, avec `permanent` à `true`. D'où trois conséquences, lues
dans le code 8.0 :

- le statut est **301** (permanent, sans demande de conserver la méthode) ;
- la **chaîne de requête** d'origine est recopiée sur l'URL cible ;
- l'attribut `_route` porte le nom de la route trouvée ; seul le contrôleur
  est remplacé.

## La barre oblique finale

Symfony redirige entre l'URL avec et sans barre finale, **uniquement pour `GET`
et `HEAD`**, avec un **301** :

| Chemin de la route | URL demandée `/foo` | URL demandée `/foo/` |
|---|---|---|
| `/foo` | correspond, **200** | **301** vers `/foo` |
| `/foo/` | **301** vers `/foo/` | correspond, **200** |

La redirection va toujours vers la forme déclarée par la route, dans les deux
sens. Le code ajoute trois conditions que le tableau ne montre pas :

- la route doit accepter `GET` : aucune contrainte `methods`, ou une liste qui
  contient `GET` ;
- la racine `/` n'est jamais concernée ;
- un `POST` vers `/foo` pour une route `/foo/` n'est **pas** redirigé. Aucune
  route ne correspond : la réponse est un **404**.

Pourquoi exclure `POST` ? RFC 9110 (section 15.4.2) permet à un client de
rejouer en `GET` une requête `POST` redirigée par un 301 : le corps serait
perdu.

**La redirection passe avant une route générique.** Avec une route `/foo/`
suivie d'une route `/{name}`, une requête `GET /foo` est redirigée vers
`/foo/` ; elle ne tombe pas sur `/{name}`. Le test `testFallbackPage` du
composant l'établit.

## Le schéma

L'option `schemes` déclare le schéma exigé par une route :

```php
#[Route('/login', name: 'login', schemes: ['https'])]
```

Elle agit dans les deux sens, et c'est le point à retenir.

**À la génération** : l'URL de `login` utilise toujours HTTPS. Conséquence
visible dans un gabarit — `path('login')` produit un chemin relatif `/login` si
la requête courante est déjà en HTTPS, mais une **URL absolue**
`https://example.com/login` si elle est en HTTP, puisqu'il faut bien changer de
schéma.

**À l'appariement** : une requête `GET` en HTTP vers `/login` est
**redirigée** en 301 vers la même URL en HTTPS. Précisions du code :

- là encore, **seulement `GET` et `HEAD`**. Un `POST` en HTTP vers `/login`
  reçoit un 404. La documentation écrit simplement que la requête est
  redirigée, sans cette restriction ;
- avec plusieurs schémas, aucune redirection si l'un d'eux correspond ; sinon
  la cible est le **premier** schéma listé ;
- barre finale manquante **et** mauvais schéma : une seule redirection corrige
  les deux.

Le schéma peut être imposé à tout un groupe de routes au moment de leur import
(`schemes` sur l'entrée d'import). Derrière un proxy qui termine TLS, la
documentation avertit que `schemes`, combiné à une configuration de proxy
incorrecte, produit une boucle de redirection.

## Depuis une route

Une route peut enfin déléguer à `RedirectController`, fourni par
FrameworkBundle, ce qui évite d'écrire un contrôleur pour un simple
déplacement d'URL. Ses options sont traitées dans le lot Controllers.

## Pièges d'examen

**Ni barre finale ni schéma ne redirigent un `POST`.** Les deux mécanismes
sont réservés à `GET` et `HEAD` ; un `POST` qui ne correspond qu'à une autre
forme d'URL reçoit un 404.

**Le schéma agit à l'appariement *et* à la génération.** Une requête dans le
mauvais schéma est redirigée ; et l'URL générée devient absolue dès que le
schéma courant diffère, alors qu'elle serait restée un chemin relatif.

**Le composant seul ne redirige pas.** Son `UrlMatcher` échoue en 404 ;
c'est le matcher de FrameworkBundle qui sait rediriger.

**Aucun contrôleur applicatif n'intervient.** Le matcher route la requête vers
`RedirectController` ; le 301 conserve la chaîne de requête.

## Points clés

- Barre finale : redirection **301**, seulement en `GET` et `HEAD`, vers la
  forme déclarée, si la route accepte `GET`.
- `schemes` force le schéma à la génération **et** redirige la requête
  entrante, vers le premier schéma listé, en `GET` et `HEAD` seulement.
- Un `POST` sur la mauvaise forme ou le mauvais schéma : **404**.
- La redirection est produite par `RedirectableCompiledUrlMatcher`, qui délègue
  à `RedirectController`.
- `path()` peut retourner une URL absolue quand le schéma doit changer.

## Sources officielles

- [Routing, « Redirecting URLs with Trailing Slashes »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [Routing, « Forcing HTTPS on Generated URLs »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`RedirectableCompiledUrlMatcher`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Routing/RedirectableCompiledUrlMatcher.php) et [`CompiledUrlMatcherTrait::match()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherTrait.php)
- [`RedirectController::urlRedirectAction()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/RedirectController.php)
- [`RedirectableUrlMatcherTest`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Tests/Matcher/RedirectableUrlMatcherTest.php)
- [RFC 9110, section 15.4.2](https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html)
