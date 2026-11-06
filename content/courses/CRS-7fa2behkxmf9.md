---
id: CRS-7fa2behkxmf9
official_item: OIT-bz1f5nh8g9b2
title: "Cookies"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
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
outcome_sections:
  OUT-sh5sn773tmkx: "Lire et écrire"
  OUT-v9x1yyn34t4x: "Les attributes de sécurité"
  OUT-z584m37h6jj1: "Les trois valeurs de SameSite"
  OUT-d3rkaszp9nga: "Pièges d'examen"
  OUT-mgpn4708q6rn: "Les préfixes `__Secure-` et `__Host-`"
---

## Objectif

Poser un cookie avec les bons attributes de sécurité et savoir ce que chacun
protège.

## L'essentiel

Un cookie se lit dans la Request et s'écrit dans la Response. L'object
`Cookie` est **immuable**, et `Cookie::create()` pose déjà des défauts sûrs :
`HttpOnly`, `SameSite=Lax`, `Secure` décidé d'après la Request. Chaque
attribute protège d'un risque précis — et les navigateurs appliquent des
règles que Symfony ne vérifie pas.

## Le schéma

```text
lire     $request->cookies->get('theme')
écrire   $response->headers->setCookie(Cookie::create('theme')->withValue('dark'))   ← garder le retour
effacer  $response->headers->clearCookie('theme', $path, $domain)    ← mêmes path et domain
défauts create()       path '/' · httpOnly true · sameSite lax · secure null (= HTTPS courant) · expire 0
défauts fromString()   secure false · httponly false · samesite null
HttpOnly → XSS (lecture)   SameSite → CSRF (envoi)   Secure → HTTPS   Domain → ÉLARGIT
```

## L'exemple : un cookie de préférence

```php
$response->headers->setCookie(
    Cookie::create('theme')
        ->withValue('dark')
        ->withExpires(new \DateTimeImmutable('+1 year'))
        ->withSecure(true)
        ->withSameSite(Cookie::SAMESITE_LAX)
);
```

## Lire et écrire

- `Cookie` est immuable : chaque `with*()` rend une **nouvelle** instance ;
  ignorer la return value est sans effet.
- `$secure = null` ne veut pas dire « non sécurisé » : le cookie devient
  `Secure` si la Request courante est en HTTPS.
- `withExpires(0)` — ou toute date négative — donne un **cookie de session**,
  qui disparaît à la fermeture du navigateur.
- `clearCookie()` envoie le cookie expiré qui efface ; `removeCookie()` retire
  seulement le `Set-Cookie` de la Response.

## Les attributes de sécurité

| Attribute | Ce qu'il protège |
|---|---|
| `HttpOnly` | Rend le cookie invisible à JavaScript — atténue le vol par XSS |
| `Secure` | N'envoie le cookie que sur HTTPS |
| `SameSite` | Contrôle l'envoi en contexte tiers — atténue le CSRF |
| `Path` | Restreint l'envoi à un path et ses sous-répertoires |
| `Domain` | **Élargit** aux sous-domaines ; l'omettre limite à l'origine |

`withPartitioned()` cloisonne un cookie tiers par site intégrateur (CHIPS) ;
l'attribute est sérialisé en dernier, après `samesite`.

## Les trois valeurs de SameSite

- **`strict`** — jamais envoyé lors d'une navigation venant d'un autre site ;
  le user venu d'un lien externe apparaît déconnecté.
- **`lax`** — envoyé lors des navigations de premier niveau en safe method,
  pas sur un `POST` intersite. Le compromis usuel, et le défaut de `create()`.
- **`none`** — toujours envoyé. **Exige `Secure`**, sinon le navigateur rejette
  le cookie.

`withSameSite('Strict')` est accepté : la valeur passe en minuscules ; une
valeur inconnue lève une `InvalidArgumentException`.

## Les préfixes `__Secure-` et `__Host-`

| Préfixe | Ce que le navigateur exige, sinon il rejette |
|---|---|
| `__Secure-` | l'attribute `Secure` |
| `__Host-` | `Secure`, `Path=/`, et **aucun** `Domain` |

Le navigateur reconnaît le préfixe sans tenir compte de la casse. **Symfony
8.0 ne vérifie rien** : `Cookie::create('__Host-session')` sans `Secure` part
sans une plainte, et le navigateur le jette en silence.

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
lecture de header brut ne fabrique donc **pas** un cookie sûr.

**`removeCookie()` n'efface rien chez le client.** Son propre docblock le dit :
« removes a cookie from the array, but does not unset it in the browser ». Il
retire le `Set-Cookie` de la Response ; c'est `clearCookie()` qui envoie le
cookie expiré qui efface.

**Les défauts de `Cookie::create()` sont déjà sûrs.** `httpOnly` vaut `true` et
`sameSite` vaut `lax` sans rien demander ; l'erreur d'examen consiste à croire
qu'un cookie créé sans arguments est nu.

## Application

- Un cookie de session d'authentication → `HttpOnly`, `Secure`,
  `SameSite=Lax`, et un nom en `__Host-`.
- Un widget intégré sur d'autres sites → `SameSite=None`, `Secure` et
  `withPartitioned()`.

## QCM d'entraînement

**Question 1.** Que donne `Cookie::create('x')->withValue('v')->withExpires(-1)` ?

- **A.** Un cookie déjà expiré, qui efface `x`
- **B.** Une `InvalidArgumentException` : date négative
- **C.** Un cookie qui expire dans une seconde
- **D.** Un cookie de session

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Une échéance négative est ramenée à `0`, qui signifie
« pas d'expiration » : le cookie vit jusqu'à la fermeture du navigateur.

</details>

**Question 2.** Un cookie est relu depuis un header brut par
`Cookie::fromString('a=b')`. Est-il `HttpOnly` ?

- **A.** Oui, comme avec `create()`
- **B.** Non
- **C.** Oui, si la Request est en HTTPS
- **D.** Cela dépend de `framework.session`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `fromString()` a ses propres défauts, `secure` et
`httponly` à faux, `samesite` à `null` : relire un header ne produit pas un
cookie sûr.

</details>

**Question 3.** Le code appelle `withSameSite('Strict')`, avec une majuscule.
Résultat ?

- **A.** Une `InvalidArgumentException`
- **B.** La valeur est ignorée, `lax` reste
- **C.** Acceptée, comme `strict`
- **D.** Acceptée telle quelle, `Strict`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La valeur est passée en minuscules avant d'être
contrôlée ; seule une valeur hors de `strict`, `lax` et `none` est refusée.

</details>

**Question 4.** Un cookie est posé par `shop.example.com` **sans** attribute
`Domain`. Où le navigateur le renvoie-t-il ?

- **A.** Au seul serveur d'origine
- **B.** À tous les sous-domaines d'`example.com`
- **C.** À tout site, faute de restriction
- **D.** Nulle part : `Domain` est obligatoire

<details>
<summary>Correction</summary>

**Bonne réponse : A.** L'absence de `Domain` limite le cookie à l'origine ;
c'est le poser qui élargit l'envoi aux sous-domaines.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Lire par **`$request->cookies`**, écrire par **`setCookie()`** ; `Cookie` est
  immuable.
- `create()` : `path '/'`, **`httpOnly`**, **`lax`**, `secure` auto, session.
- `fromString()` : **rien de sûr** par défaut.
- `HttpOnly` contre XSS, **`SameSite` contre CSRF**, `Secure` pour HTTPS.
- `Domain` **élargit** ; son absence restreint à l'origine.
- `SameSite=none` exige **`Secure`** ; `__Host-` exige aussi `Path=/` sans
  `Domain`.
- `clearCookie()` **avec les mêmes path et domain** ; `removeCookie()` n'efface
  rien chez le client.

Moyen mnémotechnique : **« HttpOnly cache, SameSite retient, Secure chiffre »**.

## Sources officielles

- [`Cookie`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Cookie.php) — `create()` et ses défauts l. 75, constantes `SAMESITE_*`,
  `withPartitioned()` l. 262, serialization de `partitioned` l. 313 (branche 8.0, `6f841c0`)
- [Brouillon httpbis « Cookies »](https://github.com/httpwg/http-extensions/blob/main/draft-ietf-httpbis-rfc6265bis.md) — préfixes `__Secure-` et `__Host-` : exigences
  serveur, puis « Cookie Name Prefixes » pour celles du navigateur
- [Component HttpFoundation](https://github.com/symfony/symfony-docs/blob/8.0/components/http_foundation.rst)
