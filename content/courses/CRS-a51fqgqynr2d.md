---
id: CRS-a51fqgqynr2d
official_item: OIT-e41m74xaqhy7
title: "The session"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/session.rst"
    anchor: "sessions"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    anchor: "managing-the-session"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Obtenir la session depuis un contrôleur, connaître son API de base, et
comprendre quand elle démarre réellement.

## L'obtenir

Trois chemins, selon l'endroit :

| Contexte | Accès |
|---|---|
| contrôleur, requête en main | `$request->getSession()` |
| contrôleur, argument typé | `SessionInterface $session` |
| service | `RequestStack::getSession()` |

Le deuxième fonctionne grâce au `SessionValueResolver` : typer l'argument avec
`SessionInterface` suffit.

## Le démarrage est paresseux

C'est le point le plus interrogé. La session n'est **pas** démarrée parce que la
configuration l'active ; elle démarre lorsqu'on **lit, écrit ou même teste** une
valeur.

La conséquence est directe et voulue : une page qui ne touche jamais à la
session n'émet aucun cookie de session, et reste donc cachable par un proxy
partagé. Activer la session ne coûte rien tant que personne ne s'en sert. À
l'inverse, une simple vérification suffit à la démarrer — et à faire apparaître
le cookie.

Certaines fonctionnalités la démarrent indirectement, parce qu'elles s'en
servent : l'authentification et les messages flash, notamment.

## L'API

```php
$session->set('user_id', 42);
$userId = $session->get('user_id', 0);   // 0 est la valeur par défaut
$session->has('user_id');
$session->remove('user_id');
$session->all();
$session->clear();
```

Deux méthodes portent la sécurité et se confondent :

- `migrate()` : régénère l'identifiant de session en **conservant** les données.
  C'est ce qu'on appelle après une authentification réussie, contre la fixation
  de session.
- `invalidate()` : régénère l'identifiant **et supprime** les données. C'est la
  déconnexion.

## Points clés

- `$request->getSession()`, argument typé `SessionInterface`, ou `RequestStack`
  dans un service.
- Démarrage paresseux : lire, écrire **ou tester** démarre la session.
- Une page qui n'y touche pas n'émet pas de cookie de session.
- `migrate()` conserve les données, `invalidate()` les détruit.

## Sources officielles

- [Sessions](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/session.rst)
- [Controller, « Managing the Session »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst)
