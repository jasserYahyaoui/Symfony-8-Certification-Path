---
id: CRS-cb51hcng9dh5
official_item: OIT-kbg00jqxxwhq
title: "Custom commands"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/console.rst"
    anchor: "creating-a-command"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Attribute/AsCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Attribute/AsCommand.php"
    branch: "8.0"
    symbol_or_lines: "AsCommand"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    branch: "8.0"
    symbol_or_lines: "registerAttributeForAutoconfiguration(AsCommand::class) and registerForAutoconfiguration(Command::class) — console.command"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Command/Command.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Command/Command.php"
    branch: "8.0"
    symbol_or_lines: "__construct() — an invokable subclass that does not override execute() runs __invoke()"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console/input.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/console/input.rst"
    branch: "8.0"
    symbol_or_lines: "Interactive Input — #[Ask], #[Interact]"
    verified_at: "2026-10-02"
outcome_sections:
  OUT-86vxgq9k86kd: "La forme recommandée : la command invocable"
  OUT-g23jwhyz5189: "Comment Symfony la trouve"
  OUT-npjsrz7new8m: "La syntaxe classique, toujours valide"
---

## Objectif

Écrire une command à soi et savoir comment Symfony la découvre — deux syntaxes
coexistent en 8.0, et l'examen attend qu'on distingue la recommandée de l'autre.

## Prérequis

Le cycle d'une command et le code de retour.

## La forme recommandée : la command invocable

En Symfony 8.0, une command est **une class ordinaire** portant l'attribute
`#[AsCommand]`, avec une method `__invoke()` qui retourne un int :

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'app:create-user')]
class CreateUserCommand
{
    public function __invoke(): int
    {
        return Command::SUCCESS;
    }
}
```

Le point à noter : **elle n'étend pas `Command`**. La constant `Command::SUCCESS`
reste utilisée pour la lisibilité, mais l'inheritance n'est plus nécessaire.

## Comment Symfony la trouve

La class est un service comme un autre. `#[AsCommand]` est un tag
d'autoconfiguration : le container pose le tag `console.command` sur tout service
qui la porte, et l'`Application` récupère les services ainsi étiquetés.

Lu dans `FrameworkExtension` (8.0), l'autoconfiguration pose ce tag dans
**deux** cas : sur une class qui porte `#[AsCommand]`, et sur **toute
sous-classe de `Command`**, attribute ou non. Exécuté sur FrameworkBundle
8.0.15, quatre classes dans le dossier chargé par `services.yaml` :

| Class | Attribute | Résultat |
|---|---|---|
| étend `Command`, nom posé dans `configure()` | non | **enregistrée**, exécutable |
| invocable, n'étend rien | non | **absente** de l'application |
| invocable, `hidden: true`, alias `app:h` | oui | absente de `list`, exécutable par son nom **et** son alias |
| étend `Command` et définit `__invoke()` | oui | `initialize()` puis `__invoke()` |

Conséquences :

- avec la configuration `services.yaml` par défaut, une class dans `src/Command/`
  est enregistrée sans un mot de configuration ;
- une class invocable **sans** l'attribute n'est pas une command : il faut
  poser le tag `console.command` à la main ;
- une class d'une bibliothèque n'est pas chargée par la ressource `src/` : il
  faut la déclarer comme service — l'autoconfiguration lui pose le tag si elle
  étend `Command`.

## Les dependencies

La command étant un service, on injecte **par le constructor** :

```php
#[AsCommand(name: 'app:create-user')]
class CreateUserCommand
{
    public function __construct(private UserManager $userManager)
    {
    }

    public function __invoke(OutputInterface $output): int
    {
        $this->userManager->create('alice');
        $output->writeln('User successfully generated!');

        return Command::SUCCESS;
    }
}
```

`__invoke()` reçoit ce qu'on lui déclare : l'output, l'entrée, et les arguments
et options décrits par attributes.

## La syntaxe classique, toujours valide

Étendre `Command` et écrire `execute()` reste supporté :

```php
#[AsCommand(name: 'app:create-user')]
class CreateUserCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
```

La documentation la nomme *legacy syntax* et recommande la forme invocable.
Elle garde une utilité concrète : `initialize()` est une method de `Command`,
sans équivalent par attribute. Une command qui en a besoin étend `Command` — et
peut alors définir `__invoke()` ou `execute()` ; lu dans le constructor de
`Command`, une sous-classe qui ne redéfinit pas `execute()` mais est invocable
passe par `__invoke()`.

Pour **interagir**, l'inheritance n'est plus nécessaire en 8.0. Exécuté :

```php
public function __invoke(#[Argument, Ask('Name?')] string $name): int
```

| Lancement | Résultat |
|---|---|
| sans argument, Response `alice` | la question est posée, `$name` vaut `alice` |
| `app:ask bob` | aucune question |
| `app:ask -n` | « Not enough arguments (missing: "name"). », code 1 |

`#[Interact]` sur une method publique couvre la logique d'interaction plus
riche.

## Pièges d'examen

**Une command invocable n'étend rien.** L'ancienne obligation d'hériter de
`Command` n'existe plus.

**`__invoke()` doit retourner un `int`.** C'est l'exit code du process.

**`#[AsCommand]` exige un `name`.** C'est le seul parameter obligatoire ;
`description`, `aliases`, `hidden`, `help` et `usages` sont facultatifs.

**Sans attribute, une sous-classe de `Command` est quand même enregistrée** ;
une class invocable, non.

**`hidden: true` retire la command de `list`, pas de l'application.**

**Interagir ne suppose plus d'étendre `Command`** : `#[Ask]` et `#[Interact]`.
Seul `initialize()` reste propre à `Command`.

## Points clés

- Class ordinaire + `#[AsCommand(name: …)]` + `__invoke(): int`.
- L'attribute, ou l'inheritance de `Command`, déclenche l'autoconfiguration du
  tag `console.command`.
- Dependencies par le constructor : la command est un service.
- `extends Command` avec `execute()` reste supporté, et nécessaire pour
  `initialize()` ; l'interaction passe aussi par `#[Ask]` et `#[Interact]`.

## Sources officielles

- [Console Commands](https://github.com/symfony/symfony-docs/blob/8.0/console.rst)
- [`AsCommand`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Attribute/AsCommand.php)
- [FrameworkBundle 8.0, `FrameworkExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php)
- [Console Input, « Interactive Input »](https://github.com/symfony/symfony-docs/blob/8.0/console/input.rst)
