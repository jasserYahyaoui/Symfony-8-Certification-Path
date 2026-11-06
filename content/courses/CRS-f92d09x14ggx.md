---
id: CRS-f92d09x14ggx
official_item: OIT-e2zrm9qkpx7j
title: "Conditional request matching"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "matching-expressions"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherDumper.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherDumper.php"
    symbol_or_lines: "compileRoute"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherTrait.php"
    symbol_or_lines: "doMatch"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/services.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/services.php"
    symbol_or_lines: "container.getenv, container.get_routing_condition_service"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Routing/Attribute/AsRoutingConditionService.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Routing/Attribute/AsRoutingConditionService.php"
    symbol_or_lines: "AsRoutingConditionService"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-s9bajh1gydjw: "L'option `condition`"
  OUT-er1yxf3bmwn9: "Les variables disponibles"
  OUT-tgxzpmwjg951: "Les variables disponibles"
  OUT-413aqdhzwkfw: "La limite, et elle est importante"
---

## Objectif

Conditionner l'appariement d'une route à une logique arbitraire, et connaître la
limite de ce mécanisme.

## L'essentiel

L'option `condition` prend une expression **ExpressionLanguage**, compilée une
fois en PHP dans le cache du router, puis exécutée à chaque Request qui atteint
la route. Elle voit `context`, `request` et `params`, et appelle `env()` ou
`service()`. Fausse, elle **efface** la route : ni 405, ni redirect. Et elle est
**ignorée à la génération** d'URL.

## Le schéma

```text
condition: "…"   ExpressionLanguage::compile() une fois → PHP dans le matcher en cache
variables        context (RequestContext) · request (Request) · params (parameters appariés)
functions        env('NOM') · service('alias') ← #[AsRoutingConditionService] / tag routing.condition_service
                 Request construite seulement si le PHP compilé mentionne $request
condition fausse → route ignorée AVANT barre finale, schéma, method : pas de 405, pas de redirect
génération       condition jamais évaluée → URL produite quand même
```

## L'exemple

```php
#[Route(
    '/contact',
    name: 'contact',
    condition: "context.getMethod() in ['GET', 'HEAD'] and request.headers.get('User-Agent') matches '/firefox/i'",
)]
```

## L'option `condition`

L'expression est évaluée à l'appariement ; fausse, la route ne correspond pas.
C'est la porte de sortie pour ce que `methods`, `host` et `schemes` ne savent
pas exprimer.

`CompiledUrlMatcherDumper` la **compile** en PHP brut quand il écrit le matcher
en cache, par `ExpressionLanguage::compile()` avec les noms `context`,
`request` et `params`. Une condition ne coûte donc que l'exécution de ce PHP. Si
le code compilé ne mentionne pas `$request`, le matcher ne construit pas
d'object `Request`.

## Les variables disponibles

| Variable | Contenu |
|---|---|
| `context` | le `RequestContext` : method, host, schéma, port |
| `request` | l'object `Request` complet |
| `params` | les parameters de route déjà appariés : `"params['id'] < 1000"` |

FrameworkBundle ajoute deux functions : `env('NOM')` lit une variable
d'environment, avec ses processeurs ; `service('alias')` appelle un service
marqué `#[AsRoutingConditionService]` ou tagué `routing.condition_service` —
sans alias, par son identifier. `service()` passe par un locator de ces seuls
services : une condition n'atteint pas le reste du container. Un parameter de
configuration s'écrit entre pourcents.

## Une condition fausse efface la route

Dans le matcher compilé, la condition est testée **avant** la barre finale, le
schéma et la method. Une route dont la condition est fausse n'ajoute pas ses
methods à la liste d'un **405**, et ne déclenche aucun redirect de barre finale
ou de schéma. Si rien d'autre ne correspond : 404.

## La limite, et elle est importante

> Les conditions **ne sont pas prises en compte lors de la génération d'URL**.

Le generator ne connaît ni la Request ni le user agent : une route protégée par
une `condition` reste générable, y compris vers un contexte où elle ne
correspondrait pas. Une condition filtre l'entrée ; un requirement, lui, vaut
dans les deux sens.

## Pièges d'examen

**Une condition ne s'applique pas à la génération d'URL.** Une route protégée
par une condition reste parfaitement générable, y compris vers un contexte où
elle ne correspondrait pas.

**Une condition filtre l'entrée ; elle ne documente pas l'output.** C'est toute
la différence avec un requirement de parameter, qui vaut dans les deux sens.

**L'expression est compilée en PHP, pas interprétée à chaque Request.** Elle est
compilée une fois dans le cache du router ; seul le PHP qui en résulte
s'exécute à chaque Request.

**Condition fausse : pas de 405.** La route disparaît avant le contrôle de
method.

## Application

- Activer une route selon un feature flag → `condition: "env('FEATURE_X') == '1'"`.
- Une règle métier complexe → un service `#[AsRoutingConditionService]` appelé
  par `service()`.

## QCM d'entraînement

**Question 1.** Une condition n'utilise que `context.getMethod()`. Le matcher
construit-il un object `Request` pour l'évaluer ?

- **A.** Non
- **B.** Oui, toujours
- **C.** Oui, une fois par Request
- **D.** Seulement en mode debug

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Le dumper repère si le PHP compilé mentionne `$request` ;
sinon, la condition est évaluée sans créer d'object Request.

</details>

**Question 2.** Quand l'expression d'une condition est-elle transformée en
PHP ?

- **A.** À chaque Request, avant l'appariement
- **B.** Une fois, à l'écriture du matcher en cache
- **C.** À la génération d'URL
- **D.** Au premier appel de `service()` dans la condition, puis gardée en mémoire

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le dumper compile l'expression en PHP brut dans le
matcher mis en cache ; seul ce PHP s'exécute ensuite à chaque Request.

</details>

**Question 3.** La route `/order/{n}` est réservée à `POST` et porte une
condition vraie pour tout `n` positif. Un form envoie un `GET` vers
`/order/3`. Quel statut le client obtient-il ?

- **A.** 404 : la condition masque la route
- **B.** 400
- **C.** 405
- **D.** 200 : la condition l'emporte

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La condition est vraie pour `3` : la route reste
candidate, et c'est le contrôle de method qui produit le refus.

</details>

**Question 4.** La route `/blog/` a une condition fausse pour la Request. Le
client appelle `/blog`, sans barre finale. Que reçoit-il ?

- **A.** Un redirect vers `/blog/`
- **B.** Un 405
- **C.** Un 301 vers `https://…/blog/`
- **D.** Un 404

<details>
<summary>Correction</summary>

**Bonne réponse : D.** La condition est testée avant la barre finale : la route
est ignorée, et aucun redirect n'est proposé à sa place.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- `condition` = **ExpressionLanguage**, compilée **une fois** en PHP.
- Variables : **`context`**, **`request`**, **`params`**.
- Functions : **`env()`**, **`service()`** (`#[AsRoutingConditionService]`).
- Fausse → route **effacée** : pas de 405, pas de redirect, 404.
- Vraie → les contrôles suivants (method, schéma) s'appliquent.
- **Ignorée** à la génération d'URL.

Moyen mnémotechnique : **« La condition filtre l'arrivée, jamais la génération »**.

## Sources officielles

- [Routing, section « Matching Expressions »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`CompiledUrlMatcherDumper::compileRoute()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherDumper.php) et [`CompiledUrlMatcherTrait::doMatch()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherTrait.php)
- [FrameworkBundle, `services.php`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/services.php) et [`AsRoutingConditionService`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Routing/Attribute/AsRoutingConditionService.php)

