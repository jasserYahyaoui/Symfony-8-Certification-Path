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
| une traduction | fichier de même nom dans `translations/` |

Il n'y a pas de mécanisme unique : chaque sous-système a le sien, et c'est
exactement ce que l'examen vérifie.

## Les gabarits

Un gabarit se surcharge en plaçant un fichier **de même nom et de même chemin
relatif** sous `templates/bundles/<NomDuBundle>/`. Pour un gabarit
`registration/confirmed.html.twig` d'`AcmeUserBundle`, le fichier devient
`templates/bundles/AcmeUserBundle/registration/confirmed.html.twig`.

Il faut parfois vider le cache après avoir ajouté un gabarit à un emplacement
nouveau, **même en mode debug**.

Le piège vient quand on ne veut surcharger qu'un bloc. Écrire
`{% extends "@AcmeUser/registration/confirmed.html.twig" %}` dans le gabarit qui
surcharge ce même gabarit produit une **boucle infinie** : le nom résout vers la
surcharge, c'est-à-dire vers lui-même. La solution est le préfixe spécial `!` :

```html
{% extends "@!AcmeUser/registration/confirmed.html.twig" %}
```

`@!` signifie « le gabarit **original**, pas la surcharge ».

## Les services

Pour modifier le comportement d'un service existant sans le remplacer, on le
**décore** : le nouveau service reçoit l'ancien en argument et l'enveloppe.

La **passe de compilation** est le niveau au-dessous : elle intervient pendant
la compilation du conteneur et permet des manipulations que la configuration ne
permet pas — supprimer une définition, en modifier une créée par un autre
bundle, ajouter un argument.

## La validation ne se surcharge pas

C'est l'exception à retenir : Symfony charge la configuration de validation de
**tous** les bundles et la fusionne en un seul arbre de métadonnées. On peut donc
**ajouter** une contrainte à une propriété, jamais en **remplacer** une. Le seul
contournement est que le bundle tiers ait prévu des groupes de validation.

## Points clés

- Gabarits : `templates/bundles/<NomDuBundle>/`, même chemin relatif.
- `@!` évite la boucle infinie quand on étend le gabarit que l'on surcharge.
- Services : décoration ; manipulations avancées : passe de compilation.
- Validation : les contraintes s'ajoutent, elles ne se remplacent pas.

## Sources officielles

- [How to Override any Part of a Bundle](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/bundles/override.rst)
