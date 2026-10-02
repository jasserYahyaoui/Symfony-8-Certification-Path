---
id: CRS-fppjrr6r23tk
official_item: OIT-kv7mbksn7m8v
title: "Error handling"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/error_pages.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller/error_pages.rst"
    anchor: "how-to-customize-error-pages"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/configuration/framework.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/configuration/framework.rst"
    branch: "8.0"
    symbol_or_lines: "exceptions; php_errors: throw"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/EventListener/ErrorListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/ErrorListener.php"
    branch: "8.0"
    symbol_or_lines: "logKernelException(); WithHttpStatus"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/ErrorHandler/Exception/FlattenException.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ErrorHandler/Exception/FlattenException.php"
    branch: "8.0"
    symbol_or_lines: "createFromThrowable(); RequestExceptionInterface"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/ErrorRenderer/TwigErrorRenderer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/ErrorRenderer/TwigErrorRenderer.php"
    branch: "8.0"
    symbol_or_lines: "findTemplate()"
    verified_at: "2026-10-02"
---

## Objectif

Savoir ce que Symfony fait d'une erreur, et à quel niveau intervenir selon ce
qu'on veut changer.

## Prérequis

Le cycle requête-réponse et l'événement `kernel.exception`.

## Tout est une exception

Le principe de départ : **toutes les erreurs sont traitées comme des
exceptions**, qu'il s'agisse d'un 404 ou d'une erreur fatale levée dans le code.

Les erreurs PHP non fatales — un avertissement, une notice — ne sont converties
en `ErrorException` que si l'option `framework.php_errors.throw` est vraie. Sa
valeur par défaut est `%kernel.debug%`. Exécuté sur une clé de tableau absente
(Symfony 8.0.15) :

| Mode | Résultat |
|---|---|
| debug | 500, `ErrorException` : *Warning: Undefined array key* |
| sans debug | 200 : l'avertissement est journalisé, le code continue |

De là découle un mécanisme unique, et donc un seul endroit à comprendre.

## Deux affichages, selon l'environnement

| Environnement | Ce que voit l'utilisateur |
|---|---|
| `dev` | la **page d'exception** : message, trace complète, journaux |
| `prod` | une **page d'erreur** minimale et générique |

La page de développement contient des informations internes sensibles ; elle
n'est jamais affichée en production. Ce n'est pas un réglage à activer, c'est le
comportement par défaut.

Conséquence pratique : en développement, on ne voit pas sa propre page d'erreur
personnalisée. Pour la prévisualiser, FrameworkBundle fournit des routes
chargées sous `when@dev`, préfixées par `/_error`.

## Le code de statut

La documentation 8.0 (*How to Customize Error Pages*) n'en donne qu'une source :
implémenter **`HttpExceptionInterface`**, dont `getStatusCode()` fournit le code ;
sinon **500**. Le code de la branche 8.0 en connaît davantage — `ErrorListener`
et `FlattenException`. Exécuté, une exception levée par contrôleur, avec et sans
debug :

| Exception levée | Statut |
|---|---|
| `RuntimeException` ordinaire | **500** |
| classe marquée `#[WithHttpStatus(404)]` | 404 |
| `BadRequestException` de HttpFoundation (`RequestExceptionInterface`) | 400 |
| classe déclarée sous `framework.exceptions` avec `status_code: 418` | 418 |
| `NotFoundHttpException` d'une route absente (`HttpExceptionInterface`) | 404 |

L'ordre, lu dans `ErrorListener` : la configuration `framework.exceptions`
d'abord — elle s'impose même à une `HttpExceptionInterface` ; puis l'attribut
`#[WithHttpStatus]`, ignoré si l'exception implémente déjà l'interface ; puis
`getStatusCode()` ; puis 400 pour une `RequestExceptionInterface` ; sinon 500.

La règle à retenir reste celle-ci : une exception métier ordinaire — une
`RuntimeException` — produit un 500, même si le développeur pensait à un 404.
**Le message n'est jamais lu.** Les raccourcis du contrôleur, comme
`createNotFoundException()`, existent pour lever une exception qui porte le bon
statut.

## Quatre niveaux d'intervention

La documentation les ordonne du plus léger au plus complet :

| Besoin | Intervention |
|---|---|
| changer l'apparence de la page | **surcharger les gabarits** d'erreur |
| changer une sortie non-HTML (JSON, XML) | écrire un **normaliseur** |
| changer la logique de génération | **surcharger le contrôleur d'erreur** |
| maîtriser entièrement le traitement | écouter **`kernel.exception`** |

Le principe : prendre le niveau le plus bas qui suffit. Écouter
`kernel.exception` pour changer une couleur est une réponse disproportionnée.

## Surcharger un gabarit

Le rendu passe par `TwigErrorRenderer`, qui choisit le fichier en deux temps :

1. un gabarit pour ce code précis — `error404.html.twig` ;
2. sinon, le gabarit générique — `error.html.twig`.

Sans aucun des deux, la page minimale par défaut s'affiche. En mode debug, les
gabarits sont ignorés : c'est la page d'exception. Exécuté sans debug, avec les
deux fichiers présents : un 404 rend `error404.html.twig`, un 500, un 400 et un
418 rendent `error.html.twig`.

Ils se placent dans `templates/bundles/TwigBundle/Exception/`. Le gabarit
reçoit `status_code` et `status_text`. La variable
`exception` est disponible : `{{ exception.message }}`, et
`{{ exception.traceAsString }}` — que l'on ne montre jamais à un utilisateur
final, la trace contenant des données sensibles.

## Un piège d'ordre

**La sécurité n'est pas disponible sur une page 404.** L'ordre de chargement du
routage et de la sécurité fait que l'utilisateur y apparaît déconnecté. Le
symptôme est déroutant : cela fonctionne en test et échoue en production.

## Pièges d'examen

**Une exception ordinaire donne 500**, quel que soit son message. Ce qui change
le statut : l'interface, l'attribut `#[WithHttpStatus]`, la configuration
`framework.exceptions`, ou une `RequestExceptionInterface` (400).

**Un avertissement PHP n'est une exception qu'en debug**, par défaut.

**La page d'exception détaillée n'existe qu'en `dev`.**

**Sa propre page d'erreur ne s'affiche pas en `dev`** : il faut les routes
`/_error`.

**`error<code>.html.twig` d'abord, `error.html.twig` ensuite.**

**Pas d'information de sécurité sur une 404.**

## Points clés

- Toute erreur est une exception, erreurs PHP comprises.
- `dev` montre la trace, `prod` une page générique — par défaut.
- Le statut vient de la configuration, de l'attribut, de l'interface ou d'une
  `RequestExceptionInterface` ; sinon 500.
- Quatre niveaux : gabarit, normaliseur, contrôleur d'erreur, `kernel.exception`.

## Sources officielles

- [How to Customize Error Pages](https://github.com/symfony/symfony-docs/blob/8.0/controller/error_pages.rst)
- [Framework configuration reference](https://github.com/symfony/symfony-docs/blob/8.0/reference/configuration/framework.rst)
- [`ErrorListener`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/ErrorListener.php)
- [`FlattenException`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ErrorHandler/Exception/FlattenException.php)
- [`TwigErrorRenderer`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/ErrorRenderer/TwigErrorRenderer.php)
