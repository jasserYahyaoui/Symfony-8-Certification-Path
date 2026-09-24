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
---

## Objectif

Traduire depuis un gabarit, gérer le pluriel et savoir ce que le filtre et la
balise font différemment. L'organisation des catalogues et la détection de la
locale appartiennent aux lots Internationalisation et Routing.

## Le filtre

```html
<h1>{{ 'page.title'|trans }}</h1>
<p>{{ 'user.greeting'|trans({'%name%': user.name}) }}</p>
<p>{{ 'checkout.total'|trans({}, 'store') }}</p>
<p>{{ 'legal.notice'|trans({}, 'messages', 'de') }}</p>
```

Dans le code de `TranslationExtension` (Twig Bridge 8.0), la signature est
`trans(arguments = [], domain = null, locale = null, count = null)` : **paramètres,
domaine, locale**, puis un compte qui renseigne `%count%`. Un domaine omis vaut
`messages`, une locale omise vaut la locale courante.

Twig accepte les **arguments nommés** : `|trans(domain: 'store')` — ou
`domain = 'store'`, l'autre séparateur admis par Twig 3.22 — évite le tableau
vide.

Le deuxième argument doit être un tableau. Une chaîne n'y est admise que si le
message est un objet traduisible : elle désigne alors la **locale**. Sur une
chaîne ordinaire, `|trans('store')` lève une `TypeError`.

## Les emplacements

Symfony remplace les emplacements par `strtr()`. Les pourcents de `%name%` sont
une **convention** : la documentation précise que `#name#` ou `{name}`
fonctionnent aussi, pourvu que la clé du tableau corresponde exactement.

## La balise

```html
{% trans with {'%name%': 'Fabien'} from 'app' into 'fr' %}Hello %name%{% endtrans %}
```

- `count`, `with`, `from`, `into`, **dans cet ordre** : `TransTokenParser` les
  lit ainsi, et un mot hors de place est une erreur de syntaxe.
- Dans la balise, la notation `%var%` est **obligatoire**. Un emplacement absent
  de `with` est lu dans le **contexte** : `%name%` vaut la variable `name`
  (`TransNode`).
- Le corps doit être du texte simple.
- Un pourcent littéral se double : `%percent%%%`.

`{% trans_default_domain 'app' %}` fixe le domaine du gabarit courant — et de
lui seul : un gabarit inclus garde `messages`, pour éviter les effets de bord.

## Échappé ou non

La documentation le souligne : la sortie du **filtre** est échappée, celle de la
**balise** ne l'est **pas**. Pour afficher du HTML traduit par le filtre, on
ajoute `|raw` après `|trans`.

## Le pluriel

La documentation 8.0 traite le pluriel — et le genre, et les formats propres à
une locale — par **ICU MessageFormat** :

- le fichier de catalogue porte le suffixe `+intl-icu` —
  `messages+intl-icu.en.yaml` ;
- les emplacements s'écrivent `{name}`, et `MessageFormatter` traite tous les
  messages du fichier.

La syntaxe à barres verticales — `'{0} Aucun résultat|one result|%count%
results'` — sélectionne d'après `%count%`. La documentation la qualifie de
syntaxe *legacy* ; elle fonctionne toujours et garde une chose qu'ICU n'a pas :
les intervalles personnalisés, `]0,1000]`.

## L'objet traduisible

`t('notification.welcome', {…}, 'domaine')` construit un `TranslatableMessage`,
qui transporte ses paramètres et son domaine et se traduit à l'affichage par
`|trans`, dans la locale courante — ou celle passée en deuxième argument.
Retourner une clé brute, depuis une énumération par exemple, perd paramètres et
domaine, et `translation:extract` ne la voit pas.

## Pièges d'examen

**L'ordre des arguments du filtre est paramètres, domaine, locale.** Les
arguments nommés évitent de le deviner.

**Une chaîne en deuxième argument** n'est une locale que pour un objet
traduisible ; ailleurs, c'est une erreur.

**Le filtre échappe, la balise non.**

**`trans_default_domain` ne traverse pas un `include`.**

**Dans la balise, un emplacement non fourni vient du contexte.**

**Accolades et pluriel** : sans le suffixe `+intl-icu`, `{name}` n'est qu'un
emplacement ordinaire — rien n'est pluralisé.

## Points clés

- `|trans(paramètres, domaine, locale, count)` ; arguments nommés admis.
- `%nom%` est une convention pour le filtre, une obligation pour la balise.
- Balise : `count`, `with`, `from`, `into`, dans cet ordre ; sortie non échappée.
- Pluriel : ICU, fichier suffixé `+intl-icu`, `{nom}` ; barres = *legacy*.
- `t()` produit un objet traduit à l'affichage.

## Sources officielles

- [Symfony Translation, traductions dans les gabarits](https://github.com/symfony/symfony-docs/blob/8.0/translation.rst) et [ICU MessageFormat](https://github.com/symfony/symfony-docs/blob/8.0/reference/formats/message_format.rst)
- [Symfony Twig Reference, filtre `trans`](https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst)
- [Twig Bridge 8.0, `TranslationExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/TranslationExtension.php), [`TransTokenParser`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/TokenParser/TransTokenParser.php) et [`TransNode`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Node/TransNode.php)
