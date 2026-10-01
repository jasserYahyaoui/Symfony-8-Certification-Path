---
id: CRS-5gezsra1sp01
official_item: OIT-hs1297vvhr89
title: "Messenger component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Concepts: Sender, Receiver, Handler, Middleware, Envelope, Envelope Stamps"
    verified_at: "2026-10-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/HandleTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/HandleTrait.php"
    branch: "8.0"
    symbol_or_lines: "handle() — exactly one HandledStamp expected"
    verified_at: "2026-10-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Middleware/HandleMessageMiddleware.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/HandleMessageMiddleware.php"
    branch: "8.0"
    symbol_or_lines: "handle() — HandledStamp; NoHandlerForMessageException"
    verified_at: "2026-10-01"
---

## Objectif

Nommer correctement les concepts du composant et comprendre ce que
« dispatcher » veut dire. Transports, handlers et middleware ont chacun leur
item.

## Le problème résolu

Certaines tâches n'ont pas à retarder la réponse : envoyer un courriel,
redimensionner une image, appeler une API lente. Messenger permet de **décider
plus tard** — le contrôleur émet un message et rend la main ; un autre processus
le traite.

Le même code sert aussi de bus **synchrone** : c'est la configuration qui décide,
pas le code appelant.

## Les six concepts de la documentation

La section « Concepts » de `messenger.rst` (8.0) en nomme six :

| Concept | Rôle |
|---|---|
| **Sender** | sérialise et envoie le message vers « quelque chose » : un broker, une API |
| **Receiver** | récupère, désérialise et transmet aux handlers |
| **Handler** | exécute la logique métier ; appelé par `HandleMessageMiddleware` |
| **Middleware** | accède au message et à l'enveloppe pendant la traversée du bus |
| **Envelope** | emballe le message pour y attacher des informations |
| **Envelope Stamps** | ces informations : délai, transport, résultat, marqueurs |

Le **message** lui-même n'est pas dans la liste : c'est un objet PHP ordinaire,
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

| Routage | Handlers exécutés avant le retour de `dispatch()` | Stamps de l'enveloppe retournée |
|---|---|---|
| aucun (synchrone) | **les deux** | `DelayStamp`, `BusNameStamp`, `HandledStamp` ×2 |
| vers un transport `in-memory://` | **aucun** | `DelayStamp`, `BusNameStamp`, `SentStamp`, `TransportMessageIdStamp` |

En synchrone, le message est donc **déjà traité** quand `dispatch()` rend la
main. En asynchrone, il est seulement envoyé : le transport le contient, aucun
handler n'a tourné.

Le `DelayStamp(5000)` posé dans les deux cas n'a eu **aucun effet en
synchrone** : les handlers ont tourné immédiatement. Le délai est une consigne
pour le transport.

Un message **non routé et sans handler** n'est pas ignoré : exécuté,
`NoHandlerForMessageException` — « No handler for message "App\…\Orphan" ».

## L'enveloppe et les stamps

```php
$this->bus->dispatch(new SendWelcomeEmail($id), [new DelayStamp(5000)]);

$envelope = $this->bus->dispatch($message);
$handled  = $envelope->last(HandledStamp::class);
```

`dispatch()` **retourne l'enveloppe** ; le résultat d'un handler se lit sur son
`HandledStamp`, et seulement si le traitement a été synchrone. Avec deux
handlers, il y a deux `HandledStamp` : exécuté, `last()` rend le résultat du
second, `all(HandledStamp::class)` les deux.

`HandleTrait::handle()` raccourcit cette lecture, mais exige **exactement un**
handler. Exécuté avec les deux : `LogicException` — « was handled multiple
times. Only one handler is expected ».

## Pièges d'examen

**`dispatch()` ne signifie pas « traité » en asynchrone** — et signifie « déjà
traité » en synchrone.

**Un message n'implémente aucune interface.** C'est une classe ordinaire.

**Le résultat se lit sur l'enveloppe retournée**, via `HandledStamp`, et
uniquement en synchrone.

**Un `DelayStamp` ne retarde rien en synchrone.**

**Sans handler ni routage, `dispatch()` lève une exception.**

**Les six concepts de la documentation** sont sender, receiver, handler,
middleware, envelope et stamps — pas « message » ni « bus ».

## Points clés

- Six concepts : sender, receiver, handler, middleware, envelope, stamps.
- `dispatch()` confie au bus ; le routage décide du reste.
- L'enveloppe porte les stamps et est retournée par `dispatch()`.
- Plusieurs handlers : plusieurs `HandledStamp` ; `HandleTrait` en refuse plus
  d'un.

## Sources officielles

- [Messenger, « Concepts »](https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst)
- [Messenger 8.0, `HandleTrait`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/HandleTrait.php)
- [Messenger 8.0, `HandleMessageMiddleware`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/HandleMessageMiddleware.php)
