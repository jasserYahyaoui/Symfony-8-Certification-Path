---
id: CRS-hgkzgm1dh3my
official_item: OIT-76x1t7z916f4
title: "Retries and failures"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-01"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "retry_strategy, Unrecoverable and Recoverable exceptions, failure_transport, messenger:failed"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/EventListener/SendFailedMessageForRetryListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/SendFailedMessageForRetryListener.php"
    branch: "8.0"
    symbol_or_lines: "shouldRetry() — RecoverableExceptionInterface returns true before the retry strategy"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/EventListener/SendFailedMessageToFailureTransportListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/SendFailedMessageToFailureTransportListener.php"
    branch: "8.0"
    symbol_or_lines: "onMessageFailed() — SentToFailureTransportStamp and RedeliveryStamp(0)"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Retry/MultiplierRetryStrategy.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Retry/MultiplierRetryStrategy.php"
    branch: "8.0"
    symbol_or_lines: "getWaitingTime() — delay * multiplier ** retries, max delay 0 means no maximum"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Command/FailedMessagesRetryCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Command/FailedMessagesRetryCommand.php"
    branch: "8.0"
    symbol_or_lines: "runWorker() on the failure transport receiver"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Middleware/HandleMessageMiddleware.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/HandleMessageMiddleware.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "handle() — a handler with a HandledStamp is skipped; failures wrapped in HandlerFailedException"
    verified_at: "2026-11-01"
outcome_sections:
  OUT-7rq6sxm6qqr3: "La strategy de retry"
  OUT-y57ephy9n9ey: "Court-circuiter la strategy"
  OUT-d866n6yn58qx: "Le failure transport"
  OUT-jd6ca4q02538: "L'enchaînement complet"
  OUT-4f772gc14626: "Le failure transport"
---

## Objectif

Décider ce qui arrive à un message dont le handler a levé une exception : le
rejouer, l'abandonner, ou le mettre de côté. Trois mécanismes se superposent, et
c'est leur ordre qui décide.

## Avant la règle : le problème à résoudre

Une API de paiement répond en timeout ; une adresse email n'existe plus ; un
identifier pointe vers une ligne supprimée. Trois échecs, trois réactions
différentes : **réessayer plus tard**, **abandonner tout de suite**, ou **mettre
de côté** pour qu'un humain décide. Il faut savoir laquelle Messenger choisit,
et comment la lui imposer.

On suppose connus les transports et les workers.

## Le fil rouge : le paiement et le client supprimé

```yaml
framework:
    messenger:
        failure_transport: failed
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy: { max_retries: 3, delay: 1000, multiplier: 2 }
            failed: '%env(MESSENGER_FAILED_DSN)%'
```

```php
#[AsMessageHandler]
final class ChargeCustomerHandler
{
    public function __invoke(ChargeCustomer $message): void
    {
        $customer = $this->customers->find($message->customerId)
            ?? throw new UnrecoverableMessageHandlingException('Customer deleted.');

        $this->gateway->charge($customer);   // un timeout lève une exception ordinaire
    }
}
```

Un timeout : quatre tentatives, à environ 1 s, 2 s, 4 s d'intervalle (le
`jitter` par défaut les disperse), puis la file `failed`. Un client supprimé : une seule tentative, puis la file `failed`.

## D'abord : synchrone ou asynchrone ?

Tout ce qui suit ne concerne que les messages **consommés par un worker**. Un
message non routé, traité pendant `dispatch()`, n'a ni retry ni file d'échec :
l'exception remonte à l'appelant. Exécuté avec Messenger 8.0.15 :
`HandlerFailedException` — « Handling "App\P13\M\Boom" failed: boom » —, une
seule tentative.

**L'exception d'origine est emballée.** Rejoué : un handler synchrone lève une
`DomainException` ; un `catch (\DomainException $e)` autour de `dispatch()`
**ne l'attrape pas**. Ce qui remonte est la `HandlerFailedException`, et
l'exception d'origine se lit dans `getWrappedExceptions()`.

## Le comportement par défaut

Pour un message asynchrone, une exception dans un handler **n'est pas fatale** :
le message est renvoyé dans le transport et rejoué plus tard. C'est voulu — la
plupart des échecs sont temporaires : base indisponible, API en timeout.

Le nombre de tentatives est fini. Sans configuration de repli, un message qui a
épuisé ses tentatives est **abandonné**.

**Un retry ne rejoue que ce qui a échoué.** Rejoué : deux handlers, l'un échoue
au premier essai puis réussit, l'autre réussit toujours. Après le retry, le
premier a été appelé deux fois, le second **une seule**. Lu dans
`HandleMessageMiddleware` : un handler dont l'Envelope porte déjà le
`HandledStamp` est sauté.

## La strategy de retry

```yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000        # premier délai, en millisecondes
                    multiplier: 2      # 1s, 2s, 4s
                    max_delay: 10000   # plafond ; 0 = pas de plafond
                    jitter: 0.1        # aléatoire, de 0 à 1
```

Les default values de FrameworkBundle 8.0 sont celles-ci, sauf `max_delay`,
qui vaut **0**. Le délai croît **exponentiellement** — `delay × multiplier^n` —
pour ne pas marteler un service déjà en difficulté ; `max_delay` plafonne la
progression ; le `jitter` disperse les retries pour que mille messages ne
repartent pas à la même seconde. Calculé avec `MultiplierRetryStrategy` :
1000, 2000, 4000 ms sans jitter ; avec un jitter de 0,1, chaque délai varie au
hasard de ±10 % — un tirage : 1096, 2109, 4298 ms.

`max_retries: 3` signifie **trois retries après l'échec initial**, donc quatre
tentatives en tout. Exécuté avec un handler qui échoue toujours : **4**
tentatives.

## Court-circuiter la strategy

Deux familles d'exceptions changent la décision, dans les deux sens :

| Exception | Effet |
|---|---|
| `UnrecoverableMessageHandlingException` | **aucun retry** : le message part directement en échec |
| `RecoverableMessageHandlingException` | retry **forcé** ; son quatrième argument, `retryDelay`, fixe le délai en ms |

Lu dans `SendFailedMessageForRetryListener::shouldRetry()` : une exception
*recoverable* renvoie `true` **avant** que `max_retries` ne soit consulté. Elle
réessaie donc **sans limite**. Exécuté, worker limité à 12 messages : 12
tentatives, et le message toujours en file, jamais en échec.

Rejoué : `new RecoverableMessageHandlingException('later', 0, null, 5000)`
renvoie le message avec un `DelayStamp(5000)`, alors que la strategy du
transport disait `delay: 0`.

Quand plusieurs handlers échouent, leurs exceptions sont emballées dans une
`HandlerFailedException` : il faut que **toutes** soient *unrecoverable* pour
que le retry soit refusé. Rejoué : un handler lève
`UnrecoverableMessageHandlingException`, l'autre une `DomainException` — quatre
tentatives, comme pour une exception ordinaire.

La première est la plus utile. Un message invalide — un identifier qui
n'existera jamais — ne deviendra pas valide en le rejouant. C'est la
distinction de fond : **la strategy traite l'échec temporaire,
`Unrecoverable` l'échec permanent**. La seconde se manie avec prudence : sans
condition de sortie, elle tourne sans fin.

## Le failure transport

```yaml
framework:
    messenger:
        failure_transport: failed
        transports:
            failed: '%env(MESSENGER_FAILED_DSN)%'
```

Sans lui, un message épuisé est **perdu**. Avec lui, il est déplacé dans une file
dédiée, où il attend une décision humaine. Exécuté :

| Exception | Tentatives | Sans `failure_transport` | Avec |
|---|---|---|---|
| ordinaire | 4 | perdu | en file d'échec |
| `Unrecoverable…` | 1 | perdu | en file d'échec |
| `Recoverable…` | illimitées | jamais en échec | jamais en échec |

```bash
php bin/console messenger:failed:show
php bin/console messenger:failed:show --stats
php bin/console messenger:failed:retry
php bin/console messenger:failed:remove <id>
```

`messenger:failed:retry` ne renvoie **pas** le message vers son transport
d'origine : la command le **traite elle-même**, en lisant la file d'échec. Le
compteur de tentatives a été remis à zéro à l'entrée en file d'échec
(`RedeliveryStamp(0)`). Exécuté : un message qui échoue encore pendant
`failed:retry` revient dans la file d'échec, avec un compteur à 1 — rien n'est
envoyé vers `async`.

Rejoué avec Messenger 8.0, en plus : il y revient avec un `DelayStamp` d'environ
une seconde, puisque la file `failed` a sa propre `retry_strategy`, par défaut.
Et hors mode interactif — un script, une CI —, `messenger:failed:retry` sans
identifier refuse : « Message id must be passed when in non-interactive mode. »
La command le dit elle-même : pour tout rejouer, `messenger:consume failed`.

## L'enchaînement complet

```mermaid
---
title: Un handler lève, pendant la consommation par un worker
---
flowchart TD
  accTitle: Ce que devient un message dont le handler a levé une exception
  accDescr: Dans l'ordre de shouldRetry(). Une RecoverableMessageHandlingException force le retry, sans limite. Si toutes les exceptions sont Unrecoverable, aucun retry. Sinon, s'il reste des tentatives selon max_retries, retry après un délai croissant. Sans retry, le message part dans le failure_transport s'il est configuré, et il est perdu sinon.
  F(["le handler lève"]) --> R{"Recoverable ?"}
  R -->|"oui"| RETRY["retry, sans limite"]
  R -->|"non"| U{"toutes Unrecoverable ?"}
  U -->|"non"| N{"tentatives restantes ?"}
  N -->|"oui"| DELAY["retry après délai croissant"]
  N -->|"non"| FT{"failure_transport ?"}
  U -->|"oui"| FT
  FT -->|"oui"| FAILED["file d'échec, en attente humaine"]
  FT -->|"non"| LOST(["message perdu"])
```

Chaque étage change l'issue, et c'est l'ordre qui compte : une
`UnrecoverableMessageHandlingException` saute la strategy mais **pas** le
failure transport.

## Concrètement dans ton application

- Toujours un `failure_transport` en production : sans lui, l'échec est une
  perte.
- Échec permanent connu — ligne supprimée, donnée invalide : `Unrecoverable…`.
- Service tiers qui annonce un délai : `Recoverable…` avec `retryDelay`, et une
  condition de sortie.
- Un `max_delay` pour ne pas attendre des heures au dixième retry.
- Des handlers idempotents : un retry rejoue ceux qui ont échoué.
- `messenger:failed:show --stats` dans la supervision ; `messenger:consume
  failed` pour tout rejouer d'un coup.

## À comprendre

- **Pourquoi réessayer par défaut ?** La plupart des échecs d'un worker sont
  passagers ; abandonner au premier coûterait plus que réessayer.
- **Pourquoi un délai exponentiel ?** Un service en difficulté a besoin de
  temps ; le frapper à intervalle fixe le maintient à terre.
- **Pourquoi la file d'échec n'est-elle pas un retry ?** Elle sert aux échecs
  qu'aucune attente ne corrigera : il faut un regard humain, ou un correctif.

## À mémoriser

- **Synchrone** : `HandlerFailedException`, pas de retry.
- **`max_retries: 3`** = **quatre** tentatives ; délais `1000 × 2^n`.
- Défauts : `max_retries` 3, `delay` 1000, `multiplier` 2, **`max_delay` 0**,
  `jitter` 0,1.
- **`Unrecoverable…`** : zéro retry ; **`Recoverable…`** : retry sans limite.
- **`failure_transport`** : sinon, perdu.
- **`messenger:failed:retry`** traite lui-même ; non interactif → un id.
- Moyen mnémotechnique : **« réessayer, abandonner, mettre de côté »**.

## Pièges d'examen

- En **synchrone**, l'exception remonte au `dispatch()` : pas de retry.
- En asynchrone, une exception ne perd pas le message : elle déclenche un retry.
- `max_retries: 3` = **quatre** tentatives au total.
- `RecoverableMessageHandlingException` ignore `max_retries`.
- Sans `failure_transport`, un message épuisé est **définitivement perdu**.
- `UnrecoverableMessageHandlingException` saute les retries, pas la file d'échec.
- Le délai est **exponentiel**, pas constant ; `max_delay` vaut 0 par défaut.
- `messenger:failed:retry` traite le message lui-même, depuis la file d'échec.

## Application : trois scénarios

**Scénario 1 — Le catch qui ne sert à rien.** Un controller entoure un
`dispatch()` synchrone d'un `catch (PaymentDeclined $e)` ; l'exception passe
au travers. *Solution.* Attraper `HandlerFailedException` et chercher
`PaymentDeclined` dans `getWrappedExceptions()`.

**Scénario 2 — L'email envoyé deux fois.** Un message a deux handlers :
facture et email. La facture échoue, le message est rejoué. *Solution.* Rien à
faire pour l'email : le handler qui a réussi porte son `HandledStamp` et n'est
pas rappelé. Seule la facture doit être idempotente.

**Scénario 3 — Le script de reprise.** Une CI lance `messenger:failed:retry
--force` après un incident ; la command échoue. *Solution.* Hors mode
interactif, il faut un id ; pour tout rejouer, `messenger:consume failed`.

## QCM d'entraînement

Cinq questions sur les échecs. Choisis avant d'ouvrir la correction.

**Question 1.** Un message non routé ; son handler lève une `DomainException`.
Le code appelle `dispatch()` dans un `try`, avec `catch (\DomainException $e)`.
Le catch s'exécute-t-il ?

- **A.** Oui : c'est l'exception levée par le handler
- **B.** Oui, après trois retries
- **C.** Non : aucune exception ne remonte en synchrone
- **D.** Non : elle arrive emballée

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Rejoué : ce qui remonte est une `HandlerFailedException`,
qui emballe la `DomainException` ; on la retrouve dans
`getWrappedExceptions()`.

A oublie l'emballage. B confond avec un worker. C est faux : une exception
remonte bien, mais pas celle-là.

</details>

**Question 2.** Un message asynchrone a deux handlers. Le premier lève au
premier essai, le second réussit. Au retry, combien de fois le second a-t-il
été appelé en tout ?

- **A.** Une fois, au premier essai
- **B.** Deux fois : le retry rejoue tout le message
- **C.** Zéro : un échec annule tous les handlers
- **D.** Quatre fois : `max_retries` vaut 3

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Rejoué. Lu dans `HandleMessageMiddleware` : le
`HandledStamp` du second est resté sur l'Envelope, et un handler déjà traité
est sauté.

B est l'idée reçue. C et D sont faux.

</details>

**Question 3.** Un handler lève
`new RecoverableMessageHandlingException('later', 0, null, 5000)`. La strategy
du transport dit `delay: 1000`. Quel délai avant le retry ?

- **A.** 1000 ms : la strategy décide
- **B.** 5000 ms, le délai de l'exception
- **C.** Aucun : `Recoverable` rejoue immédiatement
- **D.** 6000 ms : les deux s'additionnent

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Rejoué : le message repart avec un `DelayStamp(5000)`.
Lu dans `SendFailedMessageForRetryListener::getWaitingTime()` : le
`retryDelay` de l'exception passe avant la strategy.

A, C et D sont faux.

</details>

**Question 4.** Deux handlers échouent sur le même message : l'un lève
`UnrecoverableMessageHandlingException`, l'autre une `DomainException`.
`max_retries: 3`. Combien de tentatives ?

- **A.** Une : un `Unrecoverable` suffit à tout arrêter
- **B.** Sans limite
- **C.** Quatre, comme une exception ordinaire
- **D.** Deux : une par handler

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Rejoué. Lu dans `shouldRetry()` : le retry n'est refusé
que si **toutes** les exceptions emballées sont *unrecoverable* ; sinon la
strategy s'applique.

A est l'idée reçue. B décrirait un `Recoverable`. D est faux.

</details>

**Question 5.** Une CI, non interactive, lance `messenger:failed:retry --force`
sans identifier. Résultat ?

- **A.** Tous les messages de la file d'échec sont rejoués
- **B.** Une erreur : il faut passer un identifier
- **C.** Les messages repartent vers leur transport d'origine
- **D.** Rien : la command attend une confirmation

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Rejoué : « Message id must be passed when in
non-interactive mode. » La command indique elle-même `messenger:consume failed`
pour tout rejouer.

A est vrai en mode interactif. C est faux : la command traite elle-même. D est
faux : `--force` supprime la confirmation, pas le besoin d'un id.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **Synchrone** : une `HandlerFailedException` remonte, sans retry.
- **`max_retries: 3`** = quatre tentatives, délais exponentiels.
- **`max_delay`** vaut **0** par défaut : pas de plafond.
- **`Unrecoverable…`** saute les retries, **pas** la file d'échec.
- **`Recoverable…`** force le retry, sans limite ; `retryDelay` fixe le délai.
- Sans **`failure_transport`**, un message épuisé est perdu.
- **`messenger:failed:retry`** traite lui-même ; en script, un id.

**En une phrase, pour tout reconstruire :**

```text
sync → HandlerFailedException (getWrappedExceptions) ; async → Recoverable ? retry sans fin : toutes Unrecoverable ? échec : max_retries (+1 tentative) ;
délai delay×multiplier^n, max_delay 0 ; échec → failure_transport ou perdu ; failed:retry traite lui-même, id hors interactif
```

## Sources officielles

- [Messenger, « Retries & Failures »](https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst)
- [Messenger 8.0, `SendFailedMessageForRetryListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/SendFailedMessageForRetryListener.php)
- [Messenger 8.0, `SendFailedMessageToFailureTransportListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/SendFailedMessageToFailureTransportListener.php)
- [Messenger 8.0, `MultiplierRetryStrategy`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Retry/MultiplierRetryStrategy.php)
- [Messenger 8.0, `FailedMessagesRetryCommand`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Command/FailedMessagesRetryCommand.php)
- [Messenger 8.0, `HandleMessageMiddleware`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/HandleMessageMiddleware.php)
