---
id: CRS-ckdp50w2dmy4
official_item: OIT-9a8aa389vk48
title: "Messages and handlers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-01"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Creating a Message and Handler, AsMessageHandler, HandledStamp"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Attribute/AsMessageHandler.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Attribute/AsMessageHandler.php"
    branch: "8.0"
    symbol_or_lines: "bus, fromTransport, handles, method, priority, sign"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Transport/Serialization/PhpSerializer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Transport/Serialization/PhpSerializer.php"
    branch: "8.0"
    symbol_or_lines: "decode() — unserialize(), no constructor call"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Handler/HandlersLocator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Handler/HandlersLocator.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "getHandlers(), listTypes(), shouldHandle() — type by type; from_transport only on received messages"
    verified_at: "2026-11-01"
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

## Avant la règle : le problème à résoudre

Un achat est validé : il faut une facture et une notification. Deux équipes,
deux morceaux de code, un même event. Il faut un object qui **décrit ce qui
s'est passé**, du code qui **y réagit**, et un câblage qui les relie sans table
écrite à la main. Et comme le message peut attendre des heures dans une file, il
doit survivre à un deployment.

Trois questions : **à quoi ressemble un message**, **qu'est-ce qui le relie à
ses handlers**, et **comment le faire évoluer** sans casser la file.

## Le fil rouge : l'achat validé

```php
final class OrderPlaced
{
    public function __construct(public readonly int $orderId) {}
}

#[AsMessageHandler]
final class CreateInvoice
{
    public function __invoke(OrderPlaced $message): void { /* recharge l'achat, facture */ }
}

#[AsMessageHandler(priority: 10)]
final class NotifyCustomer
{
    public function __invoke(OrderPlaced $message): void { /* recharge l'achat, notifie */ }
}
```

Un `dispatch(new OrderPlaced(42))` appelle les deux, `NotifyCustomer` d'abord.
Le message ne porte que `42` : chaque handler recharge l'achat.

## Le cycle complet en un schéma

Lu dans `HandlersLocator::getHandlers()` et rejoué avec Messenger 8.0 :

```mermaid
---
title: Quels handlers reçoivent un message, et dans quel ordre
---
flowchart TD
  accTitle: Comment HandlersLocator choisit et ordonne les handlers d'un message
  accDescr: HandlersLocator parcourt les types du message dans cet ordre : sa class, ses classes parentes, ses interfaces, les namespaces en étoile, puis l'étoile seule. Pour chaque type, il prend les handlers enregistrés, du plus prioritaire au moins prioritaire. Si l'Envelope porte un ReceivedStamp et qu'un handler a un fromTransport, ce handler n'est gardé que si le message vient de ce transport. Un handler déjà retenu pour un type précédent n'est pas appelé deux fois. Chaque handler retenu est appelé et ajoute un HandledStamp.
  MSG["Envelope du message"] --> TYPES["types, dans l'ordre : class, parents, interfaces, namespaces en étoile, étoile seule"]
  TYPES --> PER["pour chaque type : ses handlers, par priority décroissante"]
  PER --> REC{"ReceivedStamp et fromTransport sur le handler ?"}
  REC -->|"non"| KEEP["gardé"]
  REC -->|"oui"| SAME{"même transport ?"}
  SAME -->|"oui"| KEEP
  SAME -->|"non"| SKIP(["écarté"])
  KEEP --> SEEN{"déjà retenu pour un type précédent ?"}
  SEEN -->|"oui"| SKIP
  SEEN -->|"non"| CALL(["appelé : un HandledStamp"])
```

Deux conséquences qui surprennent : **`priority` ne trie qu'à l'intérieur d'un
même type**, et **`fromTransport` ne concerne que les messages reçus**.

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

**Rejoué : ce que `fromTransport` écarte, et ce qu'il laisse passer.** Un
handler `#[AsMessageHandler(fromTransport: 'async')]` et un handler sans option,
pour un message routé vers `async` et `other` :

| Le message arrive… | Handler `fromTransport: 'async'` | Handler sans option |
|---|---|---|
| lu par un worker sur `other` | **écarté** | appelé |
| lu par un worker sur `async` | appelé | appelé |
| dispatché sans routage, en synchrone | **appelé** | appelé |

Lu dans `HandlersLocator::shouldHandle()` : sans `ReceivedStamp`, tout handler
est gardé. `fromTransport` ne rend pas un handler « asynchrone seulement ».

Les options de l'attribute en 8.0 : `bus`, `fromTransport`, `handles`, `method`,
`priority`, `sign`.

## Plusieurs handlers

Un même message peut avoir **plusieurs** handlers ; tous sont appelés. C'est
voulu : un `OrderPlaced` peut déclencher une facture et une notification, écrits
indépendamment. L'option `priority` ordonne, sans exclure. Exécuté : sans
priority, l'ordre d'enregistrement ; avec `priority: 10` sur le second, il passe
**avant** le premier.

**Mais seulement entre handlers du même type.** Rejoué : `Invoice` implémente
`Billable` ; un handler typé `Invoice` sans priority, un handler typé
`Billable` avec `priority: 100`. Ordre obtenu : le handler de la **class**
d'abord, celui de l'interface ensuite. `HandlersLocator` parcourt les types
avant les priorities.

L'Envelope porte alors un `HandledStamp` **par handler** :

```php
$envelope->last(HandledStamp::class);   // le dernier
$envelope->all(HandledStamp::class);    // tous
```

Récupérer une return value n'a donc de sens qu'avec un handler unique — et
seulement en traitement synchrone.

## Versionner un message

Un message en attente dans une file a été sérialisé par l'ancien code et sera
lu par le nouveau. Le serializer par défaut, `PhpSerializer`, s'appuie sur
`unserialize()`, qui **n'appelle pas le constructor**. Exécuté : un message
encodé avec une version, décodé avec la suivante, PHP 8.4 :

| Changement de la class | Lecture de l'ancien message |
|---|---|
| property **promue** ajoutée au constructor, avec défaut | **`Error`** à l'accès : « must not be accessed before initialization » |
| property **déclarée** ajoutée, avec défaut | lue avec sa default value |
| property retirée | décodé, avec l'avertissement « Creation of dynamic property … is deprecated » |

« Ajouter une property avec une default value est sûr » n'est donc vrai que
pour une property **déclarée** : le défaut d'un parameter promu appartient au
constructor, que la désérialisation ne voit pas. Pour un changement de sens, on
introduit une **nouvelle class** et on garde l'ancienne le temps que les files
se vident.

Rejoué une nouvelle fois, encodé et décodé dans deux process distincts : les
trois lignes se reproduisent à l'identique.

## Concrètement dans ton application

- Un message : `final`, des properties `readonly`, des identifiers.
- Un handler par responsabilité, plutôt qu'un handler qui fait tout.
- Plusieurs messages dans une class : `#[AsMessageHandler]` sur chaque method.
- Un ordre entre handlers d'un même message : `priority`, sur le même type.
- Un handler réservé à un transport : `fromTransport`, en sachant qu'il tourne
  aussi en synchrone.
- Une évolution : une property **déclarée** avec défaut, ou une nouvelle class.

## À comprendre

- **Pourquoi le type de l'argument ?** C'est la seule information que le code
  donne déjà ; l'en déduire évite une table qui dériverait.
- **Pourquoi plusieurs handlers ?** Un message décrit un fait ; plusieurs
  parties de l'application peuvent avoir à y réagir, sans se connaître.
- **Pourquoi la property promue casse-t-elle ?** Son défaut vit dans le
  constructor ; `unserialize()` recrée l'object sans l'appeler.

## À mémoriser

- Message = **class ordinaire sérialisable**, avec des **identifiers**.
- Handler = **`#[AsMessageHandler]`** + **type de l'argument** (union,
  interface acceptées) ; sans type → **build cassé**.
- **Plusieurs handlers** : tous appelés ; un `HandledStamp` chacun.
- **`priority`** : ordonne, n'exclut pas, et seulement **dans un même type**.
- **`fromTransport`** : ne concerne que les messages **reçus**.
- Évolution : property **déclarée** avec défaut, oui ; **promue**, non.
- Moyen mnémotechnique : **« le type relie, la priority range, la file se
  souvient »**.

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

## Application : trois scénarios

**Scénario 1 — La priority qui ne marche pas.** Un handler d'audit typé sur
l'interface `Billable`, `priority: 100`, devait passer avant la facturation,
typée sur `Invoice`. Il passe après. *Solution.* `priority` ne compare que des
handlers du même type : typer l'audit sur `Invoice`, ou ne pas dépendre de
l'ordre.

**Scénario 2 — Le handler « asynchrone seulement ».** Un handler coûteux reçoit
`fromTransport: 'async'` pour ne jamais tourner dans la Request. En test, sans
routage, il tourne quand même. *Solution.* `fromTransport` ne concerne que les
messages reçus ; c'est le **routage** qui décide de l'asynchrone.

**Scénario 3 — Le deployment du lundi.** Une property `public string $channel =
'email'` est ajouté au constructor de `SendWelcomeEmail` ; les messages déjà en
file échouent. *Solution.* Le déclarer comme property, `public string $channel
= 'email';`, hors du constructor : les anciens messages le lisent avec son
défaut.

## QCM d'entraînement

Cinq questions sur les messages et les handlers. Choisis avant d'ouvrir la
correction.

**Question 1.** Un handler porte `#[AsMessageHandler(fromTransport: 'async')]`.
Son message n'est routé nulle part et il est dispatché. Le handler tourne-t-il ?

- **A.** Non : il n'accepte que les messages venus d'`async`
- **B.** Oui, comme tout handler du message
- **C.** Non, et `dispatch()` lève `NoHandlerForMessageException`
- **D.** Seulement si un worker consomme `async`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Rejoué. Lu dans `HandlersLocator::shouldHandle()` :
sans `ReceivedStamp`, le filter `from_transport` n'est pas consulté.

A est l'idée reçue. C et D supposent un filtre sur l'envoi.

</details>

**Question 2.** `Invoice` implémente `Billable`. Handler X typé `Invoice`,
sans priority ; handler Y typé `Billable`, `priority: 100`. Dans quel ordre
tournent-ils ?

- **A.** Y puis X : la priority la plus haute d'abord
- **B.** Un seul des deux : le plus précis
- **C.** X puis Y : le type passe avant
- **D.** Un ordre aléatoire

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Rejoué. Lu dans `HandlersLocator::getHandlers()` : les
types du message sont parcourus dans l'ordre class, parents, interfaces ; la
priority ne range que les handlers d'un même type.

A applique la priority entre types. B et D sont faux.

</details>

**Question 3.** Une class porte `#[AsMessageHandler]` sur une method
`onUnion(Ping|Pong $m)` et sur une method `onNotice(Notice $m)`. `Ping`
implémente `Notice`. Combien de fois est-elle appelée pour un `Ping` ?

- **A.** Une : la première method trouvée
- **B.** Aucune : une union n'est pas acceptée
- **C.** Une exception : deux methods pour un même message
- **D.** Deux : `onUnion()` puis `onNotice()`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Rejoué avec Messenger 8.0 : `union:Ping`, puis
`notice:Ping`. Chaque method attribuée est un handler distinct.

A et C supposent un seul handler par class. B est faux.

</details>

**Question 4.** La version 2 d'un message ajoute `public string $source =
'web';`, déclarée hors du constructor. Un message de la version 1 est lu. Que
vaut `$message->source` ?

- **A.** `'web'`, sa default value
- **B.** Une `Error` : property non initialisée
- **C.** `null`
- **D.** Une exception du serializer, version inconnue

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Rejoué, encodé et décodé dans deux process : une
property déclarée garde sa default value. B serait vrai pour une property
promue.

C et D sont faux.

</details>

**Question 5.** La version 2 d'un message retire une property. Un message de
la version 1 est lu sous PHP 8.4. Que se passe-t-il ?

- **A.** Une `Error` à la lecture
- **B.** Le message est rejeté vers le failure transport
- **C.** Décodé, avec une deprecation PHP
- **D.** La property est ignorée en silence

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Rejoué : « Creation of dynamic property … is
deprecated ». `unserialize()` recrée la property disparue comme property
dynamique, ce que PHP 8.2 et suivants déprécient.

A, B et D sont faux.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Un message est une **class ordinaire** sérialisable, avec des
  **identifiers**.
- **`#[AsMessageHandler]`** + le **type de l'argument** relient handler et
  message.
- Union et interface acceptées ; **sans type**, le build échoue.
- **Plusieurs handlers** : tous appelés, un `HandledStamp` chacun.
- **`priority`** ordonne, et seulement **dans un même type**.
- **`fromTransport`** ne concerne que les messages **reçus**.
- Property **promue** ajoutée → les anciens messages cassent.

**En une phrase, pour tout reconstruire :**

```text
message = class + identifiers ; handler = #[AsMessageHandler] + type (union, interface) ; types : class > parents > interfaces > * ;
priority dans un type ; fromTransport si ReceivedStamp ; unserialize sans constructor : déclarée oui, promue non
```

## Sources officielles

- [Messenger, « Creating a Message & Handler »](https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst)
- [Messenger 8.0, `AsMessageHandler`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Attribute/AsMessageHandler.php)
- [Messenger 8.0, `HandlersLocator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Handler/HandlersLocator.php)
- [Messenger 8.0, `PhpSerializer`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Transport/Serialization/PhpSerializer.php)
