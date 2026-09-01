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
    anchor: "overriding-the-default-error-templates"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    anchor: "managing-errors-and-404-pages"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Produire un 404 depuis un contrôleur et personnaliser la page affichée.

## Produire le 404

```php
throw $this->createNotFoundException('The product does not exist');
```

Le mot important est `throw`. `createNotFoundException()` **construit et
retourne** une `NotFoundHttpException` ; elle ne la lève pas. Oublier le `throw`
est une erreur silencieuse : le contrôleur continue, et la page s'affiche
normalement.

La méthode est un simple raccourci : `new NotFoundHttpException(...)` fait la
même chose.

Toute exception implémentant `HttpExceptionInterface` — dont `HttpException` et
ses filles — donne le statut qu'elle porte. Une exception ordinaire donne 500.

## Personnaliser la page

En production, Symfony affiche une page d'erreur générique. Pour la remplacer,
on surcharge les gabarits de TwigBundle, dans
`templates/bundles/TwigBundle/Exception/`.

Le `TwigErrorRenderer` applique une logique en deux temps :

1. chercher un gabarit portant le code de statut — `error404.html.twig` ;
2. à défaut, retomber sur le gabarit générique `error.html.twig`.

Il n'y a donc pas besoin d'un fichier par statut : `error.html.twig` couvre tout
le reste, 500 compris.

Trois variables sont disponibles dans ces gabarits : `status_code`,
`status_text`, et `exception` — dont `exception.message` porte le message passé
à `createNotFoundException()`. Ne jamais afficher `exception.traceAsString` à un
utilisateur final.

## Deux pièges

**En développement**, la grande page d'exception s'affiche à la place de votre
page d'erreur. Pour la prévisualiser, FrameworkBundle expose une route interne :
`/_error/{statusCode}`, et `/_error/{statusCode}.{format}` pour les autres
formats.

**La sécurité n'est pas disponible sur une page 404.** À cause de l'ordre de
chargement du routage et de la sécurité, l'utilisateur y apparaît déconnecté.
Cela fonctionne en test et échoue en production — c'est exactement le genre de
différence que l'examen aime.

## Points clés

- `throw $this->createNotFoundException()` — la méthode ne lève pas.
- Gabarits dans `templates/bundles/TwigBundle/Exception/`.
- `error<code>.html.twig`, sinon `error.html.twig`.
- `status_code`, `status_text`, `exception` sont fournies au gabarit.
- `/_error/{statusCode}` prévisualise en développement.
- Pas d'information de sécurité sur une page 404.

## Sources officielles

- [How to Customize Error Pages](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/error_pages.rst)
- [Controller, « Managing Errors and 404 Pages »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst)
