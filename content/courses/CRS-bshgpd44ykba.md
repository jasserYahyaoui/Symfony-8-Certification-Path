---
id: CRS-bshgpd44ykba
official_item: OIT-n0zfkcr189af
title: "Traits"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/traits.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: "sections Precedence and Conflict Resolution"
    verified_at: "2026-09-01"
---

## Objectif

Composer des traits, résoudre un conflit, et connaître l'ordre de précédence —
qui est contre-intuitif.

## Ce qu'un trait apporte

Un trait est une **unité de réutilisation horizontale** : du code partagé entre
classes sans relation d'héritage. Il ne peut pas être instancié.

```php
trait Timestampable
{
    private ?\DateTimeImmutable $updatedAt = null;   // propriétés autorisées

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    abstract public function getId(): int;            // exigence sur la classe hôte
}

class Article
{
    use Timestampable;

    public function getId(): int { return 1; }
}
```

Un trait peut contenir propriétés, méthodes statiques, méthodes abstraites et
constantes.

## L'ordre de précédence

C'est le point le plus piégeux :

```text
méthodes de la classe courante
  > méthodes du trait
    > méthodes héritées de la classe parente
```

Une méthode définie dans la classe **écrase** celle du trait. Le trait, lui,
écrase celle du parent. L'intuition inverse — « le trait, plus proche, gagne
sur la classe » — est fausse.

## Résolution de conflit

Deux traits fournissant la même méthode provoquent une **erreur fatale** tant
que le conflit n'est pas arbitré :

```php
class Reporter
{
    use Csv, Json {
        Csv::export insteadof Json;   // on choisit
        Json::export as exportJson;   // on garde l'autre sous un alias
    }
}
```

- `insteadof` **écarte** une implémentation.
- `as` crée un **alias**, et peut aussi changer la visibilité :
  `Csv::export as protected internalExport;`

## Pièges d'examen

**Le conflit n'est pas résolu automatiquement.** Sans `insteadof`, c'est une
erreur fatale — pas un choix silencieux du premier trait.

**`as` n'écarte rien.** Il ajoute un nom ; l'original reste. Utiliser `as` seul
face à un conflit ne le résout pas.

**Un trait n'est pas un type.** `$x instanceof Timestampable` ne compile pas
comme test de trait — pour typer, il faut une interface.

## Points clés

- Précédence : classe courante > trait > classe parente.
- Conflit non arbitré = erreur fatale ; `insteadof` écarte, `as` renomme.
- Un trait porte de l'état, mais n'est pas un type.

## Sources officielles

- Manuel PHP — *Traits*, sections *Precedence* et *Conflict Resolution*
