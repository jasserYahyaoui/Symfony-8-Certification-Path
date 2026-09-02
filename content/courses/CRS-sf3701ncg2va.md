---
id: CRS-sf3701ncg2va
official_item: OIT-1hdmw4gm819r
title: "Configuration"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Attribute/AsCommand.php"
    branch: "8.0"
    symbol_or_lines: "AsCommand::__construct"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console.rst"
    anchor: "legacy-syntax-to-define-commands"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-01"
---

## Objectif

Déclarer l'identité d'une commande — nom, description, aide, alias, visibilité —
et savoir quand cette déclaration est lue.

## Prérequis

L'écriture d'une commande personnalisée.

## Tout dans l'attribut

`#[AsCommand]` porte six paramètres :

| Paramètre | Rôle |
|---|---|
| `name` | le nom tapé après `bin/console` — **seul paramètre obligatoire** |
| `description` | la ligne affichée par `list` |
| `help` | le texte long affiché par `--help` |
| `usages` | des exemples d'utilisation, sans répéter le nom de la commande |
| `aliases` | d'autres noms qui lancent la même commande |
| `hidden` | retire la commande de `list` sans l'empêcher de tourner |

```php
#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user.',
    help: 'This command allows you to create a user...',
    usages: ['bob', 'alice --as-admin'],
)]
```

## Les alias

Ils s'écrivent aussi **dans le nom**, séparés par une barre verticale. Le
**premier** est le nom réel, les suivants sont des alias :

```php
#[AsCommand(name: 'app:create-user|app:add-user|app:new-user')]
```

C'est la même chose que le paramètre `aliases` : le constructeur de l'attribut
fusionne les deux dans une seule chaîne séparée par `|`.

## Pourquoi la description compte

Renseigner la description **dans l'attribut** plutôt que par `setDescription()`
permet à Symfony de la lire **sans instancier la classe**. `bin/console list`
n'a alors pas à construire chaque commande de l'application pour afficher son
tableau — la différence de vitesse est visible sur un gros projet.

C'est le seul argument de performance à retenir ici, et il est explicite dans la
documentation.

## La méthode `configure()`

Elle appartient au style classique — une classe qui étend `Command` :

```php
protected function configure(): void
{
    $this
        ->setDescription('Creates a new user.')
        ->setHelp('This command allows you to create a user...')
        ->addArgument('username', InputArgument::REQUIRED, 'How the user should be named?')
    ;
}
```

Le point que l'examen teste est **le moment de son appel** : `configure()` est
appelée **à la fin du constructeur** de `Command`, pas au lancement de la
commande. D'où la conséquence contre-intuitive : si la commande définit son
propre constructeur, il faut affecter ses propriétés **avant** l'appel à
`parent::__construct()`, sinon elles ne sont pas encore là quand `configure()`
s'exécute :

```php
public function __construct(bool $requirePassword = false)
{
    $this->requirePassword = $requirePassword;   // d'abord
    parent::__construct();                       // ensuite : configure() tourne ici
}
```

C'est l'unique cas de la documentation Symfony où l'on est invité à ne *pas*
appeler le constructeur parent en premier.

## Pièges d'examen

**`name` est le seul paramètre obligatoire de `#[AsCommand]`.**

**`configure()` s'exécute à la construction**, pas à l'exécution : elle ne voit
pas l'entrée de l'utilisateur.

**Propriétés avant `parent::__construct()`** quand `configure()` en dépend.

**Une commande `hidden` reste exécutable** — elle disparaît de `list`, elle n'est
pas désactivée.

**Le premier segment avant `|` est le nom**, le reste sont des alias.

## Points clés

- `#[AsCommand]` : `name`, `description`, `help`, `usages`, `aliases`, `hidden`.
- La description dans l'attribut est lue sans instancier la classe.
- Les alias s'écrivent dans le nom avec `|`, ou par `aliases`.
- `configure()` (style classique) est appelée à la fin du constructeur.

## Sources officielles

- [`AsCommand`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Attribute/AsCommand.php)
- [Console Commands](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console.rst)
