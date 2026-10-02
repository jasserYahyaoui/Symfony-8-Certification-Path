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
---

## Objectif

Écrire un message et son handler, savoir ce qui les apparie, ce qui se passe
quand il y a plusieurs handlers, et faire évoluer un message sans casser ceux
qui attendent en file.

## Le message

Une classe ordinaire, sans interface ni classe parente. La seule exigence est
qu'elle soit **sérialisable**, parce qu'un transport asynchrone l'écrit puis la
relit.

```php
final class SendWelcomeEmail
{
    public function __construct(public readonly int $userId) {}
}
```

D'où la règle qui en découle : on transporte un **identifiant**, pas un objet
chargé. L'objet sérialisé serait une copie figée, périmée à la lecture ; le
handler recharge l'état courant à partir de l'identifiant.

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

Deux éléments décident du câblage : l'attribut `#[AsMessageHandler]`, lu par
l'autoconfiguration, et le **type de l'argument**, qui désigne le message
traité. Il n'y a pas de table de correspondance à écrire. Exécuté avec
Messenger 8.0.15 :

| Handler | Messages reçus |
|---|---|
| méthode `onUnion(Ping\|Pong $m)` avec `#[AsMessageHandler]` | `Ping` et `Pong` |
| méthode `onNotice(Notice $m)`, `Notice` étant une interface | `Ping`, qui l'implémente |
| `__invoke($m)` sans type | **échec au build** : « argument "$m" … must have a type-hint corresponding to the message class it handles » |

L'attribut se pose donc aussi sur des **méthodes** — plusieurs par classe —, le
type peut être une **union** ou une **interface**, et l'absence de type est
refusée dès la compilation. L'option `handles` de l'attribut nomme le message
quand le type ne suffit pas ; `fromTransport` restreint un handler aux messages
reçus d'un transport donné.

## Plusieurs handlers

Un même message peut avoir **plusieurs** handlers ; tous sont appelés. C'est
voulu : un `OrderPlaced` peut déclencher une facture et une notification, écrits
indépendamment. L'option `priority` ordonne, sans exclure. Exécuté : sans
priorité, l'ordre d'enregistrement ; avec `priority: 10` sur le second, il passe
**avant** le premier.

L'enveloppe porte alors un `HandledStamp` **par handler** :

```php
$envelope->last(HandledStamp::class);   // le dernier
$envelope->all(HandledStamp::class);    // tous
```

Récupérer une valeur de retour n'a donc de sens qu'avec un handler unique — et
seulement en traitement synchrone.

## Versionner un message

Un message en attente dans une file a été sérialisé par l'ancien code et sera
lu par le nouveau. Le sérialiseur par défaut, `PhpSerializer`, s'appuie sur
`unserialize()`, qui **n'appelle pas le constructeur**. Exécuté : un message
encodé avec une version, décodé avec la suivante, PHP 8.4 :

| Changement de la classe | Lecture de l'ancien message |
|---|---|
| propriété **promue** ajoutée au constructeur, avec défaut | **`Error`** à l'accès : « must not be accessed before initialization » |
| propriété **déclarée** ajoutée, avec défaut | lue avec sa valeur par défaut |
| propriété retirée | décodé, avec l'avertissement « Creation of dynamic property … is deprecated » |

« Ajouter une propriété avec une valeur par défaut est sûr » n'est donc vrai que
pour une propriété **déclarée** : le défaut d'un paramètre promu appartient au
constructeur, que la désérialisation ne voit pas. Pour un changement de sens, on
introduit une **nouvelle classe** et on garde l'ancienne le temps que les files
se vident.

## Pièges d'examen

**Aucune interface à implémenter**, ni pour le message ni pour le handler.

**Le message est apparié par le type de l'argument** — union et interface
comprises ; sans type, le conteneur refuse de se construire.

**Plusieurs handlers sont légitimes** et tous s'exécutent ; `priority` les
ordonne.

**Une propriété promue ajoutée casse les messages déjà en file**, même avec un
défaut.

**Ne pas transporter d'objet chargé** : un identifiant, et un rechargement dans
le handler.

## Points clés

- Message = classe ordinaire sérialisable ; on y met des identifiants.
- `#[AsMessageHandler]` + type de l'argument suffisent ; sur une classe ou sur
  des méthodes.
- Plusieurs handlers possibles ; un `HandledStamp` par handler.
- Évolution : propriété déclarée avec défaut, oui ; promue, non.

## Sources officielles

- [Messenger, « Creating a Message & Handler »](https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst)
- [Messenger 8.0, `AsMessageHandler`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Attribute/AsMessageHandler.php)
- [Messenger 8.0, `PhpSerializer`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Transport/Serialization/PhpSerializer.php)
