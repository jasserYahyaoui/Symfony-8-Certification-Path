---
id: CRS-q2dxjhx6d35k
official_item: OIT-nr9m883d15qq
title: "Options and arguments (using PHP attributes)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Attribute/Option.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Attribute/Option.php"
    branch: "8.0"
    symbol_or_lines: "Option::tryFrom"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Attribute/Argument.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Attribute/Argument.php"
    branch: "8.0"
    symbol_or_lines: "Argument::tryFrom"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console/input.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/console/input.rst"
    anchor: "console-input-options"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Input/InputDefinition.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Input/InputDefinition.php"
    branch: "8.0"
    symbol_or_lines: "addArgument() — no argument after an array argument; no required after optional"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Attribute/Option.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Attribute/Option.php"
    branch: "8.0"
    symbol_or_lines: "tryFrom() — union types, default false, allowed types, BackedEnum suggestions"
    verified_at: "2026-10-02"
outcome_sections:
  OUT-ghsttz17xxqe: "Déclarer"
  OUT-dvv3qwaprsck: "Le mode d'une option"
  OUT-2tyae0a8545m: "L'équivalent classique"
---

## Objectif

Déclarer l'entrée d'une command par attributes. Le mode — requis, optionnel,
array, drapeau — n'est plus écrit : il est **déduit de la signature**. Savoir
lire cette déduction est l'essentiel de l'item.

## Prérequis

L'écriture d'une command invocable.

## La différence de fond

Un **argument** est positionnel : `app:greet Alice`. Une **option** est nommée
et précédée de tirets : `--iterations=5`, `-i 5`. C'est la distinction qui
gouverne tout le reste.

## Déclarer

Les deux attributes se posent sur les **parameters de `__invoke()`** :

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

Chaque attribute accepte `description`, `name` et `suggestedValues` ; `#[Option]`
accepte en plus `shortcut`.

Le `name` est facultatif : par défaut c'est **le nom du parameter en
kebab-case** — `$lastName` devient `last-name`, `$maxRetries` devient
`max-retries`. C'est aussi la key de `$input->getArgument('last-name')`.

## Le mode d'un argument

Il se déduit du type et de la default value :

| Signature | Mode |
|---|---|
| `string $name` | **requis** |
| `string $name = ''` ou `?string $name = null` | optionnel |
| `array $names = []` | array |

La règle tient en une phrase : **sans default value et non nullable, c'est
requis**.

## Le mode d'une option

Même principe, mais avec une constraint supplémentaire et un array plus riche :

| Signature | Mode | Usage |
|---|---|---|
| `bool $yell = false` | `VALUE_NONE` | `--yell` |
| `bool $yell = true` ou `?bool $yell = null` | `VALUE_NONE` + `VALUE_NEGATABLE` | `--yell` ou `--no-yell` |
| `string $format = 'json'`, `int $limit = 10` | `VALUE_REQUIRED` | `--format=csv` |
| `array $roles = []` | `VALUE_REQUIRED` + `VALUE_IS_ARRAY` | `--roles=ADMIN --roles=USER` |
| `string\|bool $output = false` | `VALUE_OPTIONAL` | `--output` ou `--output=f.txt` |

La constraint : **une option doit déclarer une default value**. Sans elle,
la command lève une `LogicException` dès son ajout à l'application — exécuté :
« must declare a default value » —, pas une erreur à l'exécution, une erreur
de definition. La logique est cohérente : une option est
par nature facultative, elle a donc toujours une valeur en l'absence de saisie.

Le cas des types union mérite attention : seuls `bool|string`, `bool|int` et
`bool|float` sont acceptés, et leur default value **doit être `false`**.
`--output` seul rend alors `true`, `--output=f.txt` rend la string.

Exécuté avec Console 8.0.15 :

| Déclaration | Résultat |
|---|---|
| `int\|string $x = 1` | `LogicException` : seuls `bool\|string`, `bool\|int`, `bool\|float` |
| `bool\|string $x = 'x'` | `LogicException` : « must have a default value of false » |
| `bool $loud = true`, sans option | `true` ; avec `--no-loud`, `false` |
| `Fmt $fmt`, `--fmt=xml` | « The value "xml" is not valid for the "fmt" option », code 1 |

## Les types admis

`string`, `bool`, `int`, `float`, `array` — et les **enums typées**
(`BackedEnum`). Tout autre type lève une `LogicException`. Avec une enum,
la string saisie est convertie en cas de l'enum, et les cas alimentent
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

Les attributes ne font rien d'autre que produire ces objects : `#[Argument]`
fabrique un `InputArgument`, `#[Option]` un `InputOption`. Connaître les
constantes reste donc utile pour lire la déduction.

## Pièges d'examen

**Une option sans default value est une erreur**, pas une option requise.
Une option n'est jamais requise ; c'est un argument qui peut l'être.

**`bool $flag = true` n'est pas un drapeau simple** : il accepte une forme
négative, donc `--no-flag` existe.

**Le nom par défaut est kebab-case**, pas le nom du parameter tel quel.

**`shortcut` n'existe que pour les options** — un argument n'a pas de nom, donc
pas de raccourci.

**L'ordre des arguments est contraint.** `InputDefinition` lève une
`LogicException` pour un argument requis déclaré après un argument optionnel,
et pour **tout** argument déclaré après un argument array — celui-ci
absorbant toutes les valeurs positionnelles restantes. Exécuté :
`array $a = [], string $b = ''` est refusé, même `$b` optionnel, avec un
message trompeur : « Cannot add a required argument "b" after an array
argument "a". »

## Points clés

- `#[Argument]` et `#[Option]` se posent sur les parameters de `__invoke()`.
- Le mode est déduit du type et de la default value, jamais écrit.
- Une option **doit** avoir une default value ; un argument sans défaut est
  requis.
- Types admis : `string`, `bool`, `int`, `float`, `array`, `BackedEnum`.
- Les attributes produisent des `InputArgument` et `InputOption` ordinaires.

## Sources officielles

- [Console Input](https://github.com/symfony/symfony-docs/blob/8.0/console/input.rst)
- [`Attribute\Option`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Attribute/Option.php)
- [`Attribute\Argument`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Attribute/Argument.php)
