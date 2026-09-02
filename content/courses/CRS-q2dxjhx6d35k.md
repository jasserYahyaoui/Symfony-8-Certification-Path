---
id: CRS-q2dxjhx6d35k
official_item: OIT-nr9m883d15qq
title: "Options and arguments (using PHP attributes)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Attribute/Option.php"
    branch: "8.0"
    symbol_or_lines: "Option::tryFrom"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Attribute/Argument.php"
    branch: "8.0"
    symbol_or_lines: "Argument::tryFrom"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console/input.rst"
    anchor: "console-input-options"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-01"
---

## Objectif

Déclarer l'entrée d'une commande par attributs. Le mode — requis, optionnel,
tableau, drapeau — n'est plus écrit : il est **déduit de la signature**. Savoir
lire cette déduction est l'essentiel de l'item.

## Prérequis

L'écriture d'une commande invocable.

## La différence de fond

Un **argument** est positionnel : `app:greet Alice`. Une **option** est nommée
et précédée de tirets : `--iterations=5`, `-i 5`. C'est la distinction qui
gouverne tout le reste.

## Déclarer

Les deux attributs se posent sur les **paramètres de `__invoke()`** :

```php
#[AsCommand(name: 'app:greet')]
class GreetCommand
{
    public function __invoke(
        #[Argument] string $name,
        #[Argument] string $lastName = '',
        #[Option] int $iterations = 1,
        #[Option] bool $yell = false,
    ): int {
        // ...
    }
}
```

Chaque attribut accepte `description`, `name` et `suggestedValues` ; `#[Option]`
accepte en plus `shortcut`.

Le `name` est facultatif : par défaut c'est **le nom du paramètre en
kebab-case** — `$lastName` devient `last-name`, `$maxRetries` devient
`max-retries`. C'est aussi la clé de `$input->getArgument('last-name')`.

## Le mode d'un argument

Il se déduit du type et de la valeur par défaut :

| Signature | Mode |
|---|---|
| `string $name` | **requis** |
| `string $name = ''` ou `?string $name = null` | optionnel |
| `array $names = []` | tableau |

La règle tient en une phrase : **sans valeur par défaut et non nullable, c'est
requis**.

## Le mode d'une option

Même principe, mais avec une contrainte supplémentaire et un tableau plus riche :

| Signature | Mode | Usage |
|---|---|---|
| `bool $yell = false` | `VALUE_NONE` | `--yell` |
| `bool $yell = true` ou `?bool $yell = null` | `VALUE_NEGATABLE` | `--yell` ou `--no-yell` |
| `string $format = 'json'`, `int $limit = 10` | `VALUE_REQUIRED` | `--format=csv` |
| `array $roles = []` | `VALUE_IS_ARRAY` | `--role=ADMIN --role=USER` |
| `string\|bool $output = false` | `VALUE_OPTIONAL` | `--output` ou `--output=f.txt` |

La contrainte : **une option doit déclarer une valeur par défaut**. Sans elle,
la commande lève une `LogicException` au démarrage — pas une erreur à
l'exécution, une erreur de définition. La logique est cohérente : une option est
par nature facultative, elle a donc toujours une valeur en l'absence de saisie.

Le cas des types union mérite attention : seuls `bool|string`, `bool|int` et
`bool|float` sont acceptés, et leur valeur par défaut **doit être `false`**.
`--output` seul rend alors `true`, `--output=f.txt` rend la chaîne.

## Les types admis

`string`, `bool`, `int`, `float`, `array` — et les **énumérations typées**
(`BackedEnum`). Tout autre type lève une `LogicException`. Avec une énumération,
la chaîne saisie est convertie en cas de l'énumération, et les cas alimentent
l'autocomplétion ; une valeur inconnue produit une erreur listant les valeurs
valides.

## L'équivalent classique

Le style qui étend `Command` déclare la même chose dans `configure()`, avec les
constantes explicites :

```php
$this
    ->addArgument('name', InputArgument::REQUIRED, 'Who to greet')
    ->addOption('iterations', 'i', InputOption::VALUE_REQUIRED, 'How many times', 1)
;
```

`InputArgument` : `REQUIRED`, `OPTIONAL`, `IS_ARRAY`.
`InputOption` : `VALUE_NONE`, `VALUE_REQUIRED`, `VALUE_OPTIONAL`,
`VALUE_IS_ARRAY`, `VALUE_NEGATABLE`.

Les attributs ne font rien d'autre que produire ces objets : `#[Argument]`
fabrique un `InputArgument`, `#[Option]` un `InputOption`. Connaître les
constantes reste donc utile pour lire la déduction.

## Pièges d'examen

**Une option sans valeur par défaut est une erreur**, pas une option requise.
Une option n'est jamais requise ; c'est un argument qui peut l'être.

**`bool $flag = true` n'est pas un drapeau simple** : il devient négociable, donc
`--no-flag` existe.

**Le nom par défaut est kebab-case**, pas le nom du paramètre tel quel.

**`shortcut` n'existe que pour les options** — un argument n'a pas de nom, donc
pas de raccourci.

**L'ordre des arguments est contraint.** `InputDefinition` lève une
`LogicException` pour un argument **requis** déclaré après un argument optionnel,
et pour un argument **requis** déclaré après un argument tableau — celui-ci
absorbant toutes les valeurs positionnelles restantes.

## Points clés

- `#[Argument]` et `#[Option]` se posent sur les paramètres de `__invoke()`.
- Le mode est déduit du type et de la valeur par défaut, jamais écrit.
- Une option **doit** avoir une valeur par défaut ; un argument sans défaut est
  requis.
- Types admis : `string`, `bool`, `int`, `float`, `array`, `BackedEnum`.
- Les attributs produisent des `InputArgument` et `InputOption` ordinaires.

## Sources officielles

- [Console Input](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console/input.rst)
- [`Attribute\Option`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Attribute/Option.php)
- [`Attribute\Argument`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Attribute/Argument.php)
