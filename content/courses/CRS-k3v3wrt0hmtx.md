---
id: CRS-k3v3wrt0hmtx
official_item: OIT-g3p8wdtww344
title: "Template inheritance"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/extends.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/extends.rst"
    anchor: "extends"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/use.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/use.rst"
    anchor: "use"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Parser.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Parser.php"
    symbol_or_lines: "filterBodyNodes, Multiple extends tags are forbidden"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/tests/Fixtures/exceptions/child_contents_outside_blocks.test"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/tests/Fixtures/exceptions/child_contents_outside_blocks.test"
    symbol_or_lines: "SyntaxError"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/functions/block.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/block.rst"
    symbol_or_lines: "block, is defined"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-09-24"
---

## Objectif

Construire une hiérarchie de gabarits, savoir ce que l'héritage autorise, et
distinguer l'héritage de la réutilisation horizontale.

## Le mécanisme

Un gabarit parent déclare des **blocs** ; un gabarit enfant `extends` le parent
et redéfinit ceux qui l'intéressent.

```html
{% extends 'base.html.twig' %}

{% block title %}Articles{% endblock %}

{% block body %}
    <h1>Derniers articles</h1>
{% endblock %}
```

Un bloc non redéfini garde le contenu du parent. Un enfant n'affiche **que** ses
blocs.

## Hors d'un bloc : une erreur, pas un oubli silencieux

Dans un gabarit qui étend un parent, du texte ou un `{{ … }}` écrit hors de tout
bloc **n'est pas ignoré** : le compilateur de Twig 3.22 lève une
`Twig\Error\SyntaxError` : `A template that extends another one cannot include
content outside Twig blocks` (`Parser::filterBodyNodes()`, fixture de test
`child_contents_outside_blocks.test`). Seuls les blancs sont tolérés.

Les balises qui n'affichent rien, comme `{% set %}`, restent permises hors bloc.

## Les règles de l'héritage

**Un seul héritage.** Un gabarit ne peut étendre qu'un seul parent : une seconde
balise lève « Multiple extends tags are forbidden. » Placée dans un bloc ou une
macro, `extends` est refusée elle aussi.

**`extends` en tête.** La documentation demande qu'elle soit la première balise
du gabarit.

**Un nom de bloc, une seule fois par gabarit.** Un bloc est à la fois un trou à
remplir et le contenu qui le remplit : deux blocs de même nom rendraient
l'héritage ambigu.

Rien n'empêche en revanche d'empiler les niveaux — `base` → `layout de section`
→ `page` — et c'est le motif habituel.

## L'héritage dynamique

Le parent est une expression :

- `{% extends some_var %}` — une variable, éventuellement un objet
  `TemplateWrapper` ;
- `{% extends ['layout.html.twig', 'base.html.twig'] %}` — le **premier
  gabarit qui existe** est retenu ;
- `{% extends standalone ? 'minimum.html.twig' : 'base.html.twig' %}` —
  héritage conditionnel.

## Reprendre, réafficher, tester un bloc

`{{ parent() }}` insère le contenu que le bloc avait dans le parent, ce qui
permet d'ajouter au lieu de remplacer :

```html
{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="page.css">
{% endblock %}
```

Un bloc ne s'affiche qu'une fois par sa balise ; pour le réafficher, la
**fonction** `block('title')` — qui peut aussi lire un bloc d'un autre gabarit,
`block('title', 'common.html.twig')`. `block('footer') is defined` teste son
existence. Deux formes utiles : le nom répété après `endblock`, et le
raccourci `{% block title page_title|title %}`.

**Un bloc ne rend pas conditionnel ce qui l'entoure.** Placé dans un `if`, il
reste défini : seul son affichage suit la condition. Pour conditionner le
contenu, on met le `if` **dans** le bloc. Dans un enfant, un bloc imbriqué sous
un `if` lève d'ailleurs « A block definition cannot be nested under
non-capturing nodes ».

## Réutilisation horizontale

`{% use 'blocks.html.twig' %}` **importe les blocs** d'un autre gabarit sans
l'étendre ni les afficher. C'est la réponse de Twig au besoin d'héritage
multiple, sans sa complexité : on peut `use` plusieurs gabarits tout en n'en
`extends` qu'un. Un bloc du gabarit courant l'emporte sur un bloc importé du
même nom ; `with sidebar as base_sidebar` renomme à l'import. La cible de `use`
ne peut pas être une expression.

La documentation la présente comme une fonctionnalité avancée, rarement
nécessaire dans un gabarit ordinaire — mais c'est exactement pour cela qu'elle
est interrogeable.

## Pièges d'examen

**Du contenu hors bloc dans un enfant est une erreur de syntaxe**, pas un
contenu ignoré.

**Un seul parent**, mais un parent choisi dynamiquement : variable, liste dont
le premier existant gagne, ou ternaire.

**`block()` réaffiche, `parent()` reprend.** La balise, elle, n'affiche qu'une
fois.

**Un bloc dans un `if` reste défini.** Seul son rendu dépend de la condition.

**Importer les blocs d'un autre gabarit n'est pas en hériter.**

## Points clés

- `extends` + `block` ; un bloc non redéfini garde le contenu du parent.
- Hors bloc, dans un enfant : `SyntaxError`, blancs exceptés.
- Héritage simple ; `extends` en tête ; un nom de bloc par gabarit.
- Parent dynamique : variable, liste, ternaire.
- `parent()` ajoute ; `block()` réaffiche ; `is defined` teste.
- `{% use %}` importe des blocs — réutilisation horizontale, pas héritage.

## Sources officielles

- [Twig 3.22, balise `extends`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/extends.rst)
- [Twig 3.22, balise `use`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/use.rst) et [fonction `block`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/block.rst)
- [Twig 3.22, `Parser`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Parser.php) et [fixture `child_contents_outside_blocks.test`](https://github.com/twigphp/Twig/blob/v3.22.0/tests/Fixtures/exceptions/child_contents_outside_blocks.test)
