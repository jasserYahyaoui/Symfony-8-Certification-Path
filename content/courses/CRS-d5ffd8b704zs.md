---
id: CRS-d5ffd8b704zs
official_item: OIT-8hs6e05vq91g
title: "Argument value resolvers"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/value_resolver.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller/value_resolver.rst"
    anchor: "built-in-value-resolvers"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller.rst"
    anchor: "automatic-mapping-of-the-request"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Controller/ArgumentResolver.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Controller/ArgumentResolver.php"
    symbol_or_lines: "getArguments"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/web.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/web.php"
    symbol_or_lines: "controller.argument_value_resolver, controller.targeted_value_resolver"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Attribute/MapQueryParameter.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Attribute/MapQueryParameter.php"
    symbol_or_lines: "__construct"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/appendices/migration84/deprecated.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/appendices/migration84/deprecated.xml"
    symbol_or_lines: "Implicitly nullable parameter"
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-np4jrjh0phfs: "Le contrat"
  OUT-5kafbd3d9nt8: "Ce que fait `ArgumentResolver`"
  OUT-mtg83g8mv3bb: "L'ordre"
  OUT-cbtvxgfkskae: "Le catalogue"
  OUT-ynyxk5m0hyzd: "Les resolvers ciblés"
  OUT-chxt3emfrcjg: "Le catalogue"
---

## Objectif

Comprendre le mécanisme qui remplit les arguments d'un controller : qui décide,
dans quel ordre, et comment s'y insérer. C'est ce mécanisme qui explique
pourquoi typer `Request`, `SessionInterface` ou une enum suffit.

## Prérequis

Le trajet d'une Request et l'event `kernel.controller_arguments`.

## Le contrat

Le kernel ne sait pas remplir un argument : il délègue à une chaîne de
**resolvers**. Chacun implémente `ValueResolverInterface`, dont l'unique
method est :

```php
public function resolve(Request $request, ArgumentMetadata $argument): iterable;
```

`ArgumentMetadata` porte tout ce que la signature déclare : nom, type,
variadicité, présence d'une default value, attributes.

La documentation le formule ainsi : retourner **toujours un array** — vide s'il
ne sait pas résoudre l'argument, à un élément pour une valeur, à plusieurs pour
un argument variadique. Le type déclaré est `iterable`, et `ArgumentResolver`
parcourt le résultat par `foreach` : un generator fonctionne aussi. Retourner
la valeur elle-même est l'erreur classique — le return type la refuse.

## Ce que fait `ArgumentResolver`

Pour chaque argument, il appelle les resolvers dans l'ordre :

- le **premier** qui produit au moins une valeur **arrête** la chaîne de resolvers pour cet
  argument ;
- plus d'une valeur pour un argument non variadique lève une
  `InvalidArgumentException` ;
- si **aucun** ne produit de valeur, il lève une `RuntimeException` :
  « Controller … requires the "$x" argument that could not be resolved ».

Un array vide passe donc la main sans erreur ; c'est l'échec de **toute** la
chaîne de resolvers qui en produit une.

## L'ordre

Les services tagués `controller.argument_value_resolver` sont triés par
**priority**, lue dans la configuration de FrameworkBundle :

| Priority | Resolvers |
|---|---|
| 120 | `RequestValueResolver`, `SessionValueResolver` |
| 100 | `RequestAttributeValueResolver`, `BackedEnumValueResolver`, `UidValueResolver`, `DateTimeValueResolver` |
| −50 | `ServiceValueResolver` |
| −100 | `DefaultValueResolver` |
| −150 | `VariadicValueResolver` |

Cet ordre n'est pas décoratif. `SessionValueResolver` passe avant
`DefaultValueResolver` : c'est ce qui permet d'obtenir la session s'il y en a
une, `null` sinon. Un resolver maison qui lit les attributes doit se placer à
`100` ou plus.

### L'exemple de la documentation et PHP 8.4

La documentation écrit `SessionInterface $session = null`. Symfony 8.0 exige
PHP 8.4, qui **déprécie** ce nullable implicite. La forme à écrire est
`?SessionInterface $session = null`. Le mécanisme est inchangé : sans valeur par
défaut, un type nullable reçoit déjà `null` par `DefaultValueResolver`.

## Le catalogue

| Resolver | Ce qu'il remplit |
|---|---|
| `RequestAttributeValueResolver` | un argument dont le nom correspond à un attribute de la Request — les parameters de route |
| `RequestValueResolver` | un argument typé `Request` ou une sous-classe |
| `SessionValueResolver` | un argument typé `SessionInterface` |
| `BackedEnumValueResolver` | un cas d'enum depuis un parameter de route |
| `UidValueResolver` | un identifier du component Uid |
| `DateTimeValueResolver` | un `\DateTimeInterface` depuis une string — un `DateTimeImmutable` si le type est l'interface |
| `ServiceValueResolver` | un service, lu dans le locator propre à l'action, par nom d'argument |
| `DefaultValueResolver` | la default value, ou `null` pour un type nullable |
| `VariadicValueResolver` | un argument variadique depuis un attribute array |

Deux précisions de code. `RequestValueResolver` reconnaît un type qui se termine
par `\Request` sans être celui de HttpFoundation et répond « Looks like you
required a Request object with the wrong class name » — le signe d'un mauvais
import. Et une valeur invalide venue de l'URL donne **404** pour une
enum, une date ou un identifier Uid.

## Les resolvers ciblés

Deux resolvers portent un autre tag, `controller.targeted_value_resolver` :
`RequestPayloadValueResolver` et `QueryParameterValueResolver`. Ils sont
**retirés de la chaîne de resolvers** et ne s'exécutent que si un attribute les désigne.

`#[ValueResolver]` épingle un resolver par son nom — pour ceux du framework,
leur FQCN. La chaîne de l'argument devient alors **trois** resolvers : celui
épinglé, puis `RequestAttributeValueResolver`, puis `DefaultValueResolver`. La
documentation ne cite que le dernier. Épingler deux resolvers sur un argument
lève une `LogicException` ; `disabled: true` en saute un.

Les attributes de correspondance sont la forme confortable du même mécanisme :

| Attribute | Source | Échec par défaut |
|---|---|---|
| `#[MapQueryParameter]` | un parameter de la query string | **404** |
| `#[MapQueryString]` | la query string entière, vers un object | **404** |
| `#[MapRequestPayload]` | le body de la Request, vers un object | **422** |
| `#[MapUploadedFile]` | un file téléversé | **422** |

Ce qui vient de l'URL échoue en 404, ce qui vient du body en 422 quand il ne
valide pas. Un body illisible — un JSON malformé — donne **400**, un format sans
decoder **415** ; l'option `validationFailedStatusCode` change le 422, pas
ceux-là. `#[MapQueryParameter]` accepte
`filter`, `flags` et `options` ; avec `FILTER_NULL_ON_FAILURE`, une valeur
invalide devient `null` au lieu de lever.

## Pièges d'examen

- La documentation dit « toujours un array » ; le type est `iterable`.
- Un array vide passe la main ; si aucun resolver ne fournit de valeur, une
  `RuntimeException` est levée.
- Le premier resolver qui fournit une valeur **arrête** la chaîne de resolvers.
- Une valeur invalide dans l'URL donne **404**, pas 400.
- `#[MapQueryParameter]` échoue en **404**, `#[MapRequestPayload]` en **422**.
- Un JSON malformé donne **400**, pas 422 : il n'y a rien à valider.
- Un resolver ciblé ne s'exécute pas sans son attribute.
- `SessionInterface $session = null` est déprécié en PHP 8.4.

## Points clés

- `ValueResolverInterface::resolve()` + `ArgumentMetadata`, retour itérable.
- Tag `controller.argument_value_resolver`, ordre par priority, premier servi.
- 120 pour `Request`/`Session`, 100 pour les attributes, −100 pour le défaut.
- Épingler : épinglé, puis attributes, puis défaut.
- URL invalide → 404 ; body qui ne valide pas → 422 ; illisible → 400.

## Sources officielles

- [Value Resolvers](https://github.com/symfony/symfony-docs/blob/8.0/controller/value_resolver.rst)
- [Controller, « Automatic Mapping Of The Request »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
- [`ArgumentResolver`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Controller/ArgumentResolver.php)
- [Priorities dans FrameworkBundle](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/web.php)
- [`MapQueryParameter`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Attribute/MapQueryParameter.php)
- [PHP 8.4, nullable implicite déprécié](https://github.com/php/doc-en/blob/master/appendices/migration84/deprecated.xml)
