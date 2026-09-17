---
id: CRS-7fa2behkxmf9
official_item: OIT-bz1f5nh8g9b2
title: "Cookies"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-16"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Cookie.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Cookie.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "SAMESITE_NONE, SAMESITE_LAX, SAMESITE_STRICT lines 21-23"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/httpwg/http-extensions/main/draft-ietf-httpbis-rfc6265bis.md"
    readable_url: "https://github.com/httpwg/http-extensions/blob/main/draft-ietf-httpbis-rfc6265bis.md"
    branch: "main"
    symbol_or_lines: "exigences serveur lignes 842-893 ; exigences navigateur « Cookie Name Prefixes » lignes 1245-1288 et storage model etapes 20-21 lignes 1817-1831"
    verified_at: "2026-09-16"
---

## Objectif

Poser un cookie avec les bons attributs de sécurité et savoir ce que chacun
protège.

## Lire et écrire

La lecture passe par la requête, l'écriture par la réponse :

```php
$value = $request->cookies->get('theme');

$response->headers->setCookie(
    Cookie::create('theme')
        ->withValue('dark')
        ->withExpires(new \DateTimeImmutable('+1 year'))
        ->withPath('/')
        ->withSecure(true)
        ->withHttpOnly(true)
        ->withSameSite(Cookie::SAMESITE_LAX)
);

$response->headers->clearCookie('theme');
```

`Cookie` est **immuable** : chaque `with*()` renvoie une nouvelle instance.
Ignorer la valeur de retour est sans effet.

### Ce que `Cookie::create()` pose sans qu'on le demande

La fabrique a déjà des défauts, et ce sont eux qui sont interrogés :

| Paramètre | Défaut | Conséquence |
|---|---|---|
| `$path` | `'/'` | portée sur tout le site |
| `$httpOnly` | `true` | **invisible à JavaScript par défaut** |
| `$sameSite` | `Cookie::SAMESITE_LAX` | protection CSRF de base déjà active |
| `$secure` | `null` | **auto-activé si la requête courante est déjà en HTTPS** |
| `$expire` | `0` | cookie de session |

```php
Cookie::create('theme');   // path '/', httpOnly true, sameSite lax, secure auto
```

`$secure = null` ne vaut donc pas « non sécurisé » : c'est « décide d'après la
requête ». Le forcer à `true` sur un site servi en HTTP empêcherait le cookie
d'être posé.

## Les attributs de sécurité

| Attribut | Ce qu'il protège |
|---|---|
| `HttpOnly` | Rend le cookie invisible à JavaScript — atténue le vol par XSS |
| `Secure` | N'envoie le cookie que sur HTTPS |
| `SameSite` | Contrôle l'envoi en contexte tiers — atténue le CSRF |
| `Path` | Restreint l'envoi à un chemin et ses sous-répertoires |
| `Domain` | **Élargit** aux sous-domaines ; l'omettre limite à l'origine |

## Les trois valeurs de SameSite

- **`strict`** — jamais envoyé lors d'une navigation venant d'un autre site.
  Le plus sûr, mais l'utilisateur qui arrive par un lien externe apparaît
  déconnecté.
- **`lax`** — envoyé lors des navigations de premier niveau en méthode sûre,
  pas sur les requêtes intersites en `POST`. C'est le compromis usuel.
- **`none`** — toujours envoyé. **Exige `Secure`** : sans lui, les navigateurs
  rejettent le cookie.

## `Partitioned` — le cookie tiers cloisonné (CHIPS)

```php
Cookie::create('widget')->withSecure(true)->withSameSite(Cookie::SAMESITE_NONE)
    ->withPartitioned(true);        // ajoute « ; partitioned »
```

`withPartitioned()` vaut `true` sans argument ; `create()` le laisse à `false`,
et l'attribut est sérialisé en dernier, **après** `samesite`.

Un cookie tiers partitionné est rangé dans un pot séparé par site intégrateur :
le widget reconnaît son visiteur sur `a.test` sans que ce soit le même cookie
que sur `b.test`. Le suivi intersites tombe, l'usage légitime reste.

## Les préfixes `__Secure-` et `__Host-`

Des règles **de nommage** appliquées par le navigateur, qu'aucune API ne pose :

| Préfixe | Ce que le navigateur exige, sinon il rejette |
|---|---|
| `__Secure-` | l'attribut `Secure` |
| `__Host-` | `Secure`, `Path=/`, et **aucun** `Domain` |

**Le serveur l'écrit exactement ; le navigateur le reconnaît sans la casse.**
`__host-sid` est donc préfixé, et rejeté sans `Secure` — sans quoi un
`__SeCuRe-SID` posé par un tiers passerait pour un cookie ordinaire.

**Symfony 8.0 ne vérifie rien de tout cela.** Ni `Cookie` ni `ResponseHeaderBag`
ne connaissent ces chaînes : `Cookie::create('__Host-session')` sans `Secure`
part sans une plainte, et le navigateur le jette en silence.

## Pièges d'examen

**`SameSite=none` sans `Secure` est rejeté.** Ce n'est pas un avertissement :
le cookie n'est pas posé du tout.

**`HttpOnly` ne protège pas du CSRF.** Il empêche la lecture par script, pas
l'envoi automatique par le navigateur. C'est `SameSite` qui traite ce risque.

**Supprimer un cookie n'est pas l'écraser.** `clearCookie()` renvoie le cookie
avec une date d'expiration passée, et **doit reprendre les mêmes `path` et
`domain`** ; sinon le navigateur conserve l'original.

**Un cookie de session n'a pas d'expiration** : il disparaît à la fermeture du
navigateur, et `withExpires(0)` le produit.

**`Cookie::fromString()` n'a pas les mêmes défauts que `create()`.** Sa table
interne pose `secure => false`, `httponly => false`, `samesite => null` : une
lecture d'en-tête brut ne fabrique donc **pas** un cookie sûr.

**`removeCookie()` n'efface rien chez le client.** Son propre docblock le dit :
« removes a cookie from the array, but does not unset it in the browser ». Il
retire le `Set-Cookie` de la réponse ; c'est `clearCookie()` qui envoie le
cookie expiré qui efface.

**Les défauts de `Cookie::create()` sont déjà sûrs.** `httpOnly` vaut `true` et
`sameSite` vaut `lax` sans rien demander ; l'erreur d'examen consiste à croire
qu'un cookie créé sans arguments est nu.

## Points clés

- Lecture par `$request->cookies`, écriture par `$response->headers->setCookie()`.
- `Cookie` est immuable : chaîner les `with*()` et utiliser le retour.
- `HttpOnly` contre XSS, `SameSite` contre CSRF, `Secure` pour HTTPS ;
  `Domain` élargit aux sous-domaines au lieu de restreindre.
- Défauts de `Cookie::create()` : `path '/'`, `httpOnly true`, `sameSite lax`,
  `secure` auto-activé en HTTPS, `expire 0` — mais `fromString()` a les siens,
  bien moins sûrs.
- `removeCookie()` retire de la réponse ; `clearCookie()` efface chez le client.
- `withPartitioned()` cloisonne un cookie tiers ; les préfixes `__Host-` et
  `__Secure-` sont tenus par le navigateur, jamais par Symfony.
- `SameSite=none` impose `Secure` ; suppression = mêmes `path` et `domain`.

## Aller lire la source

- [`Cookie`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Cookie.php) — `create()` et ses défauts l. 75, constantes `SAMESITE_*`,
  `withPartitioned()` l. 262, sérialisation de `partitioned` l. 313 (branche 8.0, `6f841c0`)
- [Brouillon httpbis « Cookies »](https://github.com/httpwg/http-extensions/blob/main/draft-ietf-httpbis-rfc6265bis.md) — préfixes `__Secure-` et `__Host-` : exigences
  serveur, puis « Cookie Name Prefixes » pour celles du navigateur
- [Composant HttpFoundation](https://github.com/symfony/symfony-docs/blob/8.0/components/http_foundation.rst)
