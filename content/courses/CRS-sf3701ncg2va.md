---
id: CRS-sf3701ncg2va
official_item: OIT-1hdmw4gm819r
title: "Configuration"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Attribute/AsCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Attribute/AsCommand.php"
    branch: "8.0"
    symbol_or_lines: "AsCommand::__construct"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/console.rst"
    anchor: "legacy-syntax-to-define-commands"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/DependencyInjection/AddConsoleCommandPass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/DependencyInjection/AddConsoleCommandPass.php"
    branch: "8.0"
    symbol_or_lines: "process() — empty first segment means hidden; description from the tag makes a LazyCommand"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    branch: "8.0"
    symbol_or_lines: "registerAttributeForAutoconfiguration(AsCommand::class) — tag carries command, description, help"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Command/Command.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Command/Command.php"
    branch: "8.0"
    symbol_or_lines: "__construct() calls configure(); addUsage() prefixes the command name"
    verified_at: "2026-10-02"
outcome_sections:
  OUT-axs8gacjytk9: "Tout dans l'attribute"
  OUT-e1vg5bhm9yn9: "Les alias"
  OUT-szgbkmx55kyh: "La method `configure()`"
---

## Objectif

Déclarer l'identité d'une command — nom, description, aide, alias, visibilité —
et savoir quand cette déclaration est lue.

## L'essentiel

L'identité d'une command — nom, description, aide, usages, alias, visibilité
— se déclare dans **`#[AsCommand]`**, dont seul `name` est obligatoire. La
déclarer là permet à Symfony de la lire **sans construire la class**. Le style
classique la pose dans `configure()`, qui tourne **dans le constructor**.

## Le schéma

```text
#[AsCommand(name: 'app:create-user|app:add-user', description: …, help: …, usages: […], hidden: …)]
name     premier segment = le nom ; les suivants = des alias ; premier segment VIDE = cachée
lecture  description dans l'attribute → `list` ne construit pas la class
         description dans configure() → `list` construit la class
configure()  appelée à la FIN du constructor de Command → properties AVANT parent::__construct()
```

## L'exemple : créer un user

```php
#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user.',
    help: 'This command allows you to create a user...',
    usages: ['bob', 'alice --as-admin'],
    aliases: ['app:add-user'],
)]
class CreateUserCommand
{
    public function __invoke(): int
    {
        return Command::SUCCESS;
    }
}
```

## Tout dans l'attribute

| Parameter | Role |
|---|---|
| `name` | le nom tapé après `bin/console` — **seul obligatoire** |
| `description` | la ligne affichée par `list` |
| `help` | le texte long de `--help` |
| `usages` | des exemples, sans répéter le nom |
| `aliases` | d'autres noms pour la même command |
| `hidden` | retire la command de `list`, sans l'empêcher de tourner |

- Une description dans l'attribute est lue **sans instancier** la class : la
  command n'est pas construite par `list` ; posée par `setDescription()`, si.
- La documentation montre `usages` dans l'attribute, mais FrameworkBundle 8.0
  ne transmet que `name`, `description` et `help` au tag `console.command` :
  une command invocable y perd ses usages (`getUsages()` vaut `[]`).

## Les alias

```php
#[AsCommand(name: 'app:create-user|app:add-user|app:new-user')]
```

Le **premier** segment est le nom, les suivants des alias — la même chose que
`aliases`, que l'attribute fusionne avec `|`. Un **premier segment vide**
cache la command : `name: '|app:secret'` équivaut à `hidden: true`, et la
command reste exécutable.

## La method `configure()`

```php
protected function configure(): void
{
    $this
        ->setDescription('Creates a new user.')
        ->addArgument('username', InputArgument::REQUIRED, 'How the user should be named?')
    ;
}
```

Elle appartient au style classique, et elle est appelée **à la fin du
constructor** de `Command`, pas au lancement. Une command qui définit son
propre constructor affecte donc ses properties **avant**
`parent::__construct()` :

```php
public function __construct(bool $requirePassword = false)
{
    $this->requirePassword = $requirePassword;   // d'abord
    parent::__construct();                       // ensuite : configure() tourne ici
}
```

## Pièges d'examen

**`name` est le seul parameter obligatoire de `#[AsCommand]`.**

**`configure()` s'exécute à la construction**, pas à l'exécution : elle ne voit
pas l'entrée du user.

**Properties avant `parent::__construct()`** quand `configure()` en dépend.

**Une command `hidden` reste exécutable** — elle disparaît de `list`, elle n'est
pas désactivée.

**Le premier segment avant `|` est le nom**, le reste sont des alias ; un
premier segment **vide** cache la command.

**Des `usages` dans l'attribute d'une command invocable sont ignorés** dans une
application FrameworkBundle 8.0.

## Application

- Une command lente à construire, listée par `bin/console list` → sa
  description dans `#[AsCommand]`.
- Un ancien nom à garder pour les scripts existants → un alias,
  `name: 'app:new|app:old'`.

## QCM d'entraînement

**Question 1.** `#[AsCommand(name: 'app:create-user|app:add-user|app:new-user')]`.
Quel est le nom réel de la command ?

- **A.** `app:create-user|app:add-user|app:new-user`, tel quel
- **B.** `app:new-user`, le dernier segment
- **C.** `app:create-user`
- **D.** Aucun : le nom ne peut pas contenir `|`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le premier segment est le nom ; `app:add-user` et
`app:new-user` deviennent des alias de la même command.

</details>

**Question 2.** Une command porte `#[AsCommand(name: '|app:secret')]`. Que
donne `bin/console app:secret` ?

- **A.** Elle s'exécute, hors de `list`
- **B.** « Command "app:secret" is not defined. »
- **C.** Une erreur de nom invalide
- **D.** Elle s'exécute et figure dans `list`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Un premier segment vide équivaut à `hidden: true` :
la command disparaît de `list` mais reste exécutable.

</details>

**Question 3.** Une sous-classe de `Command` déclare `private bool $requirePassword`,
appelle `parent::__construct()`, **puis** affecte la property, que
`configure()` lit. Que se passe-t-il à la construction ?

- **A.** `configure()` attend que la property soit affectée
- **B.** Une `Error` : property lue avant son initialisation
- **C.** `configure()` lit `false`, la default value d'un bool
- **D.** Rien : `configure()` ne tourne qu'au lancement

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `configure()` tourne à la fin du constructor parent,
avant l'affectation : la property typée n'est pas encore initialisée.

</details>

**Question 4.** Dans une application FrameworkBundle, une command pose sa
description dans `#[AsCommand(description: …)]`. Que fait
`bin/console list` ?

- **A.** Il construit la command pour lire sa description
- **B.** Il n'affiche pas la description
- **C.** Il lit la description sans construire la class
- **D.** Il exige aussi `setDescription()`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La description de l'attribute est lue à la
compilation ; posée dans `configure()`, elle forcerait la construction.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- `#[AsCommand]` : **`name`** seul obligatoire ; `description`, `help`,
  `usages`, `aliases`, `hidden`.
- `'a|b|c'` : **`a`** est le nom, `b` et `c` des alias.
- Premier segment **vide** : command cachée, mais exécutable.
- Description dans l'attribute : **pas de construction** pour `list`.
- `usages` d'une command invocable : **perdus** dans FrameworkBundle 8.0.
- `configure()` à la **fin du constructor** : properties d'abord.

Moyen mnémotechnique : **« Déclarer dans l'attribute, configurer dans le
constructor »**.

## Sources officielles

- [`AsCommand`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Attribute/AsCommand.php)
- [Console Commands](https://github.com/symfony/symfony-docs/blob/8.0/console.rst)
- [`AddConsoleCommandPass`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/DependencyInjection/AddConsoleCommandPass.php)
- [FrameworkBundle 8.0, `FrameworkExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php)

