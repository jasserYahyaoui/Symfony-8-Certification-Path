---
id: CRS-v1808qnchx6g
official_item: OIT-3dd7821h069b
title: "Internationalization and localization (Note: Intl component utilities to access ICU data are not included)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/translation.rst"
    anchor: "translation-fallback"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Traduire un message selon la locale de l'utilisateur, et savoir exactement quel
fichier Symfony va lire — c'est là que se jouent les surprises.

## Périmètre

L'énoncé officiel le précise : **les utilitaires d'accès aux données ICU du
composant Intl ne sont pas inclus**. Cette page ne traite donc pas des classes
qui listent pays, langues ou devises.

Elle traite en revanche **ICU MessageFormat**, qui appartient à la traduction et
non à ces utilitaires.

## Prérequis

La détection de la locale à partir de la requête, et la locale de route.

## Deux mots à ne pas confondre

**Internationalisation** (*i18n*) : sortir de l'application les chaînes et tout
ce qui dépend de la locale, pour les placer dans une couche traduisible.
**Localisation** : fournir les valeurs pour une locale donnée.

La **locale** est la langue et le pays. La forme recommandée est le code langue
ISO 639-1, un souligné, le code pays ISO 3166-1 alpha-2 : `fr_FR`.

## Traduire

```php
$translator->trans('Hello World');
$translator->trans('say_hello', ['%name%' => 'Fabien']);
$translator->trans('Hello World', domain: 'app');
```

Les messages se rangent en **domaines** ; le domaine par défaut est `messages`.

Le point important sur `trans()` : **si le message n'est pas dans le catalogue,
la chaîne d'origine est retournée**. Aucune erreur, aucun message vide. Une
traduction manquante est donc silencieuse — le symptôme est une clé affichée
telle quelle.

## Le nom du fichier n'est pas décoratif

Chaque fichier suit le motif **`domaine.locale.chargeur`** :

```text
messages.fr_FR.yaml
admin.en.xlf
```

- **domaine** — le groupe de messages ;
- **locale** — `en`, `en_GB`… ;
- **chargeur** — l'extension choisit l'analyseur : `yaml`, `xlf`, `php`, `csv`,
  `json`, `ini`, `po`, `mo`…

La documentation recommande YAML pour un projet simple et XLIFF lorsque des
traducteurs ou des outils spécialisés interviennent.

Symfony lit d'abord le répertoire **`translations/` du projet**, puis celui des
bundles. La priorité du projet est ce qui permet de **redéfinir** la traduction
d'un bundle — et la redéfinition se fait **clé par clé** : seules les clés
surchargées ont besoin d'être présentes dans le fichier prioritaire.

Un **nouveau** catalogue exige un `cache:clear` pour être découvert.

## Les deux syntaxes d'espace réservé

C'est la distinction la plus testable de l'item.

| | Format classique | ICU MessageFormat |
|---|---|---|
| Fichier | `messages.en.yaml` | `messages+intl-icu.en.yaml` |
| Écriture | `Hello %name%!` | `Hello {name}!` |

Le suffixe **`+intl-icu`** dans le nom du domaine est ce qui déclenche le
traitement par `MessageFormatter`. Sans lui, `{name}` reste littéral.

ICU apporte ce que le format classique ne sait pas faire — le pluriel :

```text
num_of_apples: >-
    {apples, plural,
        =0    {There are no apples}
        =1    {There is one apple}
        other {There are # apples!}
    }
```

`#` reprend le nombre. `=0` cible une valeur **exacte** ; `one`, `few`, `many`,
`other` sont des **catégories linguistiques** qui diffèrent selon la langue :
l'anglais n'a que `one` et `other`, le russe en a quatre.

## Dans un gabarit

```twig
{{ message|trans }}
{{ message|trans({'%name%': 'Fabien'}, 'app') }}
{% trans %}Hello %name%{% endtrans %}
{% trans_default_domain 'app' %}
```

Le filtre traduit une **expression** ; la balise, un **bloc statique**. La
notation `%var%` est **obligatoire** avec la balise. Les messages traduits sont
échappés par défaut ; `|raw` après `|trans` lève l'échappement.

`trans_default_domain` ne vaut que pour le gabarit courant, jamais pour les
gabarits inclus.

## La cascade de repli

Locale `es_AR`, clé absente. Symfony essaie, dans cet ordre :

1. `es_AR` — la locale demandée ;
2. **la locale parente**, définie automatiquement pour certaines locales
   seulement — ici `es_419`, espagnol d'Amérique latine ;
3. `es` — la langue seule ;
4. l'option **`fallbacks`**, qui vaut `default_locale` si elle n'est pas
   configurée.

L'étape 2 est celle qu'on oublie : le repli n'est pas un simple « pays puis
langue ».

## Pièges d'examen

**Un message non traduit est retourné tel quel**, sans erreur.

**`+intl-icu` est un suffixe de nom de fichier**, pas une option de
configuration.

**`%name%` en format classique, `{name}` en ICU** — les deux ne se mélangent
pas.

**Le répertoire `translations/` du projet prime** sur celui des bundles, clé
par clé.

**La locale parente s'intercale** dans le repli.

**`trans_default_domain` ne franchit pas un `include`.**

## Points clés

- `domaine.locale.chargeur` : le nom du fichier décide de tout.
- Projet prioritaire sur bundles, redéfinition clé par clé.
- `%name%` classique contre `{name}` ICU, ce dernier via `+intl-icu`.
- ICU apporte le pluriel, avec catégories propres à chaque langue.
- Repli : locale, locale parente, langue, `fallbacks`.

## Sources officielles

- [Translations](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/translation.rst)
- [ICU MessageFormat](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/formats/message_format.rst)
