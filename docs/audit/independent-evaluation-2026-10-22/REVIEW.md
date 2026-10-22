# Évaluation indépendante — tentative du 2026-10-22

## Relecteur et méthode

**Relecteur :** M365 Copilot, sur un modèle de raisonnement GPT-5, sollicité par
le propriétaire avec un prompt rédigé ici (liens vers le site, le dépôt au
commit `800278b`, le dossier du relecteur, le suivi, le syllabus, les sources
Symfony 8.0).

**Ce qu'il a pu faire :** aucun des onze liens tentés n'a rendu de contenu
exploitable dans son environnement (dépôt GitHub, fichiers bruts, API GitHub,
pages du site). Il n'a donc lu ni le dossier, ni un cours, ni une question ; il
n'a rien exécuté.

## Verdict

**NON ÉVALUABLE — aucune note.** Le relecteur l'écrit lui-même : produire
« VALIDÉ » ou une note aurait été artificiel. Il n'attribue aucune anomalie au
site, ne pouvant dire si l'échec d'accès vient du site, du dépôt ou de son
environnement. Il déclare n'avoir ouvert aucun des contenus réservés au Mock 4.

Ce rapport ne compte donc pas comme l'évaluation indépendante de la mission :
elle reste `MISSING`.

## Ce que la tentative a appris au projet

1. **Un relecteur peut ne pas naviguer.** D'où `tools/review/build_pack.py` :
   des fichiers à joindre, le projet d'un côté, la référence Symfony 8.0 de
   l'autre.
2. **Le dossier se trompait sur le HOLDOUT.** Il ne nommait que
   `mock-04-holdout.yml`, et le prompt autorisait `golden-slice.yml` et les
   fichiers `lot-*.yml`. Le garde de `build_pack.py`, à sa première
   construction, a refusé le paquet : 27 questions HOLDOUT sont rangées dans les
   fichiers des lots 01 à 13 et de la tranche de référence, et la matrice cite
   leurs identifiants. Rien n'a été exposé — le relecteur n'a rien pu ouvrir —
   mais un relecteur qui navigue l'aurait pu. Le dossier interdit désormais les
   fichiers bruts de questions et renvoie au paquet, qui les en retire et le
   prouve.

## Suite

Une nouvelle tentative avec les deux paquets joints. Le rapport suivant ira dans
`docs/audit/independent-evaluation-<date>/REVIEW.md`.
