---
id: CRS-txp6tjhjtfy8
official_item: OIT-tsxxgp3ppj1n
title: "Form component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst"
    anchor: "the-data-transformation-lifecycle"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Comprendre ce que le composant Form fait réellement : convertir un objet
applicatif en champs HTML, et le chemin inverse. C'est ce modèle qui explique
tout le reste du lot.

## Le problème résolu

Un navigateur n'envoie que du texte. Une application manipule des objets. Le
composant tient les deux bouts : il construit les champs à partir d'un objet, et
reconstruit l'objet à partir de ce que le navigateur renvoie.

Il est **autonome** : utilisable hors du framework, comme HttpFoundation.

## Les trois couches de données

C'est le modèle central, et il porte trois noms qu'il faut savoir distinguer :

| Couche | Contenu |
|---|---|
| **Model data** | la donnée au format de l'application : un `DateTime`, un objet métier. C'est ce qu'on passe à `createForm()` et ce que rend `getData()` |
| **Normalized data** | une représentation intermédiaire, identique au modèle pour la plupart des types |
| **View data** | le format des champs HTML, essentiellement des chaînes, puisque c'est ce qu'un navigateur envoie |

L'exemple du `DateType` rendu en trois listes déroulantes les sépare nettement :

- modèle : un objet `DateTime` ;
- normalisée : `['year' => 2026, 'month' => 10, 'day' => 18]`, des entiers ;
- vue : `['year' => '2026', 'month' => '10', 'day' => '18']`, des chaînes.

## Les deux sens

**Au rendu** : donnée du modèle → transformateurs de modèle → donnée normalisée
→ transformateurs de vue → donnée de vue → HTML.

**À la soumission** : valeurs brutes de la requête → transformateurs de vue à
l'envers → donnée normalisée → transformateurs de modèle à l'envers → donnée du
modèle, réécrite dans l'objet.

Les mêmes transformateurs servent dans les deux sens ; c'est leur méthode
inverse qui est appelée à la soumission.

## Quand cela devient visible

La plupart du temps ces couches restent invisibles. Elles apparaissent dans deux
situations : quand un champ ne s'affiche pas ou ne se soumet pas comme prévu, et
quand on écrit un transformateur — l'item *Data transformers* de ce lot.

## Points clés

- Composant autonome ; il traduit entre objets et champs HTML.
- Trois couches : model, normalized, view.
- Le rendu descend model → view ; la soumission remonte view → model.
- `createForm()` reçoit la donnée du modèle, `getData()` la rend.
- La couche vue est textuelle parce qu'un navigateur n'envoie que du texte.

## Sources officielles

- [Forms, « The Data Transformation Lifecycle »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst)
