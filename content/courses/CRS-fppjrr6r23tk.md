---
id: CRS-fppjrr6r23tk
official_item: OIT-kv7mbksn7m8v
title: "Error handling"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/error_pages.rst"
    anchor: "how-to-customize-error-pages"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Savoir ce que Symfony fait d'une erreur, et à quel niveau intervenir selon ce
qu'on veut changer.

## Prérequis

Le cycle requête-réponse et l'événement `kernel.exception`.

## Tout est une exception

Le principe de départ : **toutes les erreurs sont traitées comme des
exceptions**, qu'il s'agisse d'un 404 ou d'une erreur fatale levée dans le code.
Les erreurs PHP elles-mêmes sont converties en exceptions.

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

Il vient de l'exception, et d'une seule façon : l'exception doit implémenter
**`HttpExceptionInterface`**, dont la méthode `getStatusCode()` le fournit.
Toute autre exception donne **500**.

C'est la règle centrale de l'item. Une exception métier ordinaire — une
`RuntimeException` — produit un 500, même si le développeur pensait à un 404.
Les raccourcis du contrôleur, comme `createNotFoundException()`, existent
précisément pour lever une exception qui porte le bon statut.

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

Ils se placent dans `templates/bundles/TwigBundle/Exception/`. La variable
`exception` est disponible : `{{ exception.message }}`, et
`{{ exception.traceAsString }}` — que l'on ne montre jamais à un utilisateur
final, la trace contenant des données sensibles.

## Un piège d'ordre

**La sécurité n'est pas disponible sur une page 404.** L'ordre de chargement du
routage et de la sécurité fait que l'utilisateur y apparaît déconnecté. Le
symptôme est déroutant : cela fonctionne en test et échoue en production.

## Pièges d'examen

**Une exception sans `HttpExceptionInterface` donne 500**, quel que soit son
sens métier.

**La page d'exception détaillée n'existe qu'en `dev`.**

**Sa propre page d'erreur ne s'affiche pas en `dev`** : il faut les routes
`/_error`.

**`error<code>.html.twig` d'abord, `error.html.twig` ensuite.**

**Pas d'information de sécurité sur une 404.**

## Points clés

- Toute erreur est une exception, erreurs PHP comprises.
- `dev` montre la trace, `prod` une page générique — par défaut.
- Le statut vient de `HttpExceptionInterface`, sinon 500.
- Quatre niveaux : gabarit, normaliseur, contrôleur d'erreur, `kernel.exception`.

## Sources officielles

- [How to Customize Error Pages](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/error_pages.rst)
