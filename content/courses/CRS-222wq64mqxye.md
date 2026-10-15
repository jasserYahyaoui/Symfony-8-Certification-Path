---
id: CRS-222wq64mqxye
official_item: OIT-d3nyk9z0q2pd
title: "HTTP request"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-16"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "public bag properties lines 94-130, getClientIp line 821, getClientIps line 798 and its docblock, normalizeAndFilterClientIps line 2146-2183 ending on array_reverse()"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/InputBag.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/InputBag.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "InputBag::get, BadRequestException on non-scalar"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/http_foundation.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/http_foundation.rst"
    anchor: "accessing-request-data"
    symbol_or_lines: "\"A Request object holds information about the client request. This information can be accessed via several public properties\"; Request::createFromGlobals and Request::create examples"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-15"
outcome_sections:
  OUT-hm4eajgn6rks: "Les sacs"
  OUT-8beznche62fc: "InputBag n'accepte que des scalaires"
  OUT-w17b65zev3y5: "InputBag n'accepte que des scalaires"
  OUT-g11rrz2thqp0: "Client et proxys"
  OUT-04bvez73yte3: "Le host, et la seconde liste de confiance"
---

## Objectif

Choisir le bon sac de la `Request` et connaître la constraint que `InputBag`
impose — c'est là que se situe le piège.

## D'où vient l'object

```php
$request = Request::createFromGlobals();   // depuis $_GET, $_POST, $_COOKIE, $_FILES, $_SERVER
$request = Request::create('/search?q=php', 'GET');   // fabriquée de toutes pièces
```

`createFromGlobals()` est ce que fait le *front controller* : il emballe les
superglobales dans un object. `create()` fabrique une Request sans elles — tests
et sub-Requests. Dans un controller on n'appelle ni l'une ni l'autre : on
type-hinte `Request`.

## Les sacs

`Request` expose sept properties publiques typées, chacune un sac spécialisé :

| Property | Type | Contenu |
|---|---|---|
| `$query` | `InputBag` | Parameters d'URL (`$_GET`) |
| `$request` | `InputBag` | Corps de form (`$_POST`) |
| `$cookies` | `InputBag` | Cookies |
| `$attributes` | `ParameterBag` | Données internes à l'application (route, `_route`, `_controller`) |
| `$headers` | `HeaderBag` | Headers |
| `$server` | `ServerBag` | `$_SERVER` |
| `$files` | `FileBag` | Files téléversés |

Le nom `$request` désigne le **corps POST**, pas la Request entière : c'est une
source de confusion classique.

**`Request::get()` n'existe plus en 8.0.** Le raccourci qui cherchait dans
`attributes`, puis `query`, puis `request` est supprimé : on nomme le sac.

## InputBag n'accepte que des scalaires

C'est la différence de fond avec `ParameterBag`.

```php
// URL : /search?tags[]=php&tags[]=http
$request->query->get('tags');   // BadRequestException : valeur non scalaire
$request->query->all('tags');   // ['php', 'http'] — correct
```

`InputBag::get()` lève une `BadRequestException` si la valeur n'est pas un
scalaire. La raison est défensive : un array reçu là où un scalaire est
attendu doit échouer bruyamment plutôt que de se propager. Pour une valeur
multiple, `all()` est l'accessor prévu.

`$attributes` est un `ParameterBag` sans cette restriction, parce que son
contenu vient de l'application, pas du client.

## Corps brut et method

```php
$request->getContent();          // corps brut, utile pour du JSON
$request->getPayload();          // InputBag depuis JSON ou form-data
$request->isMethod('POST');      // comparaison insensible à la casse
$request->getMethod();           // méthode effective, override compris
$request->getRealMethod();       // ce que le serveur a reçu, sans override
```

**`getMethod()` peut ne pas être la method reçue.** Sur un `POST`, Symfony lit
le header `X-HTTP-Method-Override` **sans qu'on ait rien à activer** ; seul
`_method` exige `enableHttpMethodParameterOverride()`. `getRealMethod()` donne la
method brute. **Symfony 8.0 restreint le mécanisme** : un override vers `GET`,
`HEAD`, `CONNECT` ou `TRACE` est désormais ignoré, et
`setAllowedHttpMethodOverride()` permet de limiter la liste, voire de tout
interdire.

`getPayload()` retourne un **`InputBag`** : la constraint scalaire ci-dessus s'y
applique donc aussi, quel que soit le format d'entrée.

## Client et proxys

```php
$request->getClientIp();
```

Derrière un reverse proxy, cette valeur n'est fiable **que si** les proxys de
confiance sont déclarés (`Request::setTrustedProxies()` ou la configuration
`trusted_proxies` du framework). Sans cela, Symfony ignore délibérément
`X-Forwarded-For`, car un client peut le falsifier.

**Quelle adresse est renvoyée, une fois les proxys déclarés.** `X-Forwarded-For`
est une liste : chaque proxy traversé ajoute la sienne à droite. Symfony complète
la string avec `REMOTE_ADDR`, **retire toute adresse appartenant à un proxy de
confiance**, puis **renverse** ce qui reste. `getClientIp()` renvoie le premier
élément du résultat, c'est-à-dire l'adresse non fiable **la plus proche du
serveur**.

```php
// tous les intermédiaires sont déclarés de confiance : le cas visé
// X-Forwarded-For: 203.0.113.7, 10.0.0.1, 10.0.0.2   (10.0.0.* de confiance)
$request->getClientIp();    // '203.0.113.7' — il ne reste qu'une adresse

// un intermédiaire n'est PAS déclaré : le résultat change
// X-Forwarded-For: 1.2.3.4, 5.6.7.8                  (aucun des deux de confiance)
$request->getClientIp();    // '5.6.7.8', et non '1.2.3.4'
```

Le client d'origine n'est donc renvoyé **que si toute la string
d'intermédiaires est déclarée de confiance**. Dès qu'un saut non déclaré
subsiste, c'est lui qu'on obtient — et c'est le comportement sûr, puisque tout
ce qui se trouve à sa gauche est écrit par un maillon auquel on ne fait pas
confiance.

`getClientIps()` renvoie la liste complète, « la plus fiable d'abord » selon son
propre docblock, et conseille de lui préférer `getClientIp()`.

## Le host, et la seconde liste de confiance

`X-Forwarded-For` n'est pas le seul header que le client contrôle. `Host` l'est
aussi, et `getHost()` le lit — d'abord `X-Forwarded-Host` si la Request vient
d'un proxy de confiance, sinon le header `Host`, sinon `SERVER_NAME`, sinon
`SERVER_ADDR`. Le port est retiré et la valeur passée en minuscules.

Un `Host` falsifié empoisonne tout ce qui est construit à partir de lui : une
URL absolue dans un email de réinitialisation de password, une key de
cache. La parade est une **seconde** liste, distincte des proxys :

```php
Request::setTrustedHosts(['^(.+\.)?example\.com$']);
```

`getHost()` lève alors une `SuspiciousOperationException` sur un host hors
liste — et aussi sur un host syntaxiquement invalide, avant même la liste.

**Ce sont des expressions régulières, et elles ne sont pas ancrées pour vous.**
Chaque pattern est enveloppé en `{motif}i` : insensible à la casse, et surtout
**non ancré**. `setTrustedHosts(['example.com'])` accepte donc
`example.com.attaquant.test`, puisque le pattern y est trouvé en sous-chaîne — et
le point non échappé accepte en prime `exampleXcom`. Un pattern de confiance
s'écrit avec `^`, `$` et des points échappés, faute de quoi la liste donne
l'apparence d'une protection sans en être une.

**Une liste vide ne vérifie rien.** Le contrôle ne s'exécute que
`if (count(self::$trustedHostPatterns) > 0)` : par défaut, n'importe quel `Host`
est accepté. L'absence de configuration n'est pas un mode strict.

## Pièges d'examen

**`$request->request` n'est pas la Request** : c'est le body POST.

**`get()` sur un array lève.** Ce n'est pas `null`, ni le premier élément.

**`getClientIp()` sans trusted proxies renvoie l'IP du proxy**, pas celle du
client — et c'est le comportement sûr.

**`getClientIp()` ne renvoie pas « la plus à gauche ».** Il renvoie l'adresse
non fiable la plus proche du serveur — ce qui coïncide avec le client d'origine
uniquement quand toute la string est déclarée de confiance. Fonder une
allow-list sur la lecture inverse revient à faire confiance à une valeur que le
client contrôle.

**`Request::create()` n'est pas `createFromGlobals()`.** La première fabrique
une Request arbitraire — tests, sub-Requests ; la seconde lit les superglobales.

## Tips d'examen

**`get()` refuse un array, `all($clé)` refuse ce qui n'en est pas un** : deux
`BadRequestException` symétriques. Une key absente, elle, rend `[]`.

**Deux listes, deux dangers** : `trusted_proxies` garde `X-Forwarded-For`,
`trusted_hosts` garde `Host`. Vides par défaut toutes les deux.

**Un host pattern est une expression régulière non ancrée** : sans `^` ni `$`,
la sous-chaîne suffit.

## Points clés

- Sept sacs typés ; `$request` = corps POST, `$attributes` = données internes.
- `InputBag::get()` exige un scalaire et lève sinon ; `all()` pour un array.
- `getPayload()` lit JSON comme form-data, et retourne un `InputBag`.
- `getClientIp()` n'est fiable qu'avec des trusted proxies déclarés, et renvoie
  l'adresse non fiable la plus proche du serveur : le client d'origine seulement
  si toute la string est de confiance.
- `createFromGlobals()` lit les superglobales ; `create()` fabrique une Request.

## Aller lire la source

- [Component HttpFoundation](https://github.com/symfony/symfony-docs/blob/8.0/components/http_foundation.rst) — *Accessing Request Data*,
  `createFromGlobals()`, `create()`
- [`Request`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php) — `setTrustedHosts()` l. 642, `getHost()` l. 1132,
  sacs publics l. 94-130, `getClientIp()` l. 821,
  `getClientIps()` l. 798, `normalizeAndFilterClientIps()` l. 2146-2183 (branche 8.0, `6f841c0`)
- [`InputBag`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/InputBag.php) — `get()` et sa `BadRequestException` (branche 8.0, `6f841c0`)
