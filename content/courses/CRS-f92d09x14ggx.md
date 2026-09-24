---
id: CRS-f92d09x14ggx
official_item: OIT-e2zrm9qkpx7j
title: "Conditional request matching"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
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
---

## Objectif

Conditionner l'appariement d'une route à une logique arbitraire, et connaître la
limite de ce mécanisme.

## L'option `condition`

Elle prend une expression du composant ExpressionLanguage, évaluée à
l'appariement. Si elle est fausse, la route ne correspond pas.

```php
#[Route(
    '/contact',
    name: 'contact',
    condition: "context.getMethod() in ['GET', 'HEAD'] and request.headers.get('User-Agent') matches '/firefox/i'",
)]
```

C'est la porte de sortie pour ce que `methods`, `host` et `schemes` ne savent
pas exprimer.

## Compilée une fois, évaluée à chaque requête

`CompiledUrlMatcherDumper` **compile** l'expression en PHP brut quand il écrit
le matcher en cache : `ExpressionLanguage::compile()` avec les noms `context`,
`request` et `params`. La documentation en tire la conséquence : une condition
ne coûte que le temps d'exécution de ce PHP. Le code compilé s'exécute ensuite
à chaque requête qui atteint la route.

Détail du dumper : si le PHP compilé ne mentionne pas `$request`, le matcher ne
construit pas d'objet `Request` pour évaluer la condition.

## Les variables disponibles

| Variable | Contenu |
|---|---|
| `context` | le `RequestContext` : méthode, hôte, schéma, port |
| `request` | l'objet `Request` complet |
| `params` | les paramètres de route déjà appariés |

`params` permet de conditionner sur une valeur d'URL :
`condition: "params['id'] < 1000"`.

Deux fonctions complètent le jeu, fournies par FrameworkBundle :

- `env('NOM')` lit une variable d'environnement, avec ses processeurs ;
- `service('alias')` appelle un service déclaré par l'attribut
  `#[AsRoutingConditionService]` ou par le tag `routing.condition_service`.
  Sans alias, on le désigne par son identifiant, le nom de sa classe.

`service()` ne passe que par un localisateur des services ainsi étiquetés : une
condition n'atteint pas le reste du conteneur. Une expression peut aussi
contenir un paramètre de configuration, entre pourcents.

## Une condition fausse efface la route

Dans le matcher compilé, la condition est testée **avant** la barre finale, le
schéma et la méthode. Une route dont la condition est fausse est ignorée comme
si elle n'existait pas :

- elle n'ajoute pas ses méthodes à la liste qui produirait un **405** ;
- elle ne déclenche aucune redirection de barre finale ou de schéma.

Si aucune autre route ne correspond, la réponse est un 404.

## La limite, et elle est importante

> Les conditions **ne sont pas prises en compte lors de la génération d'URL**.

Le générateur ne connaît ni la requête courante ni l'agent utilisateur : il ne
peut pas évaluer l'expression. Une route protégée par une `condition` reste donc
parfaitement générable, y compris vers un contexte où elle ne correspondrait
pas. Une condition filtre l'entrée, elle ne documente pas la sortie.

C'est la différence de fond avec `requirements`, qui compte dans les deux sens.

## Pièges d'examen

**Une condition ne s'applique pas à la génération d'URL.** Une route protégée
par une condition reste parfaitement générable, y compris vers un contexte où
elle ne correspondrait pas.

**Une condition filtre l'entrée ; elle ne documente pas la sortie.** C'est toute
la différence avec une contrainte de paramètre, qui vaut dans les deux sens.

**L'expression est compilée en PHP, pas interprétée à chaque requête.** Elle est
compilée une fois dans le cache du routeur ; seul le PHP qui en résulte
s'exécute à chaque requête.

**Condition fausse : pas de 405.** La route disparaît avant le contrôle de
méthode.

## Points clés

- `condition` = expression ExpressionLanguage compilée en PHP, exécutée à
  l'appariement.
- Variables : `context`, `request`, `params`. Fonctions : `env()`, `service()`.
- `#[AsRoutingConditionService]` expose un service à `service()`.
- Une condition fausse retire la route avant le contrôle de méthode.
- Les conditions sont **ignorées** à la génération d'URL.

## Sources officielles

- [Routing, section « Matching Expressions »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`CompiledUrlMatcherDumper::compileRoute()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherDumper.php) et [`CompiledUrlMatcherTrait::doMatch()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/Dumper/CompiledUrlMatcherTrait.php)
- [FrameworkBundle, `services.php`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/services.php) et [`AsRoutingConditionService`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Routing/Attribute/AsRoutingConditionService.php)
