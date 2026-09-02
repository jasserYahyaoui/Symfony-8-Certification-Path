---
id: CRS-h304h6ht87kc
official_item: OIT-j4vpn5bh9f27
title: "Mailer"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/mailer.rst"
    anchor: "transport-setup"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Envoyer un message, choisir son transport, et savoir ce que l'envoi rend — ou
ne rend pas.

## Périmètre

Le syllabus exclut les **ponts vers des services tiers** : leurs DSN et leur
configuration ne sont pas au programme. Seuls les transports intégrés le sont.

La composition du message — `Email`, adresses, parties MIME — appartient à
l'item *Mime*. L'envoi asynchrone repose sur Messenger (lot 11) : le bus, les
transports et le routage y sont enseignés et ne sont pas repris ici.

## Le transport

L'envoi passe toujours par un transport, décrit par un DSN :

```env
MAILER_DSN=smtp://user:pass@smtp.example.com:port
```

Trois protocoles intégrés :

| DSN | Comportement |
|---|---|
| `smtp` | passe par un serveur SMTP |
| `sendmail` | passe par le binaire `sendmail` local |
| `native` | passe par le binaire et les options réglés dans `php.ini` |

La documentation **déconseille fortement `native://default`** : la
configuration de `sendmail` échappe à l'application. Si `php.ini` utilise
`sendmail -t`, il n'y a **pas de remontée d'erreur** et les en-têtes `Bcc` ne
sont **pas retirés**.

Un caractère réservé d'URI dans l'identifiant, le mot de passe ou l'hôte doit
être encodé.

## Envoyer

```php
public function sendEmail(MailerInterface $mailer): Response
{
    $email = new Email()->from('hello@example.com')->to('you@example.com')
        ->subject('Time for Symfony Mailer!')->text('Sending emails is fun again!');

    $mailer->send($email);
}
```

**Le point à retenir** : si l'application dispose du composant Messenger,
les messages partent **asynchrones par défaut**.

## Ce que rend l'envoi

Deux interfaces, deux comportements :

- `MailerInterface::send()` **ne rend rien** — parce qu'il peut différer
  l'envoi ;
- `TransportInterface::send()` rend un `SentMessage` et envoie **toujours de
  façon synchrone**, même si Messenger est présent.

`SentMessage` donne accès au message d'origine (`getOriginalMessage()`) et à
des informations de débogage (`getDebug()`).

## Quand l'envoi échoue

Le succès signifie seulement que **le transport a accepté** le message ; ce qui
se passe ensuite chez le fournisseur échappe à l'application.

Une erreur de remise au transport lève une
`TransportExceptionInterface`.

## Plusieurs transports

`transports` remplace `dsn`. **Le premier déclaré sert par défaut** ; l'en-tête
`X-Transport` en désigne un autre, et Mailer le retire du message final.

## Configurer globalement

`framework.mailer.envelope` fixe `sender` et `recipients` pour tous les envois ;
`framework.mailer.headers` fixe des en-têtes communs, `From` compris.

## Les trois événements

- `MessageEvent` — avant l'envoi : modifier message et enveloppe, ajouter des
  tampons Messenger, ou appeler `reject()` pour **empêcher** l'envoi (ce qui
  arrête aussi la propagation) ;
- `SentMessageEvent` — après un envoi réussi ;
- `FailedMessageEvent` — après un échec, avec `getError()`.

## Pièges d'examen

**Avec Messenger installé, l'envoi est asynchrone par défaut.**

**`MailerInterface::send()` ne rend rien** ; c'est `TransportInterface` qui rend
un `SentMessage`, et lui envoie toujours de façon synchrone.

**Le premier transport déclaré est celui par défaut** ; `X-Transport` choisit.

**`native://default` est déconseillé** : ni erreurs ni retrait des `Bcc`.

## Points clés

- Transports intégrés : `smtp`, `sendmail`, `native`.
- Succès = accepté par le transport, pas remis au destinataire.
- Échec de remise au transport : `TransportExceptionInterface`.
- `X-Transport` sélectionne, puis disparaît du message.
- `MessageEvent::reject()` annule l'envoi.

## Sources officielles

- [`mailer.rst`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/mailer.rst)
