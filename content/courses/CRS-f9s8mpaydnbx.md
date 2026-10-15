---
id: CRS-f9s8mpaydnbx
official_item: OIT-efzq92vdtayj
title: "Framework overloading"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/bundles/override.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/bundles/override.rst"
    anchor: "override-templates"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
outcome_sections:
  OUT-w0kft9548h99: "L'array"
  OUT-2r6g1ds91vvw: "Les templates"
  OUT-s8kw5gsv7y2e: "Les templates"
  OUT-97dtctsck41f: "La validation ne se surcharge pas"
---

## Objectif

Savoir quel mécanisme employer pour surcharger chaque partie d'un bundle tiers —
et pourquoi ils diffèrent.

## L'array

| Ce que l'on surcharge | Mécanisme |
|---|---|
| un template | file dans `templates/bundles/<NomDuBundle>/` |
| un service existant | **décoration** de service |
| supprimer ou manipuler une definition | **compiler pass** |
| un form type | **extension de form type** |
| une constraint de validation | validation groups — la constraint ne se remplace pas |
| une translation | file dans `translations/` portant le **même domain** |
| une route | ne pas l'importer du tout, ou copier le file et importer sa copie |
| un controller **non-service** | route de **même path**, chargée **avant** celle du bundle |
| un controller **qui est un service** | **décoration**, comme n'importe quel service |
| le mapping d'une entité | **uniquement** si le bundle expose une *mapped superclass* ; porte alors sur les **attributes** et les **associations** |

Dix lignes pour les **huit** sections du document officiel : décoration et passe
de compilation relèvent toutes deux de *Services & Configuration*, et le
controller se dédouble selon qu'il est ou non un service. Il n'y a pas de
mécanisme unique : chaque sous-système a le sien, et c'est exactement ce que
l'examen vérifie.

**Le routage est le cas à part.** Symfony n'importe **jamais** une route de
bundle automatiquement. « Surcharger » un routage revient donc à ne pas
l'importer : il n'y a rien à neutraliser.

## Les templates

Un file **de même nom et de même path relatif** sous
`templates/bundles/<NomDuBundle>/`. Pour `registration/confirmed.html.twig`
d'`AcmeUserBundle` :
`templates/bundles/AcmeUserBundle/registration/confirmed.html.twig`.

Il faut parfois vider le cache après avoir ajouté un template à un emplacement
nouveau, **même en mode debug**.

Le piège vient quand on ne veut surcharger qu'un block : écrire
`{% extends "@AcmeUser/registration/confirmed.html.twig" %}` dans le template qui
surcharge ce même template produit une **loop infinie** : le nom résout vers la
surcharge, c'est-à-dire vers lui-même. La solution est le préfixe spécial `!` :

```html
{% extends "@!AcmeUser/registration/confirmed.html.twig" %}
```

`@!` signifie « le template **original**, pas la surcharge ».

## Les services

On **décore** : le nouveau service reçoit l'ancien en argument et l'Envelope.

Le **compiler pass** est le niveau au-dessous — il intervient pendant
la compilation du container et permet ce que la configuration ne permet pas :
supprimer une definition, modifier celle d'un autre bundle, ajouter un argument.

## Le controller a deux cas

Le document ouvre sa section par une condition : **« si le controller est un
service, voir la section suivante »** — la décoration. La route de même path
n'est que la branche **sinon**. Or dans une application moderne, les controllers
sont des services autoconfigurés : c'est donc la première branche qui s'applique
le plus souvent.

## Les translations ne dépendent pas des bundles

Le document est explicite : elles sont liées à un **domain**, pas à un bundle.
Un file du `translations/` du projet surcharge donc celui d'un bundle dès
qu'il porte le même domain, sans rien déclarer — créer
`translations/AcmeUserBundle.es.yaml` à la racine suffit. Le nom du bundle
n'apparaît là que parce qu'il **est** le nom du domain.

## La validation ne se surcharge pas

Symfony charge la configuration de validation de **tous** les bundles et la
fusionne en un seul arbre de métadonnées. D'où l'exception : on **ajoute** une
constraint à une property, jamais on n'en **remplace**. Le contournement
suppose que le bundle ait prévu des **validation groups**.

## Pièges d'examen

**Étendre le template que l'on surcharge crée une loop infinie.** Le nom résout
vers la surcharge, donc vers lui-même. Le préfixe `@!` désigne l'original et
rompt le cycle.

**Une constraint de validation ne se remplace pas.** Symfony fusionne la
configuration de tous les bundles : on ajoute, on ne retire jamais. Le seul
contournement est un validation group prévu par le bundle.

**Décoration et compiler pass ne sont pas au même niveau.** La décoration
enveloppe un service existant ; supprimer ou modifier une definition demande une
compiler pass.

**Un contrôleur-service ne se surcharge pas par une route.** Le document
renvoie d'abord à la décoration ; la route de même path est la Response de
repli, pour un controller qui n'est pas un service.

**Une translation se surcharge par le domain, pas par le bundle.** Le nom du
bundle dans le nom de file n'est que le nom du domain.

## Tips d'examen

**Une question par sous-système, jamais de réponse universelle.** « Comment
surcharger X ? » n'a pas de Response unique : c'est la liste qu'il faut avoir.

**Deux exceptions à retenir par cœur** : la validation, qui ne se surcharge
pas, et le routage, qui ne s'importe jamais tout seul.

## Points clés

- Templates : `templates/bundles/<NomDuBundle>/`, même path relatif.
- `@!` évite la loop infinie quand on étend le template que l'on surcharge.
- Services : décoration ; manipulations avancées : compiler pass.
- Validation : les constraints s'ajoutent, elles ne se remplacent pas.
- Controller : décoration s'il est un service, route de même path sinon.
- Translations : par **domain**, depuis `translations/` du projet.
- Mapping : attributes et associations, et seulement via une *mapped superclass*.

## Sources officielles

- [How to Override any Part of a Bundle](https://github.com/symfony/symfony-docs/blob/8.0/bundles/override.rst)
