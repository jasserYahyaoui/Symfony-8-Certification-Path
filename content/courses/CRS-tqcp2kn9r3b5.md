---
id: CRS-tqcp2kn9r3b5
official_item: OIT-1kjqdnryaw0h
title: "Attributes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/attributes.xml"
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Attribute/Route.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
---

## Objectif

Déclarer un attribut, le poser correctement, et le lire par réflexion — le
mécanisme sur lequel repose une grande partie de la configuration Symfony.

## Déclarer

Un attribut est une classe ordinaire marquée par `#[Attribute]` :

```php
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Route
{
    public function __construct(
        public string $path,
        public ?string $name = null,
    ) {}
}
```

Les cibles disponibles : `TARGET_CLASS`, `TARGET_FUNCTION`, `TARGET_METHOD`,
`TARGET_PROPERTY`, `TARGET_CLASS_CONSTANT`, `TARGET_PARAMETER`, `TARGET_ALL`.
`IS_REPEATABLE` autorise plusieurs occurrences au même endroit.

## Poser

```php
#[Route('/blog', name: 'blog_list')]
public function list(): Response { /* ... */ }
```

Les arguments suivent les règles d'appel habituelles, y compris les arguments
nommés. Ils doivent être des **expressions constantes** : pas d'appel de
fonction, pas de variable.

## Lire

C'est le point souvent négligé : **un attribut ne fait rien tout seul**.

```php
$reflection = new \ReflectionMethod($controller, 'list');

foreach ($reflection->getAttributes(Route::class) as $attribute) {
    $route = $attribute->newInstance();     // instancie ENFIN la classe Route
    echo $route->path;
}
```

`getAttributes()` renvoie des `ReflectionAttribute`, pas des instances.
`newInstance()` est ce qui construit réellement l'objet — et donc ce qui peut
lever si les arguments sont invalides.

## Pièges d'examen

**Déclarer un attribut ne l'exécute pas.** Sans code de réflexion qui le lit,
un attribut est inerte. En Symfony, c'est le framework qui lit.

**`getAttributes()` ne construit rien.** Tant que `newInstance()` n'est pas
appelé, la classe d'attribut n'est pas instanciée et ses erreurs n'apparaissent
pas.

**Les arguments sont des expressions constantes.** `#[Route(path: getPath())]`
ne compile pas.

**Sans `IS_REPEATABLE`, répéter un attribut est une erreur** au moment de la
lecture par réflexion.

## Points clés

- `#[Attribute]` avec des drapeaux de cible ; `IS_REPEATABLE` pour répéter.
- Arguments = expressions constantes, arguments nommés autorisés.
- `getAttributes()` → `ReflectionAttribute` ; `newInstance()` construit l'objet.
- Un attribut est de la donnée : sans lecteur, il ne produit aucun effet.

## Sources officielles

- Manuel PHP — *Attributes*
- `Symfony\Component\Routing\Attribute\Route` (branche 8.0, `6f841c0`)
