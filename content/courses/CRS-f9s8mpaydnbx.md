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
---

## Objectif

Savoir quel mécanisme employer pour surcharger chaque partie d'un bundle tiers —
et pourquoi ils diffèrent.

## Le tableau

| Ce que l'on surcharge | Mécanisme |
|---|---|
| un gabarit | fichier dans `templates/bundles/<NomDuBundle>/` |
| un service existant | **décoration** de service |
| supprimer ou manipuler une définition | **passe de compilation** |
| un type de formulaire | **extension de type de formulaire** |
| une contrainte de validation | groupes de validation — la contrainte ne se remplace pas |
| une traduction | fichier dans `translations/` portant le **même domaine** |
| une route | ne pas l'importer du tout, ou copier le fichier et importer sa copie |
| un contrôleur **non-service** | route de **même chemin**, chargée **avant** celle du bundle |
| un contrôleur **qui est un service** | **décoration**, comme n'importe quel service |
| le mapping d'une entité | **uniquement** si le bundle expose une *mapped superclass* ; porte alors sur les **attributs** et les **associations** |

Dix lignes pour les **huit** sections du document officiel : décoration et passe
de compilation relèvent toutes deux de *Services & Configuration*, et le
contrôleur se dédouble selon qu'il est ou non un service. Il n'y a pas de
mécanisme unique : chaque sous-système a le sien, et c'est exactement ce que
l'examen vérifie.

**Le routage est le cas à part.** Symfony n'importe **jamais** une route de
bundle automatiquement. « Surcharger » un routage revient donc à ne pas
l'importer : il n'y a rien à neutraliser.

## Les gabarits

Un fichier **de même nom et de même chemin relatif** sous
`templates/bundles/<NomDuBundle>/`. Pour `registration/confirmed.html.twig`
d'`AcmeUserBundle` :
`templates/bundles/AcmeUserBundle/registration/confirmed.html.twig`.

Il faut parfois vider le cache après avoir ajouté un gabarit à un emplacement
nouveau, **même en mode debug**.

Le piège vient quand on ne veut surcharger qu'un bloc : écrire
`{% extends "@AcmeUser/registration/confirmed.html.twig" %}` dans le gabarit qui
surcharge ce même gabarit produit une **boucle infinie** : le nom résout vers la
surcharge, c'est-à-dire vers lui-même. La solution est le préfixe spécial `!` :

```html
{% extends "@!AcmeUser/registration/confirmed.html.twig" %}
```

`@!` signifie « le gabarit **original**, pas la surcharge ».

## Les services

On **décore** : le nouveau service reçoit l'ancien en argument et l'enveloppe.

La **passe de compilation** est le niveau au-dessous — elle intervient pendant
la compilation du conteneur et permet ce que la configuration ne permet pas :
supprimer une définition, modifier celle d'un autre bundle, ajouter un argument.

## Le contrôleur a deux réponses

Le document ouvre sa section par une condition : **« si le contrôleur est un
service, voir la section suivante »** — la décoration. La route de même chemin
n'est que la branche **sinon**. Or dans une application moderne, les contrôleurs
sont des services autoconfigurés : c'est donc la première branche qui s'applique
le plus souvent.

## Les traductions ne dépendent pas des bundles

Le document est explicite : elles sont liées à un **domaine**, pas à un bundle.
Un fichier du `translations/` du projet surcharge donc celui d'un bundle dès
qu'il porte le même domaine, sans rien déclarer — créer
`translations/AcmeUserBundle.es.yaml` à la racine suffit. Le nom du bundle
n'apparaît là que parce qu'il **est** le nom du domaine.

## La validation ne se surcharge pas

Symfony charge la configuration de validation de **tous** les bundles et la
fusionne en un seul arbre de métadonnées. D'où l'exception : on **ajoute** une
contrainte à une propriété, jamais on n'en **remplace**. Le contournement
suppose que le bundle ait prévu des **groupes de validation**.

## Pièges d'examen

**Étendre le gabarit que l'on surcharge crée une boucle infinie.** Le nom résout
vers la surcharge, donc vers lui-même. Le préfixe `@!` désigne l'original et
rompt le cycle.

**Une contrainte de validation ne se remplace pas.** Symfony fusionne la
configuration de tous les bundles : on ajoute, on ne retire jamais. Le seul
contournement est un groupe de validation prévu par le bundle.

**Décoration et passe de compilation ne sont pas au même niveau.** La décoration
enveloppe un service existant ; supprimer ou modifier une définition demande une
passe de compilation.

**Un contrôleur-service ne se surcharge pas par une route.** Le document
renvoie d'abord à la décoration ; la route de même chemin est la réponse de
repli, pour un contrôleur qui n'est pas un service.

**Une traduction se surcharge par le domaine, pas par le bundle.** Le nom du
bundle dans le nom de fichier n'est que le nom du domaine.

## Tips d'examen

**Une question par sous-système, jamais de réponse universelle.** « Comment
surcharger X ? » n'a pas de réponse unique : c'est la liste qu'il faut avoir.

**Deux exceptions à retenir par cœur** : la validation, qui ne se surcharge
pas, et le routage, qui ne s'importe jamais tout seul.

## Points clés

- Gabarits : `templates/bundles/<NomDuBundle>/`, même chemin relatif.
- `@!` évite la boucle infinie quand on étend le gabarit que l'on surcharge.
- Services : décoration ; manipulations avancées : passe de compilation.
- Validation : les contraintes s'ajoutent, elles ne se remplacent pas.
- Contrôleur : décoration s'il est un service, route de même chemin sinon.
- Traductions : par **domaine**, depuis `translations/` du projet.
- Mapping : attributs et associations, et seulement via une *mapped superclass*.

## Sources officielles

- [How to Override any Part of a Bundle](https://github.com/symfony/symfony-docs/blob/8.0/bundles/override.rst)
