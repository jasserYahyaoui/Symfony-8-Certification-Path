---
lot: lot-25
verification_status: VERIFIED
reviewed_at: '2026-11-07'
---

## En une phrase

Avec le component Runtime, le file d'entrée `public/index.php` ne démarre plus
l'application lui-même : il rend une function, et c'est le runtime qui
l'appelle, lui fournit ses arguments et exécute ce qu'elle rend.

## L'image de la vie réelle

Pense à un **chef d'orchestre et sa partition**. Le musicien ne décide pas
quand commencer : il remet sa partition (la function) au chef (le **runtime**),
qui choisit le moment, lui donne ce dont il a besoin — le pupitre, la lumière,
ce sont les **arguments** — et lance le morceau, qu'il s'agisse d'un concert web
ou d'une répétition en console.

## Notion par notion

### Runtime

**C'est quoi ?** Le component qui sort l'amorçage du file d'entrée :
`index.php` rend une closure, que le runtime appelle avant d'exécuter son
résultat — un kernel HTTP ou une application de console.

**Dans la vraie vie** La partition remise au chef : le musicien ne lève pas sa
propre baguette.

**Pour l'examen** Le script d'entrée s'exécute deux fois : il ne doit avoir
aucun effet de bord au-dessus du `require_once`. Les arguments de la closure,
comme `$context` ou `$request`, sont reconnus par leur type et leur nom.

## Les erreurs de débutant

- Écrire du code avec effet de bord dans `index.php`, au-dessus du
  `require_once`.
- Croire que `index.php` démarre lui-même le kernel.

## La fiche du lot

- `index.php` **rend une closure**.
- Le **runtime** l'appelle et exécute son résultat.
- Le script d'entrée tourne **deux fois**.
- Arguments reconnus par **type et nom**.
- Même mécanique pour le **web** et la **console**.
