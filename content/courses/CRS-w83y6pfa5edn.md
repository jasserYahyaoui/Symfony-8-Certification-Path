---
id: CRS-w83y6pfa5edn
official_item: OIT-ds2p5d4eg0pq
title: "Template includes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/include.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/include.rst"
    anchor: "include"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/embed.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/embed.rst"
    anchor: "embed"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/functions/include.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/include.rst"
    anchor: "include function"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "with_context, ignore_missing, sandboxed"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Extension/CoreExtension.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Extension/CoreExtension.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "CoreExtension::include()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/TokenParser/IncludeTokenParser.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/TokenParser/IncludeTokenParser.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "IncludeTokenParser::parseArguments()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "Including Templates"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "underscore prefix is optional"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-2dk5sjxn9j7s: "Les deux écritures"
  OUT-ng5q8rr0804g: "Le contexte"
  OUT-k04wxdqz7vn1: "Quel template, et s'il manque"
  OUT-kb1neg7efnfp: "`include` ou `embed`"
---

## Objectif

Insérer un template dans un autre, contrôler ce qu'il voit, et choisir entre
`include` et `embed`.

## L'essentiel

Un fragment — une carte d'article, un bandeau — s'insère dans plusieurs pages.
`include` l'insère tel quel, `embed` l'insère en redéfinissant certains de ses
blocks. Par défaut, le fragment voit **tout** le contexte de l'appelant ;
`only` le limite à ce qu'on lui passe.

## Le schéma

```mermaid
---
title: Ce que voit un fragment inclus, et s'il manque
---
flowchart TD
  accTitle: Les règles de contexte et d'absence de include et embed dans Twig 3.22
  accDescr: Le nom peut être une expression ou une liste dont le premier template existant est retenu. Si aucun n'existe, une LoaderError est levée, sauf avec ignore missing en tag ou ignore_missing true en function, qui ne rendent rien. Le fragment reçoit le contexte de l'appelant plus les variables passées, sauf avec only ou with_context false, qui ne laissent que les variables passées ; les globales restent visibles dans tous les cas. Les variables que le fragment pose ne remontent pas à l'appelant. embed fait de même, et permet en plus de redéfinir des blocks du fragment.
  CALL["include ou embed"] --> FIND{"un template existe ? liste : le premier"}
  FIND -->|"non"| MISS{"ignore missing ?"}
  MISS -->|"oui"| EMPTY(["rien n'est rendu"])
  MISS -->|"non"| LERR(["LoaderError"])
  FIND -->|"oui"| CTX{"only, ou with_context: false ?"}
  CTX -->|"non"| ALL(["contexte de l'appelant + variables passées + globales"])
  CTX -->|"oui"| PASSED(["variables passées + globales"])
  ALL --> BACK(["rien ne remonte vers l'appelant"])
  PASSED --> BACK
```
## L'exemple

```twig
{% for post in posts %}
    {{ include('article/_card.html.twig', {article: post}, with_context: false) }}
{% endfor %}

{% embed 'article/_card.html.twig' with {article: featured} only %}
    {% block footer %}<strong>À la une</strong>{% endblock %}
{% endembed %}
```

## Les deux écritures

- La **function** `{{ include('x.html.twig', {a: 1}) }}` est la forme
  recommandée : elle se compose — `{% set html = include('x.html.twig') %}`,
  `include('x.html.twig')|upper` — et ses named arguments n'imposent aucun
  ordre.
- Le **tag** `{% include 'x.html.twig' with {a: 1} %}` fait la même chose.

Un fragment est souvent préfixé d'un souligné, `_card.html.twig` ; la
documentation Symfony précise que ce préfixe est facultatif.

## Le contexte

| Écriture | Le fragment voit |
|---|---|
| `include('x.html.twig', {a: 1})` | tout le contexte de l'appelant, plus `a` |
| `{% include 'x.html.twig' with {a: 1} only %}` | seulement `a` |
| `include('x.html.twig', {a: 1}, with_context: false)` | seulement `a` |

- Les **globales** restent visibles, même avec `only`.
- Rien ne remonte : une variable posée par `set` dans le fragment n'existe pas
  chez l'appelant.

## Quel template, et s'il manque

Le nom est une **expression** : variable, ternaire, ou **liste** dont le premier
template existant est inclus.

Un template absent lève une `LoaderError` **au rendu**, sauf :

- en tag, `ignore missing`, **juste après le nom** :
  `{% include 'x.html.twig' ignore missing with {a: 1} only %}` ;
- en function, `ignore_missing: true`, qui rend une string vide.

Pour un template écrit par un user, `include('page.html.twig', sandboxed: true)`
— effectif seulement si `SandboxExtension` est enregistrée ; sinon l'option est
ignorée, sans erreur.

## `include` ou `embed`

`{% embed %}` combine `include` et `extends` : il insère un template **et**
redéfinit ses blocks au point d'insertion. Il accepte les mêmes arguments que le
tag `include` : `with`, `only`, `ignore missing`.

- Fragment identique à chaque appel : `include` ; une partie à changer :
  `embed`.
- Un block redéfini dans un `embed … only` ne voit que les variables de l'embed,
  pas celles de l'appelant.

## Pièges d'examen

**Par défaut, le template inclus voit tout le contexte de l'appelant.** Le
fragment marche par accident tant que l'appelant possède la variable, puis
casse ailleurs.

**Restreindre le contexte s'écrit différemment selon la forme employée** — un
mot-clé dans le tag, un named argument dans la function.

**Tolérer l'absence aussi** : `ignore missing` en tag, `ignore_missing: true`
en function.

**Une liste de noms n'inclut qu'un template** : le premier qui existe.

## Application

- Un fragment partagé casse sur une page qui n'a pas la variable attendue →
  l'inclure avec `with_context: false` et lui passer ses entrées.
- Un bandeau optionnel par thème → `include('…', ignore_missing: true)`.

## QCM d'entraînement

**Question 1.** Un fragment inclus fait `{% set leaked = 'L' %}`. Après
`{{ include('_card.html.twig') }}`, que rend `{{ leaked ?? 'absent' }}` chez
l'appelant ?

- **A.** `L`
- **B.** `absent`
- **C.** Une erreur : variable déjà définie
- **D.** `L`, seulement sans `only`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le fragment travaille sur une copie du contexte : rien
ne remonte.

</details>

**Question 2.** `{{ include('user_tpl.html.twig', sandboxed: true) }}`, sans
`SandboxExtension` enregistrée. Que se passe-t-il ?

- **A.** Une erreur : `SandboxExtension` non enregistrée
- **B.** Le template n'est pas rendu
- **C.** Le template est rendu sans bac à sable
- **D.** Twig active un bac à sable par défaut

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le bac à sable n'est activé que si l'extension est
présente ; sinon l'option est ignorée, sans message.

</details>

**Question 3.** Un fragment est inclus avec `only`. Une globale `site` existe.
Le fragment peut-il lire `site` ?

- **A.** Oui
- **B.** Non : `only` coupe tout
- **C.** Seulement si elle est passée dans `with`
- **D.** Seulement avec la function, pas le tag

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `only` retire le contexte de l'appelant, pas les
globales.

</details>

**Question 4.** Que fait `{{ include('missing.html.twig') }}` si le template
n'existe pas ?

- **A.** Rend une string vide
- **B.** Lève une `LoaderError` au rendu
- **C.** Rend le nom du template
- **D.** Lève une `SyntaxError` à la compilation

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le template appelant compile ; c'est le rendu qui échoue.
A décrirait `ignore_missing: true`.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **`include()`** recommandée : composable, named arguments.
- Contexte **hérité** par défaut ; **`only`** / **`with_context: false`** le
  coupe.
- Les **globales** restent visibles ; **rien ne remonte** du fragment.
- Liste : le **premier existant** ; absent : **`LoaderError`** au rendu.
- **`ignore missing`** juste après le nom ; **`ignore_missing: true`** en
  function.
- **`sandboxed: true`** n'agit qu'avec **`SandboxExtension`**.
- **`embed`** : include + blocks redéfinis, mêmes arguments.

Moyen mnémotechnique : **« include insère, only isole, embed retouche »**.

```text
include() > {% include %} ; contexte hérité sauf only/with_context:false (globales gardées), rien ne remonte ;
nom = expression ou liste ; absent = LoaderError sauf ignore missing ; sandboxed sans extension = rien ; embed = blocks
```

## Sources officielles

- [Twig 3.22, tag `include`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/include.rst) et [function `include`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/include.rst)
- [Twig 3.22, tag `embed`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/embed.rst)
- [Twig 3.22, `CoreExtension::include()`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Extension/CoreExtension.php)
- [Symfony Templates, inclusion de templates](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)

