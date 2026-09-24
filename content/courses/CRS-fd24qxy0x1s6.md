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
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller/forwarding.rst"
    anchor: "how-to-forward-requests-to-another-controller"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    symbol_or_lines: "forward()"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    symbol_or_lines: "duplicate, __clone"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Controller/ArgumentResolver/RequestAttributeValueResolver.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Controller/ArgumentResolver/RequestAttributeValueResolver.php"
    symbol_or_lines: "resolve"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
---

## Objectif

Distinguer une redirection interne d'une redirection HTTP, savoir ce que la
sous-requête garde et ce qu'elle perd, et connaître les effets de bord.

## Ce que fait `forward()`

```php
$response = $this->forward('App\Controller\OtherController::fancy', [
    'name'  => $name,
    'color' => 'green',
]);
```

Le noyau exécute une **sous-requête** : il appelle l'autre contrôleur dans le
même cycle PHP et rend la `Response` que *ce* contrôleur retourne. Le navigateur
n'en sait rien. La réponse peut encore être modifiée avant d'être retournée.

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

## Le mécanisme, en quatre lignes

```php
protected function forward(string $controller, array $path = [], array $query = []): Response
{
    $request = $this->container->get('request_stack')->getCurrentRequest();
    $path['_controller'] = $controller;
    $subRequest = $request->duplicate($query, null, $path);

    return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
}
```

Tout le comportement se lit dans l'appel à `duplicate()`, dont les trois
premiers paramètres sont la query string, le corps POST et les **attributs**.
Un paramètre `null` garde la valeur d'origine ; un tableau la **remplace**.

### Ce que la sous-requête perd

- **Les attributs.** Le tableau `$path` devient les attributs de la sous-requête
  et les remplace entièrement — seul `_format` est reporté s'il manque. Voilà
  pourquoi `_route` et `_route_params` disparaissent : ce n'est pas un oubli du
  routeur, c'est un remplacement.
- **La query string.** Le troisième argument vaut `[]` par défaut, pas `null` :
  les paramètres GET d'origine sont remplacés par un tableau vide. Pour les
  transmettre, il faut les passer : `forward($ctrl, [], $request->query->all())`.

### Ce qu'elle garde

- **Le corps POST**, passé à `null`, donc conservé.
- **Les cookies, les en-têtes, les variables serveur** : non passés, conservés.
- **La session**, qui n'est pas clonée : la sous-requête partage celle de la
  requête en cours.

## Les arguments passent par leur nom

Puisque le tableau devient les attributs, il fournit les arguments du
contrôleur cible par le même mécanisme qu'une route : `RequestAttributeValueResolver`
cherche dans les attributs une clé portant le **nom** de l'argument. L'ordre ne
compte pas ; réordonner la signature de la cible ne casse rien.

## L'effet de bord à connaître

Après un `forward()`, `app.current_route`, `app.current_route_parameters` et
`_route_params` sont **vides** dans le gabarit rendu par la cible. On peut les
fournir soi-même en ajoutant les clés `_route` et `_route_params` au tableau
passé à `forward()` — ce sont des attributs comme les autres.

## Pièges d'examen

**Un *forward* n'est pas une redirection.** Il n'y a qu'une requête HTTP,
l'URL affichée ne bouge pas, et c'est la réponse du contrôleur cible qui part au
navigateur. Aucun 3xx n'est émis.

**La query string d'origine n'arrive pas à la cible.** Le défaut du troisième
argument est un tableau vide, qui remplace ; il faut la transmettre
explicitement.

**Le corps POST, lui, arrive.** L'asymétrie avec la query string vient de ce que
l'un est passé à `null` et l'autre à `[]`.

**Après un forward, la route courante est vide dans le gabarit.** Les attributs
ont été remplacés ; `_route` n'est renseigné que si on le passe soi-même.

**Les arguments du contrôleur cible s'apparient par nom**, comme pour une route.

## Points clés

- `forward()` exécute une sous-requête et retourne la réponse de la cible.
- L'URL du navigateur ne change pas ; il n'y a qu'une requête HTTP.
- Le tableau devient les **attributs** et remplace ceux d'origine.
- Query string perdue par défaut ; corps POST, cookies et session conservés.
- `app.current_route` est vide après un forward, sauf à passer `_route`.

## Sources officielles

- [How to Forward Requests to another Controller](https://github.com/symfony/symfony-docs/blob/8.0/controller/forwarding.rst)
- [`AbstractController::forward()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php)
- [`Request::duplicate()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php)
- [`RequestAttributeValueResolver`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Controller/ArgumentResolver/RequestAttributeValueResolver.php)
