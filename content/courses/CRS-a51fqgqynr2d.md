---
id: CRS-a51fqgqynr2d
official_item: OIT-e41m74xaqhy7
title: "The session"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
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
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/EventListener/AbstractSessionListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/AbstractSessionListener.php"
    branch: "8.0"
    symbol_or_lines: "onKernelResponse: cookie only for a non-empty session, private on usage"
    verified_at: "2026-10-08"
outcome_sections:
  OUT-wc78qqp1pj1p: "L'obtenir"
  OUT-vvctkweagzvz: "Le démarrage est lazy"
  OUT-srphtyf5qzqs: "Le démarrage est lazy"
  OUT-7t4egm4bdr19: "L'API"
---

## Objectif

Obtenir la session depuis un controller, connaître son API de base, et
comprendre quand elle démarre réellement.

## L'essentiel

La session s'obtient par la Request, par un argument typé `SessionInterface`
ou, dans un service, par `RequestStack`. Elle démarre **paresseusement** :
pas parce que la configuration l'active, mais au premier accès — lire, écrire
**ou tester**. `migrate()` change l'identifier en gardant les données ;
`invalidate()` vide le stockage, puis appelle `migrate(true)`.

## Le schéma

```text
accès        $request->getSession() · SessionInterface $session (SessionValueResolver) · RequestStack::getSession()
démarrage    has · get · set · all · replace · remove · clear  → getAttributeBag() → start()
             aucun accès → aucun cookie, page cachable
Response     session démarrée → private · cookie seulement si la session n'est pas vide
migrate($destroy = false, $lifetime = null): bool   nouvel id, données gardées, ancienne session laissée
invalidate($lifetime = null): bool                  storage->clear() puis migrate(true, $lifetime)
```

## L'exemple

```php
public function addToCart(SessionInterface $session, int $id): Response
{
    $cart = $session->get('cart', []);   // [] par défaut
    $cart[] = $id;
    $session->set('cart', $cart);
    // ...
}
```

## L'obtenir

| Contexte | Accès |
|---|---|
| controller, Request en main | `$request->getSession()` |
| controller, argument typé | `SessionInterface $session`, via `SessionValueResolver` |
| service | `RequestStack::getSession()` |

## Le démarrage est lazy

La documentation écrit que la session démarre quand on la lit ou l'écrit. Le
code va plus loin : sept methods de `Session` — `has()`, `get()`, `set()`,
`all()`, `replace()`, `remove()`, `clear()` — passent par `getAttributeBag()`,
qui démarre la session. Il n'y a donc **aucune consultation neutre** : `has()`
la démarre comme `get()`.

Une page qui ne touche jamais à la session n'émet aucun cookie et reste
cachable par un proxy partagé. Dès qu'un accès a lieu, fût-il un `has()`,
elle devient privée : `SessionListener` marque la Response `private`. Le cookie,
lui, ne part que si la session **n'est pas vide** — après un `has()` seul,
Response privée sans `Set-Cookie`. L'authentication et les flash messages la
démarrent indirectement.

## L'API

`set()`, `get($name, $default)`, `has()`, `remove()`, `all()`, `clear()`.
Pour la security, deux methods, l'une bâtie sur l'autre :

- **`migrate($destroy = false, $lifetime = null)`** régénère l'identifier et
  garde les données ; avec `destroy` à `false`, l'ancienne session **reste sur
  le serveur**. On l'appelle après une authentication réussie, contre la
  fixation de session.
- **`invalidate($lifetime = null)`** vide le stockage, *puis* appelle
  `migrate(true, $lifetime)`. C'est le vidage qui supprime les données, pas la
  régénération. On l'appelle à la déconnexion.

Les deux rendent un `bool`.

## Pièges d'examen

**Tester une valeur démarre la session, comme la lire.** Il n'existe pas de
consultation neutre : `has()` passe par le même accès au sac que `get()`. La
vérification suffit à rendre la page non cachable par un proxy partagé — sans
émettre de cookie tant que la session reste vide.

**Activer la session dans la configuration ne la démarre pas.** Le démarrage est
lazy ; une page qui n'y touche jamais n'émet aucun cookie de session.

**`invalidate()` n'est pas l'opposé de `migrate()`, elle l'utilise.** Elle vide
le stockage puis appelle `migrate(true, …)`. La suppression des données vient du
vidage, pas de la régénération.

**`migrate()` seule ne détruit pas l'ancienne session.** Son argument `$destroy`
vaut `false` par défaut : l'identifier change, mais la session précédente reste
sur le serveur. C'est `invalidate()` qui passe `true`.

**La documentation ne mentionne pas le test.** Elle parle de lire et d'écrire ;
c'est le code qui montre que tester compte aussi. Sur ce point précis, se fier
au texte seul donne une réponse fausse.

## Application

- Après un login réussi → `migrate()` : le panier reste, l'identifier change.
- Une page publique censée rester en cache → aucun appel à la session, pas même
  `has()`.

## QCM d'entraînement

**Question 1.** Après une connexion, le code appelle `$session->migrate()` sans
argument. Que devient l'ancienne session côté serveur ?

- **A.** Elle est détruite
- **B.** Elle reste sur le serveur
- **C.** Elle est vidée de ses données
- **D.** Elle est copiée sous le nouvel identifier, puis détruite

<details>
<summary>Correction</summary>

**Bonne réponse : B.** L'argument de destruction vaut `false` par défaut : seul
l'identifier change, et c'est `invalidate()` qui passe `true`.

</details>

**Question 2.** Dans `invalidate()`, quelle opération supprime les données de
la session ?

- **A.** La régénération de l'identifier
- **B.** La destruction du cookie côté client
- **C.** Le vidage du stockage
- **D.** Le garbage collector de PHP

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La method vide d'abord le stockage, puis régénère par
`migrate(true)` ; la régénération seule garderait les données.

</details>

**Question 3.** Quel resolver fournit l'argument `SessionInterface $session`
d'une action ?

- **A.** `SessionValueResolver`
- **B.** `RequestAttributeValueResolver`
- **C.** `ServiceValueResolver`
- **D.** L'autowiring du container

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Typer l'argument avec l'interface suffit : ce resolver
dédié rend la session de la Request courante.

</details>

**Question 4.** Une action publique, en cache 60 secondes, appelle
`$session->set('cart', [1])`. Que porte la Response ?

- **A.** Publique, cookie de session posé
- **B.** Publique, sans cookie de session
- **C.** Privée, sans cookie de session
- **D.** Privée, cookie de session posé

<details>
<summary>Correction</summary>

**Bonne réponse : D.** La session démarrée rend la Response privée, et comme
elle contient désormais une donnée, le cookie de session est envoyé.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Accès : Request, **`SessionInterface`** typé, **`RequestStack`** en service.
- Démarrage **lazy** : lire, écrire **ou tester** — `has()` compte.
- Pas d'accès → pas de cookie, page **cachable**.
- Accès → Response **private** ; cookie seulement si session **non vide**.
- `migrate()` : nouvel id, données gardées, `destroy` à **`false`**.
- `invalidate()` = **vider** puis `migrate(true)`.

Moyen mnémotechnique : **« Migrate garde, invalidate vide »**.

## Sources officielles

- [Sessions](https://github.com/symfony/symfony-docs/blob/8.0/session.rst)
- [Controller, « Managing the Session »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
- [`Session`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Session/Session.php)

