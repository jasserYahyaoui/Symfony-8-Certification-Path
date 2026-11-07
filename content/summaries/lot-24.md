---
lot: lot-24
verification_status: VERIFIED
reviewed_at: '2026-11-07'
---

## En une phrase

PropertyAccess lit et écrit une valeur à l'aide d'un path écrit en texte :
des crochets pour un array, un point pour la property d'un object, en passant
par les getters et les setters quand ils existent.

## L'image de la vie réelle

Pense à une **adresse postale décomposée** : « pays, puis ville, puis rue ».
Avec des **crochets**, tu ouvres une case d'un classeur (`[ville]`) ; avec un
**point**, tu frappes à la porte d'une maison et tu demandes à l'habitant
(`.adresse`), qui te répond lui-même. Si la case du classeur est vide, tu ne
trouves rien ; si personne n'habite la maison, on te le dit franchement.

## Notion par notion

### PropertyAccess

**C'est quoi ?** Le component qui lit et écrit une valeur désignée par un path,
comme `[address][city]` pour un array ou `address.city` pour un object.

**Dans la vraie vie** Le livreur qui suit l'adresse étape par étape, et passe
par l'accueil (le getter) plutôt que d'entrer par la fenêtre.

**Pour l'examen** Un index absent dans un array rend `null`, mais une property
absente sur un object lève une exception : deux défauts opposés. Avant de lire
une property directement, PropertyAccess essaie les accessors (`get`, `is`,
`has`).

## Les erreurs de débutant

- Utiliser un point sur un array, ou des crochets sur un object.
- Croire qu'un path absent rend toujours `null`.

## La fiche du lot

- **`[index]`** pour un array, **`.property`** pour un object.
- Absent : array → **`null`**, object → **exception**.
- Les **accessors** passent avant la property publique.
- `isReadable()` et `isWritable()` rendent un **bool**.
- Le builder du component peut **renverser** les défauts.
