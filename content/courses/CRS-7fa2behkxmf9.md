---
id: CRS-7fa2behkxmf9
official_item: OIT-bz1f5nh8g9b2
title: "Cookies"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Cookie.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Cookie.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "SAMESITE_NONE, SAMESITE_LAX, SAMESITE_STRICT lines 21-23"
    verified_at: "2026-09-01"
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

L'exemple ci-dessus est explicite de bout en bout, mais la fabrique a déjà des
défauts, et ce sont eux qui sont interrogés :

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
| `Domain` / `Path` | Restreignent la portée d'envoi |

## Les trois valeurs de SameSite

- **`strict`** — jamais envoyé lors d'une navigation venant d'un autre site.
  Le plus sûr, mais l'utilisateur qui arrive par un lien externe apparaît
  déconnecté.
- **`lax`** — envoyé lors des navigations de premier niveau en méthode sûre,
  pas sur les requêtes intersites en `POST`. C'est le compromis usuel.
- **`none`** — toujours envoyé. **Exige `Secure`** : sans lui, les navigateurs
  rejettent le cookie.

## Pièges d'examen

**`SameSite=none` sans `Secure` est rejeté.** Ce n'est pas un avertissement :
le cookie n'est pas posé du tout.

**`HttpOnly` ne protège pas du CSRF.** Il empêche la lecture par script, pas
l'envoi automatique par le navigateur. C'est `SameSite` qui traite ce risque.

**Supprimer un cookie n'est pas l'écraser.** `clearCookie()` renvoie le cookie
avec une date d'expiration passée, et **doit reprendre les mêmes `path` et
`domain`** ; sinon le navigateur conserve l'original.

**Un cookie de session n'a pas d'expiration** : il disparaît à la fermeture du
navigateur. `withExpires(0)` produit ce comportement.

**Les défauts de `Cookie::create()` sont déjà sûrs.** `httpOnly` vaut `true` et
`sameSite` vaut `lax` sans rien demander ; l'erreur d'examen consiste à croire
qu'un cookie créé sans arguments est nu.

## Points clés

- Lecture par `$request->cookies`, écriture par `$response->headers->setCookie()`.
- `Cookie` est immuable : chaîner les `with*()` et utiliser le retour.
- `HttpOnly` contre XSS, `SameSite` contre CSRF, `Secure` pour HTTPS.
- Défauts de `Cookie::create()` : `path '/'`, `httpOnly true`, `sameSite lax`,
  `secure` auto-activé en HTTPS, `expire 0`.
- `SameSite=none` impose `Secure` ; suppression = mêmes `path` et `domain`.

## Aller lire la source

- [`Cookie`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Cookie.php) — `create()` et ses défauts, constantes `SAMESITE_*`
  (branche 8.0, `6f841c0`)
- [Composant HttpFoundation](https://github.com/symfony/symfony-docs/blob/8.0/components/http_foundation.rst)
