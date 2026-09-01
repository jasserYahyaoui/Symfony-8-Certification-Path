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
    anchor: "built-in-value-resolvers"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    anchor: "automatic-mapping-of-the-request"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
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

La règle de retour est la contrainte à retenir : `resolve()` retourne **toujours
un tableau** — vide s'il ne sait pas résoudre l'argument, à un élément pour une
valeur, à plusieurs pour un argument variadique. Retourner directement la valeur
est l'erreur classique.

## L'ordre

Pour chaque argument, **tous** les services tagués
`controller.argument_value_resolver` sont appelés à tour de rôle **jusqu'à ce
que l'un fournisse une valeur**. L'ordre dépend de leur **priorité**.

Cet ordre n'est pas décoratif. Le `SessionValueResolver` passe avant le
`DefaultValueResolver` — c'est précisément ce qui permet d'écrire
`SessionInterface $session = null` et d'obtenir la session s'il y en a une, la
valeur par défaut sinon. Inverser les deux rendrait la valeur par défaut
toujours gagnante.

Le résolveur qui lit les attributs de la requête a une priorité de `100` : un
résolveur maison qui lit lui aussi les attributs doit se placer à `100` ou plus.

## Le catalogue

| Résolveur | Ce qu'il remplit |
|---|---|
| `RequestAttributeValueResolver` | un argument dont le nom correspond à un attribut de la requête — les paramètres de route |
| `RequestValueResolver` | un argument typé `Request` |
| `SessionValueResolver` | un argument typé `SessionInterface` |
| `BackedEnumValueResolver` | un cas d'énumération depuis un paramètre de route ; valeur invalide → **404** |
| `UidValueResolver` | un identifiant du composant Uid |
| `DateTimeValueResolver` | un `\DateTimeInterface` depuis une chaîne |
| `RequestPayloadValueResolver` | le corps ou la chaîne de requête vers un objet — **ciblé** |
| `ServiceValueResolver` | un service du conteneur |
| `VariadicValueResolver` | un argument variadique depuis un attribut tableau |
| `DefaultValueResolver` | la valeur par défaut de la signature |

## Les résolveurs ciblés

Certains résolveurs ne s'activent **que** si un attribut les demande. C'est le
sens de « ciblé ». `#[ValueResolver(SessionValueResolver::class)]` désigne
explicitement le résolveur à appeler en premier, ce qui évite d'exécuter toute
la chaîne. Le même attribut, avec `$disabled: true`, **désactive** un résolveur
pour cet argument.

Les attributs de correspondance sont la forme confortable du même mécanisme :

| Attribut | Source | Cible |
|---|---|---|
| `#[MapQueryParameter]` | un paramètre de la chaîne de requête | un argument scalaire, tableau, énumération ou identifiant Uid |
| `#[MapQueryString]` | la chaîne de requête entière | un objet |
| `#[MapRequestPayload]` | le corps de la requête | un objet |
| `#[MapUploadedFile]` | un fichier téléversé | un `UploadedFile` |

`#[MapQueryParameter]` accepte un `filter` — les constantes `FILTER_VALIDATE_*`
de PHP — et ses `options`.

## Pièges d'examen

- `resolve()` retourne un **tableau**, jamais la valeur.
- Un tableau vide signifie « je ne sais pas » et passe la main ; il ne provoque
  pas d'erreur.
- Le premier résolveur qui fournit une valeur **arrête** la chaîne.
- Une valeur d'énumération invalide dans l'URL donne **404**, pas 400.
- Un résolveur ciblé ne s'exécute pas sans son attribut.

## Points clés

- `ValueResolverInterface::resolve()` + `ArgumentMetadata`, retour tableau.
- Tag `controller.argument_value_resolver`, ordre par priorité, premier servi.
- `SessionValueResolver` avant `DefaultValueResolver` : c'est ce qui rend
  `SessionInterface $session = null` utile.
- Les attributs `Map…` sont des résolveurs ciblés, pas un mécanisme distinct.

## Sources officielles

- [Value Resolvers](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/value_resolver.rst)
- [Controller, « Automatic Mapping Of The Request »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst)
