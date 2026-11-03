---
id: CRS-dfyab4kpcapw
official_item: OIT-30gnb617ksex
title: "String interpolation"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst"
    anchor: "string-interpolation"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Lexer.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Lexer.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "Lexer::stripcslashes(), REGEX_STRING"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/operators_precedence.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/operators_precedence.rst"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "~ infix: 40 => 27; + infix: 30"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/NodeVisitor/SafeAnalysisNodeVisitor.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/NodeVisitor/SafeAnalysisNodeVisitor.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "ConstantExpression is safe"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-rve93x3e2gjq: "La syntaxe"
  OUT-w25h6r5rdb9c: "La constraint"
  OUT-tf752s70s9sw: "Échapper"
---

## Objectif

Insérer une expression dans une string, connaître la constraint qui l'entoure,
et savoir ce que Twig fait des barres obliques inverses dans une string.

## L'essentiel

`#{expression}` insère une expression dans une string — entre **guillemets
doubles** seulement. Les barres obliques inverses sont traitées dans les deux
sortes de guillemets, à l'inverse de PHP. Et une string interpolée est
échappée **en entier**.

## Le schéma

```mermaid
---
title: Ce que Twig fait d'une string littérale
---
flowchart TD
  accTitle: Le traitement d'une string littérale par Twig 3.22
  accDescr: Dans les deux sortes de guillemets, les séquences d'escaping sont traitées ; une barre oblique inverse devant un caractère non reconnu est ignorée avec une deprecation depuis Twig 3.12. Entre guillemets simples, il n'y a pas d'interpolation et la string reste une constant, que l'escaping laisse intacte. Entre guillemets doubles, un dièse suivi d'une accolade ouvre une interpolation, sauf s'il est précédé d'une barre oblique inverse. L'expression interpolée est évaluée et concaténée ; la string obtenue n'est plus une constant, et l'escaping s'applique à toute la string, tags écrites dans le template comprises.
  LIT["string littérale"] --> ESC["séquences \n \t \x41 \101 traitées ; \P : deprecation 3.12, barre ignorée"]
  ESC --> Q{"guillemets ?"}
  Q -->|"simples"| CONST(["constante : #{…} est du texte ; jamais échappée"])
  Q -->|"doubles"| H{"#{ présent ?"}
  H -->|"non, ou \#{"| CONST
  H -->|"oui"| EXPR["l'expression est évaluée et concaténée"]
  EXPR --> ALL(["plus une constant : toute la string est échappée"])
```
## La syntaxe

```html
{{ "premier #{middle} dernier" }}
{{ "résultat : #{1 + 2}" }}
{{ "Bonjour #{user.name|upper}" }}
```

`#{…}` accepte **n'importe quelle expression** — variable, calcul, filter, et
même une autre string interpolée.

## La constraint

L'interpolation ne fonctionne qu'entre **guillemets doubles**. Entre guillemets
simples, `#{…}` est du texte affiché tel quel, sans erreur. Un `#` qui n'est pas
suivi de `{` reste du texte. Le choix des guillemets n'a aucun effet sur les
performances.

## Échapper

```html
{# affiche : premier #{1 + 2} dernier #}
{{ "premier \#{1 + 2} dernier" }}
```

## Les séquences d'escaping, dans les deux guillemets

Contrairement à PHP, Twig traite les séquences d'escaping **aussi entre
guillemets simples** : `'a\nb'` contient un vrai saut de ligne. Sont reconnus
`\n`, `\t`, `\r`, `\v`, `\f`, `\\`, l'hexadécimal `\x41`, l'octal `\101`, et le
guillemet de la string.

Tout autre caractère précédé d'une barre est **déprécié depuis Twig 3.12** : la
barre est ignorée en Twig 3, avec une notice. Un path Windows double donc ses
barres : `'c:\\Program Files'`.

## L'escaping de la string entière

Une string **littérale** est une constant, jugée sûre : `{{ '<b>x</b>' }}`
affiche du gras. Une string **interpolée** — ou concaténée avec une variable —
est échappée **en entier** : `{{ "<b>#{name}</b>" }}` écrit `&lt;b&gt;Ada&lt;/b&gt;`,
et `raw` à l'intérieur ne protège rien. On écrit le HTML hors de l'expression :
`<b>{{ name }}</b>`.

## L'alternative

`~` concatène : `{{ "Bonjour " ~ user.name ~ " !" }}`. `+` additionne, il ne
concatène pas.

Attention à la précédence : en Twig 3.22, `~` lie **plus fort** que `+`.
`{{ "total " ~ 1 + 2 }}` échoue — « Unsupported operand types: string + int » ;
on écrit `"total " ~ (1 + 2)` ou `"total #{1 + 2}"`.

## Pièges d'examen

**L'interpolation ne fonctionne qu'entre guillemets doubles.** En guillemets
simples, la même écriture est du texte littéral affiché tel quel — et rien ne
signale l'erreur.

**Le choix des guillemets ne coûte rien en performance.**

**`\n` est un saut de ligne même entre guillemets simples**, à l'inverse de
PHP.

**L'operator d'addition ne concatène pas.**

## Application

- `{{ "<strong>#{price}</strong> €" }}` affiche les tags en texte → écrire
  `<strong>{{ price }}</strong> €`.
- `'c:\Users\ada'` perd ses barres, avec des deprecations → doubler :
  `'c:\\Users\\ada'`.

## QCM d'entraînement

**Question 1.** Avec l'autoescape `html` et `name = 'Ada'`, que contient le
source HTML produit par `{{ "<b>#{name}</b>" }}` ?

- **A.** `<b>Ada</b>`
- **B.** `<b>#{name}</b>`
- **C.** `&lt;b&gt;Ada&lt;/b&gt;`
- **D.** `<b>Ada</b>`, `name` seul étant échappé

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La string interpolée n'est plus une constant : le
résultat entier est échappé, `<b>` compris. A vaudrait pour
`<b>{{ name }}</b>`.

</details>

**Question 2.** Que fait `{{ "total " ~ 1 + 2 }}` ?

- **A.** Il affiche `total 3`
- **B.** Il affiche `total 12`, la concaténation passant d'abord
- **C.** Il ne compile pas
- **D.** Il lève une erreur au rendu

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `~` lie plus fort que `+` : `("total " ~ 1) + 2`, que
PHP refuse. A est le résultat de `"total " ~ (1 + 2)`.

</details>

**Question 3.** Que rend `{{ 'c:\Program Files' }}` sur Twig 3.22 ?

- **A.** `c:\Program Files`, la barre étant conservée telle quelle
- **B.** `c:Program Files`, avec une deprecation
- **C.** Une `SyntaxError`
- **D.** `c:` suivi d'un caractère de contrôle

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `\P` n'est pas une séquence reconnue : la barre est
ignorée, avec la deprecation 3.12. A demande `'c:\\Program Files'`.

</details>

**Question 4.** Que rend `{{ '\x41\101' }}`, entre guillemets simples ?

- **A.** `\x41\101`, tel quel
- **B.** `x41101`
- **C.** `A\101`
- **D.** `AA`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Le `Lexer` traite l'hexadécimal et l'octal dans les deux
sortes de guillemets : `\x41` et `\101` valent tous deux `A`. A est le
comportement de PHP.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **`#{expression}`** : toute expression, **guillemets doubles**.
- Guillemets simples : **texte littéral**, sans erreur.
- **`\#{`** affiche le marqueur ; un **`#`** seul reste du texte.
- **`\n`, `\x41`, `\101`** traités dans les deux guillemets.
- **`\P`** inconnu : barre ignorée, **deprecation** ; doubler les barres.
- String interpolée : **échappée en entier** ; littérale : non.
- **`~`** lie plus fort que **`+`** : des parenthèses.

Moyen mnémotechnique : **« doubles pour interpoler, simples pour copier, et
des parenthèses autour du calcul »**.

```text
#{expr} entre doubles seulement ; \#{ pour l'afficher ; escapes dans les deux guillemets, \P déprécié ;
littérale = constante non échappée, interpolée = échappée en entier ; ~ avant + : parenthèses
```

## Sources officielles

- [Twig 3.22, « String Interpolation » et « Literals »](https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst)
- [Twig 3.22, précédence des operators](https://github.com/twigphp/Twig/blob/v3.22.0/doc/operators_precedence.rst)
- [Twig 3.22, `Lexer::stripcslashes()`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Lexer.php) et [`SafeAnalysisNodeVisitor`](https://github.com/twigphp/Twig/blob/v3.22.0/src/NodeVisitor/SafeAnalysisNodeVisitor.php)
