---
lot: lot-21
verification_status: VERIFIED
reviewed_at: '2026-11-07'
---

## En une phrase

Filesystem agit sur les files et directories en levant une exception à la
moindre erreur, Path calcule des paths sans toucher au disque, et Finder
cherche des files selon des critères qu'on enchaîne.

## L'image de la vie réelle

Pense à un **déménagement**. Le déménageur qui porte, range et jette les
cartons, et qui te prévient à voix haute dès qu'un carton ne passe pas la
porte : c'est **Filesystem**. Le plan de l'appartement sur lequel on trace les
trajets, sans rien déplacer : c'est **Path**. Et la personne qui fouille les
cartons pour trouver « tous les livres de cuisine, en bas des étagères » :
c'est **Finder**.

## Notion par notion

### Filesystem

**C'est quoi ?** Le component qui crée, copie, déplace et supprime files et
directories, avec la class `Filesystem` pour agir et `Path` pour calculer.

**Dans la vraie vie** Le déménageur qui annonce un problème au lieu de laisser
un carton cassé dans le couloir sans rien dire.

**Pour l'examen** Une erreur lève une exception au lieu de rendre `false` en
silence. `mkdir()` crée aussi les directories parents. `dumpFile()` écrit de
façon atomique. `Path` ne touche jamais au disque.

### Finder

**C'est quoi ?** La class qui cherche files et directories par critères
chaînés : `in()`, `name()`, `size()`, `date()`…

**Dans la vraie vie** La liste de recherche qu'on complète : « dans le
salon », « les livres », « de cuisine ».

**Pour l'examen** `in()` est le seul critère obligatoire. Par défaut, le
Finder rend files et directories, récursivement. L'object garde ses critères :
un second `name()` s'ajoute au premier, et pour réutiliser une recherche, on
la clone.

## Les erreurs de débutant

- Tester un `false` après une opération de Filesystem au lieu d'attendre une
  exception.
- Croire que `Path` crée ou vérifie les files.
- Réutiliser le même Finder en croyant repartir de zéro.

## La fiche du lot

- **Filesystem** agit, **Path** calcule.
- Erreur = **exception**, jamais `false` silencieux.
- `mkdir()` **récursif** ; `dumpFile()` **atomique**.
- Finder : **`in()`** obligatoire.
- Par défaut : **files et directories**, récursif.
- Finder **à état** : on le **clone** pour réutiliser.
