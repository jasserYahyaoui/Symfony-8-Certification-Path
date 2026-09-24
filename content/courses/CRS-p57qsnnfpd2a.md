---
id: CRS-p57qsnnfpd2a
official_item: OIT-emymfwgesh99
title: "Generate 404 pages"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/error_pages.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller/error_pages.rst"
    anchor: "overriding-the-default-error-templates"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller.rst"
    anchor: "managing-errors-and-404-pages"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/ErrorRenderer/TwigErrorRenderer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/ErrorRenderer/TwigErrorRenderer.php"
    symbol_or_lines: "render, isDebug, findTemplate"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/ErrorHandler/Exception/FlattenException.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ErrorHandler/Exception/FlattenException.php"
    symbol_or_lines: "createFromThrowable"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Controller/ErrorController.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Controller/ErrorController.php"
    symbol_or_lines: "preview"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
---

## Objectif

Produire un 404 depuis un contrôleur, savoir quel statut produit une exception,
et personnaliser la page affichée.

## Produire le 404

```php
throw $this->createNotFoundException('The product does not exist');
```

Le mot important est `throw`. `createNotFoundException()` **construit et
retourne** une `NotFoundHttpException` ; elle ne la lève pas. Oublier le `throw`
est une erreur silencieuse : le contrôleur continue, et la page s'affiche
normalement.

La méthode est un simple raccourci pour `new NotFoundHttpException($message,
$previous)`. Sans argument, le message vaut `'Not Found'`.

## Quel statut pour quelle exception

Le statut se décide dans `FlattenException::createFromThrowable()`, dans cet
ordre :

| Exception levée | Statut |
|---|---|
| implémente `HttpExceptionInterface` | celui qu'elle porte |
| implémente `RequestExceptionInterface` | **400** |
| toute autre | **500** |

La deuxième ligne est celle que la documentation omet : elle dit « sinon 500 ».
`BadRequestException`, `SuspiciousOperationException` et
`ConflictingHeadersException`, toutes trois de HttpFoundation, implémentent
cette interface et donnent donc un 400 sans être des exceptions HTTP.

## Personnaliser la page

En production, Symfony affiche une page d'erreur générique. Pour la remplacer,
on surcharge les gabarits de TwigBundle, dans
`templates/bundles/TwigBundle/Exception/`.

Le `TwigErrorRenderer` cherche, dans l'ordre :

1. un gabarit portant le code de statut — `error404.html.twig` ;
2. à défaut, le gabarit générique `error.html.twig` ;
3. si aucun n'existe, il passe la main au rendu HTML intégré, `HtmlErrorRenderer`.

Il n'y a donc pas besoin d'un fichier par statut : `error.html.twig` couvre tout
le reste, 500 compris. Ces gabarits ne servent qu'au HTML ; une autre sortie
passe par le Serializer.

### Ce que reçoit le gabarit

Trois variables : `status_code`, `status_text`, et `exception`. La documentation
présente `exception` comme l'objet `HttpException`. **Le code passe autre
chose** :

```php
return $flattenException->setAsString($this->twig->render($template, [
    'exception' => $flattenException,
    'status_code' => $flattenException->getStatusCode(),
    'status_text' => $flattenException->getStatusText(),
]));
```

C'est une `FlattenException`, une classe autonome qui n'étend pas `Exception`.
`exception.message` fonctionne parce qu'elle expose `getMessage()`, mais un test
`instanceof` sur la classe d'origine échouerait. Ne jamais afficher
`exception.traceAsString` à un utilisateur final.

## Deux pièges

**En développement**, la grande page d'exception s'affiche à la place de votre
page d'erreur. Le mode debug fait sauter le gabarit Twig. Pour prévisualiser, on
importe en `when@dev` les routes de FrameworkBundle sous le préfixe `/_error` :
`/_error/{statusCode}`, et `/_error/{statusCode}.{format}` pour les autres
formats. La prévisualisation fonctionne parce qu'elle lance une sous-requête avec
l'attribut `showException` à `false`, ce qui désactive le debug pour ce rendu.

**La sécurité n'est pas disponible sur une page 404.** À cause de l'ordre de
chargement du routage et de la sécurité, l'utilisateur y apparaît déconnecté.
Cela fonctionne en test et échoue en production.

## Pièges d'examen

**La méthode d'aide construit l'exception, elle ne la lève pas.** Sans `throw`,
le contrôleur poursuit et la page s'affiche normalement.

**Toute exception non HTTP ne donne pas 500.** Celles qui implémentent
`RequestExceptionInterface` donnent 400.

**`exception` dans le gabarit n'est pas l'exception levée.** C'est sa version
aplatie.

**Il n'y a pas besoin d'un gabarit par statut.** Le rendu retombe sur le gabarit
générique, puis sur le rendu intégré.

**La sécurité n'est pas disponible sur une page 404.** Une page d'erreur ne peut
pas afficher le nom de l'utilisateur.

## Points clés

- `throw $this->createNotFoundException()` — la méthode ne lève pas.
- Statut : celui de l'exception HTTP, 400 pour `RequestExceptionInterface`, sinon 500.
- `error<code>.html.twig`, puis `error.html.twig`, puis le rendu intégré.
- `exception` est une `FlattenException`.
- `/_error/{statusCode}` prévisualise en développement.
- Pas d'information de sécurité sur une page 404.

## Sources officielles

- [How to Customize Error Pages](https://github.com/symfony/symfony-docs/blob/8.0/controller/error_pages.rst)
- [Controller, « Managing Errors and 404 Pages »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
- [`TwigErrorRenderer`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/ErrorRenderer/TwigErrorRenderer.php)
- [`FlattenException`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ErrorHandler/Exception/FlattenException.php)
- [`ErrorController::preview()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Controller/ErrorController.php)
