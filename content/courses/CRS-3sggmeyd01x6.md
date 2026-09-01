---
id: CRS-3sggmeyd01x6
official_item: OIT-hqz5c2qse24w
title: "Enums"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/enumerations.xml"
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
---

## Objectif

Distinguer enum pure et enum adossée, choisir entre `from()` et `tryFrom()`, et
savoir ce qu'une enum ne peut pas faire.

## Pure ou adossée

```php
enum Status                       // pure : les cas n'ont pas de valeur scalaire
{
    case Draft;
    case Published;
}

enum Suit: string                 // adossée (backed) : chaque cas a une valeur
{
    case Hearts = 'H';
    case Spades = 'S';
}
```

Le type adossé ne peut être que `int` ou `string`. Les valeurs doivent être
uniques et être des expressions constantes.

## L'API

Toutes les enums exposent :

```php
Suit::cases();          // [Suit::Hearts, Suit::Spades]
$suit->name;            // 'Hearts'  — readonly
```

Les enums **adossées uniquement** ajoutent :

```php
$suit->value;               // 'H' — readonly
Suit::from('H');            // Suit::Hearts, sinon \ValueError
Suit::tryFrom('X');         // null si aucun cas ne correspond
Suit::tryFrom('X') ?? Suit::Spades;
```

Une enum adossée implémente automatiquement l'interface interne `BackedEnum` ;
toute enum implémente `UnitEnum`.

## Ce qu'une enum peut faire

Méthodes, méthodes statiques, constantes, implémentation d'interfaces, et
attributs. Une constante peut désigner un cas, créant un alias.

```php
enum Suit: string implements Colorful
{
    case Hearts = 'H';
    case Spades = 'S';

    const Default = self::Spades;          // alias

    public function color(): string
    {
        return match ($this) {
            self::Hearts => 'Red',
            self::Spades => 'Black',
        };
    }
}
```

## Ce qu'elle ne peut pas faire

**Aucun état.** Une enum n'a pas de propriétés d'instance : ses cas sont des
singletons. `$suit->value` est en lecture seule, et toute modification indirecte
est une erreur fatale.

**Pas de constructeur**, pas d'héritage (`extends` interdit), pas
d'instanciation par `new`.

## Pièges d'examen

**`from()` lève, `tryFrom()` renvoie `null`.** Sur une entrée utilisateur,
`from()` produit une `\ValueError` non rattrapée. C'est la distinction la plus
testée.

**Redéfinir `from()` ou `tryFrom()` sur une enum adossée est une erreur
fatale.** Ces méthodes sont fournies par le moteur.

**`cases()` sur une enum adossée renvoie les cas, pas les valeurs.** Pour les
valeurs : `array_column(Suit::cases(), 'value')`.

**Une enum pure n'a pas de `->value`.** Y accéder est une erreur.

## Points clés

- Pure = pas de valeur ; adossée = `int` ou `string`, unique et constante.
- `from()` lève `\ValueError` ; `tryFrom()` renvoie `null`.
- Méthodes, constantes et interfaces autorisées ; état, héritage et `new` non.
- `name` et `value` sont en lecture seule.

## Sources officielles

- Manuel PHP — *Enumerations*
