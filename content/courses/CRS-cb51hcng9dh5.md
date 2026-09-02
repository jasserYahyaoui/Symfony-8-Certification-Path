---
id: CRS-cb51hcng9dh5
official_item: OIT-kbg00jqxxwhq
title: "Custom commands"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console.rst"
    anchor: "creating-a-command"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Attribute/AsCommand.php"
    branch: "8.0"
    symbol_or_lines: "AsCommand"
    verified_at: "2026-09-01"
---

## Objectif

Écrire une commande à soi et savoir comment Symfony la découvre — deux syntaxes
coexistent en 8.0, et l'examen attend qu'on distingue la recommandée de l'autre.

## Prérequis

Le cycle d'une commande et le code de retour.

## La forme recommandée : la commande invocable

En Symfony 8.0, une commande est **une classe ordinaire** portant l'attribut
`#[AsCommand]`, avec une méthode `__invoke()` qui retourne un entier :

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

Le point à noter : **elle n'étend pas `Command`**. La constante `Command::SUCCESS`
reste utilisée pour la lisibilité, mais l'héritage n'est plus nécessaire.

## Comment Symfony la trouve

La classe est un service comme un autre. `#[AsCommand]` est une étiquette
d'autoconfiguration : le conteneur pose le tag `console.command` sur tout service
qui la porte, et l'`Application` récupère les services ainsi étiquetés.

Deux conséquences :

- avec la configuration `services.yaml` par défaut, une classe dans `src/Command/`
  est enregistrée sans un mot de configuration ;
- sans l'attribut — parce que la classe vient d'une bibliothèque, par exemple —
  il faut poser le tag `console.command` à la main.

## Les dépendances

La commande étant un service, on injecte **par le constructeur** :

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

`__invoke()` reçoit ce qu'on lui déclare : la sortie, l'entrée, et les arguments
et options décrits par attributs.

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

La documentation la nomme *legacy syntax* et recommande la forme invocable. Elle
garde cependant une utilité concrète : `initialize()` et `interact()` sont des
méthodes de `Command`. Une commande qui a besoin de ces points d'entrée étend
`Command` — et peut alors définir `__invoke()` ou `execute()`.

## Pièges d'examen

**Une commande invocable n'étend rien.** L'ancienne obligation d'hériter de
`Command` n'existe plus.

**`__invoke()` doit retourner un `int`.** C'est le code de sortie du processus.

**`#[AsCommand]` exige un `name`.** C'est le seul paramètre obligatoire.

**Pas d'attribut, pas d'enregistrement automatique** : il reste le tag
`console.command`.

**`initialize()` et `interact()` supposent l'héritage de `Command`.**

## Points clés

- Classe ordinaire + `#[AsCommand(name: …)]` + `__invoke(): int`.
- L'attribut déclenche l'autoconfiguration du tag `console.command`.
- Dépendances par le constructeur : la commande est un service.
- `extends Command` avec `execute()` reste supporté, et reste nécessaire pour
  `initialize()` et `interact()`.

## Sources officielles

- [Console Commands](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console.rst)
- [`AsCommand`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Attribute/AsCommand.php)
