---
lot: lot-18
verification_status: VERIFIED
reviewed_at: '2026-11-07'
---

## En une phrase

Le component Cache garde dans le code des valeurs coûteuses à calculer : on
demande une valeur avec une function qui sait la produire, et le cache ne
l'appelle que si la valeur manque.

## L'image de la vie réelle

Pense à un **post-it sur le frigo**. Plutôt que de recalculer chaque matin le
trajet le plus rapide, tu l'écris une fois sur un post-it. Le lendemain, tu lis
le post-it ; s'il n'y en a pas, tu fais le calcul et tu écris le post-it. Le
frigo est le **pool**, le post-it est l'**item**, et « faire le calcul s'il
manque », c'est le callable passé à `get()`.

## Notion par notion

### Cache

**C'est quoi ?** Le component qui stocke des valeurs dans des pools, avec deux
API : les Cache Contracts, recommandées, et PSR-6.

**Dans la vraie vie** Le post-it : on le lit s'il existe, on le refait s'il
manque, on le jette quand l'information change.

**Pour l'examen** Les Cache Contracts n'ont que `get()` et `delete()` : pas de
`set()`, car `get()` reçoit le callable qui calcule la valeur au besoin. Une
valeur `null` en cache n'est pas une absence. `cache.app` sert aux données de
l'application, `cache.system` à ce qui dérive du code.

## Les erreurs de débutant

- Chercher une method `set()` dans les Cache Contracts.
- Croire qu'une valeur `null` veut dire « pas en cache ».
- Ranger des données métier dans `cache.system`.

## La fiche du lot

- **Pool** = le stockage, **item** = une valeur.
- Cache Contracts : **`get()`** et **`delete()`**, pas de `set()`.
- Le callable de `get()` calcule **au manque**.
- **`null`** n'est pas une absence.
- **`cache.app`** pour les données, **`cache.system`** pour le code.
