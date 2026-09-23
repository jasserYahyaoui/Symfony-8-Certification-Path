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
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/session.rst"
    anchor: "sessions"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller.rst"
    anchor: "managing-the-session"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Session/Session.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Session/Session.php"
    symbol_or_lines: "has, get, set, getAttributeBag, invalidate, migrate"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-22"
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

La documentation est plus étroite que cela : elle écrit *« Sessions are only
started if you read from or write to them »*, sans mentionner le test. Le code
tranche. Dans `Session`, **sept méthodes passent par le même accès au sac
d'attributs** :

```php
public function has(string $name): bool
{
    return $this->getAttributeBag()->has($name);
}
```

`has()`, `get()`, `set()`, `all()`, `replace()`, `remove()` et `clear()` ont
toutes ce corps-là, à la méthode appelée près. Il n'y a donc **aucune
consultation neutre** : `has()` démarre la session exactement comme `get()`.

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

### `invalidate()` est bâtie sur `migrate()`

Les deux méthodes qui portent la sécurité ne sont pas deux mécanismes
parallèles. L'une appelle l'autre :

```php
public function invalidate(?int $lifetime = null): bool
{
    $this->storage->clear();

    return $this->migrate(true, $lifetime);
}

public function migrate(bool $destroy = false, ?int $lifetime = null): bool
```

Trois choses se lisent là, qu'un tableau de deux lignes ne dirait pas.

**`invalidate()` fait deux gestes, pas un.** Elle vide le stockage, *puis*
régénère. C'est ce `clear()` qui supprime les données, pas la régénération.

**`$destroy` vaut `false` par défaut.** Un `migrate()` nu régénère l'identifiant
mais **laisse l'ancienne session sur le serveur**. `invalidate()` passe `true`,
donc elle la détruit.

**Les deux acceptent une durée de vie et rendent un booléen.** La signature n'est
pas `void`.

En pratique : `migrate()` après une authentification réussie, contre la fixation
de session — on garde le panier, on change l'identifiant. `invalidate()` à la
déconnexion.

## Pièges d'examen

**Tester une valeur démarre la session, comme la lire.** Il n'existe pas de
consultation neutre : `has()` passe par le même accès au sac que `get()`. La
vérification suffit à émettre le cookie et à rendre la page non cachable par un
proxy partagé.

**Activer la session dans la configuration ne la démarre pas.** Le démarrage est
paresseux ; une page qui n'y touche jamais n'émet aucun cookie de session.

**`invalidate()` n'est pas l'opposé de `migrate()`, elle l'utilise.** Elle vide
le stockage puis appelle `migrate(true, …)`. La suppression des données vient du
vidage, pas de la régénération.

**`migrate()` seule ne détruit pas l'ancienne session.** Son argument `$destroy`
vaut `false` par défaut : l'identifiant change, mais la session précédente reste
sur le serveur. C'est `invalidate()` qui passe `true`.

**La documentation ne mentionne pas le test.** Elle parle de lire et d'écrire ;
c'est le code qui montre que tester compte aussi. Sur ce point précis, se fier
au texte seul donne une réponse fausse.

## Tips d'examen

**Une question pour trancher `migrate()` contre `invalidate()`** : veut-on
garder les données ? Oui → `migrate()`, c'est l'après-connexion. Non →
`invalidate()`, c'est la déconnexion.

**Pour savoir si une page émet un cookie de session** : y a-t-il un seul appel
au sac d'attributs, fût-il un `has()` ? Si oui, le cookie part.

## Points clés

- `$request->getSession()`, argument typé `SessionInterface`, ou `RequestStack`
  dans un service.
- Démarrage paresseux : lire, écrire **ou tester** démarre la session — sept
  méthodes passent par le même accès au sac.
- Une page qui n'y touche pas n'émet pas de cookie de session.
- `invalidate()` = `clear()` du stockage **puis** `migrate(true, …)`.
- `migrate()` laisse l'ancienne session en place par défaut : `$destroy` vaut
  `false`.

## Sources officielles

- [Sessions](https://github.com/symfony/symfony-docs/blob/8.0/session.rst)
- [Controller, « Managing the Session »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
- [`Session`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Session/Session.php)
