---
id: CRS-tgbv3wp66rcc
official_item: OIT-d51jbkfs21pt
title: "Trigger redirects"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    anchor: "redirecting-urls-with-trailing-slashes"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    anchor: "routing-force-https"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Connaître les redirections que **le routage** déclenche de lui-même. Les
redirections émises par un contrôleur — `redirect()`, `redirectToRoute()` — et
les options de `RedirectController` appartiennent au lot Controllers.

## La barre oblique finale

Symfony redirige entre l'URL avec et sans barre finale, **uniquement pour `GET`
et `HEAD`**, avec un **301** :

| Chemin de la route | URL demandée `/foo` | URL demandée `/foo/` |
|---|---|---|
| `/foo` | correspond, **200** | **301** vers `/foo` |
| `/foo/` | **301** vers `/foo/` | correspond, **200** |

La redirection va donc toujours vers la forme déclarée par la route, dans les
deux sens. Restreindre aux méthodes sûres est délibéré : rediriger un `POST`
lui ferait perdre son corps.

## Le schéma

L'option `schemes` déclare le schéma exigé par une route :

```php
#[Route('/login', name: 'login', schemes: ['https'])]
```

Elle agit dans les deux sens, et c'est le point à retenir.

**À la génération** : l'URL de `login` utilise toujours HTTPS. Conséquence
visible dans un gabarit — `path('login')` produit un chemin relatif `/login` si
la requête courante est déjà en HTTPS, mais une **URL absolue**
`https://example.com/login` si elle est en HTTP, puisqu'il faut bien changer de
schéma.

**À l'appariement** : une requête en HTTP vers `/login` est **automatiquement
redirigée** vers la même URL en HTTPS.

Le schéma par défaut peut être imposé à tout un groupe de routes au moment de
leur import.

## Depuis une route

Une route peut enfin déléguer à `RedirectController`, fourni par
FrameworkBundle, ce qui évite d'écrire un contrôleur pour un simple
déplacement d'URL. Ses options sont traitées dans le lot Controllers.

## Points clés

- Barre finale : redirection **301**, seulement en `GET` et `HEAD`, vers la
  forme déclarée.
- `schemes` force le schéma à la génération **et** redirige la requête entrante.
- `path()` peut retourner une URL absolue quand le schéma doit changer.
- `RedirectController` permet de rediriger sans contrôleur applicatif.

## Sources officielles

- [Routing, « Redirecting URLs with Trailing Slashes »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst)
- [Routing, « Forcing HTTPS on Generated URLs »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst)
