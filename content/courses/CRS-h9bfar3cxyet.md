---
id: CRS-h9bfar3cxyet
official_item: OIT-amnpjdprky6z
title: "Middleware"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Middleware, MiddlewareInterface, StackInterface"
    verified_at: "2026-11-03"
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
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Middleware/DispatchAfterCurrentBusMiddleware.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/DispatchAfterCurrentBusMiddleware.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "handle() — messages stamped DispatchAfterCurrentBusStamp wait for the current handler, dropped if it fails"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Middleware/SendMessageMiddleware.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/SendMessageMiddleware.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "handle() — NoSenderForMessageException unless allowNoSenders"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-se3m27j02981: "Ce qu'un middleware voit"
  OUT-f1z737x6w2pv: "Ce qu'un middleware voit"
  OUT-gcjncj08dwdf: "Deux passages en asynchrone"
  OUT-kc77500x8et6: "Où se place un middleware maison"
---

## Objectif

Intercepter un message pendant qu'il traverse le bus, savoir combien de fois la
chaîne de middlewares s'exécute, et où se place un middleware maison.

## L'essentiel

Le bus n'appelle pas le handler directement : il fait traverser une **chaîne de
middlewares**, chacun recevant l'**Envelope** et décidant de passer la main.
Un middleware maison se place **avant** l'envoi et le traitement ; il est
traversé **deux fois** pour un message asynchrone ; et s'il ne passe pas la
main, tout s'arrête **sans erreur**.

## Le schéma

```mermaid
---
title: La pile de middlewares du bus par défaut
---
flowchart TD
  accTitle: L'ordre des middlewares que traverse un message dans le bus par défaut
  accDescr: Le message traverse d'abord les middlewares par défaut qui posent des stamps et gèrent les cas particuliers, add_default_stamps, add_bus_name_stamp, reject_redelivered_message, dispatch_after_current_bus et failed_message_processing. Viennent ensuite les middlewares déclarés sous middleware, dans l'ordre de la liste. send_message envoie au transport si le message est routé, et la traversée s'arrête alors. Sinon handle_message appelle les handlers. Un middleware qui ne rappelle pas la suite arrête la traversée à sa place.
  IN(["dispatch()"]) --> DEF["add_default_stamps, add_bus_name_stamp, reject_redelivered_message, dispatch_after_current_bus, failed_message_processing"]
  DEF --> MINE["vos middlewares, dans l'ordre de la liste middleware"]
  MINE -->|"ne rappelle pas la suite"| STOP(["traversée arrêtée, rien d'autre"])
  MINE --> SEND{"send_message : routé ?"}
  SEND -->|"oui"| SENT(["envoyé au transport"])
  SEND -->|"non"| HANDLE(["handle_message : les handlers"])
```

Un middleware maison s'insère donc **avant** l'envoi et le traitement : il
peut empêcher l'un comme l'autre.

## L'exemple

```yaml
framework:
    messenger:
        buses:
            messenger.bus.default:
                middleware:
                    - 'App\Middleware\AuditMiddleware'
```

```php
final class AuditMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $side = $envelope->last(ReceivedStamp::class) ? 'received' : 'dispatched';
        $this->logger->info($side, ['class' => $envelope->getMessage()::class]);

        return $stack->next()->handle($envelope, $stack);
    }
}
```

## Ce qu'un middleware voit

- Il reçoit l'**Envelope** — le message *et* ses stamps — et rend une
  Envelope.
- `$stack->next()->handle(...)` passe au suivant. **Ne pas l'appeler arrête la
  chaîne de middlewares** : aucun handler appelé, et même pour un message **sans handler**,
  **aucune exception** — `NoHandlerForMessageException` est levée par
  `HandleMessageMiddleware`, jamais atteint.
- Il peut **modifier l'Envelope** et agir avant *et* après la suite : un stamp
  ajouté avant d'appeler la suite se retrouve sur l'Envelope que `dispatch()`
  retourne.

## Deux passages en asynchrone

La chaîne de middlewares est traversée à l'**envoi**, puis à la **réception** par le worker :

| Moment | Journal de deux middlewares `A` puis `B` |
|---|---|
| après `dispatch()` | `A:dispatched`, `B` |
| après `messenger:consume --limit=1` | … puis `A:received`, `B`, et le handler |

Un middleware qui compte compte deux fois ; le `ReceivedStamp` distingue la
réception de l'envoi.

## Où se place un middleware maison

```text
add_default_stamps_middleware
add_bus_name_stamp_middleware
reject_redelivered_message_middleware
dispatch_after_current_bus
failed_message_processing_middleware
   ← vos middlewares, dans l'ordre déclaré
send_message
handle_message
```

`send_message` envoie au transport ; `handle_message` appelle les handlers et
ferme la chaîne de middlewares. Avec le component Lock, `deduplicate_middleware` s'ajoute en
fin de premier group.

## La pile par défaut

| `default_middleware` | Pile | Sans handler | Avec handler |
|---|---|---|---|
| (défaut) | complète | `NoHandlerForMessageException` | traité |
| `allow_no_handlers` | complète | accepté, aucun `HandledStamp` | traité |
| `false` | **vide** | accepté | **non traité**, sans erreur |

- Avec `default_middleware: false`, la pile se limite à la liste
  `middleware:` : sans `send_message` ni `handle_message`, rien n'est fait.
- `allow_no_senders` vaut `true` par défaut ; à `false`, tout message non
  routé lève `NoSenderForMessageException`, même s'il a des handlers.

## `DispatchAfterCurrentBusStamp`

Un handler qui dispatche un message peut le faire trop tôt : s'il échoue
ensuite, le message parti ne revient pas.

```php
$this->bus->dispatch(new OrderConfirmationEmail($command->orderId), [new DispatchAfterCurrentBusStamp()]);
```

Le second message est traité **après** la fin du handler courant, et **jamais**
si celui-ci lève : le middleware `dispatch_after_current_bus` le retient.

## Pièges d'examen

**Ne pas appeler `$stack->next()` arrête tout** — silencieusement, même pour un
message sans handler.

**La chaîne de middlewares s'exécute deux fois** en asynchrone : à l'envoi et à la réception ;
`ReceivedStamp` les distingue.

**Le middleware reçoit l'Envelope**, pas le message nu : les stamps sont
lisibles.

**Un middleware maison passe avant `send_message` et `handle_message`.**

**`default_middleware: false` retire aussi le traitement** : il faut alors
déclarer soi-même `send_message` et `handle_message`.

## Application

- Le client reçoit la confirmation d'un achat dont le paiement a échoué →
  `DispatchAfterCurrentBusStamp` sur l'email.
- Des messages disparaissent sans erreur derrière un middleware de filtrage →
  chercher la branche qui ne rappelle pas `$stack->next()`.

## QCM d'entraînement

**Question 1.** Un handler de `PlaceOrder` dispatche `OrderConfirmationEmail`
avec `DispatchAfterCurrentBusStamp`, puis lève une exception. Le handler de
l'email tourne-t-il ?

- **A.** Oui, avant que le premier handler ne lève
- **B.** Oui, après l'exception, comme un message ordinaire
- **C.** Non, jamais : le message est abandonné
- **D.** Oui, mais seulement au retry du premier message

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le message retenu n'est dispatché qu'à la fin réussie du
handler courant ; A et B décrivent un dispatch sans le stamp.

</details>

**Question 2.** Un bus déclare `default_middleware: { enabled: true,
allow_no_senders: false }`. Un message non routé, qui a deux handlers, est
dispatché. Que se passe-t-il ?

- **A.** `NoSenderForMessageException`
- **B.** Les deux handlers tournent, comme d'habitude
- **C.** Le message part vers le premier transport déclaré
- **D.** `NoHandlerForMessageException`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Sans sender, `SendMessageMiddleware` lève l'exception
avant `handle_message` ; B est le comportement par défaut.

</details>

**Question 3.** Un middleware ajoute un stamp maison avant d'appeler la suite.
Le message est traité en synchrone. Le stamp est-il sur l'Envelope que
`dispatch()` retourne ?

- **A.** Non : les middlewares ne modifient pas l'Envelope
- **B.** Oui, il y est
- **C.** Non : seul `handle_message` peut ajouter des stamps
- **D.** Seulement si le message est routé

<details>
<summary>Correction</summary>

**Bonne réponse : B.** L'Envelope enrichie descend la chaîne de middlewares, et le middleware
rend celle que lui rend la suite, qui porte toujours le stamp.

</details>

**Question 4.** Quelle configuration accepte un message sans handler, sans
retirer la pile par défaut ?

- **A.** `default_middleware: false`
- **B.** `allow_no_senders: true`
- **C.** Un middleware qui rend l'Envelope sans appeler la suite
- **D.** `default_middleware: allow_no_handlers`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** La forme courte vaut `enabled: true,
allow_no_handlers: true` ; A vide la pile, et plus rien n'est traité.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Un middleware reçoit l'**Envelope** et rend une Envelope.
- **`$stack->next()->handle()`** continue ; l'omettre arrête, **sans erreur**.
- En asynchrone, **deux passages** ; **`ReceivedStamp`** pour les distinguer.
- Vos middlewares passent **avant** `send_message` et `handle_message`.
- **`allow_no_handlers`** : pas d'erreur sans handler.
- **`default_middleware: false`** : plus d'envoi, plus de traitement.
- **`DispatchAfterCurrentBusStamp`** : après le handler courant, jamais s'il
  échoue.

Moyen mnémotechnique :
**« recevoir l'Envelope, passer la main, ou tout arrêter »**.

```text
défauts (stamps, redelivered, dispatch_after_current_bus, failed) > vos middlewares > send_message > handle_message ;
next() sinon arrêt muet ; 2 passages (ReceivedStamp) ; allow_no_handlers / allow_no_senders / false = pile vide
```

## Sources officielles

- [Messenger, « Middleware » et « Transactional Messages »](https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst)
- [FrameworkBundle 8.0, `FrameworkExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php)
- [FrameworkBundle 8.0, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php)
- [Messenger 8.0, `HandleMessageMiddleware`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/HandleMessageMiddleware.php)
- [Messenger 8.0, `SendMessageMiddleware`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/SendMessageMiddleware.php)
- [Messenger 8.0, `DispatchAfterCurrentBusMiddleware`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/DispatchAfterCurrentBusMiddleware.php)

