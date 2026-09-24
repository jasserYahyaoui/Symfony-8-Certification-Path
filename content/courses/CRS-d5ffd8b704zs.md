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
---

## Objectif

Comprendre le mécanisme qui remplit les arguments d'un contrôleur : qui décide,
dans quel ordre, et comment s'y insérer. C'est ce mécanisme qui explique
pourquoi typer `Request`, `SessionInterface` ou une énumération suffit.

## Prérequis

Le trajet d'une requête et l'événement `kernel.controller_arguments`.

## Le contrat

Le noyau ne sait pas remplir un argument : il délègue à une chaîne de
**résolveurs**. Chacun implémente `ValueResolverInterface`, dont l'unique
méthode est :

```php
public function resolve(Request $request, ArgumentMetadata $argument): iterable;
```

`ArgumentMetadata` porte tout ce que la signature déclare : nom, type,
variadicité, présence d'une valeur par défaut, attributs.

La documentation le formule ainsi : retourner **toujours un tableau** — vide s'il
ne sait pas résoudre l'argument, à un élément pour une valeur, à plusieurs pour
un argument variadique. Le type déclaré est `iterable`, et `ArgumentResolver`
parcourt le résultat par `foreach` : un générateur fonctionne aussi. Retourner
la valeur elle-même est l'erreur classique — le type de retour la refuse.

## Ce que fait `ArgumentResolver`

Pour chaque argument, il appelle les résolveurs dans l'ordre :

- le **premier** qui produit au moins une valeur **arrête** la chaîne pour cet
  argument ;
- plus d'une valeur pour un argument non variadique lève une
  `InvalidArgumentException` ;
- si **aucun** ne produit de valeur, il lève une `RuntimeException` :
  « Controller … requires the "$x" argument that could not be resolved ».

Un tableau vide passe donc la main sans erreur ; c'est l'échec de **toute** la
chaîne qui en produit une.

## L'ordre

Les services tagués `controller.argument_value_resolver` sont triés par
**priorité**, lue dans la configuration de FrameworkBundle :

| Priorité | Résolveurs |
|---|---|
| 120 | `RequestValueResolver`, `SessionValueResolver` |
| 100 | `RequestAttributeValueResolver`, `BackedEnumValueResolver`, `UidValueResolver`, `DateTimeValueResolver` |
| −50 | `ServiceValueResolver` |
| −100 | `DefaultValueResolver` |
| −150 | `VariadicValueResolver` |

Cet ordre n'est pas décoratif. `SessionValueResolver` passe avant
`DefaultValueResolver` : c'est ce qui permet d'obtenir la session s'il y en a
une, `null` sinon. Un résolveur maison qui lit les attributs doit se placer à
`100` ou plus.

### L'exemple de la documentation et PHP 8.4

La documentation écrit `SessionInterface $session = null`. Symfony 8.0 exige
PHP 8.4, qui **déprécie** ce nullable implicite. La forme à écrire est
`?SessionInterface $session = null`. Le mécanisme est inchangé : sans valeur par
défaut, un type nullable reçoit déjà `null` par `DefaultValueResolver`.

## Le catalogue

| Résolveur | Ce qu'il remplit |
|---|---|
| `RequestAttributeValueResolver` | un argument dont le nom correspond à un attribut de la requête — les paramètres de route |
| `RequestValueResolver` | un argument typé `Request` ou une sous-classe |
| `SessionValueResolver` | un argument typé `SessionInterface` |
| `BackedEnumValueResolver` | un cas d'énumération depuis un paramètre de route |
| `UidValueResolver` | un identifiant du composant Uid |
| `DateTimeValueResolver` | un `\DateTimeInterface` depuis une chaîne — un `DateTimeImmutable` si le type est l'interface |
| `ServiceValueResolver` | un service, lu dans le localisateur propre à l'action, par nom d'argument |
| `DefaultValueResolver` | la valeur par défaut, ou `null` pour un type nullable |
| `VariadicValueResolver` | un argument variadique depuis un attribut tableau |

Deux précisions de code. `RequestValueResolver` reconnaît un type qui se termine
par `\Request` sans être celui de HttpFoundation et répond « Looks like you
required a Request object with the wrong class name » — le signe d'un mauvais
import. Et une valeur invalide venue de l'URL donne **404** pour une
énumération, une date ou un identifiant Uid.

## Les résolveurs ciblés

Deux résolveurs portent un autre tag, `controller.targeted_value_resolver` :
`RequestPayloadValueResolver` et `QueryParameterValueResolver`. Ils sont
**retirés de la chaîne** et ne s'exécutent que si un attribut les désigne.

`#[ValueResolver]` épingle un résolveur par son nom — pour ceux du framework,
leur FQCN. La chaîne de l'argument devient alors **trois** résolveurs : celui
épinglé, puis `RequestAttributeValueResolver`, puis `DefaultValueResolver`. La
documentation ne cite que le dernier. Épingler deux résolveurs sur un argument
lève une `LogicException` ; `disabled: true` en saute un.

Les attributs de correspondance sont la forme confortable du même mécanisme :

| Attribut | Source | Échec par défaut |
|---|---|---|
| `#[MapQueryParameter]` | un paramètre de la chaîne de requête | **404** |
| `#[MapQueryString]` | la chaîne de requête entière, vers un objet | **404** |
| `#[MapRequestPayload]` | le corps de la requête, vers un objet | **422** |
| `#[MapUploadedFile]` | un fichier téléversé | **422** |

Ce qui vient de l'URL échoue en 404, ce qui vient du corps en 422 ; l'option
`validationFailedStatusCode` change le statut. `#[MapQueryParameter]` accepte
`filter`, `flags` et `options` ; avec `FILTER_NULL_ON_FAILURE`, une valeur
invalide devient `null` au lieu de lever.

## Pièges d'examen

- La documentation dit « toujours un tableau » ; le type est `iterable`.
- Un tableau vide passe la main ; si aucun résolveur ne fournit de valeur, une
  `RuntimeException` est levée.
- Le premier résolveur qui fournit une valeur **arrête** la chaîne.
- Une valeur invalide dans l'URL donne **404**, pas 400.
- `#[MapQueryParameter]` échoue en **404**, `#[MapRequestPayload]` en **422**.
- Un résolveur ciblé ne s'exécute pas sans son attribut.
- `SessionInterface $session = null` est déprécié en PHP 8.4.

## Points clés

- `ValueResolverInterface::resolve()` + `ArgumentMetadata`, retour itérable.
- Tag `controller.argument_value_resolver`, ordre par priorité, premier servi.
- 120 pour `Request`/`Session`, 100 pour les attributs, −100 pour le défaut.
- Épingler : épinglé, puis attributs, puis défaut.
- URL invalide → 404 ; corps invalide → 422.

## Sources officielles

- [Value Resolvers](https://github.com/symfony/symfony-docs/blob/8.0/controller/value_resolver.rst)
- [Controller, « Automatic Mapping Of The Request »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
- [`ArgumentResolver`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Controller/ArgumentResolver.php)
- [Priorités dans FrameworkBundle](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/web.php)
- [`MapQueryParameter`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Attribute/MapQueryParameter.php)
- [PHP 8.4, nullable implicite déprécié](https://github.com/php/doc-en/blob/master/appendices/migration84/deprecated.xml)
