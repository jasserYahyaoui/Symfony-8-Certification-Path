---
id: CRS-w82wtddwhexg
official_item: OIT-ns36thqnh2jk
title: "Auto escaping"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/escape.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/escape.rst"
    anchor: "escaping-strategies"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/autoescape.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/autoescape.rst"
    anchor: "autoescape"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/FileExtensionEscapingStrategy.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/FileExtensionEscapingStrategy.php"
    symbol_or_lines: "guess"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/raw.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/raw.rst"
    symbol_or_lines: "raw is the last filter applied"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php"
    symbol_or_lines: "autoescape name"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Extension/CoreExtension.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Extension/CoreExtension.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    symbol_or_lines: "getFilters() — nl2br pre_escape html, is_safe html; json_encode not safe"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-43tnheqyaw2v: "Le défaut : la strategy dépend du nom du template"
  OUT-mhbzxr5470xc: "Le défaut : la strategy dépend du nom du template"
  OUT-ss6r3pwx0fjy: "Les cinq strategies"
  OUT-947gftrjzm6w: "Désactiver, avec parcimonie"
---

## Objectif

Savoir ce que Twig échappe par défaut, avec quelle strategy, et comment
choisir la bonne selon l'endroit du document.

## L'essentiel

Une variable affichée sans escaping peut injecter du script : c'est une faille
XSS. Twig échappe **d'office** ; la question est **avec quelle strategy** — elle
dépend du nom du template et de l'endroit du document — et **comment s'en
écarter** sans ouvrir de faille.

## Le schéma

```mermaid
---
title: Une expression affichée est-elle échappée, et comment
---
flowchart TD
  accTitle: Comment Twig décide d'échapper une expression affichée
  accDescr: Dans un block autoescape, la strategy du block s'applique, ou aucune avec false. Sinon la strategy vient de l'extension du template, avec autoescape name réglé par TwigBundle html pour html et les extensions inconnues, js pour js et json, css pour css, aucune pour txt. Une expression statique, une macro, parent() ou un filter marqué sûr, comme raw en dernier ou nl2br, ne sont pas échappés à nouveau. Un filter escape dont la strategy est écrite en dur et identique ne double pas l'escaping ; une strategy en variable l'échappe deux fois.
  OUT["{{ expression }}"] --> BLOCK{"dans un block autoescape ?"}
  BLOCK -->|"oui"| BS(["strategy du block, ou aucune avec false"])
  BLOCK -->|"non"| EXT{"extension du template ?"}
  EXT -->|".html, .xml, .md, autre"| H(["html"])
  EXT -->|".js, .json"| J(["js"])
  EXT -->|".css"| C(["css"])
  EXT -->|".txt"| N(["aucune"])
  H --> SAFE{"statique, macro, parent(), raw en dernier, filter sûr ?"}
  SAFE -->|"oui"| AS(["affiché tel quel"])
  SAFE -->|"non"| ESC(["échappé"])
```

## L'exemple

```twig
{# templates/user/profile.html.twig #}
<h1>{{ user.name }}</h1>
<div data-role={{ user.role|e('html_attr') }}></div>
<a href="/search?q={{ user.city|e('url') }}">Voisins</a>
<script>const name = "{{ user.name|e('js') }}";</script>
```

Le nom est échappé en HTML sans rien demander ; l'attribute sans guillemets, le
parameter d'URL et la string JavaScript demandent chacun leur strategy.

## Le défaut : la strategy dépend du nom du template

TwigBundle règle l'option `autoescape` sur `'name'` : la strategy dépend de
l'**extension** du template, `.twig` retiré.

| Template | Strategy |
|---|---|
| `page.html.twig`, et toute extension non listée (`.xml`, `.md`…) | `html` |
| `data.js.twig`, `data.json.twig` | `js` |
| `style.css.twig` | `css` |
| `email.txt.twig` | **aucun escaping** |

- L'escaping a lieu **à l'affichage**, pas à l'affectation : `{% set x = y %}`
  ne transforme rien.
- Les **expressions statiques** ne sont pas échappées : `{{ "<strong>x</strong>" }}`
  s'affiche en gras. Dès qu'une variable s'y mêle, **tout** est échappé :
  `{{ "<b>" ~ v }}` échappe aussi le `<b>`.
- Les macros et `parent()` retournent un contenu considéré comme sûr.

## Les cinq strategies

| Strategy | Contexte visé |
|---|---|
| `html` | corps HTML, ou valeur d'attribute **entre guillemets** |
| `js` | string JavaScript ou JSON |
| `css` | tout ce qui est inséré dans du CSS |
| `url` | un **sous-composant** d'URI ou un parameter, jamais une URI entière |
| `html_attr` | nom d'attribute, ou valeur d'attribute **sans guillemets** |

`html` utilise `htmlspecialchars`. On enregistre une strategy de plus avec
`EscaperRuntime::setEscaper()` (Twig 3.10).

```html
{{ user.name|e }}          {# équivaut à |e('html') #}
{{ user.name|escape('url') }}
```

`e` est l'alias de `escape`. Twig évite le **double escaping** quand la strategy
du filter, écrite en dur, est celle de l'auto-escaping ; `|escape(strategy)`
avec une **variable** échappe deux fois.

Deux filters à connaître : `json_encode` n'est pas déclaré sûr, donc échappé en
HTML (`&quot;`) ; `nl2br` échappe son entrée puis ajoute des `<br />` intacts.

## Désactiver, avec parcimonie

- `{{ trusted|raw }}` affiche sans échapper — **si `raw` est le dernier
  filter** : `v|raw|upper` est de nouveau échappé.
- `{% autoescape 'js' %}…{% endautoescape %}` change la strategy d'un block ;
  sans argument, c'est `html` ; `{% autoescape false %}` la coupe.

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

## Application

- `{{ "<b>" ~ title ~ "</b>" }}` affiche les balises en texte → écrire le HTML
  hors de l'expression : `<b>{{ title }}</b>`.
- Un email `.txt.twig` affiche `<` brut → c'est voulu : un `.txt` n'est pas
  échappé.

## QCM d'entraînement

**Question 1.** Dans une application Symfony, un template `feed.xml.twig`
affiche `{{ v }}`, où `v` vaut `<b>`. Que sort-il ?

- **A.** `<b>`, l'extension `.xml` n'étant pas listée
- **B.** `<b>`, comme en JavaScript
- **C.** `&lt;b&gt;`
- **D.** Une erreur : extension non prise en charge

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Avec `autoescape: 'name'`, une extension non listée,
comme `.xml`, reçoit la strategy `html` par défaut.

</details>

**Question 2.** Dans `page.html.twig`, que sort `{{ "<b>" ~ v }}` avec
`v = '<u>'` ?

- **A.** `<b><u>` : l'expression commence par une statique
- **B.** `&lt;b&gt;&lt;u&gt;`
- **C.** `<b>&lt;u&gt;` : seule la variable est échappée
- **D.** Une erreur : on ne concatène pas une statique

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Une concaténation avec une variable n'est plus statique :
le résultat entier est échappé.

</details>

**Question 3.** Dans `page.html.twig`, `data` vaut `{a: '<b>'}`. Que sort
`{{ data|json_encode }}` ?

- **A.** `{"a":"<b>"}`
- **B.** `{&quot;a&quot;:&quot;&lt;b&gt;&quot;}`
- **C.** `{"a":"<b>"}`
- **D.** Une erreur : `json_encode` attend une string

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `json_encode` n'est pas déclaré sûr : son output est
échappé en HTML.

</details>

**Question 4.** Que vaut la strategy de `{% autoescape %}…{% endautoescape %}`,
sans argument, dans un template `.js.twig` ?

- **A.** Celle de l'extension du template
- **B.** `js`
- **C.** Aucune : l'escaping est coupé
- **D.** `html`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Sans argument, le tag `autoescape` applique la
strategy `html`, quelle que soit l'extension du template.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Escaping **actif** ; strategy choisie par l'**extension** du template.
- `.html` et inconnu → **html** ; `.js`, `.json` → **js** ; `.css` → **css** ;
  `.txt` → **aucune**.
- À l'**affichage** ; les **statiques** ne sont pas échappées, une
  concaténation avec une variable l'est en entier.
- Cinq strategies : **`html`**, **`js`**, **`css`**, **`url`**, **`html_attr`**.
- **`raw` en dernier**, sinon sans effet.
- `escape(variable)` **double** l'escaping.

Moyen mnémotechnique : **« le nom choisit, l'affichage échappe, raw ferme la
marche »**.

```text
autoescape 'name' : html (défaut), js, css, txt = rien ; à l'affichage ; statique non échappée ;
html / js / css / url (sous-composant) / html_attr (sans guillemets) ; raw en dernier
```

## Sources officielles

- [Twig 3.22, filter `escape`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/escape.rst)
- [Twig 3.22, tag `autoescape`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/autoescape.rst) et [filter `raw`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/raw.rst)
- [Twig 3.22, `FileExtensionEscapingStrategy`](https://github.com/twigphp/Twig/blob/v3.22.0/src/FileExtensionEscapingStrategy.php)
- [Twig 3.22, `CoreExtension`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Extension/CoreExtension.php)
- [TwigBundle, `TwigExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php)
