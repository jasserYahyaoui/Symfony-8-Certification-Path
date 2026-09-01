---
id: CRS-7zy5ndjgnpk1
official_item: OIT-bwfqarnn6s2f
title: "HTTP redirects"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    anchor: "controller-redirect"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Rediriger le navigateur depuis un contrôleur, et connaître le danger attaché à
l'une des deux méthodes.

## Les deux méthodes

```php
return $this->redirectToRoute('homepage');        // vers une route
return $this->redirect('https://symfony.com/doc'); // vers une URL
```

`redirectToRoute()` est un raccourci pour
`new RedirectResponse($this->generateUrl(...))`. Les deux retournent une
`RedirectResponse`, donc une vraie réponse HTTP : le navigateur fait un second
aller-retour et **l'URL affichée change**.

## Le statut

Le troisième argument porte le code, **302 par défaut** :

```php
return $this->redirectToRoute('homepage', [], 301);
return $this->redirectToRoute('homepage', [], Response::HTTP_MOVED_PERMANENTLY);
```

Un 301 est mis en cache par le navigateur, parfois durablement. C'est la raison
de ne l'employer que pour un déplacement réellement permanent.

## Paramètres utiles

- `$this->redirectToRoute('app_lucky_number', ['max' => 10])` — paramètres de route ;
- la clé spéciale `_fragment` ajoute une ancre à l'URL générée ;
- `$this->redirectToRoute($request->attributes->get('_route'))` redirige vers la
  route courante, ce qui est le motif *Post / Redirect / Get*.

## Le danger de `redirect()`

`redirect()` **ne vérifie pas sa destination**. Passer une valeur venue de
l'utilisateur y ouvre une redirection non validée : un attaquant fabrique un
lien vers votre domaine qui renvoie vers le sien. Toute URL redirigée doit être
validée, ou provenir d'une route.

## Points clés

- `redirectToRoute()` pour une route, `redirect()` pour une URL.
- Statut **302** par défaut ; troisième argument pour 301.
- `_fragment` ajoute une ancre.
- `redirect()` ne valide rien : jamais d'entrée utilisateur telle quelle.

## Sources officielles

- [Controller, section « Redirecting »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst)
