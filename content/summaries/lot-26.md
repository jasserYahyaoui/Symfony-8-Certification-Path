---
lot: lot-26
verification_status: VERIFIED
reviewed_at: '2026-11-07'
---

## En une phrase

Le Serializer transforme un object PHP en JSON, XML ou CSV et inversement, en
deux étapes : un normalizer en fait une structure simple, un encoder en fait du
texte.

## L'image de la vie réelle

Pense à **l'envoi d'un meuble en kit**. On démonte d'abord le meuble en pièces
numérotées : c'est la **normalization**, l'object devient un array. Puis on met
les pièces dans un carton étiqueté pour l'expédition : c'est l'**encoding**,
l'array devient du JSON. À l'arrivée, on fait l'inverse : on ouvre le carton,
puis on remonte le meuble.

## Notion par notion

### Serializer

**C'est quoi ?** Le component qui sérialise et désérialise des données, avec
des normalizers (object ⇄ array) et des encoders (array ⇄ format).

**Dans la vraie vie** Démonter, emballer, expédier ; puis déballer, remonter.

**Pour l'examen** C'est le normalizer qui décide de tout ce qui compte :
quelles properties sortent, sous quel nom, avec quelle valeur. Plusieurs
normalizers sont essayés, et le premier qui accepte l'object agit.
`#[Ignore]` retire une property partout ; les groups choisissent au cas par
cas.

## Les erreurs de débutant

- Confondre normalizer et encoder.
- Croire que l'encoder choisit les properties qui sortent.
- Oublier qu'une référence circulaire fait échouer la serialization par
  défaut.

## La fiche du lot

- **Normalizer** : object ⇄ array ; **encoder** : array ⇄ format.
- Le **normalizer** décide des properties et des noms.
- Le **premier** normalizer qui accepte agit.
- **`#[Ignore]`** partout, **groups** au cas par cas.
- Une date ou une enum se normalise en **string**.
