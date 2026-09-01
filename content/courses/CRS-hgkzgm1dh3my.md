---
id: CRS-hgkzgm1dh3my
official_item: OIT-76x1t7z916f4
title: "Retries and failures"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "retry_strategy, Unrecoverable and Recoverable exceptions, failure_transport, messenger:failed"
    verified_at: "2026-09-01"
---

## Objectif

Décider ce qui arrive à un message dont le handler a levé une exception : le
rejouer, l'abandonner, ou le mettre de côté. Trois mécanismes se superposent, et
c'est leur ordre qui décide.

## Prérequis

Les transports et les workers.

## Le comportement par défaut

Une exception dans un handler **n'est pas fatale** : le message est remis dans le
transport et rejoué plus tard. C'est voulu — la plupart des échecs sont
temporaires : base indisponible, API en timeout.

Le nombre de tentatives est fini. Sans configuration de repli, un message qui a
épuisé ses tentatives est **abandonné**.

## La stratégie de réessai

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
```

Le délai croît **exponentiellement** : c'est ce qui évite de marteler un service
déjà en difficulté. `max_delay` plafonne la progression, et un facteur
d'aléatoire disperse les réessais pour que mille messages ne repartent pas tous
à la même seconde.

`max_retries: 3` signifie **trois réessais après l'échec initial**, donc quatre
tentatives en tout.

## Court-circuiter la stratégie

Deux exceptions changent la décision, dans les deux sens :

| Exception | Effet |
|---|---|
| `UnrecoverableMessageHandlingException` | **aucun réessai** : le message part directement en échec |
| `RecoverableMessageHandlingException` | réessai **forcé**, et son constructeur accepte un délai explicite |

La première est la plus utile. Un message invalide — un identifiant qui
n'existera jamais — ne deviendra pas valide en le rejouant : le rejouer trois
fois ne fait que retarder l'inévitable et polluer les journaux.

C'est la distinction de fond : **la stratégie traite l'échec temporaire, ces
exceptions traitent l'échec permanent**.

## Le transport d'échec

```yaml
framework:
    messenger:
        failure_transport: failed
        transports:
            failed: 'doctrine://default?queue_name=failed'
```

Sans lui, un message épuisé est **perdu**. Avec lui, il est déplacé dans une file
dédiée, où il attend une décision humaine :

```bash
php bin/console messenger:failed:show
php bin/console messenger:failed:show --stats
php bin/console messenger:failed:retry
php bin/console messenger:failed:remove <id>
```

`messenger:failed:retry` remet le message dans son transport d'origine, avec un
compteur de tentatives remis à zéro — c'est ce qui rend le mécanisme utile après
un correctif.

## L'enchaînement complet

```text
handler lève
  → Unrecoverable ?          oui → échec immédiat
  → tentatives restantes ?   oui → réessai après délai croissant
  → failure_transport ?      oui → file d'échec, en attente humaine
                             non → message perdu
```

Chaque étage change la réponse, et c'est l'ordre qui compte : une
`UnrecoverableMessageHandlingException` saute la stratégie mais **pas** le
transport d'échec.

## Pièges d'examen

- Une exception ne perd pas le message : elle déclenche un réessai.
- `max_retries: 3` = **quatre** tentatives au total.
- Sans `failure_transport`, un message épuisé est **définitivement perdu**.
- `UnrecoverableMessageHandlingException` saute les réessais, pas la file d'échec.
- Le délai est **exponentiel**, pas constant ; `max_delay` le plafonne.
- `messenger:failed:retry` remet le compteur à zéro.

## Points clés

- Échec = réessai, jusqu'à `max_retries`, avec un délai croissant.
- `Unrecoverable` court-circuite les réessais ; `Recoverable` les impose.
- `failure_transport` est ce qui distingue « mis de côté » de « perdu ».
- `messenger:failed:*` inspecte, rejoue et supprime.

## Sources officielles

- [Messenger, « Retries & Failures »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst)
