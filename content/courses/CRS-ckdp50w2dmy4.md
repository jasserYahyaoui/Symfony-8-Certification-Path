---
id: CRS-ckdp50w2dmy4
official_item: OIT-9a8aa389vk48
title: "Messages and handlers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Creating a Message and Handler, AsMessageHandler, HandledStamp"
    verified_at: "2026-09-01"
---

## Objectif

Écrire un message et son handler, et savoir ce qui se passe quand il y en a
plusieurs.

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

D'où la règle qui en découle : on transporte un **identifiant**, pas une entité
Doctrine. L'entité sérialisée serait périmée à la lecture, et sa désérialisation
la détacherait de l'`EntityManager`.

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

Deux éléments décident du câblage : l'attribut `#[AsMessageHandler]` — posé par
l'autoconfiguration — et le **type de l'argument** de `__invoke()`, qui désigne
le message traité. Il n'y a pas de table de correspondance à écrire.

## Plusieurs handlers

Un même message peut avoir **plusieurs** handlers ; tous sont appelés. C'est
voulu : un `OrderPlaced` peut déclencher une facture et une notification, écrits
indépendamment.

L'enveloppe porte alors un `HandledStamp` **par handler** :

```php
$envelope->last(HandledStamp::class);   // le dernier
$envelope->all(HandledStamp::class);    // tous
```

Récupérer une valeur de retour n'a donc de sens qu'avec un handler unique — et
seulement en traitement synchrone.

## Versionner un message

Un message en attente dans une file a été sérialisé par l'ancien code et sera
lu par le nouveau. Ajouter une propriété **avec une valeur par défaut** est sûr ;
en retirer une, ou changer le sens de la classe, ne l'est pas. Dans ce cas, on
introduit une **nouvelle classe** et on garde l'ancienne le temps que les files
se vident.

## Pièges d'examen

**Aucune interface à implémenter**, ni pour le message ni pour le handler.

**Le message est apparié par le type de l'argument** de `__invoke()`.

**Plusieurs handlers sont légitimes** et tous s'exécutent.

**Ne pas transporter d'entité** : un identifiant, et un rechargement dans le
handler.

## Points clés

- Message = classe ordinaire sérialisable ; on y met des identifiants.
- `#[AsMessageHandler]` + type de l'argument de `__invoke()` suffisent.
- Plusieurs handlers possibles ; un `HandledStamp` par handler.
- Ajouter une propriété avec défaut est sûr ; en retirer ne l'est pas.

## Sources officielles

- [Messenger, « Creating a Message & Handler »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst)
