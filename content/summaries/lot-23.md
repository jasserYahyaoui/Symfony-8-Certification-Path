---
lot: lot-23
verification_status: VERIFIED
reviewed_at: '2026-11-07'
---

## En une phrase

Le component Process lance un programme externe dans un sous-processus, en
s'occupant de l'escaping des arguments, et permet d'attendre sa fin, de le
laisser tourner en arrière-plan ou de l'arrêter.

## L'image de la vie réelle

Pense à **un prestataire que tu appelles**. Tu peux lui donner une liste de
consignes précise, case par case (un **array**), ou lui dicter une phrase
qu'il interprète à sa façon (une **string**, passée au shell). Tu peux rester
au téléphone jusqu'à ce qu'il ait fini (`run()`), ou raccrocher et le rappeler
plus tard (`start()`). Et s'il ne donne plus de nouvelles depuis trop
longtemps, tu mets fin à la mission : c'est le **timeout**.

## Notion par notion

### Process

**C'est quoi ?** La class `Process`, qui exécute un programme externe et
remplace `exec()`, `shell_exec()` ou `system()`.

**Dans la vraie vie** Le coup de fil au prestataire : tu attends le compte
rendu, ou tu le laisses travailler et tu reviens aux nouvelles.

**Pour l'examen** La command en array est recommandée ; en string, elle passe
par le shell et l'escaping est à ta charge. `run()` attend et rend l'exit code, `mustRun()` lève une exception en cas d'échec, `start()` rend la main
tout de suite. Le délai par défaut est de 60 secondes.

## Les erreurs de débutant

- Construire une command en string avec des données du user.
- Oublier qu'un process lancé par `start()` doit encore être vérifié.

## La fiche du lot

- Command en **array** de préférence.
- **`run()`** attend, **`mustRun()`** lève, **`start()`** rend la main.
- Timeout par défaut : **60 secondes**.
- Remplace **`exec()`**, **`shell_exec()`**, **`system()`**.
- `stop()` envoie un signal d'arrêt, puis force si besoin.
