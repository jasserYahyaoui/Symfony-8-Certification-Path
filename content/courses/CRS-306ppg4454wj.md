---
id: CRS-306ppg4454wj
official_item: OIT-sqekx9pkbe5v
title: "Filters and functions"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst"
    anchor: "functions"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst"
    anchor: "filters"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/index.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/index.rst"
    symbol_or_lines: "Filters"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/u.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/u.rst"
    symbol_or_lines: "u.truncate, twig/string-extra"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/advanced.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/advanced.rst"
    symbol_or_lines: "AsTwigFilter (3.21)"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/slice.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/slice.rst"
    symbol_or_lines: "slice"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-cqhgw19wwy6g: "La différence"
  OUT-thp60dyg5ccw: "Enchaîner"
  OUT-v2xep4yvvh3g: "Enchaîner"
  OUT-2x8ake8z462h: "Ce que Symfony ajoute"
  OUT-mhmwe1napmfn: "Ce que Symfony ajoute"
---

## Objectif

Distinguer un filter d'une function, savoir enchaîner et paramétrer, et
reconnaître les extensions que Symfony ajoute.

## Avant la règle : le problème à résoudre

Un template doit mettre en forme un prix, tronquer un résumé, générer l'URL
d'un article, traduire un libellé. Twig fournit deux outils qui se ressemblent
— le **filter** et la **function** — plus les **tests**, et Symfony en ajoute
d'autres. Il faut savoir lequel employer, comment ils s'enchaînent, et d'où
vient chacun.

Tous les faits de cette page ont été exécutés avec Twig 3.22.2, et TwigBundle
8.0.15 pour les extensions.

## Le fil rouge : la liste des articles

```twig
{% for post in posts|filter(p => p.published)|sort((a, b) => b.date <=> a.date) %}
    <h2><a href="{{ path('post_show', {slug: post.slug}) }}">{{ post.title|title }}</a></h2>
    <p>{{ post.summary|striptags|slice(0, 120) }}…</p>
    <small>{{ post.date|date('d/m/Y') }} · {{ post.price|number_format(decimal: 2) }} €</small>
{% endfor %}
```

`filter`, `sort`, `title`, `striptags`, `slice`, `date` et `number_format` sont
des filters du cœur de Twig ; `path()` est une function du bridge Symfony.

## Le cycle complet en un schéma

Comment Twig traite une expression qui mêle filters, functions et operators :

```mermaid
---
title: Filter, function et précédence
---
flowchart TD
  accTitle: Comment Twig interprète filters, functions et operators dans une expression
  accDescr: Une function produit une valeur à partir de ses arguments ; un filter transforme une valeur existante. Le filter a la précédence la plus haute : il s'applique à l'opérande le plus proche avant tout operator, y compris le moins unaire et la concaténation, sauf parenthèses. Les filters enchaînés s'appliquent de gauche à droite. Un filter ou une function inconnus sont refusés à la compilation.
  EXPR["expression"] --> KIND{"part d'une valeur existante ?"}
  KIND -->|"oui"| F["filter, par |"]
  KIND -->|"non"| FN["function, par nom(...)"]
  F --> PREC{"sans parenthèses ?"}
  PREC -->|"oui"| NEAR(["s'applique à l'opérande le plus proche, avant -, ~, +"])
  PREC -->|"non"| GROUP(["s'applique à l'expression entre parenthèses"])
  F --> CHAIN(["enchaînés : de gauche à droite"])
  FN --> KNOWN{"connue ?"}
  KNOWN -->|"non"| ERR(["SyntaxError à la compilation"])
```

## La différence

Un **filter** transforme une valeur qui existe déjà, et s'applique par le tube :

```html
{{ name|upper }}
{{ price|number_format(2, ',', ' ') }}
```

Une **function** produit une valeur à partir de ses arguments :

```html
{{ path('blog_show', {slug: post.slug}) }}
{{ random(1, 10) }}
```

La règle pratique : si l'on part d'une valeur, c'est un filter ; si l'on part de
rien, c'est une function. `date` existe sous les deux formes — `now|date(...)`
filtre une date, `date()` en construit une — ce qui en fait un bon révélateur de
la distinction. Troisième famille, à ne pas confondre : les **tests**, appliqués
par `is` — `is defined`, `is empty`, `is iterable`.

## Enchaîner

Les filters se composent de gauche à droite, chacun recevant le résultat du
précédent :

```html
{{ text|trim|lower|slice(0, 50) }}
```

L'ordre compte quand deux filters ne commutent pas. Sur `'  abcdef'`,
`|slice(0, 5)|trim` donne `abc` — la coupe garde les deux espaces — alors que
`|trim|slice(0, 5)` donne `abcde`. À l'inverse, `trim` et `lower` commutent :
l'un ne touche que les espaces, l'autre que la casse.

Les arguments peuvent être nommés : `{{ price|number_format(decimal: 2) }}`.
Plusieurs filters prennent une **function fléchée** — `filter`, `map`,
`reduce`, `sort`, `find` :

```html
{{ users|filter(u => u.active)|map(u => u.name)|join(', ') }}
```

`{% apply %}` applique un filter à tout un block :

```html
{% apply upper %}
    Ce paragraphe entier sera en majuscules.
{% endapply %}
```

**Rejoué :** `'  abcdef'|slice(0, 5)|trim` donne `abc`, l'ordre inverse
`abcde` ; `1234.5|number_format(decimal: 2)` donne `1,234.50` ;
`[3, 8, 12]|find(v => v > 5)` donne `8`, le premier qui convient ;
`[3, 1, 2]|sort((a, b) => b <=> a)|join` donne `321`.

**La précédence du filter.** La documentation : « the filter operator has the
highest precedence ». Il s'applique à l'opérande le plus proche, avant tout
operator. Rejoué : `{{ -1|abs }}` donne **`-1`** — `abs` s'applique à `1`, puis
le moins — et `{{ (-1)|abs }}` donne `1` ; `{{ 'a'|upper ~ 'b' }}` donne `Ab`,
`{{ ('a' ~ 'b')|upper }}` donne `AB`.

## Le cœur de Twig et les packages « extra »

La liste des filters de la documentation Twig mélange le cœur et des extensions
**à installer**. `truncate`, par exemple, n'est **pas** un filter du cœur de
Twig 3.22 : on tronque avec `slice`, ou avec `|u.truncate(50)`, que fournit
l'extension `StringExtension` du package `twig/string-extra` — dans Symfony,
activée par `twig/extra-bundle`. Les filters `format_*`, `markdown_to_html` ou
`inline_css` viennent de même d'extensions séparées.

Rejoué : `{{ 'abcdef'|truncate(3) }}` lève « Unknown "truncate" filter » ; une
function inconnue, « Unknown "shorten" function ». Les deux sont des
`SyntaxError` **de compilation** : le template ne se compile pas, même si la
ligne n'est jamais exécutée.

## Ce que Symfony ajoute

Le bridge Twig apporte les extensions qui exposent les components :

| Ajout | Origine |
|---|---|
| `path()`, `url()` | Routing |
| `asset()`, `asset_version()` | Asset |
| `trans` (filter), `t()` | Translation |
| `form_start()`, `form_row()`, `form_widget()` | Form |
| `is_granted()` | Security |
| `dump()` | VarDumper |

`php bin/console debug:twig` liste ce qui est réellement disponible dans
l'application, filters, functions, tests et globales — c'est la source fiable,
plutôt qu'une liste apprise par cœur.

## En ajouter

Une extension déclare ses filters et functions en étendant `AbstractExtension`.
Depuis Twig 3.21, plus court : les attributes `#[AsTwigFilter]`,
`#[AsTwigFunction]` et `#[AsTwigTest]` (namespace `Twig\Attribute`) sur
les methods publiques d'une class ; TwigBundle 8.0 les enregistre par
autoconfiguration.

```php
final class AppTwig
{
    #[AsTwigFilter('shout')]
    public function shout(string $s): string { return strtoupper($s).'!'; }

    #[AsTwigFunction('answer')]
    public function answer(): int { return 42; }

    #[AsTwigTest('even_len')]
    public function evenLen(string $s): bool { return 0 === strlen($s) % 2; }
}
```

Rejoué, la class déclarée comme service autoconfiguré, sans tag ni extension :
`{{ 'hey'|shout }} {{ answer() }} {{ 'ab' is even_len ? 'even' : 'odd' }}`
rend `HEY! 42 even`.

## Concrètement dans ton application

- Transformer une valeur : un filter ; en produire une : une function ; tester :
  `is`.
- Tronquer : `slice`, ou `u.truncate()` avec `twig/string-extra`.
- Mettre des parenthèses dès qu'un filter suit un operator : `(a ~ b)|upper`,
  `(-x)|abs`.
- Une collection : `filter`, `map`, `sort`, `find` avec une function fléchée.
- Un helper maison : `#[AsTwigFilter]` sur une method, sans class
  `AbstractExtension`.
- Avant de supposer qu'un filter existe : `debug:twig --filter=nom`.

## À comprendre

- **Pourquoi deux formes, filter et function ?** La lecture : `prix|money` se
  lit comme une transformation, `path('home')` comme une fabrication.
- **Pourquoi le filter colle-t-il à son voisin ?** Pour que `name|upper ~ '!'`
  se lise naturellement ; le prix est qu'un `-` ou un `~` placé avant
  n'est pas couvert.
- **Pourquoi une erreur de compilation ?** Twig compile le template entier en
  PHP ; un nom inconnu empêche de produire la class, quelle que soit la ligne.

## À mémoriser

- **Filter** transforme (`|`), **function** produit (`nom()`), **test** vérifie
  (`is`).
- Enchaînés **de gauche à droite** ; l'ordre compte s'ils ne commutent pas.
- Le filter a la **précédence la plus haute** : `-1|abs` vaut `-1`.
- Arguments **nommés** ; **functions fléchées** pour `filter`, `map`, `reduce`,
  `sort`, `find`.
- **Pas de `truncate`** dans le cœur ; `u`, `format_*` : packages `extra`.
- **`#[AsTwigFilter]`**, **`#[AsTwigFunction]`**, **`#[AsTwigTest]`** depuis
  Twig 3.21.
- Moyen mnémotechnique : **« le filter colle à son voisin, la parenthèse le
  décolle »**.

## Pièges d'examen

**Filter ou function se décide à la source, pas au nom.** On part d'une valeur
existante : c'est un filter. On part de rien : c'est une function. Le même mot
peut exister sous les deux formes.

**L'ordre des filters enchaînés compte quand ils ne commutent pas.** `slice`
puis `trim` n'égale pas `trim` puis `slice` ; `trim` et `lower`, si.

**Un filter de la documentation peut exiger un package.** `u`, `format_*`,
`markdown_to_html` ne sont pas dans le cœur ; `truncate` n'existe pas du tout.

**Ce que Symfony ajoute vient du bridge, pas de l'engine.** Les functions de route,
d'asset, de translation et de form n'existent pas dans un Twig installé
seul.

## Application : trois scénarios

**Scénario 1 — La différence toujours positive.** `{{ -delta|abs }}` devait
afficher une valeur absolue et affiche un nombre négatif. *Solution.* Le filter
passe avant le moins : `{{ (-delta)|abs }}`, ou plus simplement
`{{ delta|abs }}`.

**Scénario 2 — Le titre à moitié en majuscules.** `{{ first ~ ' ' ~ last|upper }}`
ne met en majuscules que le nom de famille. *Solution.* Parenthèses :
`{{ (first ~ ' ' ~ last)|upper }}`.

**Scénario 3 — La page qui ne compile plus.** Un template copié d'un autre projet
utilise `|format_currency('EUR')` ; toute la page tombe en erreur. *Solution.*
Le filter vient de `twig/intl-extra` : l'installer, ou formater autrement.
`debug:twig --filter=format_currency` le confirme avant.

## QCM d'entraînement

Cinq questions sur les filters et les functions. Choisis avant d'ouvrir la
correction.

**Question 1.** Que rend `{{ -1|abs }}` ?

- **A.** `1`
- **B.** `-1`
- **C.** Une `SyntaxError` : opérande ambigu
- **D.** `0`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Rejoué avec Twig 3.22.2 : le filter, de précédence la
plus haute, s'applique à `1` ; le moins vient ensuite. `{{ (-1)|abs }}` rend `1`.

A est l'idée reçue. C et D sont faux.

</details>

**Question 2.** Que rend `{{ 'a'|upper ~ 'b' }}` ?

- **A.** `AB`
- **B.** `ab`
- **C.** `Ab`
- **D.** Une erreur : `~` après un filter

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Rejoué : `upper` ne touche que `'a'`. Pour les deux, la
documentation montre `('HELLO' ~ 'FABIEN')|lower`.

A suppose que le filter couvre toute l'expression. B et D sont faux.

</details>

**Question 3.** Un template contient, dans un `if` jamais vrai,
`{{ title|truncate(20) }}`. Que se passe-t-il au rendu, avec Twig 3.22 seul ?

- **A.** Rien : la ligne n'est jamais exécutée
- **B.** Une `SyntaxError` : le template ne compile pas
- **C.** Une `RuntimeError` seulement si la ligne s'exécute
- **D.** Le titre est tronqué à 20 caractères

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Rejoué : « Unknown "truncate" filter », levé à la
compilation du template entier.

A et C supposent une vérification à l'exécution. D suppose un filter qui
n'existe pas dans le cœur.

</details>

**Question 4.** Que rend `{{ [3, 8, 12]|find(v => v > 5) }}` ?

- **A.** `8`
- **B.** `8, 12`
- **C.** `1`, la position
- **D.** `true`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Rejoué : `find` rend le premier élément qui satisfait la
function fléchée.

B décrirait `filter`. C et D sont faux.

</details>

**Question 5.** Une class porte `#[AsTwigFilter('shout')]` sur une method
publique et est déclarée comme service autoconfiguré. Que faut-il de plus pour
utiliser `|shout` dans Symfony 8.0 ?

- **A.** Étendre `AbstractExtension`
- **B.** Le tag `twig.extension`
- **C.** Rien
- **D.** Un `getFilters()` qui la déclare

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Rejoué : `{{ 'hey'|shout }}` rend `HEY!`. TwigBundle 8.0
enregistre les attributes de Twig 3.21 par autoconfiguration.

A, B et D décrivent l'ancienne façon de déclarer une extension.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **Filter** transforme, **function** produit, **test** vérifie avec `is`.
- Filters enchaînés **de gauche à droite**.
- Précédence **maximale** du filter : **parenthèses** dès qu'un operator précède.
- Functions fléchées : **`filter`**, **`map`**, **`sort`**, **`find`**, **`reduce`**.
- Nom inconnu : **SyntaxError à la compilation**.
- **`truncate`** n'existe pas ; **`u`**, **`format_*`** : packages extra.
- **`#[AsTwigFilter]`** et consorts : autoconfigurés par TwigBundle 8.0.

**En une phrase, pour tout reconstruire :**

```text
valeur|filter (gauche à droite, précédence max : -1|abs = -1, 'a'|upper~'b' = Ab) ; fonction(...) ; is test ;
arrow : filter map sort find reduce ; inconnu = SyntaxError ; bridge : path asset trans form is_granted ; #[AsTwig*]
```

## Sources officielles

- [Symfony Twig Reference](https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst)
- [Twig 3.22, filters et functions](https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst)
- [Twig 3.22, index des filters](https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/index.rst) et [filter `u`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/u.rst)
- [Twig 3.22, attributes `AsTwigFilter`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/advanced.rst)
