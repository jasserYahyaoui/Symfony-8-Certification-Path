---
id: CRS-thmkagkjcvh3
official_item: OIT-pfrzrr0qcmh3
title: "The cookies"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/ResponseHeaderBag.php"
    symbol_or_lines: "setCookie, removeCookie, clearCookie"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
---

## Objectif

Savoir de quel côté un cookie se lit et de quel côté il s'écrit dans un
contrôleur. Les attributs de sécurité — `SameSite`, `Secure`, `HttpOnly` — et la
contrainte de suppression sont traités dans le lot HTTP.

## Deux objets, deux sens

C'est l'asymétrie à retenir, et elle suit le protocole :

| Opération | Objet | Chemin |
|---|---|---|
| **lire** un cookie | `Request` | `$request->cookies->get('nom')` |
| **écrire** un cookie | `Response` | `$response->headers->setCookie(...)` |
| **supprimer** un cookie | `Response` | `$response->headers->clearCookie('nom')` |

Un cookie posé sur la requête ne part nulle part : la requête est ce que le
client a envoyé. Un cookie s'écrit sur la réponse, parce qu'il voyage dans
l'en-tête `Set-Cookie`.

## Conséquence pratique

Un contrôleur qui veut poser un cookie doit donc **avoir la réponse en main** :
il la construit, y attache le cookie, puis la retourne.

```php
$response = $this->render('page.html.twig');
$response->headers->setCookie(Cookie::create('theme', 'dark'));

return $response;
```

Appeler la fonction PHP `setcookie()` fonctionne au niveau du protocole, mais
court-circuite l'objet `Response` : l'en-tête est écrit hors du modèle de
Symfony, échappe aux écouteurs de `kernel.response`, et n'apparaît pas dans les
tests fonctionnels qui inspectent la réponse. C'est la raison de passer par
`ResponseHeaderBag`.

## Points clés

- Lire sur la requête, écrire et supprimer sur la réponse.
- `$response->headers` est un `ResponseHeaderBag` : `setCookie()`,
  `clearCookie()`.
- `setcookie()` de PHP contourne l'objet `Response` ; ne pas l'utiliser.

## Sources officielles

- [ResponseHeaderBag, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/ResponseHeaderBag.php)
