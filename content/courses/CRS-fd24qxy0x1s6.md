---
id: CRS-fd24qxy0x1s6
official_item: OIT-znm2tr61aw9p
title: "Internal redirects"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/forwarding.rst"
    anchor: "how-to-forward-requests-to-another-controller"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    symbol_or_lines: "forward()"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
---

## Objectif

Distinguer une redirection interne d'une redirection HTTP, et connaître les
effets de bord de la première.

## Ce que fait `forward()`

```php
$response = $this->forward('App\Controller\OtherController::fancy', [
    'name'  => $name,
    'color' => 'green',
]);
```

Le noyau exécute une **sous-requête** : il appelle l'autre contrôleur dans le
même cycle PHP et rend la `Response` que *ce* contrôleur retourne. Le navigateur
n'en sait rien.

## Le contraste

C'est la comparaison que l'examen pose :

| | `redirectToRoute()` | `forward()` |
|---|---|---|
| Réponse envoyée | une `RedirectResponse` 3xx | la réponse du contrôleur cible |
| Aller-retour navigateur | oui | **non** |
| URL affichée | change | **inchangée** |
| Nombre de requêtes HTTP | deux | une |
| Type de requête noyau | requête principale | **sous-requête** |

Une redirection dit au client d'aller ailleurs ; un *forward* va chercher le
contenu sans le lui dire.

## Les arguments passent par leur nom

Le tableau donné à `forward()` fournit les arguments du contrôleur cible, et la
correspondance se fait sur le **nom**, pas sur l'ordre — exactement comme pour
un contrôleur appelé par une route. Réordonner la signature de la cible ne casse
rien.

## L'effet de bord à connaître

Après un `forward()`, `app.current_route`, `app.current_route_parameters` et
`_route_params` sont **vides** dans le gabarit rendu par la cible : la
sous-requête n'a pas été produite par le routeur, donc elle ne porte pas ces
informations. On peut les fournir soi-même en ajoutant les clés `_route` et
`_route_params` au tableau passé à `forward()`.

## Points clés

- `forward()` exécute une sous-requête et retourne la réponse de la cible.
- L'URL du navigateur ne change pas ; il n'y a qu'une requête HTTP.
- Les arguments sont appariés par nom.
- `app.current_route` est vide après un forward, sauf à passer `_route`.

## Sources officielles

- [How to Forward Requests to another Controller](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/forwarding.rst)
