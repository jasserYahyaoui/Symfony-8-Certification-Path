---
id: CRS-ckdp50w2dmy4
official_item: OIT-9a8aa389vk48
title: "Messages and handlers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Creating a Message and Handler, AsMessageHandler, HandledStamp"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Attribute/AsMessageHandler.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Attribute/AsMessageHandler.php"
    branch: "8.0"
    symbol_or_lines: "bus, fromTransport, handles, method, priority, sign"
    verified_at: "2026-10-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Transport/Serialization/PhpSerializer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Transport/Serialization/PhpSerializer.php"
    branch: "8.0"
    symbol_or_lines: "decode() — unserialize(), no constructor call"
    verified_at: "2026-10-01"
outcome_sections:
  OUT-65c68dmyhjc9: "Le message"
  OUT-yr8h53ttvp6e: "Le handler"
  OUT-r1kvyze41rx5: "Plusieurs handlers"
  OUT-2evbydc34ggd: "Le message"
---

## Objectif

Écrire un message et son handler, savoir ce qui les apparie, ce qui se passe
quand il y a plusieurs handlers, et faire évoluer un message sans casser ceux
qui attendent en file.

## Le message

Une class ordinaire, sans interface ni class parente. La seule exigence est
qu'elle soit **sérialisable**, parce qu'un transport asynchrone l'écrit puis la
relit.

```php
final class SendWelcomeEmail
{
    public function __construct(public readonly int $userId) {}
}
```

D'où la règle qui en découle : on transporte un **identifier**, pas un object
chargé. L'object sérialisé serait une copie figée, périmée à la lecture ; le
handler recharge l'état courant à partir de l'identifier.

## Le handler

```php
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendWelcomeEmailHandler
{
    public function __invoke(SendWelcomeEmail $message): void
    {
        // ...
    }
}
```

Deux éléments décident du câblage : l'attribute `#[AsMessageHandler]`, lu par
l'autoconfiguration, et le **type de l'argument**, qui désigne le message
traité. Il n'y a pas de table de correspondance à écrire. Exécuté avec
Messenger 8.0.15 :

| Handler | Messages reçus |
|---|---|
| method `onUnion(Ping\|Pong $m)` avec `#[AsMessageHandler]` | `Ping` et `Pong` |
| method `onNotice(Notice $m)`, `Notice` étant une interface | `Ping`, qui l'implémente |
| `__invoke($m)` sans type | **échec au build** : « argument "$m" … must have a type-hint corresponding to the message class it handles » |

L'attribute se pose donc aussi sur des **methods** — plusieurs par class —, le
type peut être une **union** ou une **interface**, et l'absence de type est
refusée dès la compilation. L'option `handles` de l'attribute nomme le message
quand le type ne suffit pas ; `fromTransport` restreint un handler aux messages
reçus d'un transport donné.

## Plusieurs handlers

Un même message peut avoir **plusieurs** handlers ; tous sont appelés. C'est
voulu : un `OrderPlaced` peut déclencher une facture et une notification, écrits
indépendamment. L'option `priority` ordonne, sans exclure. Exécuté : sans
priorité, l'ordre d'enregistrement ; avec `priority: 10` sur le second, il passe
**avant** le premier.

L'Envelope porte alors un `HandledStamp` **par handler** :

```php
$envelope->last(HandledStamp::class);   // le dernier
$envelope->all(HandledStamp::class);    // tous
```

Récupérer une return value n'a donc de sens qu'avec un handler unique — et
seulement en traitement synchrone.

## Versionner un message

Un message en attente dans une file a été sérialisé par l'ancien code et sera
lu par le nouveau. Le sérialiseur par défaut, `PhpSerializer`, s'appuie sur
`unserialize()`, qui **n'appelle pas le constructeur**. Exécuté : un message
encodé avec une version, décodé avec la suivante, PHP 8.4 :

| Changement de la class | Lecture de l'ancien message |
|---|---|
| property **promue** ajoutée au constructeur, avec défaut | **`Error`** à l'accès : « must not be accessed before initialization » |
| property **déclarée** ajoutée, avec défaut | lue avec sa default value |
| property retirée | décodé, avec l'avertissement « Creation of dynamic property … is deprecated » |

« Ajouter une property avec une default value est sûr » n'est donc vrai que
pour une property **déclarée** : le défaut d'un parameter promu appartient au
constructeur, que la désérialisation ne voit pas. Pour un changement de sens, on
introduit une **nouvelle class** et on garde l'ancienne le temps que les files
se vident.

## Pièges d'examen

**Aucune interface à implémenter**, ni pour le message ni pour le handler.

**Le message est apparié par le type de l'argument** — union et interface
comprises ; sans type, le container refuse de se construire.

**Plusieurs handlers sont légitimes** et tous s'exécutent ; `priority` les
ordonne.

**Une property promue ajoutée casse les messages déjà en file**, même avec un
défaut.

**Ne pas transporter d'object chargé** : un identifier, et un rechargement dans
le handler.

## Points clés

- Message = class ordinaire sérialisable ; on y met des identifiers.
- `#[AsMessageHandler]` + type de l'argument suffisent ; sur une class ou sur
  des methods.
- Plusieurs handlers possibles ; un `HandledStamp` par handler.
- Évolution : property déclarée avec défaut, oui ; promue, non.

## Sources officielles

- [Messenger, « Creating a Message & Handler »](https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst)
- [Messenger 8.0, `AsMessageHandler`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Attribute/AsMessageHandler.php)
- [Messenger 8.0, `PhpSerializer`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Transport/Serialization/PhpSerializer.php)
