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
outcome_sections:
  OUT-2m2fsf0992k6: "Le mécanisme"
  OUT-0b65p7477ebz: "Hors d'un block : une erreur, pas un oubli silencieux"
  OUT-5gpp6vwt10fk: "Reprendre, réafficher, tester un block"
  OUT-qbyeeh719t3d: "Réutilisation horizontale"
---

## Objectif

Construire une hiérarchie de templates, savoir ce que l'inheritance autorise, et
distinguer l'inheritance de la réutilisation horizontale.

## Le mécanisme

Un template parent déclare des **blocks** ; un template enfant `extends` le parent
et redéfinit ceux qui l'intéressent.

```html
{% extends 'base.html.twig' %}

{% block title %}Articles{% endblock %}

{% block body %}
    <h1>Derniers articles</h1>
{% endblock %}
```

Un block non redéfini garde le contenu du parent. Un enfant n'affiche **que** ses
blocks.

## Hors d'un block : une erreur, pas un oubli silencieux

Dans un template qui étend un parent, du texte ou un `{{ … }}` écrit hors de tout
block **n'est pas ignoré** : le compilateur de Twig 3.22 lève une
`Twig\Error\SyntaxError` : `A template that extends another one cannot include
content outside Twig blocks` (`Parser::filterBodyNodes()`, fixture de test
`child_contents_outside_blocks.test`). Seuls les blancs sont tolérés.

Les tags qui n'affichent rien, comme `{% set %}`, restent permises hors block.

## Les règles de l'inheritance

**Un seul inheritance.** Un template ne peut étendre qu'un seul parent : une seconde
tag lève « Multiple extends tags are forbidden. » Placée dans un block ou une
macro, `extends` est refusée elle aussi.

**`extends` en tête.** La documentation demande qu'elle soit la première tag
du template.

**Un nom de block, une seule fois par template.** Un block est à la fois un trou à
remplir et le contenu qui le remplit : deux blocks de même nom rendraient
l'inheritance ambigu.

Rien n'empêche en revanche d'empiler les niveaux — `base` → `layout de section`
→ `page` — et c'est le motif habituel.

## L'inheritance dynamique

Le parent est une expression :

- `{% extends some_var %}` — une variable, éventuellement un object
  `TemplateWrapper` ;
- `{% extends ['layout.html.twig', 'base.html.twig'] %}` — le **premier
  template qui existe** est retenu ;
- `{% extends standalone ? 'minimum.html.twig' : 'base.html.twig' %}` —
  inheritance conditionnel.

## Reprendre, réafficher, tester un block

`{{ parent() }}` insère le contenu que le block avait dans le parent, ce qui
permet d'ajouter au lieu de remplacer :

```html
{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="page.css">
{% endblock %}
```

Un block ne s'affiche qu'une fois par son tag ; pour le réafficher, la
**function** `block('title')` — qui peut aussi lire un block d'un autre template,
`block('title', 'common.html.twig')`. `block('footer') is defined` teste son
existence. Deux formes utiles : le nom répété après `endblock`, et le
raccourci `{% block title page_title|title %}`.

**Un block ne rend pas conditionnel ce qui l'entoure.** Placé dans un `if`, il
reste défini : seul son affichage suit la condition. Pour conditionner le
contenu, on met le `if` **dans** le block. Dans un enfant, un block imbriqué sous
un `if` lève d'ailleurs « A block definition cannot be nested under
non-capturing nodes ».

## Réutilisation horizontale

`{% use 'blocks.html.twig' %}` **importe les blocks** d'un autre template sans
l'étendre ni les afficher. C'est la Response de Twig au besoin d'inheritance
multiple, sans sa complexité : on peut `use` plusieurs templates tout en n'en
`extends` qu'un. Un block du template courant l'emporte sur un block importé du
même nom ; `with sidebar as base_sidebar` renomme à l'import. La cible de `use`
ne peut pas être une expression.

La documentation la présente comme une fonctionnalité avancée, rarement
nécessaire dans un template ordinaire — mais c'est exactement pour cela qu'elle
est interrogeable.

## Pièges d'examen

**Du contenu hors block dans un enfant est une erreur de syntaxe**, pas un
contenu ignoré.

**Un seul parent**, mais un parent choisi dynamiquement : variable, liste dont
le premier existant gagne, ou ternaire.

**`block()` réaffiche, `parent()` reprend.** Le tag, lui, n'affiche qu'une
fois.

**Un block dans un `if` reste défini.** Seul son rendu dépend de la condition.

**Importer les blocks d'un autre template n'est pas en hériter.**

## Points clés

- `extends` + `block` ; un block non redéfini garde le contenu du parent.
- Hors block, dans un enfant : `SyntaxError`, blancs exceptés.
- Inheritance simple ; `extends` en tête ; un nom de block par template.
- Parent dynamique : variable, liste, ternaire.
- `parent()` ajoute ; `block()` réaffiche ; `is defined` teste.
- `{% use %}` importe des blocks — réutilisation horizontale, pas inheritance.

## Sources officielles

- [Twig 3.22, tag `extends`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/extends.rst)
- [Twig 3.22, tag `use`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/use.rst) et [function `block`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/block.rst)
- [Twig 3.22, `Parser`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Parser.php) et [fixture `child_contents_outside_blocks.test`](https://github.com/twigphp/Twig/blob/v3.22.0/tests/Fixtures/exceptions/child_contents_outside_blocks.test)
