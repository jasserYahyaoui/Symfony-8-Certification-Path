---
id: CRS-5gezsra1sp01
official_item: OIT-hs1297vvhr89
title: "Messenger component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Concepts: sender, receiver, handler, middleware, envelope, bus"
    verified_at: "2026-09-01"
---

## Objectif

Nommer correctement les six pièces du composant et comprendre ce que
« dispatcher » veut dire. Transports, handlers et middleware ont chacun leur
item.

## Le problème résolu

Certaines tâches n'ont pas à retarder la réponse : envoyer un courriel,
redimensionner une image, appeler une API lente. Messenger permet de **décider
plus tard** — le contrôleur émet un message et rend la main ; un autre processus
le traite.

Le même code sert aussi de bus **synchrone** : c'est la configuration qui décide,
pas le code appelant.

## Les six concepts

| Concept | Rôle |
|---|---|
| **Message** | un objet PHP ordinaire, sérialisable ; aucune interface à implémenter |
| **Bus** | reçoit le message et le fait traverser les middleware |
| **Enveloppe** | emballe le message et porte ses **stamps** |
| **Sender** | sérialise et envoie vers un transport |
| **Receiver** | récupère, désérialise et transmet aux handlers |
| **Handler** | exécute la logique métier |

## Dispatcher

```php
public function __construct(private MessageBusInterface $bus) {}

$this->bus->dispatch(new SendWelcomeEmail($userId));
```

`dispatch()` **ne traite pas** le message : il le confie au bus. Ce qui se passe
ensuite dépend du routage — traité immédiatement si le message n'est routé nulle
part, envoyé à un transport sinon.

## L'enveloppe et les stamps

Le bus ne manipule pas le message nu : il l'emballe dans une `Envelope`. Les
**stamps** sont les métadonnées collées dessus — un délai, l'identifiant du
transport, le résultat d'un handler.

```php
$this->bus->dispatch(new SendWelcomeEmail($id), [new DelayStamp(5000)]);

$envelope = $this->bus->dispatch($message);
$handled  = $envelope->last(HandledStamp::class);
```

`dispatch()` **retourne l'enveloppe**, ce qui est le seul moyen de récupérer un
résultat — et seulement quand le traitement a été synchrone.

## Pièges d'examen

**`dispatch()` ne signifie pas « traité ».** En asynchrone, la méthode rend la
main avant que quoi que ce soit ne s'exécute.

**Un message n'implémente aucune interface.** C'est une classe ordinaire.

**Le résultat se lit sur l'enveloppe retournée**, via `HandledStamp`, et
uniquement en synchrone.

## Points clés

- Six concepts : message, bus, enveloppe, sender, receiver, handler.
- `dispatch()` confie au bus ; le routage décide du reste.
- L'enveloppe porte les stamps et est retournée par `dispatch()`.
- Le même code est synchrone ou asynchrone selon la configuration.

## Sources officielles

- [Messenger, « Concepts »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst)
