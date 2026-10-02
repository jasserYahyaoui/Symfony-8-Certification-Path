---
id: CRS-h9bfar3cxyet
official_item: OIT-amnpjdprky6z
title: "Middleware"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Middleware, MiddlewareInterface, StackInterface"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    branch: "8.0"
    symbol_or_lines: "registerMessengerConfiguration() — default middleware before and after the bus middleware"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    branch: "8.0"
    symbol_or_lines: "buses.default_middleware — allow_no_handlers false, allow_no_senders true"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Middleware/HandleMessageMiddleware.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/HandleMessageMiddleware.php"
    branch: "8.0"
    symbol_or_lines: "handle() — NoHandlerForMessageException unless allowNoHandlers"
    verified_at: "2026-10-02"
---

## Objectif

Intercepter un message pendant qu'il traverse le bus, savoir combien de fois la
chaîne s'exécute, et où se place un middleware maison.

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
chaîne** : le message n'atteint jamais son handler. Exécuté avec Messenger
8.0.15, un middleware qui rend l'enveloppe sans appeler la suite pour un message
`Halt` **sans handler** : aucun handler appelé, aucun `HandledStamp`, et **aucune
exception** — la `NoHandlerForMessageException` est levée par
`HandleMessageMiddleware`, qui n'a jamais été atteint. Le message disparaît en
silence.

Un middleware peut aussi **modifier l'enveloppe** : ajouter un stamp, la
remplacer, agir avant *et* après l'appel suivant.

## Deux passages en asynchrone

La chaîne est traversée **deux fois** pour un message asynchrone : à l'envoi,
quand il est dispatché, puis à la réception, quand le worker le récupère du
transport. Exécuté, deux middleware `A` et `B` déclarés dans cet ordre, un
message routé vers un transport :

| Moment | Journal |
|---|---|
| après `dispatch()` | `A:dispatched`, `B` |
| après `messenger:consume --limit=1` | … puis `A:received`, `B`, et le handler |

Un middleware qui journalise écrit donc deux lignes ; un middleware qui compte
compte deux fois. Pour agir d'un seul côté, on regarde les stamps : la présence
d'un `ReceivedStamp` distingue la réception de l'envoi — c'est ce que
`A` a testé ci-dessus.

## Où se place un middleware maison

Le bus est lui-même fait de middleware. Lu dans `FrameworkExtension` (8.0),
puis relevé sur le bus construit, avec un middleware maison `A` :

```text
add_default_stamps_middleware
add_bus_name_stamp_middleware
reject_redelivered_message_middleware
dispatch_after_current_bus
failed_message_processing_middleware
   ← vos middleware, dans l'ordre déclaré
send_message
handle_message
```

`handle_message` (`HandleMessageMiddleware`) appelle les handlers et ferme la
chaîne ; `send_message` (`SendMessageMiddleware`) envoie au transport juste
avant. Un middleware déclaré dans `middleware:` s'insère donc **avant** l'envoi
et le traitement — ce qui lui permet d'interrompre l'un et l'autre. Quand le
composant Lock est activé, `deduplicate_middleware` s'ajoute en fin de premier
groupe.

## Déclarer

```yaml
framework:
    messenger:
        buses:
            messenger.bus.default:
                middleware:
                    - 'App\Middleware\AuditMiddleware'
```

L'ordre de la liste est l'ordre d'exécution : exécuté, `A` avant `B`.

L'option `default_middleware` du bus règle la pile par défaut. Exécuté, un
message sans handler puis un message avec handler :

| `default_middleware` | Pile | Sans handler | Avec handler |
|---|---|---|---|
| (défaut) | complète | `NoHandlerForMessageException` | traité |
| `allow_no_handlers` | complète | accepté, aucun `HandledStamp` | traité |
| `false` | **vide** | accepté | **non traité**, sans erreur |

Avec `false`, la pile se limite à la liste `middleware:` : sans
`send_message` ni `handle_message`, un `dispatch()` ne fait rien et ne le
signale pas. `allow_no_senders` vaut `true` par défaut.

## Pièges d'examen

**Ne pas appeler `$stack->next()` arrête tout** — silencieusement, même pour un
message sans handler.

**La chaîne s'exécute deux fois** en asynchrone : à l'envoi et à la réception ;
`ReceivedStamp` les distingue.

**Le middleware reçoit l'enveloppe**, pas le message nu : les stamps sont
lisibles.

**Un middleware maison passe avant `send_message` et `handle_message`.**

**`default_middleware: false` retire aussi le traitement** : il faut alors
déclarer soi-même `send_message` et `handle_message`.

## Points clés

- `MiddlewareInterface::handle(Envelope, StackInterface): Envelope`.
- `$stack->next()->handle()` continue ; l'omettre interrompt.
- Deux passages en asynchrone : envoi puis réception.
- L'ordre déclaré est l'ordre d'exécution ; `handle_message` ferme la chaîne.

## Sources officielles

- [Messenger, « Middleware »](https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst)
- [FrameworkBundle 8.0, `FrameworkExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php)
- [FrameworkBundle 8.0, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php)
- [Messenger 8.0, `HandleMessageMiddleware`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/HandleMessageMiddleware.php)
