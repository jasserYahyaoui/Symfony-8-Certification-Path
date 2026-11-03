---
id: CRS-q18xc1k4yvb9
official_item: OIT-sd08j04k60m0
title: "Translations and pluralization"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/translation.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/translation.rst"
    anchor: "translations-in-templates"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst"
    anchor: "trans"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/TranslationExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/TranslationExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "TranslationExtension::trans(), createTranslatable()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/TokenParser/TransTokenParser.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/TokenParser/TransTokenParser.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "TransTokenParser::parse()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Node/TransNode.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Node/TransNode.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "TransNode::compileString()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/formats/message_format.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/formats/message_format.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "intl-icu suffix, legacy ranges"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/ExpressionParser/Infix/ArgumentsTrait.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/ExpressionParser/Infix/ArgumentsTrait.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "named arguments"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Translation/CHANGELOG.md"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Translation/CHANGELOG.md"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "8.0: Remove TranslatableMessage::__toString() method, use trans() or getMessage() instead"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Translation/Formatter/IntlFormatter.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Translation/Formatter/IntlFormatter.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "formatIntl() : MessageFormatter($locale, ...) ; clés %name% et {name} ramenées à name"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Template.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Template.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "Template::display() echoes outside the try/catch of yield()"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-p436jre6f29p: "Le filter"
  OUT-eh8yg0599jp9: "Le tag"
  OUT-ke17qw8mxwms: "Les emplacements"
  OUT-km38p3n24htq: "Le plural"
  OUT-txry0vy6q09r: "L'object traduisible"
  OUT-c07wjtmm0n2y: "Plural et select, exécutés"
---

## Objectif

Traduire depuis un template, gérer le plural et savoir ce que le filter et le
tag font différemment. L'organisation des catalogues et la détection de la
locale appartiennent aux lots Internationalisation et Routing.

## L'essentiel

Twig traduit avec un **filter**, `|trans`, un **tag**, `{% trans %}`, et une
**function**, `t()`, qui crée un message traduit plus tard. Ils diffèrent par
l'ordre des arguments, l'escaping et les emplacements. Le plural passe par ICU,
dans un catalogue suffixé `+intl-icu`.

## Le schéma

```mermaid
---
title: Ce que fait le filter trans d'un message
---
flowchart TD
  accTitle: Le parcours d'un message à travers le filter trans de Symfony 8.0
  accDescr: Si le message est un object traduisible, une string en deuxième argument est prise comme locale et l'object se traduit avec ses propres parameters et son domain. Sinon le deuxième argument doit être un array de parameters, faute de quoi le filter lève une TypeError que Twig remonte dans une RuntimeError. Le domain est celui passé en argument, sinon celui de trans_default_domain dans le template courant, sinon messages. Si le file du catalogue porte le suffixe intl-icu, le message passe par MessageFormatter avec des emplacements entre accolades, plural et select. Sinon les emplacements sont remplacés par strtr et la syntaxe à barres choisit d'après count. L'output du filter est échappé, celui du tag ne l'est pas.
  MSG{"le message est un object traduisible ?"} -->|"oui"| OBJ["string en 2e argument = locale ; parameters et domain de l'object"]
  MSG -->|"non"| ARR{"2e argument = array ?"}
  ARR -->|"non"| TE(["TypeError, remontée dans une RuntimeError"])
  ARR -->|"oui"| DOM["domain : argument, sinon trans_default_domain du template, sinon messages"]
  OBJ --> CAT
  DOM --> CAT{"file suffixé +intl-icu ?"}
  CAT -->|"oui"| ICU["MessageFormatter : {name}, plural, select"]
  CAT -->|"non"| STRTR["strtr : %name% ; barres choisies par %count%"]
  ICU --> OUT(["filter : output échappé ; tag : non échappé"])
  STRTR --> OUT
```
## Le filter

```html
<p>{{ 'user.greeting'|trans({'%name%': user.name}) }}</p>
<p>{{ 'checkout.total'|trans({}, 'store') }}</p>
<p>{{ 'legal.notice'|trans({}, 'messages', 'de') }}</p>
<p>{{ 'checkout.total'|trans(domain: 'store') }}</p>
```

- Signature : `trans(arguments = [], domain = null, locale = null, count = null)`.
  Domain omis : `messages` ; locale omise : la locale courante ; `count`
  renseigne `%count%`.
- Les **named arguments** évitent l'array vide : `trans(domain: 'store')`.
- Le deuxième argument doit être un **array**. Une string n'y est admise que si
  le message est un object traduisible — elle désigne alors la locale. Sur une
  string ordinaire, `|trans('store')` lève une `TypeError`, que le template
  reçoit dans une `RuntimeError`.

## Les emplacements

Symfony remplace les emplacements par `strtr()`. Les pourcents de `%name%` sont
une **convention** : `#name#` ou `{name}` marchent aussi, pourvu que la key
corresponde **exactement**. Avec l'array `{'name': 'Ada'}` et le message
`Bonjour %name%`, on obtient `Bonjour %Ada%`.

## Le tag

```html
{% trans with {'%name%': 'Fabien'} from 'app' into 'fr' %}Hello %name%{% endtrans %}
```

- `count`, `with`, `from`, `into`, **dans cet ordre** ; un mot hors de place est
  une `SyntaxError`.
- Le body est du **texte simple** : `{{ name }}` dans le body est une
  `SyntaxError` ; la notation `%name%` est obligatoire.
- Un emplacement absent de `with` est lu dans le **contexte** (`TransNode`) :
  `%name%` vaut la variable `name` — erreur sous `strict_variables` si elle
  n'existe pas.
- Un pourcent littéral se double : `%percent%%%`.

`{% trans_default_domain 'app' %}` fixe le domain du template courant **seul** :
un template inclus garde `messages`.

## Échappé ou non

L'output du **filter** est échappé, celui du **tag** ne l'est **pas**. Pour du
HTML traduit par le filter : `|trans|raw`.

## Le plural

La documentation 8.0 traite plural, genre et formats par **ICU MessageFormat** :
le file de catalogue porte le suffixe `+intl-icu` — `messages+intl-icu.fr.yaml`
—, et les emplacements s'écrivent `{name}`. Sans le suffixe, les accolades ne
sont pas interprétées.

La syntaxe à barres — `'{0} Aucun|{1} Un|]1,Inf[ %count% résultats'` —
sélectionne d'après `%count%`. La documentation la dit *legacy* ; elle garde les
intervalles personnalisés, qu'ICU n'a pas.

## Plural et select, exécutés

| Message ICU | Résultat |
|---|---|
| `{n, plural, one {# fichier} other {# fichiers}}`, locale `fr` | `0 fichier`, `1,5 fichier`, `2 fichiers` |
| le même message en anglais | `0 files` |
| `=0 {aucun fichier}` placé avant `one` | `=0` l'emporte pour 0 |
| `select` ou `plural` sans `other` | `InvalidArgumentException` à chaque `trans()` |
| `select` contenant un `plural` | les deux s'imbriquent |

Les catégories `one`, `few`… suivent la **locale** : en français, 0 et 1,5
sont `one`. Une key `%name%` remplit aussi `{name}` : Symfony retire les
pourcents.

## L'object traduisible

`t('notification.welcome', {…}, 'domaine')` construit un `TranslatableMessage`
qui garde ses parameters et son domain, et se traduit à l'affichage par
`|trans`. Une enum qui retourne une key brute perd parameters et domain.

En 8.0, `TranslatableMessage` n'a plus de `__toString()` : `{{ message }}` seul
fait échouer le rendu — une `Error` PHP, « could not be converted to string »,
sur Twig 3.22 —, là où 7.4 affichait la key. Seul `{{ message|trans }}` produit
le texte.

## Pièges d'examen

**L'ordre des arguments du filter est parameters, puis domain, puis locale.** Les
named arguments évitent de le deviner.

**Une string en deuxième argument** n'est une locale que pour un object
traduisible ; ailleurs, c'est une erreur.

**Le filter échappe, le tag non.**

**`trans_default_domain` ne traverse pas un `include`.**

**Dans le tag, un emplacement non fourni vient du contexte.**

**Accolades et pluriel** : sans le suffixe `+intl-icu`, `{name}` n'est qu'un
emplacement ordinaire — rien n'est pluralisé.

## Application

- La page affiche `Bonjour %Ada%` → la key de l'array doit garder ses pourcents :
  `{'%name%': …}`.
- Le panier affiche `{count, plural, …}` tel quel → renommer le catalogue en
  `…+intl-icu.fr.yaml`.

## QCM d'entraînement

**Question 1.** Le catalogue traduit `user.greeting` par `Bonjour %name%`. Que
rend `{{ 'user.greeting'|trans({'name': 'Ada'}) }}` ?

- **A.** `Bonjour Ada`
- **B.** `Bonjour %name%`
- **C.** `Bonjour %Ada%`
- **D.** Une erreur : emplacement inconnu

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `strtr()` remplace la string `name` là où il la trouve,
et laisse les pourcents.

</details>

**Question 2.** Que fait `{% trans %}Hello {{ name }}{% endtrans %}` ?

- **A.** Il traduit `Hello` et affiche `name` à la suite
- **B.** Il traduit le message après avoir inséré `name`
- **C.** Il ignore `{{ name }}`, qui n'est pas un emplacement du tag
- **D.** Il ne compile pas

<details>
<summary>Correction</summary>

**Bonne réponse : D.** « A message inside a trans tag must be a simple text ».

</details>

**Question 3.** Le catalogue `fr` contient
`apples: '{0} Aucune pomme|{1} Une pomme|]1,Inf[ %count% pommes'`. Que rend
`{{ 'apples'|trans(count: 0) }}` ?

- **A.** `0 pommes`
- **B.** `Aucune pomme`
- **C.** `Une pomme`, 0 étant `one` en français
- **D.** Le message brut, faute de suffixe `+intl-icu`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La syntaxe à barres choisit `{0}` d'après `%count%`. C
confond avec les catégories ICU ; la syntaxe à barres n'a pas besoin du suffixe.

</details>

**Question 4.** Que rend
`{% trans with {'%percent%': 20} %}%percent%%% de remise{% endtrans %}` ?

- **A.** `20%% de remise`
- **B.** `%percent%% de remise`
- **C.** `20% de remise`
- **D.** Une erreur de syntaxe

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Dans le tag, `%%` est un pourcent littéral.

</details>

## Fiche express

**Si tu dois retenir seulement 8 choses :**

- **`|trans(paramètres, domaine, locale, count)`**, named arguments admis.
- String en 2ᵉ argument : **locale** pour `t()`, **erreur** pour une string.
- **`strtr()`** : `{'%name%': …}`, pas `{'name': …}`.
- Tag : **`count` `with` `from` `into`**, texte simple, `%var%` du contexte.
- **Filter échappé, tag non** ; `|raw` pour le HTML du filter.
- **`trans_default_domain`** : le template courant, pas les inclus.
- ICU : **`+intl-icu`**, **`{name}`**, catégories de la locale, `other`
  obligatoire.
- **`t()`** se traduit par `|trans` ; seul, il fait échouer le rendu.

Moyen mnémotechnique : **« PDL : parameters, puis domain, puis locale — le
filter échappe, le tag fait confiance »**.

```text
trans(params, domain, locale, count) ; string en 2e = locale pour t(), erreur sinon ; strtr exact ;
tag : count with from into, texte simple, non échappé ; default domain local ; ICU = +intl-icu + {name} ; t() via |trans
```

## Sources officielles

- [Symfony Translation, translations dans les templates](https://github.com/symfony/symfony-docs/blob/8.0/translation.rst) et [ICU MessageFormat](https://github.com/symfony/symfony-docs/blob/8.0/reference/formats/message_format.rst)
- [Symfony Twig Reference, filter `trans`](https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst)
- [Twig Bridge 8.0, `TranslationExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/TranslationExtension.php), [`TransTokenParser`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/TokenParser/TransTokenParser.php) et [`TransNode`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Node/TransNode.php)
- [Twig 3.22, `Template`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Template.php)

