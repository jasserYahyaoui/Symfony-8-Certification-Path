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
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "redirecting-to-urls-and-routes-directly-from-a-route"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "rendering-a-template-directly-from-a-route"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/RedirectController.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/RedirectController.php"
    symbol_or_lines: "__invoke, redirectAction, urlRedirectAction"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/TemplateController.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/TemplateController.php"
    symbol_or_lines: "templateAction"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
---

## Objectif

Connaître les contrôleurs que FrameworkBundle fournit tout faits, et le cas
d'usage de chacun : afficher une page sans écrire de code.

## Le principe

Une route désigne un contrôleur. Rien n'oblige ce contrôleur à être une classe
de l'application : FrameworkBundle en expose plusieurs, que l'on référence
directement dans la configuration de route, avec des options passées en
`defaults`. Ces options deviennent des attributs de la requête, puis des
arguments du contrôleur, appariés par nom.

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

`template` est le seul obligatoire ; `statusCode` vaut 200 par défaut.
`maxAge` et `sharedAge` posent les en-têtes de cache, `context` fournit les
variables du gabarit. Sans TwigBundle, le contrôleur lève une `LogicException`.

### Le cache public par défaut

Trois détails du code que la configuration ne montre pas :

- sans `private`, poser `maxAge` ou `sharedAge` rend la réponse **publique** ;
- `private: false` la rend publique aussi, `private: true` privée ;
- `maxAge` n'est appliqué que s'il est non nul : `maxAge: 0` ne pose **rien**,
  alors que `sharedAge: 0` pose bien un `s-maxage` à zéro.

## `RedirectController`

Redirige depuis la route elle-même, sans contrôleur applicatif. Deux modes
exclusifs : `route` pour viser une route, `path` pour un chemin ou une URL.

| Option | Effet |
|---|---|
| `permanent: true` | 301 au lieu de 302 |
| `keepQueryParams: true` | conserve la chaîne de requête — mode `route` seulement |
| `keepRequestMethod: true` | conserve la méthode HTTP |
| `ignoreAttributes` | `true` n'en transmet aucun, un tableau retire ceux-là |

`keepRequestMethod` mérite d'être retenu : conserver la méthode change le code
de statut, parce que 301 et 302 autorisent le navigateur à retomber sur `GET`.
Le statut devient **307** pour une redirection temporaire et **308** pour une
permanente.

### Ce que le code tranche

- **`route` et `path` ensemble** lèvent une `RuntimeException` (« Ambiguous
  redirection settings ») ; **aucun des deux** en lève une aussi.
- **Une cible vide** ne redirige pas : elle lève une `HttpException` **404**, ou
  **410** si `permanent` est vrai — une ressource retirée pour de bon.
- **Les paramètres de la route courante sont transmis** à la route cible par
  défaut. C'est ce que `ignoreAttributes` coupe.
- **L'URL générée est absolue** en mode `route` — schéma et hôte compris —,
  à l'inverse de `redirectToRoute()`, qui produit un chemin.
- **En mode `path`, la chaîne de requête est toujours recopiée** ; il n'y a pas
  d'option pour s'en passer. Un chemin relatif est complété par le schéma,
  l'hôte et le port, que `scheme`, `httpPort` et `httpsPort` permettent de fixer.

## `ErrorController`

Il produit les pages d'erreur, et expose en développement la route de
prévisualisation `/_error/{statusCode}`.

## Pièges d'examen

**Conserver la méthode HTTP change le code de statut** : 307 si temporaire, 308
si permanente.

**Une redirection vers une cible vide renvoie 404 ou 410**, pas une erreur de
configuration.

**`route` et `path` ensemble lèvent une exception** : les modes sont exclusifs,
et le code le vérifie.

**`keepQueryParams` n'existe qu'en mode `route`.** En mode `path`, la chaîne de
requête suit toujours.

**Un `maxAge` seul rend la réponse publique.** Il faut `private: true` pour
l'éviter.

**Un contrôleur de route n'est pas forcément dans `App\`.**

## Points clés

- Un contrôleur de route peut venir du framework, pas seulement de `App\`.
- `TemplateController` : `template` obligatoire ; `maxAge` sans `private` → public.
- `RedirectController` : `route` **ou** `path`, jamais les deux.
- Cible vide : 404, ou 410 si permanente.
- `keepRequestMethod: true` transforme 302/301 en **307/308**.
- Mode `route` : URL absolue, paramètres transmis ; mode `path` : query string recopiée.

## Sources officielles

- [Routing, redirection directe depuis une route](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [Templates, rendre un gabarit depuis une route](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
- [`RedirectController`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/RedirectController.php)
- [`TemplateController`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/TemplateController.php)
