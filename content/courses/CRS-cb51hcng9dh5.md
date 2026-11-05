---
id: CRS-cb51hcng9dh5
official_item: OIT-kbg00jqxxwhq
title: "Custom commands"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
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

## L'essentiel

En 8.0, une command est **une class ordinaire** : `#[AsCommand(name: …)]` et
une method `__invoke()` qui retourne un int — elle n'étend rien. C'est un
service, découvert par autoconfiguration. Étendre `Command` et écrire
`execute()` reste valide (*legacy syntax*), et sert encore pour
`initialize()`.

## Le schéma

```text
src/Command/CreateUserCommand.php ─ chargé par services.yaml
  #[AsCommand] ──────────────┐
  ou extends Command ────────┴─► tag console.command (autoconfiguration) ─► Application
  invocable SANS #[AsCommand] ──► pas de tag : pas une command
__invoke(OutputInterface, InputInterface, #[Argument], #[Option], #[Ask]…) : int
```

## L'exemple : créer un user

```php
#[AsCommand(name: 'app:create-user', description: 'Creates a user')]
class CreateUserCommand
{
    public function __construct(private UserManager $userManager)
    {
    }

    public function __invoke(OutputInterface $output, #[Argument, Ask('Name?')] string $name): int
    {
        $this->userManager->create($name);
        $output->writeln('User successfully generated!');

        return Command::SUCCESS;
    }
}
```

Les dependencies arrivent par le constructor ; `__invoke()` reçoit l'output,
l'entrée, et les arguments et options décrits par attributes.

## La forme recommandée : la command invocable

- La class **n'étend pas `Command`** ; la constant `Command::SUCCESS` reste
  utilisée pour la lisibilité.
- `#[AsCommand]` n'exige que `name` ; `description`, `aliases`, `hidden`,
  `help` et `usages` sont facultatifs.
- `#[Ask]` pose la question si l'argument manque ; `#[Interact]` sur une method
  publique couvre une interaction plus riche. Avec `-n` et sans argument :
  « Not enough arguments (missing: "name"). », code 1.

## Comment Symfony la trouve

Lu dans `FrameworkExtension` (8.0), l'autoconfiguration pose le tag
`console.command` sur une class qui porte `#[AsCommand]`, et sur **toute
sous-classe de `Command`**.

| Class | Attribute | Résultat |
|---|---|---|
| étend `Command`, nom posé dans `configure()` | non | **enregistrée** |
| invocable, n'étend rien | non | **absente** de l'application |
| invocable, `hidden: true`, alias `app:h` | oui | absente de `list`, exécutable par son nom **et** son alias |

Une class de bibliothèque, hors de la ressource `src/`, se déclare comme
service.

## La syntaxe classique, toujours valide

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

La documentation la nomme *legacy syntax*. `initialize()` est une method de
`Command`, sans équivalent par attribute : une command qui en a besoin étend
`Command`, et peut alors définir `__invoke()` au lieu d'`execute()` —
`initialize()` puis `__invoke()`.

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

## Application

- Une command qui a besoin d'un service → le type-hinter dans le constructor.
- Une command de maintenance à ne pas lister → `hidden: true`, toujours
  exécutable par son nom.

## QCM d'entraînement

**Question 1.** Une class invocable de `src/Command/` a une method
`__invoke(): int` mais **pas** `#[AsCommand]`. Que devient-elle ?

- **A.** Une command nommée d'après sa class
- **B.** Une erreur de compilation du container
- **C.** Rien : elle n'est pas une command
- **D.** Une command cachée, absente de `list`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Sans l'attribute et sans inheritance de `Command`,
l'autoconfiguration ne pose pas le tag `console.command`.

</details>

**Question 2.** Une command porte
`#[AsCommand(name: 'app:hid', aliases: ['app:h'], hidden: true)]`. Que
donne `bin/console app:h` ?

- **A.** Elle s'exécute
- **B.** « Command "app:h" is not defined. »
- **C.** Elle s'exécute seulement avec `-v`
- **D.** Une demande de confirmation

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `hidden: true` retire la command de `list`, pas de
l'application : son nom et ses alias restent exécutables.

</details>

**Question 3.** `__invoke(#[Argument, Ask('Name?')] string $name)` ; la
command est lancée avec `-n`, sans argument. Que se passe-t-il ?

- **A.** La question est posée malgré `-n`
- **B.** `$name` vaut une string vide
- **C.** `$name` vaut `null`
- **D.** « Not enough arguments »

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `-n` empêche la question ; l'argument requis manque
alors à la validation de l'entrée, avant `__invoke()`, et le code vaut 1.

</details>

**Question 4.** Une class étend `Command`, redéfinit `initialize()` et définit
`__invoke()`, sans `execute()`. Que se passe-t-il au lancement ?

- **A.** Une exception : `execute()` est abstraite
- **B.** `initialize()`, puis `__invoke()`
- **C.** `__invoke()` seul : `initialize()` est ignorée
- **D.** `initialize()` seule, puis le code 1

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Une sous-classe invocable de `Command` passe par
`__invoke()`, et garde le cycle de `Command`, `initialize()` compris.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **`#[AsCommand(name: …)]`** + **`__invoke(): int`**, sans inheritance.
- Tag `console.command` : l'attribute, **ou** une sous-classe de `Command`.
- Invocable sans attribute : **pas une command**.
- Dependencies par le **constructor** : la command est un service.
- `hidden: true` : hors de `list`, **toujours exécutable**.
- `extends Command` : *legacy*, mais seul accès à **`initialize()`**.

Moyen mnémotechnique : **« Un attribute, un `__invoke()`, un int »**.

## Sources officielles

- [Console Commands](https://github.com/symfony/symfony-docs/blob/8.0/console.rst)
- [`AsCommand`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Attribute/AsCommand.php)
- [FrameworkBundle 8.0, `FrameworkExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php)
- [Console Input, « Interactive Input »](https://github.com/symfony/symfony-docs/blob/8.0/console/input.rst)

