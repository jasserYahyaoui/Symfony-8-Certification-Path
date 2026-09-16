---
id: CRS-984c2bv3wh09
official_item: OIT-512se83ab7qj
title: "Status codes"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-16"
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
---

## Objectif

Reconnaître la classe d'un code de statut et les codes qui tombent à l'examen.

## Les cinq classes

RFC 9110 §15 définit cinq classes, identifiées par le premier chiffre :

| Classe | Sens | Exemple |
|---|---|---|
| `1xx` | Information — requête reçue, traitement continue | `100 Continue` |
| `2xx` | Succès | `200 OK` |
| `3xx` | Redirection — action supplémentaire requise | `301 Moved Permanently` |
| `4xx` | Erreur **client** — la requête est fautive | `404 Not Found` |
| `5xx` | Erreur **serveur** — le serveur a échoué sur une requête valide | `500 Internal Server Error` |

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
authentification manquante ou invalide. `403 Forbidden` signifie « je sais qui
vous êtes, et vous n'avez pas le droit ». Le nom `401` est trompeur : il concerne
l'**authentification**, pas l'autorisation.

**301 vs 302 vs 307/308.** `301` et `308` sont permanents, `302` et `307` sont
temporaires. La différence entre l'ancienne et la nouvelle paire tient à la
méthode : `307` et `308` **préservent la méthode et le corps** de la requête,
alors que les agents transforment historiquement `301`/`302` en `GET`.

**`303 See Other` n'entre pas dans ce 2×2.** Il demande un `GET` (ou `HEAD`)
sur une autre ressource, quelle que soit la méthode d'origine : c'est
POST-Redirect-GET, là où `307` rejouerait le `POST`.

**204 vs 200.** `204 No Content` interdit un corps ; `200` avec un corps vide
n'est pas équivalent.

**`415` et `406` ne parlent pas du même message.** `415 Unsupported Media Type`
porte sur le **corps envoyé** : RFC 9110 §15.5.16 le définit comme le refus de
servir « parce que le contenu est dans un format que cette méthode ne prend pas
en charge sur la ressource visée ». La cause peut être le `Content-Type`, le
`Content-Encoding`, ou l'inspection directe des données. `406 Not Acceptable`
porte à l'inverse sur la **représentation demandée en retour**.

L'en-tête à joindre suit la même séparation, et c'est le détail qui tranche :
sur un codage non supporté la RFC préconise `Accept-Encoding` en réponse, sur un
type de média non supporté elle préconise `Accept`.

**4xx vs 5xx.** Le premier chiffre attribue la faute : au client ou au serveur.

## Points clés

- Le premier chiffre donne la classe ; c'est le seul élément à mémoriser.
- `401` = authentification, `403` = autorisation.
- `307`/`308` préservent la méthode ; `301`/`302` non ; `303` impose un `GET`.
- `415` = le corps reçu, `406` = la représentation demandée.
- Les constantes `Response::HTTP_*` évitent les codes magiques.

## Aller lire la source

- [RFC 9110 §15 — *Status Codes*](https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html#status.codes)
- [RFC 9110 §15.5.16 — *415 Unsupported Media Type*](https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html#status.415)
- [`Response`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php) — constantes `HTTP_*` (branche 8.0, `6f841c0`)
