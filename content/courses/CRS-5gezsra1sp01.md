---
id: CRS-5gezsra1sp01
official_item: OIT-hs1297vvhr89
title: "Messenger component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-01"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Concepts: Sender, Receiver, Handler, Middleware, Envelope, Envelope Stamps"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/HandleTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/HandleTrait.php"
    branch: "8.0"
    symbol_or_lines: "handle() — exactly one HandledStamp expected"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Middleware/HandleMessageMiddleware.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/HandleMessageMiddleware.php"
    branch: "8.0"
    symbol_or_lines: "handle() — HandledStamp; NoHandlerForMessageException"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Envelope.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Envelope.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "wrap(), with() — a new Envelope each time"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Transport/Sync/SyncTransport.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Transport/Sync/SyncTransport.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "send() — dispatches again with a ReceivedStamp"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Middleware/SendMessageMiddleware.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/SendMessageMiddleware.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "handle() — a sent message is not handled; a received message is not sent back"
    verified_at: "2026-11-01"
outcome_sections:
  OUT-c3q6angezzhs: "Les six concepts de la documentation"
  OUT-w4ahdf9xdh5w: "Dispatcher"
  OUT-rr0nvj9b8x0z: "L'Envelope et les stamps"
---

## Objectif

Nommer correctement les concepts du component et comprendre ce que
« dispatcher » veut dire. Transports, handlers et middleware ont chacun leur
item.

## Avant la règle : le problème à résoudre

Certaines tâches n'ont pas à retarder la Response : envoyer un email,
redimensionner une image, appeler une API lente. Messenger permet de **décider
plus tard** — le controller émet un message et rend la main ; un autre process
le traite.

Le même code sert aussi de bus **synchrone** : c'est la configuration qui décide,
pas le code appelant.

Trois questions suffisent pour ne plus confondre les pièces : **qui fait
quoi** (le vocabulaire), **ce que `dispatch()` garantit**, et **où se lit ce qui
s'est passé** (l'Envelope).

## Le fil rouge : l'email de bienvenue

```php
// Le message : un object PHP ordinaire, sans interface
final class Welcome
{
    public function __construct(public string $who) {}
}

// Le handler : un callable reconnu par son attribute
#[AsMessageHandler]
final class WelcomeMailer
{
    public function __invoke(Welcome $message): string
    {
        // ... envoyer l'email
        return 'mailed '.$message->who;
    }
}

// Le controller : il confie le message au bus
$envelope = $this->bus->dispatch(new Welcome('alice'));
```

Sans `routing`, `WelcomeMailer` tourne pendant `dispatch()`. Avec
`routing: { App\Message\Welcome: async }`, il tourne plus tard, dans un worker.
Le controller ne change pas d'une ligne.

## Le cycle complet en un schéma

Lu dans `SendMessageMiddleware::handle()` et rejoué avec Messenger 8.0 :

```mermaid
---
title: Le trajet d'un message dans le bus
---
flowchart TD
  accTitle: Le trajet d'un message dispatché, en synchrone et en asynchrone
  accDescr: dispatch() emballe le message dans une Envelope et la fait traverser les middleware. SendMessageMiddleware cherche un sender pour ce message. Sans sender, la traversée continue jusqu'à HandleMessageMiddleware, qui appelle les handlers et ajoute un HandledStamp par handler : c'est le cas synchrone. Avec un sender, l'Envelope reçoit un SentStamp, part vers le transport, et la traversée s'arrête là. Plus tard, un worker lit le transport, redispatche l'Envelope avec un ReceivedStamp : les middleware sont traversés une seconde fois, SendMessageMiddleware ne renvoie pas un message reçu, et HandleMessageMiddleware appelle les handlers.
  D["dispatch(message)"] --> ENV["Envelope + stamps"]
  ENV --> MW["middleware, premier passage"]
  MW --> SEND{"SendMessageMiddleware : un sender pour ce message ?"}
  SEND -->|"non : synchrone"| HANDLE["HandleMessageMiddleware : handlers, un HandledStamp chacun"]
  SEND -->|"oui"| SENT(["SentStamp, envoi au transport : la traversée s'arrête"])
  SENT -.->|"plus tard"| W["worker : le receiver lit le transport"]
  W --> MW2["middleware, second passage, avec ReceivedStamp"]
  MW2 --> HANDLE
```

C'est ce que dit la documentation : les middleware sont appelés « both when a
message is originally dispatched and again later when a message is received
from a transport ». Rejoué, un middleware qui journalise : `dispatch` après
`dispatch()`, puis `received` après `messenger:consume async --limit=1`.

## Les six concepts de la documentation

La section « Concepts » de `messenger.rst` (8.0) en nomme six :

| Concept | Role |
|---|---|
| **Sender** | sérialise et envoie le message vers « quelque chose » : un broker, une API |
| **Receiver** | récupère, désérialise et transmet aux handlers |
| **Handler** | exécute la logique métier ; appelé par `HandleMessageMiddleware` |
| **Middleware** | accède au message et à l'Envelope pendant la traversée du bus |
| **Envelope** | emballe le message pour y attacher des informations |
| **Envelope Stamps** | ces informations : délai, transport, résultat, marqueurs |

Le **message** lui-même n'est pas dans la liste : c'est un object PHP ordinaire,
sans interface à implémenter. Le **bus** est ce qui relie le tout — il reçoit
le message et le fait traverser la chaîne de middleware.

La documentation précise un point qui surprend : les middleware sont appelés
**deux fois** pour un message asynchrone — à l'envoi, puis de nouveau quand le
message est reçu du transport.

## Dispatcher

```php
public function __construct(private MessageBusInterface $bus) {}

$this->bus->dispatch(new SendWelcomeEmail($userId));
```

`dispatch()` confie le message au bus. Ce qui se passe ensuite dépend du
routage. Exécuté avec FrameworkBundle et Messenger 8.0.15, un message `Welcome`
et deux handlers :

| Routage | Handlers exécutés avant le retour de `dispatch()` | Stamps de l'Envelope retournée |
|---|---|---|
| aucun (synchrone) | **les deux** | `DelayStamp`, `BusNameStamp`, `HandledStamp` ×2 |
| vers un transport `in-memory://` | **aucun** | `DelayStamp`, `BusNameStamp`, `SentStamp`, `TransportMessageIdStamp` |
| vers un transport `sync://` | **les deux** | `DelayStamp`, `BusNameStamp`, `SentStamp`, `ReceivedStamp`, `HandledStamp` ×2 |

En synchrone, le message est donc **déjà traité** quand `dispatch()` rend la
main. En asynchrone, il est seulement envoyé : le transport le contient, aucun
handler n'a tourné.

La troisième ligne est le cas mixte : `sync://` est un vrai transport, mais lu
dans `SyncTransport::send()`, il redispatche aussitôt l'Envelope avec un
`ReceivedStamp`. Le message est « envoyé » **et** traité avant le retour.

Le `DelayStamp(5000)` posé dans les deux cas n'a eu **aucun effet en
synchrone** : les handlers ont tourné immédiatement. Le délai est une consigne
pour le transport.

Un message **non routé et sans handler** n'est pas ignoré : exécuté,
`NoHandlerForMessageException` — « No handler for message "App\…\Orphan" ».

## L'Envelope et les stamps

```php
$this->bus->dispatch(new SendWelcomeEmail($id), [new DelayStamp(5000)]);

$envelope = $this->bus->dispatch($message);
$handled  = $envelope->last(HandledStamp::class);
```

`dispatch()` **retourne l'Envelope** ; le résultat d'un handler se lit sur son
`HandledStamp`, et seulement si le traitement a été synchrone. Avec deux
handlers, il y a deux `HandledStamp` : exécuté, `last()` rend le résultat du
second, `all(HandledStamp::class)` les deux.

`HandleTrait::handle()` raccourcit cette lecture, mais exige **exactement un**
handler. Exécuté avec les deux : `LogicException` — « was handled multiple
times. Only one handler is expected ». Et sur un message routé vers un
transport asynchrone, rejoué : « was handled zero times. Exactly one handler
is expected ».

**L'Envelope est immuable.** Lu dans `Envelope::with()`, qui clone avant
d'ajouter. Rejoué :

```php
$envelope = new Envelope(new Welcome('alice'));
$delayed  = $envelope->with(new DelayStamp(1000));
// $envelope n'a toujours aucun DelayStamp ; $delayed en a un

$returned = $bus->dispatch($delayed);   // routé vers in-memory://
// $returned porte le SentStamp ; $delayed, non
```

La seule Envelope qui dit ce qui s'est passé est **celle que `dispatch()`
retourne**. `Envelope::wrap()` accepte un message ou une Envelope, et garde
les stamps déjà posés.

## Concrètement dans ton application

- Un message : une class finale, des properties publiques ou en lecture, rien
  d'autre.
- Un handler : `#[AsMessageHandler]` et un `__invoke()` typé sur le message.
- Synchrone ou asynchrone : une ligne de `routing`, pas une ligne du controller.
- Lire un résultat : seulement en synchrone, sur l'Envelope **retournée**.
- Une query à un seul handler : `HandleTrait`, et jamais sur un message routé.
- Un délai : `DelayStamp`, qui n'a de sens qu'avec un transport.

## À comprendre

- **Pourquoi une Envelope plutôt que le message ?** Le message est l'affaire du
  métier ; le délai, le bus, le transport et le résultat sont l'affaire de
  Messenger. Les stamps les gardent séparés.
- **Pourquoi deux passages dans les middleware ?** Le message traverse le bus
  une fois pour partir, une fois pour être traité : deux process, deux
  traversées.
- **Pourquoi aucun résultat en asynchrone ?** Au retour de `dispatch()`, aucun
  handler n'a tourné ; il n'y a rien à lire.

## À mémoriser

- **Six concepts** : sender, receiver, handler, middleware, envelope, stamps.
- **`dispatch()`** confie au bus ; le **routage** décide.
- **Synchrone** → déjà traité ; **asynchrone** → seulement envoyé ; **`sync://`**
  → envoyé et traité.
- Résultat : **`HandledStamp`** sur l'Envelope **retournée**.
- **`HandleTrait`** : exactement un handler, en synchrone.
- Envelope **immuable** : `with()` en rend une nouvelle.
- Moyen mnémotechnique : **« emballer, envoyer ou traiter, lire le retour »**.

## Pièges d'examen

**`dispatch()` ne signifie pas « traité » en asynchrone** — et signifie « déjà
traité » en synchrone.

**Un message n'implémente aucune interface.** C'est une class ordinaire.

**Le résultat se lit sur l'Envelope retournée**, via `HandledStamp`, et
uniquement en synchrone.

**Un `DelayStamp` ne retarde rien en synchrone.**

**Sans handler ni routage, `dispatch()` lève une exception.**

**Les six concepts de la documentation** sont sender, receiver, handler,
middleware, envelope et stamps — pas « message » ni « bus ».

## Application : trois scénarios

**Scénario 1 — Le résultat introuvable.** Un service passe une Envelope à
`dispatch()`, puis cherche le `SentStamp` sur la variable qu'il a passée. Il
n'y est pas. *Solution.* L'Envelope est immuable : lire celle que `dispatch()`
retourne.

**Scénario 2 — La query devenue asynchrone.** Quelqu'un route
`GetInvoiceTotal` vers `async` ; le service qui utilisait `HandleTrait` lève
une `LogicException`. *Solution.* « was handled zero times » : une query qui
attend une valeur reste synchrone.

**Scénario 3 — Le délai sans effet.** Un `DelayStamp(60000)` sur un message
non routé : l'email part tout de suite. *Solution.* Router le message vers un
transport ; le délai est une consigne pour lui.

## QCM d'entraînement

Cinq questions sur le component. Choisis avant d'ouvrir la correction.

**Question 1.** `Welcome` est routé vers un transport `sync://`. Au retour de
`dispatch()`, qu'est-il vrai ?

- **A.** Le message attend un worker : seul un `SentStamp` est posé
- **B.** Les handlers ont tourné ; un `SentStamp` est aussi posé
- **C.** Une exception : `sync://` n'est pas un transport
- **D.** Les handlers ont tourné, sans aucun `SentStamp` posé

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Rejoué avec Messenger 8.0 : `SentStamp`,
`ReceivedStamp` et deux `HandledStamp`. `SyncTransport::send()` redispatche
l'Envelope sur-le-champ.

A décrit `in-memory://` ou un broker. C est faux. D décrit le cas sans routage.

</details>

**Question 2.** Un service utilise `HandleTrait` sur un message routé vers
`async`. Que fait `$this->handle($message)` ?

- **A.** Une `LogicException` : traité zéro fois
- **B.** Rend `null`, le résultat arrivera plus tard
- **C.** Attend que le worker ait traité le message
- **D.** Rend l'Envelope, avec son `SentStamp`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Rejoué : « was handled zero times. Exactly one handler
is expected ». Le message est parti vers le transport ; aucun `HandledStamp`.

B et C supposent une attente qui n'existe pas. D est faux : `handle()` rend le
résultat d'un handler, ou lève.

</details>

**Question 3.** Un middleware journalise chaque Envelope qu'il voit. Un
message routé vers `async` est dispatché, puis consommé par
`messenger:consume async`. Combien de lignes ?

- **A.** Une, à l'envoi seulement
- **B.** Une, à la réception seulement
- **C.** Deux : à l'envoi, puis à la réception
- **D.** Aucune : les middleware ne voient que les messages synchrones

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Rejoué : `dispatch` puis `received`. La documentation :
« both when a message is originally dispatched and again later when a message
is received from a transport ».

A et B oublient l'un des deux passages. D est faux.

</details>

**Question 4.** Que vaut `$a === $b` ?

```php
$a = new Envelope(new Welcome('alice'));
$b = $a->with(new DelayStamp(1000));
```

- **A.** `true` : `with()` ajoute le stamp à l'Envelope
- **B.** `true`, mais seulement sans transport
- **C.** Une erreur : `with()` attend un array
- **D.** `false` : c'est une autre Envelope

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Lu dans `Envelope::with()`, qui clone avant d'ajouter,
et rejoué : `$a` n'a toujours aucun `DelayStamp`.

A et B supposent une Envelope modifiable. C est faux : `with()` est variadique.

</details>

**Question 5.** Un message est routé vers `in-memory://`. Où se trouve-t-il
juste après `dispatch()` ?

- **A.** Dans le transport, aucun handler lancé
- **B.** Dans le transport, après un premier handler
- **C.** Nulle part : `in-memory://` le perd
- **D.** Dans la file des handlers synchrones

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Rejoué : une Envelope dans `getSent()` du transport,
aucun handler appelé. Lu dans `SendMessageMiddleware` : avec un sender, la
traversée s'arrête avant `HandleMessageMiddleware`.

B, C et D sont faux.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **Six concepts** : sender, receiver, handler, middleware, envelope, stamps.
- Un **message** est une class ordinaire ; le **routage** décide de son sort.
- **Synchrone** : traité au retour ; **asynchrone** : seulement envoyé.
- Le résultat : **`HandledStamp`**, sur l'Envelope **retournée**.
- **`HandleTrait`** : exactement un handler, sinon `LogicException`.
- Les middleware voient un message asynchrone **deux fois**.

**En une phrase, pour tout reconstruire :**

```text
dispatch → Envelope (immuable) → middleware → sender ? oui : SentStamp, stop / non : handlers, HandledStamp ;
worker → ReceivedStamp → middleware à nouveau → handlers ; HandleTrait = un seul handler, synchrone
```

## Sources officielles

- [Messenger, « Concepts »](https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst)
- [Messenger 8.0, `HandleTrait`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/HandleTrait.php)
- [Messenger 8.0, `HandleMessageMiddleware`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/HandleMessageMiddleware.php)
- [Messenger 8.0, `SendMessageMiddleware`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/SendMessageMiddleware.php)
- [Messenger 8.0, `Envelope`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Envelope.php)
- [Messenger 8.0, `SyncTransport`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Transport/Sync/SyncTransport.php)
