---
id: CRS-h9bfar3cxyet
official_item: OIT-amnpjdprky6z
title: "Middleware"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Middleware, MiddlewareInterface, StackInterface"
    verified_at: "2026-09-01"
---

## Objectif

Intercepter un message pendant qu'il traverse le bus, et savoir combien de fois
la chaîne s'exécute.

## Ce qu'un middleware voit

Le bus n'appelle pas le handler directement : il fait traverser une **chaîne de
middleware**, chacun recevant l'**enveloppe** — donc le message *et* ses stamps.

```php
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class AuditMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->logger->info('dispatching', ['class' => $envelope->getMessage()::class]);

        return $stack->next()->handle($envelope, $stack);
    }
}
```

`$stack->next()->handle(...)` passe au suivant. **Ne pas l'appeler interrompt la
chaîne** : le message n'atteint jamais son handler. C'est un pouvoir, pas un
accident à éviter — mais un `return` oublié produit le même effet sans le vouloir.

Un middleware peut aussi **modifier l'enveloppe** : ajouter un stamp,
la remplacer, agir avant *et* après l'appel suivant.

## Le point qui se rate

La chaîne est traversée **deux fois** pour un message asynchrone : une fois à
l'envoi, quand il est dispatché, et une seconde fois à la réception, quand le
worker le récupère du transport.

Un middleware qui journalise écrira donc deux lignes ; un middleware qui compte
comptera deux fois. Pour agir d'un seul côté, il faut regarder les stamps —
la présence d'un `ReceivedStamp` distingue la réception de l'envoi.

## Ceux qui existent déjà

Le bus est lui-même fait de middleware. `HandleMessageMiddleware` est celui qui
appelle les handlers, et il est **le dernier** de la chaîne — d'où le fait qu'un
middleware placé après lui ne verrait rien.

Les autres couvrent l'envoi au transport, la validation, la journalisation, et
l'ouverture d'une transaction Doctrine autour du handler.

## Déclarer

```yaml
framework:
    messenger:
        buses:
            messenger.bus.default:
                middleware:
                    - 'App\Middleware\AuditMiddleware'
```

L'ordre de la liste est l'ordre d'exécution.

## Pièges d'examen

**Ne pas appeler `$stack->next()` arrête tout** — silencieusement.

**La chaîne s'exécute deux fois** en asynchrone : à l'envoi et à la réception.

**Le middleware reçoit l'enveloppe**, pas le message nu : les stamps sont
lisibles.

**`HandleMessageMiddleware` est le dernier** ; c'est lui qui appelle les handlers.

## Points clés

- `MiddlewareInterface::handle(Envelope, StackInterface): Envelope`.
- `$stack->next()->handle()` continue ; l'omettre interrompt.
- Deux passages en asynchrone : envoi puis réception.
- L'ordre déclaré est l'ordre d'exécution ; les handlers sont appelés en bout de chaîne.

## Sources officielles

- [Messenger, « Middleware »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst)
