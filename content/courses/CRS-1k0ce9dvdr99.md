---
id: CRS-1k0ce9dvdr99
official_item: OIT-zezwg9nya501
title: "HTTP Caching (reverse proxies, expiration, validation) Note: ESI (Edge Side Includes) is not included"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/http_cache.rst"
    anchor: "symfony-reverse-proxy"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Savoir ce que Symfony apporte **par-dessus** le protocole : un proxy inverse
écrit en PHP, un attribut déclaratif, et une trace pour comprendre pourquoi une
réponse a été servie du cache ou non.

## Périmètre

**ESI (Edge Side Includes) est hors du périmètre de l'examen** ; cette page ne
le traite pas.

Deux limites de contenu, aussi. La **sémantique des en-têtes** — les deux
modèles, `max-age` et `s-maxage`, `public` et `private`, `Vary`, les directives
voisines — appartient à l'item *Caching* du sujet HTTP. Cette page ne la redit
pas : elle suppose acquis ce que ces en-têtes signifient, et montre par quels
moyens Symfony les pose et les honore.

## Prérequis

*Caching* (HTTP) : expiration, validation, et les directives de `Cache-Control`.

## Le proxy inverse de Symfony

Un *gateway cache* est un cache partagé placé devant l'application. Symfony en
fournit un, **écrit en PHP**, activé par une option :

```yaml
when@prod:
    framework:
        http_cache: true
```

Le noyau se comporte alors immédiatement en proxy inverse : il stocke les
réponses de l'application et les ressert au client.

Deux points que la documentation souligne :

- il **n'est pas** un proxy inverse complet comme Varnish, et n'atteindra pas
  la vitesse d'un proxy écrit en C ;
- mais tous les proxies inverses étant équivalents dans leur principe, passer
  au suivant se fait sans changer l'application. C'est l'argument réel :
  l'application parle HTTP, pas « Symfony ».

Il est activé sous `when@prod` parce qu'un cache devant l'application masque les
changements pendant le développement.

## Lire ce que le cache a fait

En mode debug, Symfony ajoute un en-tête **`X-Symfony-Cache`** à la réponse. En
dehors, l'option `trace_level` le contrôle :

| Valeur | Effet |
|---|---|
| `none` | aucune trace |
| `short` | la requête principale seulement, en une forme concise |
| `full` | le détail |

`short` est conçue pour être enregistrée dans les journaux du serveur — dans
Apache, `%{X-Symfony-Cache}o` — afin de mesurer l'efficacité du cache route par
route. Le nom de l'en-tête se change par `trace_header`.

## Déclarer plutôt que construire

L'attribut `#[Cache]`, de `Symfony\Component\HttpKernel\Attribute\Cache`, pose
la politique sur l'action :

```php
use Symfony\Component\HttpKernel\Attribute\Cache;

#[Cache(public: true, maxage: 3600, mustRevalidate: true)]
public function index(): Response
{
    return $this->render('blog/index.html.twig');
}
```

La règle de priorité est explicite dans la documentation et vaut d'être
retenue : **les en-têtes posés dans le contrôleur l'emportent sur ceux
configurés par l'attribut.** L'attribut fixe une politique par défaut ; le code
peut la contredire.

## Poser plusieurs réglages d'un coup

```php
$response->setCache([
    'public'          => true,
    'max_age'         => 600,
    's_maxage'        => 600,
    'must_revalidate' => false,
    'immutable'       => true,
    'last_modified'   => new \DateTime(),
    'etag'            => 'abcdef',
]);
```

Les mêmes clés sont disponibles sur l'attribut `#[Cache]`.

Deux méthodes complètent l'ensemble :

```php
$response->expire();          // marque la réponse périmée
$response->setNotModified();  // force un 304 sans contenu
```

## Ce que le cache ne fera pas

**La clé de cache est l'URI de la requête** — sauf variation déclarée. Deux
utilisateurs différents sur la même URL reçoivent donc la même entrée, ce qui
explique pourquoi une page personnalisée ne se met pas en cache partagé sans
précaution.

**Le cache HTTP ne vaut que pour les méthodes sûres.** Trois conséquences que la
documentation énonce :

- ne pas tenter de mettre en cache `PUT` ou `DELETE` — ces méthodes modifient
  l'état, les cacher empêcherait la modification d'atteindre l'application ;
- `POST` est généralement considérée non cachable ; elle peut l'être avec une
  information de fraîcheur explicite, mais l'implémentation est peu répandue et
  la documentation conseille de l'éviter ;
- ne **jamais** modifier l'état sur un `GET` ou un `HEAD` : si la réponse est
  cachée, les requêtes suivantes n'atteindront pas le serveur.

**L'invalidation ne fait pas partie de la spécification HTTP.** Elle est utile,
Symfony la rend possible, mais elle sort du protocole — c'est pourquoi le modèle
d'expiration seul oblige à attendre l'échéance pour voir un contenu modifié.

## Pièges d'examen

**`framework.http_cache: true` suffit** à activer le proxy ; aucune classe à
écrire.

**Le contrôleur l'emporte sur `#[Cache]`**, pas l'inverse.

**La clé de cache est l'URI**, pas la route ni ses paramètres.

**Le cache HTTP ne s'applique qu'aux méthodes sûres.**

**L'invalidation est hors spécification HTTP.**

**`X-Symfony-Cache` n'apparaît qu'en debug** ou si `trace_level` le demande.

## Points clés

- `framework.http_cache: true` active un proxy inverse écrit en PHP,
  remplaçable par Varnish sans toucher à l'application.
- `X-Symfony-Cache` et `trace_level` expliquent un succès ou un échec de cache.
- `#[Cache]` déclare la politique ; le contrôleur prime sur elle.
- `setCache()` en un appel, `expire()` et `setNotModified()` en complément.
- URI comme clé, méthodes sûres seulement, invalidation hors protocole.

## Sources officielles

- [HTTP Cache](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/http_cache.rst)
- [HTTP Cache Validation](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/http_cache/validation.rst)
- [HTTP Cache Expiration](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/http_cache/expiration.rst)
