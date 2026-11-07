---
lot: lot-19
verification_status: VERIFIED
reviewed_at: '2026-11-07'
---

## En une phrase

Le component Clock remplace la clock du système par un service qu'on injecte,
pour que le code qui dépend de l'heure puisse être testé à n'importe quelle
date.

## L'image de la vie réelle

Pense à un **décor de théâtre** avec une fausse pendule : pour la scène de
minuit, on règle les aiguilles sur minuit, et elles ne bougent plus jusqu'à ce
que le machiniste les déplace. En vrai, on regarde sa montre ; sur scène, on
regarde la pendule du décor. Le code fait pareil : en production il lit
l'heure réelle, en test une clock figée.

## Notion par notion

### Clock

**C'est quoi ?** Une `ClockInterface` injectée dans les services, avec trois
implémentations : `NativeClock` pour l'heure réelle, `MockClock` pour les
tests, `MonotonicClock` pour mesurer une durée.

**Dans la vraie vie** La montre du comédien et la pendule du décor : le même
geste, regarder l'heure, mais une source différente selon le contexte.

**Pour l'examen** On écrit `$this->clock->now()` au lieu de
`new \DateTimeImmutable()`. Une `MockClock` est figée : elle ne bouge jamais
seule, et son `sleep()` est instantané.

## Les erreurs de débutant

- Écrire `new \DateTimeImmutable()` dans un service qu'on veut tester.
- Croire qu'une `MockClock` avance toute seule.

## La fiche du lot

- But : **découpler** le code de l'heure système.
- **`NativeClock`** prod, **`MockClock`** test, **`MonotonicClock`** durée.
- **`now()`** au lieu de `new \DateTimeImmutable()`.
- `MockClock` **figée** ; `sleep()` instantané.
- La clock s'**injecte**, comme un service.
