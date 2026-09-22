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
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller.rst"
    anchor: "a-basic-controller"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "generating-urls"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php"
    symbol_or_lines: "getDefaultRouteName"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-22"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Routing/AttributeRouteControllerLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Routing/AttributeRouteControllerLoader.php"
    symbol_or_lines: "configureRoute, getDefaultRouteName"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-22"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "template-naming"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-22"
---

## Objectif

Connaître les conventions propres aux contrôleurs, et savoir lesquelles sont
**obligatoires**. Les conventions générales du framework — casse, préfixes
`Abstract`, suffixes `Interface` et `Trait` — sont traitées dans le lot Symfony
Architecture.

## Rien n'est imposé, presque

La documentation le dit mot pour mot : la classe *peut techniquement s'appeler
n'importe comment*, mais elle est suffixée `Controller` **par convention**.

| Élément | Convention | Obligatoire ? |
|---|---|---|
| classe de contrôleur | suffixe `Controller` | non |
| méthode d'action | aucun suffixe `Action` | non — mais le suffixe reste reconnu |
| classe de base | étendre `AbstractController` | non |
| gabarit | `snake_case`, deux extensions | non |
| nom de route | unique dans l'application | **oui** |

Une seule ligne porte un « oui ».

## Le format `_controller`

Un contrôleur est désigné par la notation
`App\Controller\BlogController::show` — classe pleinement qualifiée, `::`, nom
de méthode. C'est cette chaîne que le routeur dépose dans l'attribut
`_controller` de la requête.

Pour une classe **invocable**, la classe seule suffit : `configureRoute()` écrit
le nom de classe sans `::` quand la méthode est `__invoke`.

## Le nom de route, et qui le fabrique

`name` omis, Symfony génère le nom — en **deux couches**, le même partage
composant / bundle que le chapitre précédent.

Le composant Routing, dans `AttributeClassLoader::getDefaultRouteName()`,
remplace les `\` du nom pleinement qualifié par des `_`, ajoute `_` puis le nom
de la méthode, et met le tout **en minuscules** :

```php
$name = str_replace('\\', '_', $class->name).'_'.$method->name;
$name = mb_strtolower($name, 'UTF-8');
if ($this->defaultRouteIndex > 0) {
    $name .= '_'.$this->defaultRouteIndex;
}
```

FrameworkBundle redéfinit cette méthode dans `AttributeRouteControllerLoader` :
il remplace les segments `bundle_` et `controller_` par `_`, retire un suffixe
`action` quand la méthode se termine par `Action` ou `_action`, puis écrase les
`__` restants.

| Méthode | Composant seul | Avec FrameworkBundle |
|---|---|---|
| `LuckyController::number` | `app_controller_luckycontroller_number` | `app_lucky_number` |
| `BlogController::showAction` | `app_controller_blogcontroller_showaction` | `app_blog_show` |
| deuxième route de la même méthode | suffixe `_1` | `app_lucky_number_1` |

Un `name` porté par la **classe** est préfixé au nom, généré ou non.

Symfony ajoute en outre des **alias** fondés sur le nom pleinement qualifié :
toute méthode ne déclarant qu'une seule route reçoit l'alias
`App\Controller\MainController::homepage` ; une classe invocable n'ajoutant
qu'une route reçoit un alias sur son nom de classe seul.

## Le nom de gabarit

La documentation recommande le `snake_case` pour les fichiers **et** les
répertoires, et **deux extensions** : `index.html.twig`. La première est le
format produit, pas le moteur de rendu.

## Pièges d'examen

**Le suffixe `Controller` est une convention, pas une contrainte** — pas plus
qu'étendre une classe de base ne l'est.

**Le nom généré n'est pas du `snake_case`.** Rien ne découpe le `CamelCase` :
`LuckyController` devient `luckycontroller`, pas `lucky_controller`. La lisibilité
de `app_lucky_number` vient du **retrait** du segment `controller_`, pas d'une
conversion de casse. Le `snake_case` est recommandé pour les **gabarits**.

**Sans FrameworkBundle, le nom généré reste celui du composant.** Le nom court
est un apport du bundle.

**Une deuxième route sur la même méthode ne collisionne pas** : elle reçoit un
suffixe numérique, `_1`, `_2`…

**Le suffixe `Action` n'est plus utilisé, mais il est encore reconnu** : le
chargeur du bundle le retire du nom généré.

## Tips d'examen

**Une seule obligation à retenir** : l'unicité du nom de route. Tout le reste de
cette page est une convention. Et pour trancher composant / bundle sur un nom
généré : s'il contient `controller`, c'est le composant nu.

## Points clés

- Le suffixe `Controller` est une convention, pas une contrainte.
- Le suffixe `Action` n'est plus utilisé, mais reste retiré du nom généré.
- `_controller` = `FQCN::méthode`, ou le FQCN seul si la classe est invocable.
- Le nom généré vient du composant, puis est raccourci par FrameworkBundle.
- Un nom de route doit être unique ; des alias fondés sur le FQCN existent en plus.
- Les gabarits, eux, suivent le `snake_case` et portent deux extensions.

## Sources officielles

- [Controller, section « A Basic Controller »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
- [Routing, génération d'URL et alias de route](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`AttributeClassLoader` (composant Routing)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php)
- [`AttributeRouteControllerLoader` (FrameworkBundle)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Routing/AttributeRouteControllerLoader.php)
- [Templates, section « Template Naming »](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
