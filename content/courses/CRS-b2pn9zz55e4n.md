---
id: CRS-b2pn9zz55e4n
official_item: OIT-v7zyhcm44m88
title: "Form events"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/events.rst"
    anchor: "form-events"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Modifier un formulaire pendant son cycle de vie : ajouter un champ selon la
donnée, assainir une valeur brute, réagir après la soumission. C'est le
mécanisme qui rend un formulaire dynamique.

## Prérequis

Les trois couches de données du composant Form, et le dispatcher d'événements.

## Deux moments, cinq événements

Le cycle se divise en deux phases : la **mise en place** de la donnée initiale,
et la **soumission**.

### Mise en place

Les entrées numérotées sont les événements. Les lignes en retrait entre elles ne
sont pas des événements : ce sont les transformations que le composant effectue.

1. **`PRE_SET_DATA`** — la donnée du **modèle**, avant toute transformation. Les
   champs ne sont pas encore tous construits : **c'est ici qu'on en ajoute ou
   qu'on en retire** en fonction de la donnée initiale.

   *→ le composant calcule les trois représentations*

2. **`POST_SET_DATA`** — les trois représentations existent. Bon endroit pour
   décider en connaissant l'état complet, par exemple « l'objet est-il neuf ou
   existant ? ».

### Soumission

3. **`PRE_SUBMIT`** — la donnée **brute de la requête**, chaînes et tableaux, non
   transformée. C'est le moment pour assainir une valeur, ou pour ajouter des
   champs d'après ce que l'utilisateur a envoyé — le cas des listes dépendantes.

   *→ le composant transforme la vue en normalisée*

4. **`SUBMIT`** — la donnée **normalisée**. On peut encore changer les valeurs,
   mais **la structure est verrouillée** : à partir d'ici, plus aucun champ ne
   peut être ajouté ni retiré.

   *→ le composant transforme la normalisée en modèle*

5. **`POST_SUBMIT`** — la donnée entièrement transformée. La structure de *ce*
   formulaire est figée. **La validation s'exécute par un écouteur sur cet
   événement**, ce qui explique qu'un objet peuplé et validé soit disponible une
   fois la soumission terminée.

## Le tableau de décision

| Besoin | Événement |
|---|---|
| modifier la donnée initiale | `PRE_SET_DATA` |
| adapter la structure à la donnée initiale | `POST_SET_DATA` |
| assainir la donnée brute soumise | `PRE_SUBMIT` |
| ajouter des champs d'après la valeur soumise | `PRE_SUBMIT` sur le **parent**, ou `POST_SUBMIT` sur l'**enfant** |
| modifier la donnée normalisée | `SUBMIT` |
| réagir après coup, journaliser | `POST_SUBMIT` |

La question qui tranche entre `PRE_SET_DATA` et `PRE_SUBMIT` est celle que pose
la documentation : réagit-on à la donnée **initiale**, qui vient de l'objet, ou à
la donnée **soumise**, qui vient de la requête ?

## Les formulaires imbriqués

Un champ dépendant d'un autre ne peut pas s'ajouter depuis son propre
`POST_SUBMIT` au formulaire lui-même : sa structure est figée. On l'ajoute au
**formulaire parent**, depuis l'événement de l'enfant. C'est ce qui rend le motif
des listes dépendantes contre-intuitif à écrire.

## Comment s'abonner

Sur le constructeur, pour un cas local :

```php
$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
    $form = $event->getForm();
    $data = $event->getData();
});
```

Ou par une classe implémentant `EventSubscriberInterface`, réutilisable et
injectable.

## Pièges d'examen

- `PRE_SUBMIT` reçoit des **chaînes**, pas des objets.
- La structure est verrouillée **à partir de `SUBMIT`**, pas de `POST_SUBMIT`.
- La validation s'exécute sur `POST_SUBMIT` — elle n'est pas antérieure.
- Un champ dépendant s'ajoute au **parent**.
- `PRE_SET_DATA` porte la donnée du modèle, `PRE_SUBMIT` celle de la requête.

## Points clés

- Cinq événements, deux phases, un ordre fixe.
- Ajout et retrait de champs : `PRE_SET_DATA` ou `PRE_SUBMIT` uniquement.
- `SUBMIT` verrouille la structure ; `POST_SUBMIT` porte la validation.
- Le choix se fait sur l'origine de la donnée : objet ou requête.

## Sources officielles

- [Form Events](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/events.rst)
