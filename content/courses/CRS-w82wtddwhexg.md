---
id: CRS-w82wtddwhexg
official_item: OIT-ns36thqnh2jk
title: "Auto escaping"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/escape.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/escape.rst"
    anchor: "escaping-strategies"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/autoescape.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/autoescape.rst"
    anchor: "autoescape"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/FileExtensionEscapingStrategy.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/FileExtensionEscapingStrategy.php"
    symbol_or_lines: "guess"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/raw.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/raw.rst"
    symbol_or_lines: "raw is the last filter applied"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php"
    symbol_or_lines: "autoescape name"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-43tnheqyaw2v: "Le défaut : la strategy dépend du nom du template"
  OUT-mhbzxr5470xc: "Le défaut : la strategy dépend du nom du template"
  OUT-ss6r3pwx0fjy: "Les cinq strategies"
  OUT-947gftrjzm6w: "Désactiver, avec parcimonie"
---

## Objectif

Savoir ce que Twig échappe par défaut, avec quelle strategy, et comment
choisir la bonne selon l'endroit du document.

## Le défaut : la strategy dépend du nom du template

L'auto-escaping est **actif** dans une application Symfony. Mais sa
strategy n'est pas fixe : TwigBundle (8.0) règle l'option `autoescape` de Twig
sur `'name'`, et Twig choisit alors la strategy d'après l'**extension** du
template, `.twig` retiré (`FileExtensionEscapingStrategy::guess()`, v3.22.0) :

| Template | Strategy |
|---|---|
| `page.html.twig`, et toute extension non listée | `html` |
| `data.js.twig`, `data.json.twig` | `js` |
| `style.css.twig` | `css` |
| `email.txt.twig` | **aucun escaping** |

Dans un template HTML, toute variable affichée par `{{ }}` est donc échappée sans
rien demander. Un template `.txt.twig` — un email en texte brut, par exemple —
affiche ses variables telles quelles.

L'escaping a lieu **à l'affichage**, pas à l'affectation :
`{% set x = y %}` ne transforme rien.

**Deux exceptions documentées.** Twig n'échappe pas les **expressions
statiques** : `{{ "<strong>world</strong>" }}` s'affiche en gras. Et les
functions qui retournent du template — les macros, `parent()` — retournent un
contenu considéré comme sûr.

## Les cinq strategies

Le contexte du document décide, et le mauvais choix laisse une faille ouverte :

| Strategy | Contexte visé |
|---|---|
| `html` | corps HTML, ou valeur d'attribute **entre guillemets** |
| `js` | string JavaScript ou JSON, encodée par séquences d'escaping |
| `css` | tout ce qui est inséré dans du CSS |
| `url` | un **sous-composant** d'URI ou un parameter, jamais une URI entière |
| `html_attr` | nom d'attribute, ou valeur d'attribute **sans guillemets** |

La distinction `html` / `html_attr` est celle qui se rate : dès que la valeur
n'est pas entre guillemets — `data-x={{ v }}` — `html` ne suffit plus. La
documentation recommande d'ailleurs de mettre la valeur entre guillemets et
d'employer `html`, `html_attr` étant moins performant.

Pour la strategy `html`, Twig utilise la function PHP `htmlspecialchars`. On
peut enregistrer d'autres strategies avec `EscaperRuntime::setEscaper()`
(Twig 3.10).

## Choisir explicitement

```html
{{ user.name|e }}          {# équivaut à |e('html') #}
{{ user.name|e('js') }}
{{ user.name|escape('url') }}
```

`e` est l'alias de `escape`.

Twig évite le **double escaping** quand la strategy du filter est celle de
l'auto-escaping — mais seulement si elle est écrite en dur.
`|escape(strategy)`, avec une variable, est échappé deux fois ; la
documentation conseille alors d'ajouter `|raw`.

## Désactiver, avec parcimonie

`{{ trusted|raw }}` affiche sans échapper — **à condition que `raw` soit le
dernier filter appliqué**. Le nom du filter est un avertissement : la valeur
doit être sûre *par construction*, pas parce qu'elle semble sûre.

Le tag `{% autoescape %}` change la strategy sur un block — sans argument,
c'est `html` —, et `{% autoescape false %}` la coupe :

```html
{% autoescape 'js' %}
    {{ value }}
{% endautoescape %}
```

## Pièges d'examen

**La strategy par défaut dépend de l'extension du template.** `.html.twig`
échappe en HTML, `.js.twig` en JavaScript, `.txt.twig` n'échappe pas.

**L'escaping a lieu à l'affichage, pas à l'affectation.** Ranger une valeur
dans une variable ne la transforme pas.

**La strategy HTML ne suffit pas dans un attribute sans guillemets.** C'est la
strategy dédiée aux attributes qu'il faut ; le mauvais choix laisse une faille
ouverte sans rien signaler.

**`raw` n'agit que s'il est le dernier filter.**

**La strategy d'URL vise un sous-composant, pas une URI entière.** L'appliquer
à une adresse complète la détruit.

## Points clés

- Escaping actif ; strategy choisie par l'extension du template
  (`autoescape: 'name'`) ; `.txt.twig` non échappé.
- Cinq strategies : `html`, `js`, `css`, `url`, `html_attr`.
- `html_attr` pour un attribute sans guillemets ; `url` pour un sous-composant.
- `e` est l'alias de `escape` ; `raw`, en dernier filter, désactive.
- `{% autoescape %}` change ou coupe la strategy sur un block.

## Sources officielles

- [Twig 3.22, filter `escape`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/escape.rst)
- [Twig 3.22, tag `autoescape`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/autoescape.rst) et [filter `raw`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/raw.rst)
- [Twig 3.22, `FileExtensionEscapingStrategy`](https://github.com/twigphp/Twig/blob/v3.22.0/src/FileExtensionEscapingStrategy.php)
- [TwigBundle, `TwigExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php)
