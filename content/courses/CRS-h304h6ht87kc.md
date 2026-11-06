---
id: CRS-h304h6ht87kc
official_item: OIT-j4vpn5bh9f27
title: "Mailer"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-09"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/mailer.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/mailer.rst"
    anchor: "transport-setup"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Mailer/Transport/Transports.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Mailer/Transport/Transports.php"
    branch: "8.0"
    symbol_or_lines: "__construct(); send()"
    verified_at: "2026-10-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Mailer/Mailer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Mailer/Mailer.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "send(): dispatches a SendEmailMessage on the bus when one is set; deferral depends on its routing"
    verified_at: "2026-10-09"
outcome_sections:
  OUT-2q2fdnv4mhv9: "Le transport"
  OUT-zpm8v90288p0: "Ce que rend l'envoi"
  OUT-ybad6g99v4cm: "Envoyer"
  OUT-f466f6crw21c: "Plusieurs transports"
  OUT-7fptsggm3eqe: "Quand l'envoi échoue"
---

## Objectif

Envoyer un message, choisir son transport, et savoir ce que l'envoi rend — ou
ne rend pas.

## L'essentiel

Mailer envoie un message par un **transport**, décrit par un DSN : `smtp`,
`sendmail` ou `native`. `MailerInterface::send()` ne rend rien, parce qu'il
peut différer l'envoi par Messenger ; `TransportInterface::send()` envoie
toujours tout de suite et rend un `SentMessage`. Un succès veut seulement dire
que le transport a accepté le message.

## Le schéma

```mermaid
---
title: Un email, de Mailer::send() au transport
---
flowchart TD
  accTitle: Le parcours d'un email envoyé par Mailer::send()
  accDescr: Sans bus Messenger, Mailer::send() appelle le transport tout de suite. Avec un bus, un premier MessageEvent marqué queued est dispatché, et s'il est rejeté rien n'est envoyé. Sinon un SendEmailMessage part sur le bus. Routé vers un transport asynchrone, il attend un worker. Non routé, son handler appelle le transport pendant dispatch(). Le transport dispatche à son tour MessageEvent. Rejeté, il rend null sans exception. Sinon il envoie, puis dispatche SentMessageEvent et rend un SentMessage, ou FailedMessageEvent et relance l'erreur.
  SEND["Mailer::send()"] --> BUS{"bus Messenger ?"}
  BUS -->|"non"| T["TransportInterface::send()"]
  BUS -->|"oui"| Q{"MessageEvent, queued : rejeté ?"}
  Q -->|"oui"| STOP(["rien n'est envoyé"])
  Q -->|"non"| ROUTE{"SendEmailMessage routé vers un transport async ?"}
  ROUTE -->|"oui"| QUEUE(["en file : un worker l'enverra"])
  ROUTE -->|"non"| T
  QUEUE -.->|"worker"| T
  T --> ME{"MessageEvent : rejeté ?"}
  ME -->|"oui"| NULL(["rend null, sans exception"])
  ME -->|"non"| DO{"envoi accepté ?"}
  DO -->|"oui"| OK["SentMessageEvent, rend un SentMessage"]
  DO -->|"non"| KO["FailedMessageEvent, l'erreur remonte"]
```

## L'exemple : envoyer depuis un controller

```php
public function sendEmail(MailerInterface $mailer): Response
{
    $email = new Email()->from('hello@example.com')->to('you@example.com')
        ->subject('Time for Symfony Mailer!')->text('Sending emails is fun again!');
    $mailer->send($email);   // ne rend rien
    // ...
}
```

```env
MAILER_DSN=smtp://user:pass@smtp.example.com:port
```

## Le transport

| DSN | Comportement |
|---|---|
| `smtp` | un serveur SMTP |
| `sendmail` | le binaire `sendmail` local |
| `native` | le binaire et les options de `php.ini` — **déconseillé** |

Avec `native://default` et `sendmail -t` dans `php.ini` : pas de remontée
d'erreur, `Bcc` non retirés. Un caractère réservé d'URI dans le DSN doit être
encodé. Les bridges vers des services tiers sont hors périmètre.

## Envoyer

Avec Messenger installé, la documentation annonce un envoi **asynchrone par
défaut** ; le code en fixe la condition : `send()` distribue un
`SendEmailMessage`, différé seulement s'il est **routé** vers un transport
asynchrone. Exécuté : sans routage, l'email part pendant `send()` ; routé
vers un transport `in-memory://`, il y reste en attente.

## Ce que rend l'envoi

| Appel | Valeur rendue |
|---|---|
| `MailerInterface::send($email)` | rien |
| `$transport->send($email)` | un `SentMessage` : `getOriginalMessage()`, `getDebug()` ; toujours synchrone |
| `$transport->send()` d'un message rejeté par `MessageEvent::reject()` | `null` — aucune exception |

## Quand l'envoi échoue

Le succès signifie que le transport a accepté le message ; la suite, chez le
provider, échappe à l'application. Une erreur de remise au transport lève une
`TransportExceptionInterface`.

## Plusieurs transports

`transports` remplace `dsn` ; **le premier déclaré** sert par défaut. Le header
`X-Transport` en désigne un autre, et Mailer le retire du message envoyé comme
de l'object `Email` d'origine.

`framework.mailer.envelope` fixe `sender` et `recipients` pour tous les envois,
`framework.mailer.headers` des headers communs, `From` compris. Trois events :
`MessageEvent` avant l'envoi (modifier, ou `reject()`, qui arrête aussi la
propagation), `SentMessageEvent` après un succès, `FailedMessageEvent` après un
échec, avec `getError()`.

## Pièges d'examen

**Avec Messenger installé, l'envoi est asynchrone par défaut** selon la
documentation, à condition que `SendEmailMessage` soit routé vers un transport
asynchrone ; sans routage, il part pendant `send()`.

**`MailerInterface::send()` ne rend rien** ; c'est `TransportInterface` qui rend
un `SentMessage`, et lui envoie toujours de façon synchrone.

**Le premier transport déclaré est celui par défaut** ; `X-Transport` choisit.

**`native://default` est déconseillé** : ni erreurs ni retrait des `Bcc`.

**Un message rejeté par `reject()` ne lève rien** : le transport rend `null`.

## Application

- Un `From` commun à tous les emails → `framework.mailer.headers`.
- Ajouter un header à chaque message juste avant l'envoi → un listener de
  `MessageEvent`.

## QCM d'entraînement

**Question 1.** Un listener doit ajouter un header à chaque email juste avant
son envoi. Quel event écoute-t-il ?

- **A.** `SentMessageEvent`
- **B.** `MessageEvent`
- **C.** `FailedMessageEvent`
- **D.** `kernel.response`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `MessageEvent` arrive avant l'envoi, avec le message
et l'Envelope modifiables ; `SentMessageEvent` arrive trop tard.

</details>

**Question 2.** Un premier listener de `MessageEvent` appelle `reject()`. Un
second listener du même event, de priority plus basse, tourne-t-il ?

- **A.** Oui : `reject()` ne touche que l'envoi
- **B.** Oui, mais il ne peut plus modifier le message
- **C.** Non : une exception interrompt le dispatch
- **D.** Non : `reject()` arrête aussi la propagation

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Rejeter le message arrête la propagation de l'event ;
le transport rend ensuite `null`, sans lever d'exception.

</details>

**Question 3.** Tous les emails de l'application doivent porter le même
header `From`, posé une fois. Où ?

- **A.** `framework.mailer.headers`
- **B.** `framework.mailer.envelope.recipients`
- **C.** Le DSN, dans `MAILER_DSN`
- **D.** Un transport `native://default`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `headers` fixe des headers communs à tous les
messages ; `envelope`, lui, règle le sender et les destinataires SMTP.

</details>

**Question 4.** Après `$sent = $transport->send($email)`, comment retrouver
l'`Email` envoyé ?

- **A.** `$sent->getEmail()`
- **B.** `$sent->getEnvelope()->getMessage()`
- **C.** `$sent->getOriginalMessage()`
- **D.** Impossible : le message est consommé

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `SentMessage` garde le message d'origine et des
informations de debug, que `MailerInterface::send()` ne rend pas.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Transports intégrés : **`smtp`**, **`sendmail`**, `native` (déconseillé).
- `MailerInterface::send()` ne rend **rien** ; `TransportInterface::send()`
  rend un **`SentMessage`**, synchrone.
- Asynchrone seulement si `SendEmailMessage` est **routé**.
- Succès = **accepté par le transport** ; échec : `TransportExceptionInterface`.
- Premier transport par défaut ; **`X-Transport`** choisit, puis disparaît.
- `MessageEvent` (et `reject()`), `SentMessageEvent`, `FailedMessageEvent`.

Moyen mnémotechnique : **« Accepté n'est pas remis »**.

## Sources officielles

- [`mailer.rst`, branche 8.0](https://github.com/symfony/symfony-docs/blob/8.0/mailer.rst)
- [`Transports`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Mailer/Transport/Transports.php)

