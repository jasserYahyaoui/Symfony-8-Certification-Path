---
id: CRS-k3v3wrt0hmtx
official_item: OIT-g3p8wdtww344
title: "Template inheritance"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/extends.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/extends.rst"
    anchor: "extends"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/use.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/use.rst"
    anchor: "use"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Parser.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Parser.php"
    symbol_or_lines: "filterBodyNodes, Multiple extends tags are forbidden"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/tests/Fixtures/exceptions/child_contents_outside_blocks.test"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/tests/Fixtures/exceptions/child_contents_outside_blocks.test"
    symbol_or_lines: "SyntaxError"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/functions/block.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/block.rst"
    symbol_or_lines: "block, is defined"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-2m2fsf0992k6: "Le mécanisme"
  OUT-0b65p7477ebz: "Hors d'un block : une erreur, pas un oubli silencieux"
  OUT-5gpp6vwt10fk: "Reprendre, réafficher, tester un block"
  OUT-qbyeeh719t3d: "Réutilisation horizontale"
---

## Objectif

Construire une hiérarchie de templates, savoir ce que l'inheritance autorise, et
distinguer l'inheritance de la réutilisation horizontale.

## L'essentiel

Toutes les pages partagent un header, un menu, un pied de page. Un **parent**
les déclare une fois et laisse des **trous** — les blocks ; chaque page
**enfant** l'étend et remplit les trous qui l'intéressent. Le compiler refuse
tout ce qui rendrait cette mécanique ambiguë.

## Le schéma

```mermaid
---
title: Ce que Twig vérifie et fait pour un template enfant
---
flowchart TD
  accTitle: Les contrôles de compilation et le rendu d'un template qui étend un parent
  accDescr: À la compilation, un second extends, un extends placé dans un block, deux blocks de même nom, un block placé sous un if dans l'enfant, ou du contenu affiché hors de tout block lèvent une SyntaxError ; les blancs et les tags qui n'affichent rien, comme set, sont acceptés. Au rendu, le parent est résolu, le premier existant pour une liste ; puis le parent est rendu, chaque block étant remplacé par celui de l'enfant s'il le définit, ou par un block importé par use, et parent() insère le contenu d'origine.
  CHILD["template enfant"] --> CHECK{"compilation : contenu hors block, 2 extends, block dupliqué, block sous un if ?"}
  CHECK -->|"oui"| ERR(["SyntaxError"])
  CHECK -->|"non"| PARENT["parent résolu : variable, liste (premier existant), ternaire"]
  PARENT --> RENDER["le parent est rendu"]
  RENDER --> BLK{"pour chaque block : défini par l'enfant, ou importé par use ?"}
  BLK -->|"oui"| OVR(["contenu de l'enfant ; parent() pour l'original"])
  BLK -->|"non"| ORIG(["contenu du parent"])
```

## Le mécanisme

Un template parent déclare des **blocks** ; un template enfant `extends` le parent
et redéfinit ceux qui l'intéressent.

```html
{% extends 'base.html.twig' %}

{% block title %}Articles{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="page.css">
{% endblock %}
```

Un block non redéfini garde le contenu du parent. Un enfant n'affiche **que** ses
blocks. On peut empiler les niveaux : `base` → layout de section → page.

## Hors d'un block : une erreur, pas un oubli silencieux

Dans un template qui étend un parent, du texte ou un `{{ … }}` écrit hors de tout
block lève une `SyntaxError` : « A template that extends another one cannot
include content outside Twig blocks ». Seuls les blancs et les tags qui
n'affichent rien, comme `{% set %}`, sont tolérés ; la variable ainsi posée est
lisible dans les blocks.

## Les règles de l'inheritance

| Template | Résultat |
|---|---|
| deux `extends` | `SyntaxError` : « Multiple extends tags are forbidden » |
| `extends` dans un block | `SyntaxError` |
| deux `{% block body %}` | `SyntaxError` : un nom de block est unique |
| `{% set x = 1 %}` **avant** `extends` | accepté : « `extends` en tête » est une consigne de la documentation, pas un contrôle du parser |

Le parent peut être une **expression** :

- `{% extends some_var %}` — une variable ;
- `{% extends ['layout.html.twig', 'base.html.twig'] %}` — le **premier
  template qui existe** ; si aucun n'existe, une `LoaderError` : « Unable to
  find one of the following templates » ;
- `{% extends standalone ? 'minimum.html.twig' : 'base.html.twig' %}`.

## Reprendre, réafficher, tester un block

- `{{ parent() }}` insère le contenu du block parent : on **ajoute** au lieu de
  remplacer.
- Un block ne s'affiche qu'une fois par son tag ; la **function**
  `block('title')` le réaffiche, y compris depuis un autre template :
  `block('title', 'common.html.twig')`.
- `block('footer') is defined` teste son existence.
- **Un block dans un `if` reste défini** : seul son affichage suit la condition.
  Dans un enfant, un block imbriqué sous un `if` est une `SyntaxError`.

## Réutilisation horizontale

`{% use 'blocks.html.twig' %}` **importe les blocks** d'un autre template sans
l'étendre ni les afficher — la réponse de Twig à l'inheritance multiple : un
seul `extends`, plusieurs `use`.

- Un block importé compte comme un block de l'enfant : il **remplace** celui du
  parent du même nom.
- Un block du template courant l'emporte sur un block importé.
- `with sidebar as base_sidebar` renomme à l'import.
- La cible de `use` est une **string** : une variable est une `SyntaxError`.

## Pièges d'examen

**Du contenu hors block dans un enfant est une erreur de syntaxe**, pas un
contenu ignoré.

**Un seul parent**, mais un parent choisi dynamiquement : variable, liste dont
le premier existant gagne, ou ternaire.

**`block()` réaffiche, `parent()` reprend.** Le tag, lui, n'affiche qu'une
fois.

**Un block dans un `if` reste défini.** Seul son rendu dépend de la condition.

**Importer les blocks d'un autre template n'est pas en hériter.**

## Application

- Un enfant contient un `<p>` hors de tout block → `SyntaxError` : le placer
  dans un block.
- Une feuille de style par page sans perdre celles du layout →
  `{% block stylesheets %}{{ parent() }}…{% endblock %}`.

## QCM d'entraînement

**Question 1.** Un template commence par `{% set x = 1 %}`, puis
`{% extends 'base.html.twig' %}`, puis un block qui affiche `{{ x }}`.
Que se passe-t-il ?

- **A.** Une `SyntaxError` : `extends` doit être le premier tag
- **B.** Le rendu fonctionne et affiche `1`
- **C.** Le rendu fonctionne, mais `x` est inconnu dans le block
- **D.** Twig ignore le `set` placé avant `extends`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** « En tête » est une consigne de la documentation ; le
parser ne refuse que le contenu affiché hors block.

</details>

**Question 2.** `{% extends ['missing.html.twig', 'absent.html.twig'] %}`, et
aucun des deux n'existe. Résultat ?

- **A.** Le template est rendu sans parent
- **B.** Une `SyntaxError` à la compilation
- **C.** Une `LoaderError` au rendu
- **D.** Twig retombe sur `base.html.twig`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** « Unable to find one of the following templates ». La
liste est valide ; aucun repli n'existe.

</details>

**Question 3.** Le parent définit `title` ; l'enfant ne le redéfinit pas mais
fait `{% use 'blocks.html.twig' %}`, qui définit lui aussi `title`. Quel titre
s'affiche ?

- **A.** Celui de `blocks.html.twig`
- **B.** Celui du parent : `use` n'hérite de rien
- **C.** Une `SyntaxError` : block défini deux fois
- **D.** Aucun : les deux s'annulent

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Un block importé compte comme un block de l'enfant, et
remplace celui du parent.

</details>

**Question 4.** Un template écrit `{% use theme_blocks %}`, avec une variable.
Que se passe-t-il ?

- **A.** Les blocks du template nommé par la variable sont importés
- **B.** Une `SyntaxError` : la cible doit être une string
- **C.** Une `LoaderError` si la variable est vide
- **D.** `use` accepte une variable seulement dans un enfant

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Contrairement à `extends`, qui accepte une variable ou
une liste, `use` exige le nom du template écrit en toutes lettres.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **`extends`** + **`block`** ; un block non redéfini garde le parent.
- Hors block, dans un enfant : **SyntaxError**, sauf blancs et **`set`**.
- **Un seul parent**, jamais dans un block ; **un nom de block unique**.
- Parent **dynamique** : liste (premier existant), ternaire, variable.
- **`parent()`** ajoute, **`block()`** réaffiche.
- Un block sous un **`if`** reste défini.
- **`use`** importe des blocks, qui **masquent** ceux du parent.

Moyen mnémotechnique : **« un parent, des trous, rien hors des trous »**.

```text
enfant = extends (1 seul, expression permise) + blocks (noms uniques) ; hors block : SyntaxError sauf set/blancs ;
parent() reprend, block() réaffiche, is defined teste ; use = blocks importés (masquent le parent), cible = string
```

## Sources officielles

- [Twig 3.22, tag `extends`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/extends.rst)
- [Twig 3.22, tag `use`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/use.rst) et [function `block`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/block.rst)
- [Twig 3.22, `Parser::filterBodyNodes()`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Parser.php) et [fixture `child_contents_outside_blocks.test`](https://github.com/twigphp/Twig/blob/v3.22.0/tests/Fixtures/exceptions/child_contents_outside_blocks.test)
