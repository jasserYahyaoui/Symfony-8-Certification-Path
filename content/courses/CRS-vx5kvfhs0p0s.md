---
id: CRS-vx5kvfhs0p0s
official_item: OIT-3r7rp470754w
title: "Debugging variables"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    anchor: "debugging-variables"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Inspecter le contenu d'une variable dans un gabarit, et savoir où le résultat
apparaît.

## Deux formes, deux destinations

C'est la distinction qui compte :

| Écriture | Où va le résultat |
|---|---|
| `{{ dump(article) }}` | **dans la page**, visible à l'endroit de l'appel |
| `{% dump articles %}` | dans la **barre de débogage**, pas dans la page |

La balise est donc la bonne quand on inspecte une variable dans une mise en page
qu'on ne veut pas casser.

`dump()` accepte des arguments nommés, qui deviennent des étiquettes :
`{{ dump(articles: articles, user: app.user) }}`.

Sans argument, `{{ dump() }}` affiche **tout le contexte** du gabarit.

## La restriction

`dump()` n'est disponible que dans les environnements **`dev` et `test`**. Dans
`prod`, l'appeler produit une erreur PHP — la fonction n'existe pas. C'est
délibéré : un `dump()` oublié divulguerait l'état interne de l'application.

Elle vient du composant VarDumper, installé par le paquet de débogage.

## Les commandes

Deux commandes complètent le tableau, hors gabarit :

- `php bin/console debug:twig` — filtres, fonctions, tests, globales et chemins
  réellement disponibles ;
- `php bin/console lint:twig templates/` — vérifie la syntaxe sans rendre.

## Pièges d'examen

**La fonction et la balise n'écrivent pas au même endroit.** L'une affiche dans
la page à l'endroit de l'appel, l'autre dans la barre de débogage. Choisir la
mauvaise casse la mise en page ou fait croire que rien ne s'affiche.

**Rien n'est disponible en production.** La fonction n'existe pas hors des
environnements de développement et de test : un appel oublié n'y affiche pas un
avertissement, il produit une erreur PHP.

**Sans argument, c'est tout le contexte du gabarit qui sort** — utile, et
volumineux.

## Points clés

- `{{ dump(x) }}` affiche dans la page ; `{% dump x %}` envoie à la barre de
  débogage.
- `{{ dump() }}` sans argument affiche tout le contexte.
- Disponible en `dev` et `test` seulement ; erreur PHP en `prod`.
- `debug:twig` inventorie, `lint:twig` valide la syntaxe.

## Sources officielles

- [Symfony Templates, « Debugging Variables »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst)
