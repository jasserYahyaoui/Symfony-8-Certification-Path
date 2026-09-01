---
id: CRS-ak7sdgcdjb7e
official_item: OIT-65bev6t7wbna
title: "The flash messages"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/session.rst"
    anchor: "flash-messages"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Poser un message flash, savoir où il est stocké et quand il disparaît.

## Ce que c'est

Un message flash est un message de **session** conçu pour être utilisé
exactement une fois : il disparaît automatiquement dès qu'il est récupéré. C'est
le mécanisme du motif *Post / Redirect / Get* — on traite, on pose un message,
on redirige, et la page suivante l'affiche puis l'oublie.

Comme c'est de la session, poser un flash **démarre la session**.

## Poser

```php
$this->addFlash('notice', 'Vos modifications ont été enregistrées.');
```

`addFlash()` est un raccourci strictement équivalent à
`$request->getSession()->getFlashBag()->add()`. Le premier argument est un
*type* libre — `notice`, `warning`, `error` sont des usages courants, pas des
valeurs imposées.

## Lire, et le piège

Deux méthodes, une différence qui décide de tout :

| Méthode | Effet |
|---|---|
| `get('warning')` / `all()` | retourne les messages **et les consomme** |
| `peek('warning')` / `peekAll()` | retourne les messages **sans** les consommer |

Dans un gabarit Twig, `app.flashes` consomme ; `app.session.flashbag.peekAll()`
ne consomme pas. Afficher les flashs deux fois dans une même page avec
`app.flashes` en fait donc disparaître la moitié.

## Points clés

- Message de session, consommé à la première lecture.
- `addFlash()` ≡ `getSession()->getFlashBag()->add()`.
- `get()` et `all()` consomment ; `peek()` et `peekAll()` non.
- Poser un flash démarre la session.

## Sources officielles

- [Sessions, section « Flash Messages »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/session.rst)
