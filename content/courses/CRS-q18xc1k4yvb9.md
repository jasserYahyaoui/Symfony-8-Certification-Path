---
id: CRS-q18xc1k4yvb9
official_item: OIT-sd08j04k60m0
title: "Translations and pluralization"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/translation.rst"
    anchor: "translations-in-templates"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst"
    anchor: "trans"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Traduire depuis un gabarit et gérer le pluriel. L'organisation des catalogues et
la détection de la locale appartiennent aux lots Internationalisation et
Routing.

## Le filtre

```html
<h1>{{ 'page.title'|trans }}</h1>
<p>{{ 'user.greeting'|trans({'%name%': user.name}) }}</p>
<p>{{ 'checkout.total'|trans({}, 'store') }}</p>
<p>{{ 'legal.notice'|trans({}, 'messages', 'de') }}</p>
```

La signature est `|trans(arguments = [], domain = null, locale = null)`. L'ordre
des trois arguments se retient mal : **paramètres, domaine, locale**. Un domaine
omis vaut `messages`, une locale omise vaut celle de la requête.

Les paramètres traditionnels s'écrivent entourés de pourcents — `%name%` — et
c'est cette forme qui apparaît dans le catalogue.

## La balise

`{% trans %}Bonjour %name%{% endtrans %}` fait la même chose, avec `with`,
`from` et `into` pour les paramètres, le domaine et la locale :

```html
{% trans with {'%name%': 'Fabien'} from 'app' into 'fr' %}Hello %name%{% endtrans %}
```

`{% trans_default_domain 'app' %}` fixe le domaine par défaut du gabarit entier.

Un pourcent littéral se double : `{% trans %}Percent: %percent%%%{% endtrans %}`.

## Le pluriel

C'est le point où Symfony 8 diffère des versions anciennes. La forme
recommandée est **ICU MessageFormat** :

- le fichier de catalogue porte le suffixe `+intl-icu` —
  `messages+intl-icu.en.yaml` ;
- les emplacements s'écrivent `{name}`, avec des accolades, et non `%name%`.

ICU couvre bien plus que le nombre : il sélectionne aussi sur le genre ou sur
des règles propres à la locale.

La syntaxe historique à barres verticales —
`'{0} Aucun résultat|one result|%count% results'` — fonctionne toujours et
sélectionne d'après `%count%`, mais ce n'est plus la voie recommandée.

## L'objet traduisible

`t('notification.welcome')` construit un `TranslatableMessage` que l'on peut
passer d'un contrôleur à un gabarit et traduire à l'affichage par `|trans`. La
traduction se fait alors au dernier moment, quand la locale est connue.

## Points clés

- `|trans(paramètres, domaine, locale)` — dans cet ordre.
- Domaine par défaut `messages` ; `trans_default_domain` le change par gabarit.
- Paramètres classiques en `%nom%` ; ICU en `{nom}`.
- Pluriel recommandé : ICU, fichier suffixé `+intl-icu`.
- `t()` produit un objet traduit à l'affichage.

## Sources officielles

- [Symfony Translation, traductions dans les gabarits](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/translation.rst)
- [Symfony Twig Reference, filtre `trans`](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst)
