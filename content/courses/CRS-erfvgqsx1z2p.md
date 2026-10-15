---
id: CRS-erfvgqsx1z2p
official_item: OIT-qnm508g1ktqm
title: "HTTP methods"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/httpwg/httpwg.github.io/master/specs/rfc9110.html"
    readable_url: "https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html"
    branch: "master"
    symbol_or_lines: "section 9.2 Common Method Properties"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "isMethodSafe line 1444, isMethodIdempotent line 1452, isMethodCacheable line 1462"
    verified_at: "2026-09-01"
outcome_sections:
  OUT-pvvwmsfr9v99: "Les trois properties"
  OUT-yfskr4jf463t: "Ce que l'array enseigne"
  OUT-vx89t3yy7xck: "Pièges d'examen"
---

## Objectif

Classer une method selon les trois properties de RFC 9110 — sûre, idempotente,
cacheable — qui sont indépendantes les unes des autres.

## Les trois properties

- **Sûre** (*safe*) : la method est en lecture seule ; elle ne demande aucune
  modification d'état.
- **Idempotente** : rejouer la même Request N fois a le même effet que l'exécuter
  une fois.
- **Cacheable** : la Response peut être stockée et réutilisée.

Toute safe method est idempotente. L'inverse est faux, et c'est là que se
situent les erreurs.

## L'array

Valeurs telles qu'implémentées par Symfony 8.0. **La RFC et Symfony ne disent
pas la même chose sur « cacheable »** : RFC 9110 §9.2.3 définit une sémantique
de cache pour `GET`, `HEAD` **et `POST`** ; `isMethodCacheable()` retient
`GET`, `HEAD` et `QUERY`. Une question sur la RFC et une question sur Symfony
n'appellent donc pas la même Response.

| Method | Sûre | Idempotente | Cacheable |
|---|:--:|:--:|:--:|
| `GET` | ✅ | ✅ | ✅ |
| `HEAD` | ✅ | ✅ | ✅ |
| `OPTIONS` | ✅ | ✅ | ❌ |
| `TRACE` | ✅ | ✅ | ❌ |
| `QUERY` | ✅ | ✅ | ✅ |
| `PUT` | ❌ | ✅ | ❌ |
| `DELETE` | ❌ | ✅ | ❌ |
| `POST` | ❌ | ❌ | ❌ |
| `PATCH` | ❌ | ❌ | ❌ |

`QUERY` est la seule ligne de l'array qui puisse surprendre : c'est une method
récente, pensée pour les lectures dont les critères sont trop volumineux pour
tenir dans une URL. Elle porte un body, comme `POST`, mais garde la sémantique
de `GET` — d'où sa place parmi les safe methods, idempotentes **et**
cacheables. Symfony 8.0 la traite comme telle dans les trois methods ci-dessous.

## Ce que l'array enseigne

**`PUT` et `DELETE` sont idempotents sans être sûrs.** Ils modifient l'état,
mais le rejouer ne change rien de plus : remplacer une ressource par la même
valeur, ou supprimer ce qui est déjà supprimé.

**`POST` n'est ni l'un ni l'autre.** Deux `POST` identiques créent deux
ressources. C'est pourquoi un navigateur avertit avant de recharger un
form soumis.

**`PATCH` n'est pas idempotent.** Contrairement à `PUT`, il décrit une
modification relative — `{"op": "increment"}` appliqué deux fois donne un
résultat différent.

**L'idempotence est une property de la spécification, pas une garantie.** Une
implémentation qui incrémente un compteur dans un `GET` viole le contrat sans
que rien ne l'en empêche.

## Côté Symfony

```php
$request->isMethodSafe();        // GET, HEAD, OPTIONS, TRACE, QUERY
$request->isMethodIdempotent();  // + PUT, DELETE, PURGE
$request->isMethodCacheable();   // GET, HEAD, QUERY uniquement
```

`isMethodIdempotent()` inclut `PURGE`, qui n'est pas une method standard mais
une convention de reverse proxy.

## Pièges d'examen

**« Sûre » ne veut pas dire « sécurisée ».** Le terme signifie « sans effet de
bord attendu ».

**`OPTIONS` et `TRACE` sont sûrs mais non cacheables.**

**Les cacheables sont `GET`, `HEAD` et `QUERY` — trois, pas deux.** Oublier
`QUERY` est l'erreur attendue, et elle se propage : `Response::isNotModified()`
s'ouvre sur `isMethodCacheable()`, donc la validation d'une Response suit
exactement cette liste.

**`DELETE` est idempotent.** L'intuition « la seconde suppression échoue, donc
ce n'est pas idempotent » confond l'*effet sur l'état*, qui est identique, avec
le *status code renvoyé*, qui peut différer.

## Tips d'examen

**Lire d'abord le référentiel de la question.** Cacheable selon RFC 9110 §9.2.3 :
`GET`, `HEAD`, `POST`. Cacheable selon `isMethodCacheable()` : `GET`, `HEAD`,
`QUERY`. Les deux affirmations sont justes, chacune chez elle.

**Symfony ajoute, et retire.** Il ajoute `QUERY` aux safe methods, `QUERY` et
`PURGE` aux idempotentes — mais il retire `POST` des cacheables. Aucune des
trois listes n'est « la RFC plus `QUERY` ».

**RFC 9110 ne définit pas `PATCH`.** C'est RFC 5789. D'où son absence des listes
de §9.2, qui portent sur « the request methods defined by this specification ».

**Le nom d'une method est sensible à la casse** (§9.1) — à l'inverse des noms de
fields de header (§5.1).

**« Sûre » décrit l'intention, pas le code.** La RFC dit qu'un serveur reste
libre d'y mettre un comportement nuisible ; la classification n'est pas une
garantie d'exécution.

## Points clés

- Sûre ⊂ idempotente ; cacheable est indépendante.
- `PUT`/`DELETE` : idempotents, non sûrs. `POST`/`PATCH` : ni l'un ni l'autre.
- Cacheables : `GET`, `HEAD`, `QUERY`.

## Aller lire la source

- [RFC 9110 §9.2 — *Common Method Properties*](https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html#method.properties)
- [`Request`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php) — `isMethodSafe()` l. 1444, `isMethodIdempotent()` l. 1452,
  `isMethodCacheable()` l. 1462 (branche 8.0, `6f841c0`)
