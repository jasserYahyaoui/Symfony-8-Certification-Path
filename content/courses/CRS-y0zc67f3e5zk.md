---
id: CRS-y0zc67f3e5zk
official_item: OIT-cqs3m73y3rpy
title: "Console events"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-09"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/ConsoleEvents.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/ConsoleEvents.php"
    branch: "8.0"
    symbol_or_lines: "COMMAND, SIGNAL, TERMINATE, ERROR"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Application.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Application.php"
    branch: "8.0"
    symbol_or_lines: "doRunCommand"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Event/ConsoleErrorEvent.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Event/ConsoleErrorEvent.php"
    branch: "8.0"
    symbol_or_lines: "setExitCode() — also sets the error code by reflection"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Event/ConsoleCommandEvent.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Event/ConsoleCommandEvent.php"
    branch: "8.0"
    symbol_or_lines: "disableCommand(); RETURN_CODE_DISABLED = 113"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Application.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Application.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "doRunCommand() : signalsToDispatchEvent, ConsoleSignalEvent puis ConsoleAlarmEvent, handleSignal(), TERMINATE puis exit()"
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Event/ConsoleSignalEvent.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Event/ConsoleSignalEvent.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "int|false $exitCode = 0 ; abortExit()"
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Event/ConsoleAlarmEvent.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Event/ConsoleAlarmEvent.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "abortExit(), getExitCode()"
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Command/SignalableCommandInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Command/SignalableCommandInterface.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "getSubscribedSignals(), handleSignal(int $signal, int|false $previousExitCode = 0): int|false"
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/console/events.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/console/events.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "The ConsoleEvents::SIGNAL Event ; The ConsoleAlarmEvent"
    verified_at: "2026-10-16"
outcome_sections:
  OUT-2e5yd7119yha: "Quatre constantes, cinq events"
  OUT-8v3ady0sf894: "L'ordre réel"
  OUT-rzgybvr9fa5g: "`ERROR` — rattraper"
  OUT-13gsk5cxzqt3: "`SIGNAL` — pendant"
  OUT-kh6qdz451gb2: "L'alarme"
---

## Objectif

Intervenir autour de l'exécution d'une command sans la modifier : journaliser,
mesurer, empêcher, rattraper une erreur.

## Prérequis

Le cycle d'une command, et le dispatcher d'events.

## Quatre constantes, cinq events

`ConsoleEvents` déclare **quatre** events — et une cinquième constante,
`ALIASES`, qui associe chaque class d'event à son nom pour que l'on puisse
s'abonner par la class. Un cinquième event, `ConsoleAlarmEvent`, n'a pas de
constante (voir *L'alarme*) :

| Constante | Valeur | Quand |
|---|---|---|
| `COMMAND` | `console.command` | avant l'exécution |
| `SIGNAL` | `console.signal` | à la réception d'un signal POSIX |
| `ERROR` | `console.error` | une exception ou une erreur est remontée |
| `TERMINATE` | `console.terminate` | après l'exécution, quoi qu'il arrive |

Ils ne sont dispatchés que si l'`Application` possède un dispatcher. Dans une
application Symfony, FrameworkBundle le lui donne ; une `Application` construite
à la main ne dispatche rien tant qu'on n'a pas appelé `setDispatcher()`.

## L'ordre réel

```mermaid
---
title: Application::doRunCommand()
---
flowchart TD
  accTitle: L'ordre des events autour d'une command console
  accDescr: Sans dispatcher, la command s'exécute seule, sans aucun event. Avec un dispatcher, console.command est dispatché. Si un listener appelle disableCommand(), la command n'est pas exécutée et le code vaut 113. Sinon elle s'exécute. Une exception mène à console.error, où poser le code 0 abandonne l'erreur. Dans tous les cas console.terminate est dispatché, puis l'erreur restante est relancée, ou l'exit code de l'event est rendu.
  D{"dispatcher ?"} -->|"non"| ALONE(["exécution seule, aucun event"])
  D -->|"oui"| CMD["console.command"]
  CMD --> DIS{"disableCommand() ?"}
  DIS -->|"oui"| C113["code 113, pas d'exécution"]
  DIS -->|"non"| RUN["exécution"]
  RUN -->|"exception"| ERR["console.error : le code 0 abandonne l'erreur"]
  RUN --> TERM["console.terminate"]
  C113 --> TERM
  ERR --> TERM
  TERM --> OUT{"erreur restante ?"}
  OUT -->|"oui"| THROW(["relancée"])
  OUT -->|"non"| CODE(["rend le code de l'event"])
```

Le point que l'examen teste : **`TERMINATE` est toujours atteint**, y compris
après une erreur. C'est donc le seul endroit fiable pour libérer une ressource
ou mesurer une durée d'exécution.

Exécuté avec Console 8.0.15, une `Application` munie d'un dispatcher :

| Cas | Events | Code |
|---|---|---|
| succès | `COMMAND`, exécution, `TERMINATE:0` | 0 |
| `disableCommand()` | `COMMAND`, **`TERMINATE:113`** — pas d'exécution | 113 |
| exception | `COMMAND`, exécution, `ERROR`, `TERMINATE:1` | 1 |
| exception, `setExitCode(0)` sur `ERROR` | … `ERROR`, `TERMINATE:0` | 0 |
| succès, `setExitCode(1)` sur `TERMINATE` | … `TERMINATE:0` | **1** |
| `__invoke(): void` | … `ERROR` (`TypeError`), `TERMINATE:1` | `TypeError` relancée |
| exception, **sans** dispatcher | exécution seule | 1 |

## `COMMAND` — avant

`ConsoleCommandEvent` donne accès à la command, à l'entrée et à l'output avant
qu'elles ne lui soient passées. On peut donc y ajouter une option, forcer une
verbosity — ou empêcher l'exécution :

```php
$event->disableCommand();
```

La command n'est alors pas lancée, et le process sort avec le code
`ConsoleCommandEvent::RETURN_CODE_DISABLED`, qui vaut **113**. Cette valeur
particulière permet de distinguer « refusée » de « échouée » (1).

## `ERROR` — rattraper

`ConsoleErrorEvent` porte l'erreur et l'exit code :

```php
$event->getError();
$event->setError(new \RuntimeException('message plus clair'));
$event->setExitCode(0);
```

`setExitCode(0)` a un effet fort : l'`Application` considère alors qu'il n'y a
plus d'erreur à propager, et l'exception est abandonnée. C'est le mécanisme qui
permet de traiter une erreur attendue sans faire échouer le process.

`ERROR` couvre tout `Throwable` — les erreurs PHP autant que les exceptions.
Exécuté : une `TypeError` déclenche `ERROR` puis `TERMINATE`, et n'est relancée
qu'ensuite ; c'est `Application::run()`, qui n'attrape pas les `Error` par
défaut, qui la laisse remonter.

## `TERMINATE` — après

`ConsoleTerminateEvent` porte l'exit code et permet de le remplacer :

```php
$event->getExitCode();
$event->setExitCode(1);
```

Le code final du process est celui que porte cet event **après** le
dispatch, pas celui qu'a retourné la command. Un listener de `TERMINATE` peut
donc transformer un succès en échec, et réciproquement.

Il expose aussi `getInterruptingSignal()` : non nul quand la command a été
interrompue par un signal.

## `SIGNAL` — pendant

Avec un dispatcher, l'`Application` intercepte `SIGINT`, `SIGQUIT`, `SIGTERM`,
`SIGUSR1`, `SIGUSR2` et `SIGALRM`. Exécuté avec Console 8.0, une command qui
reçoit `SIGINT` :

| Situation | Résultat |
|---|---|
| aucun listener ne change le code | `SIGNAL`, `TERMINATE` (`getInterruptingSignal()` = 2), sortie en code **0** |
| `abortExit()` sur `SIGNAL` | la command continue |
| `setExitCode(3)` sur `SIGNAL` | sortie en code 3 |
| `handleSignal()` d'une `SignalableCommandInterface` rend 5 | il reçoit le code courant (0) ; sortie en code 5 |
| `handleSignal()` rend `false` | la command continue |
| ni dispatcher ni command signalable | rien n'intercepte : PHP meurt du signal |

L'ordre : `SIGNAL`, puis `handleSignal()`, puis `TERMINATE` et `exit()`.

## L'alarme

`Application::setAlarmInterval(10)` programme un `SIGALRM` toutes les 10 s.
Chacun dispatche `SIGNAL`, puis `ConsoleAlarmEvent` sous son **nom de class**.
Le code par défaut reste 0 : sans `abortExit()` dans un listener, la
**première alarme termine la command** — exécuté. Il faut l'extension `pcntl`.

## S'abonner

Comme pour tout autre event :

```php
class CommandLogger implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::TERMINATE => 'onTerminate'];
    }
}
```

## Pièges d'examen

**Quatre constantes, cinq events.** `ConsoleAlarmEvent` n'a pas de constante ;
il n'existe pas d'event « après `configure()` ».

**Un signal intercepté sort en code 0** si personne ne le change.

**`TERMINATE` est dispatché même après une erreur** — c'est sa raison d'être.

**`disableCommand()` produit le code 113**, pas 0 ni 1 — et `TERMINATE` est
quand même dispatché.

**`setExitCode(0)` sur `ERROR` avale l'exception** au lieu de la laisser remonter.

**Pas de dispatcher, pas d'events** : une `Application` autonome est muette.

## Points clés

- `COMMAND`, `SIGNAL`, `ERROR`, `TERMINATE` — quatre events déclarés par
  `ConsoleEvents`, plus la table `ALIASES` ; `ConsoleAlarmEvent` sans constante.
- Un signal intercepté sort en code 0, sauf `abortExit()` ou `handleSignal()` à `false`.
- Ordre : `COMMAND` → exécution → (`ERROR`) → `TERMINATE`, toujours.
- `disableCommand()` empêche l'exécution et sort avec 113.
- `ERROR` peut remplacer l'erreur ou l'annuler par `setExitCode(0)`.
- L'exit code final est celui porté par `TERMINATE`.

## Sources officielles

- [`ConsoleEvents`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/ConsoleEvents.php)
- [`Application::doRunCommand`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Application.php)
- [Using Events](https://github.com/symfony/symfony-docs/blob/8.0/components/console/events.rst)
