---
id: CRS-zmg0wrxvqqdq
official_item: OIT-e93sa9rd4kd9
title: "Built-in internal controllers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    anchor: "redirecting-to-urls-and-routes-directly-from-a-route"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    anchor: "rendering-a-template-directly-from-a-route"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Connaître les contrôleurs que FrameworkBundle fournit tout faits, et le cas
d'usage de chacun : afficher une page sans écrire de code.

## Le principe

Une route désigne un contrôleur. Rien n'oblige ce contrôleur à être une classe
de l'application : FrameworkBundle en expose plusieurs, que l'on référence
directement dans la configuration de route, avec des options passées en
`defaults`.

## `TemplateController`

Rend un gabarit sans aucun code. C'est le cas des pages statiques — mentions
légales, page de confidentialité.

```yaml
acme_privacy:
    path: /privacy
    controller: Symfony\Bundle\FrameworkBundle\Controller\TemplateController
    defaults:
        template: 'static/privacy.html.twig'
        statusCode: 200
        maxAge: 86400
        sharedAge: 86400
        private: true
        context: { site_name: 'ACME' }
        headers: { Content-Type: 'text/html' }
```

`template` est le seul obligatoire. `maxAge` et `sharedAge` posent les en-têtes
de cache, `context` fournit les variables du gabarit.

## `RedirectController`

Redirige depuis la route elle-même, sans contrôleur applicatif. Deux modes
exclusifs : `route` pour viser une route, `path` pour une URL absolue.

| Option | Effet |
|---|---|
| `permanent: true` | 301 au lieu de 302 |
| `keepQueryParams: true` | conserve la chaîne de requête d'origine |
| `keepRequestMethod: true` | conserve la méthode HTTP |
| `ignoreAttributes` | supprime les attributs de route transmis |

`keepRequestMethod` mérite d'être retenu : conserver la méthode change le code
de statut, parce que 301 et 302 autorisent le navigateur à retomber sur `GET`.
Le statut devient **307** pour une redirection temporaire et **308** pour une
permanente.

## `ErrorController`

Il produit les pages d'erreur, et expose en développement la route de
prévisualisation `/_error/{statusCode}`.

## Points clés

- Un contrôleur de route peut venir du framework, pas seulement de `App\`.
- `TemplateController` : page statique, option `template` obligatoire.
- `RedirectController` : `route` ou `path`, `permanent` pour un 301.
- `keepRequestMethod: true` transforme 302/301 en **307/308**.

## Sources officielles

- [Routing, redirection directe depuis une route](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst)
- [Templates, rendre un gabarit depuis une route](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst)
