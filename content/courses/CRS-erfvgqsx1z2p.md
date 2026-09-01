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
    branch: "master"
    symbol_or_lines: "section 9.2 Common Method Properties"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "isMethodSafe line 1444, isMethodIdempotent line 1452, isMethodCacheable line 1462"
    verified_at: "2026-09-01"
---

## Objectif

Classer une méthode selon les trois propriétés de RFC 9110 — sûre, idempotente,
cacheable — qui sont indépendantes les unes des autres.

## Les trois propriétés

- **Sûre** (*safe*) : la méthode est en lecture seule ; elle ne demande aucune
  modification d'état.
- **Idempotente** : rejouer la même requête N fois a le même effet que l'exécuter
  une fois.
- **Cacheable** : la réponse peut être stockée et réutilisée.

Toute méthode sûre est idempotente. L'inverse est faux, et c'est là que se
situent les erreurs.

## Le tableau

Valeurs telles qu'implémentées par Symfony 8.0 :

| Méthode | Sûre | Idempotente | Cacheable |
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

## Ce que le tableau enseigne

**`PUT` et `DELETE` sont idempotents sans être sûrs.** Ils modifient l'état,
mais le rejouer ne change rien de plus : remplacer une ressource par la même
valeur, ou supprimer ce qui est déjà supprimé.

**`POST` n'est ni l'un ni l'autre.** Deux `POST` identiques créent deux
ressources. C'est pourquoi un navigateur avertit avant de recharger un
formulaire soumis.

**`PATCH` n'est pas idempotent.** Contrairement à `PUT`, il décrit une
modification relative — `{"op": "increment"}` appliqué deux fois donne un
résultat différent.

**L'idempotence est une propriété de la spécification, pas une garantie.** Une
implémentation qui incrémente un compteur dans un `GET` viole le contrat sans
que rien ne l'en empêche.

## Côté Symfony

```php
$request->isMethodSafe();        // GET, HEAD, OPTIONS, TRACE, QUERY
$request->isMethodIdempotent();  // + PUT, DELETE, PURGE
$request->isMethodCacheable();   // GET, HEAD, QUERY uniquement
```

`isMethodIdempotent()` inclut `PURGE`, qui n'est pas une méthode standard mais
une convention de reverse proxy.

## Pièges d'examen

**« Sûre » ne veut pas dire « sécurisée ».** Le terme signifie « sans effet de
bord attendu ».

**`OPTIONS` et `TRACE` sont sûrs mais non cacheables.**

**`DELETE` est idempotent.** L'intuition « la seconde suppression échoue, donc
ce n'est pas idempotent » confond l'*effet sur l'état*, qui est identique, avec
le *code de statut renvoyé*, qui peut différer.

## Points clés

- Sûre ⊂ idempotente ; cacheable est indépendante.
- `PUT`/`DELETE` : idempotents, non sûrs. `POST`/`PATCH` : ni l'un ni l'autre.
- Cacheables : `GET`, `HEAD`, `QUERY`.

## Sources officielles

- RFC 9110 §9.2 — *Common Method Properties*
- `Symfony\Component\HttpFoundation\Request` (branche 8.0, `6f841c0`)
