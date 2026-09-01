---
id: CRS-9fz4erg0wmbq
official_item: OIT-ycc2c8tnv68h
title: "Naming conventions"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    anchor: "a-basic-controller"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    anchor: "generating-urls"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Connaître les conventions propres aux contrôleurs, et savoir lesquelles sont
**obligatoires**. Les conventions générales du framework — casse, préfixes
`Abstract`, suffixes `Interface` et `Trait` — sont traitées dans le lot Symfony
Architecture.

## Rien n'est imposé, presque

C'est le point que l'examen vérifie : la documentation dit que la classe *peut
techniquement s'appeler n'importe comment*, mais qu'elle est suffixée
`Controller` **par convention**. Rien dans le framework ne l'exige.

| Élément | Convention | Obligatoire ? |
|---|---|---|
| classe de contrôleur | suffixe `Controller` | non |
| méthode d'action | aucun suffixe `Action` | non — et le suffixe n'est plus utilisé |
| classe de base | étendre `AbstractController` | non |
| gabarit | `templates/<contrôleur>/<action>.html.twig` | non |

## Le format `_controller`

Un contrôleur est désigné par la notation
`App\Controller\BlogController::show` — classe pleinement qualifiée, `::`, nom
de méthode. Pour une classe invocable, la classe seule suffit. C'est cette
chaîne que le routeur dépose dans l'attribut `_controller` de la requête.

## Le nom de route

Chaque route doit avoir un nom **unique** dans l'application. Si l'option `name`
est omise, Symfony en génère un automatiquement à partir du contrôleur et de
l'action. La convention retenue par les générateurs officiels est
`app_<contrôleur>_<action>`, en `snake_case`.

Symfony ajoute en outre un **alias de route** fondé sur le nom pleinement
qualifié : une méthode qui ne déclare qu'une seule route reçoit un alias
`App\Controller\MainController::homepage`, et une classe invocable qui n'ajoute
qu'une route reçoit un alias sur son FQCN seul.

## Points clés

- Le suffixe `Controller` est une convention, pas une contrainte.
- Le suffixe `Action` sur les méthodes n'est plus utilisé.
- `_controller` = `FQCN::méthode`, ou le FQCN seul si la classe est invocable.
- Sans `name`, le nom de route est généré ; des alias FQCN existent en plus.

## Sources officielles

- [Controller, section « A Basic Controller »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst)
- [Routing, génération d'URL et alias de route](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst)
