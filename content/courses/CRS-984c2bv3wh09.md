---
id: CRS-984c2bv3wh09
official_item: OIT-512se83ab7qj
title: "Status codes"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-07"
official_sources:
  - url: "https://raw.githubusercontent.com/httpwg/httpwg.github.io/master/specs/rfc9110.html"
    readable_url: "https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html"
    anchor: "status.codes"
    branch: "master"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Response.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Response::HTTP_* constants, lines 28-160"
    verified_at: "2026-09-01"
outcome_sections:
  OUT-7hfm105c194h: "Les cinq classes"
  OUT-8yd42b69nxnx: "Pièges d'examen"
  OUT-6sg999w56sxb: "Pièges d'examen"
  OUT-p4n2v9ecgmyv: "Pièges d'examen"
---

## Objectif

Reconnaître la class d'un status code et les codes qui tombent à l'examen.

## Les cinq classes

RFC 9110 §15 définit cinq classes, identifiées par le premier chiffre :

| Class | Sens | Exemple |
|---|---|---|
| `1xx` | Information — Request reçue, traitement continue | `100 Continue` |
| `2xx` | Succès | `200 OK` |
| `3xx` | Redirect — action supplémentaire requise | `301 Moved Permanently` |
| `4xx` | Erreur **client** — la Request est fautive | `404 Not Found` |
| `5xx` | Erreur **serveur** — le serveur a échoué sur une Request valide | `500 Internal Server Error` |

## Les codes qui comptent

```php
Response::HTTP_OK;                    // 200
Response::HTTP_CREATED;               // 201
Response::HTTP_NO_CONTENT;            // 204
Response::HTTP_MOVED_PERMANENTLY;     // 301
Response::HTTP_FOUND;                 // 302
Response::HTTP_SEE_OTHER;             // 303
Response::HTTP_NOT_MODIFIED;          // 304
Response::HTTP_TEMPORARY_REDIRECT;    // 307
Response::HTTP_PERMANENTLY_REDIRECT;  // 308 — et non HTTP_PERMANENT_REDIRECT
Response::HTTP_BAD_REQUEST;           // 400
Response::HTTP_UNAUTHORIZED;          // 401
Response::HTTP_FORBIDDEN;             // 403
Response::HTTP_NOT_FOUND;             // 404
Response::HTTP_METHOD_NOT_ALLOWED;    // 405
Response::HTTP_NOT_ACCEPTABLE;        // 406
Response::HTTP_UNSUPPORTED_MEDIA_TYPE; // 415
Response::HTTP_UNPROCESSABLE_ENTITY;  // 422
Response::HTTP_INTERNAL_SERVER_ERROR; // 500
```

## Pièges d'examen

**401 vs 403.** `401 Unauthorized` signifie « je ne sais pas qui vous êtes » —
authentication manquante ou invalide. `403 Forbidden` signifie « je sais qui
vous êtes, et vous n'avez pas le droit ». Le nom `401` est trompeur : il concerne
l'**authentication**, pas l'authorization.

**301 vs 302 vs 307/308.** `301` et `308` sont permanents, `302` et `307` sont
temporaires. La différence entre l'ancienne et la nouvelle paire tient à la
method : `307` et `308` **préservent la method et le body** de la Request,
alors qu'après `301`/`302` un agent **peut**, « pour des raisons
historiques », changer un `POST` en `GET` (§15.4.2, §15.4.3).

**`303 See Other` n'entre pas dans ce 2×2.** Il demande un `GET` (ou `HEAD`)
sur une autre ressource, quelle que soit la method d'origine : c'est
POST-Redirect-GET, là où `307` rejouerait le `POST`.

**204 vs 200.** `204 No Content` interdit un body ; `200` avec un body vide
n'est pas équivalent.

**`415` et `406` ne parlent pas du même message.** `415 Unsupported Media Type`
porte sur le **corps envoyé** : RFC 9110 §15.5.16 le définit comme le refus de
servir « parce que le contenu est dans un format que cette method ne prend pas
en charge sur la ressource visée ». La cause peut être le `Content-Type`, le
`Content-Encoding`, ou l'inspection directe des données. `406 Not Acceptable`
porte à l'inverse sur la **représentation demandée en retour**.

Le header à joindre suit la même séparation, et c'est le détail qui tranche :
sur un codage non supporté la RFC préconise `Accept-Encoding` en Response, sur un
type de média non supporté elle préconise `Accept`.

**4xx vs 5xx.** Le premier chiffre attribue la faute : au client ou au serveur.

## Tips d'examen

**401 pose « qui ? », 403 pose « quoi ? »** — identité contre permission.

**307 et 308 sont les redirects qui ne touchent à rien** : même method, même
corps. `303` fait l'inverse, et il le dit : *See Other*, donc `GET` ailleurs.
Pour 302, la RFC écrit `MAY` : le comportement n'est pas garanti, il est
indéterminé — c'est pourquoi 307 existe.

**Un code inconnu se traite comme le `x00` de sa class.** Un 499 reçu est une
erreur client générique, jamais une exception : « treat an unrecognized status
code as being equivalent to the x00 status code of that class » (§15).

**Se méfier des « jamais ».** 404 est *heuristically cacheable* d'après §15.1,
au même titre que 405, 410, 414 et 501.

## Points clés

- Le premier chiffre donne la class ; c'est le seul élément à mémoriser.
- `401` = authentication, `403` = authorization.
- `307`/`308` préservent la method ; `301`/`302` ne la garantissent pas ;
  `303` impose un `GET`.
- `415` = le body reçu, `406` = la représentation demandée.
- Les constants `Response::HTTP_*` évitent les codes magiques.

## Aller lire la source

- [RFC 9110 §15 — *Status Codes*](https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html#status.codes)
- [RFC 9110 §15.5.16 — *415 Unsupported Media Type*](https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html#status.415)
- [`Response`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php) — constantes `HTTP_*` (branche 8.0, `6f841c0`)
