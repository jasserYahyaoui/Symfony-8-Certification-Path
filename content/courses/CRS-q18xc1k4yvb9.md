---
id: CRS-q18xc1k4yvb9
official_item: OIT-sd08j04k60m0
title: "Translations and pluralization"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-24"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/translation.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/translation.rst"
    anchor: "translations-in-templates"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst"
    anchor: "trans"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/TranslationExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/TranslationExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "TranslationExtension::trans(), createTranslatable()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/TokenParser/TransTokenParser.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/TokenParser/TransTokenParser.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "TransTokenParser::parse()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Node/TransNode.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Node/TransNode.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "TransNode::compileString()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/formats/message_format.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/formats/message_format.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "intl-icu suffix, legacy ranges"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/ExpressionParser/Infix/ArgumentsTrait.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/ExpressionParser/Infix/ArgumentsTrait.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "named arguments"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Translation/CHANGELOG.md"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Translation/CHANGELOG.md"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "8.0: Remove TranslatableMessage::__toString() method, use trans() or getMessage() instead"
    verified_at: "2026-10-15"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Translation/Formatter/IntlFormatter.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Translation/Formatter/IntlFormatter.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "formatIntl() : MessageFormatter($locale, ...) ; clés %name% et {name} ramenées à name"
    verified_at: "2026-10-16"
outcome_sections:
  OUT-p436jre6f29p: "Le filter"
  OUT-eh8yg0599jp9: "Le tag"
  OUT-ke17qw8mxwms: "Les emplacements"
  OUT-km38p3n24htq: "Le plural"
  OUT-txry0vy6q09r: "L'object traduisible"
  OUT-c07wjtmm0n2y: "Plural et select, exécutés"
---

## Objectif

Traduire depuis un template, gérer le plural et savoir ce que le filter et la
tag font différemment. L'organisation des catalogues et la détection de la
locale appartiennent aux lots Internationalisation et Routing.

## Le filter

```html
<h1>{{ 'page.title'|trans }}</h1>
<p>{{ 'user.greeting'|trans({'%name%': user.name}) }}</p>
<p>{{ 'checkout.total'|trans({}, 'store') }}</p>
<p>{{ 'legal.notice'|trans({}, 'messages', 'de') }}</p>
```

Dans le code de `TranslationExtension` (Twig Bridge 8.0), la signature est
`trans(arguments = [], domain = null, locale = null, count = null)` : **parameters,
domain, locale**, puis un compte qui renseigne `%count%`. Un domain omis vaut
`messages`, une locale omise vaut la locale courante.

Twig accepte les **named arguments** : `|trans(domain: 'store')` — ou
`domain = 'store'`, l'autre séparateur admis par Twig 3.22 — évite l'array
vide.

Le deuxième argument doit être un array. Une string n'y est admise que si le
message est un object traduisible : elle désigne alors la **locale**. Sur une
string ordinaire, `|trans('store')` lève une `TypeError`.

## Les emplacements

Symfony remplace les emplacements par `strtr()`. Les pourcents de `%name%` sont
une **convention** : la documentation précise que `#name#` ou `{name}`
fonctionnent aussi, pourvu que la key de l'array corresponde exactement.

## Le tag

```html
{% trans with {'%name%': 'Fabien'} from 'app' into 'fr' %}Hello %name%{% endtrans %}
```

- `count`, `with`, `from`, `into`, **dans cet ordre** : `TransTokenParser` les
  lit ainsi, et un mot hors de place est une erreur de syntaxe.
- Dans le tag, la notation `%var%` est **obligatoire**. Un emplacement absent
  de `with` est lu dans le **contexte** : `%name%` vaut la variable `name`
  (`TransNode`).
- Le body doit être du texte simple.
- Un pourcent littéral se double : `%percent%%%`.

`{% trans_default_domain 'app' %}` fixe le domain du template courant — et de
lui seul : un template inclus garde `messages`, pour éviter les effets de bord.

## Échappé ou non

La documentation le souligne : l'output du **filter** est échappé, celui du
**tag** ne l'est **pas**. Pour afficher du HTML traduit par le filter, on
ajoute `|raw` après `|trans`.

## Le plural

La documentation 8.0 traite le plural — et le genre, et les formats propres à
une locale — par **ICU MessageFormat** :

- le file de catalogue porte le suffixe `+intl-icu` —
  `messages+intl-icu.en.yaml` ;
- les emplacements s'écrivent `{name}`, et `MessageFormatter` traite tous les
  messages du file.

La syntaxe à barres verticales — `'{0} Aucun résultat|one result|%count%
results'` — sélectionne d'après `%count%`. La documentation la qualifie de
syntaxe *legacy* ; elle fonctionne toujours et garde une chose qu'ICU n'a pas :
les intervalles personnalisés, `]0,1000]`.

## Plural et select, exécutés

Exécuté avec Translation 8.0.15 et l'extension intl :

| Message ICU | Résultat |
|---|---|
| `{n, plural, one {# fichier} other {# fichiers}}`, locale `fr` | `0 fichier`, `1,5 fichier`, `2 fichiers` |
| le même message en anglais | `0 files` |
| `=0 {aucun fichier}` placé avant `one` | `=0` l'emporte pour 0 |
| `select` ou `plural` sans `other` | `InvalidArgumentException` à chaque `trans()`, même pour une valeur prévue |
| `{gender, select, female {{n, plural, …}} other {…}}` | les deux s'imbriquent |
| `#` dans un `plural` imbriqué | le nombre du `plural` le plus intérieur |

Les catégories `one`, `few`… suivent les règles de la **locale** : en
français, 0 et 1,5 sont `one`. Une key de parameter `%name%` remplit `{name}` —
Symfony retire les pourcents —, mais `%name%` écrit dans un message ICU reste
du texte.

## L'object traduisible

`t('notification.welcome', {…}, 'domaine')` construit un `TranslatableMessage`,
qui transporte ses parameters et son domain et se traduit à l'affichage par
`|trans`, dans la locale courante — ou celle passée en deuxième argument.
Retourner une key brute, depuis une enum par exemple, perd parameters et
domain, et `translation:extract` ne la voit pas.

Il ne s'affiche **que** traduit : en 8.0, `TranslatableMessage` n'a plus de
`__toString()`. Exécuté contre 7.4.21 : `{{ message }}` y affichait la key
brute ; en 8.0.15 il lève une `RuntimeError`, et seul `{{ message|trans }}`
produit le texte.

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

## Points clés

- `|trans(paramètres, domaine, locale, count)` ; named arguments admis.
- `%nom%` est une convention pour le filter, une obligation pour le tag.
- Tag : `count`, `with`, `from`, `into`, dans cet ordre ; sortie non échappée.
- Pluriel : ICU, file suffixé `+intl-icu`, `{nom}` ; barres = *legacy*.
- `t()` produit un object traduit à l'affichage — par `|trans` seulement en 8.0.

## Sources officielles

- [Symfony Translation, translations dans les templates](https://github.com/symfony/symfony-docs/blob/8.0/translation.rst) et [ICU MessageFormat](https://github.com/symfony/symfony-docs/blob/8.0/reference/formats/message_format.rst)
- [Symfony Twig Reference, filter `trans`](https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst)
- [Twig Bridge 8.0, `TranslationExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/TranslationExtension.php), [`TransTokenParser`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/TokenParser/TransTokenParser.php) et [`TransNode`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Node/TransNode.php)
