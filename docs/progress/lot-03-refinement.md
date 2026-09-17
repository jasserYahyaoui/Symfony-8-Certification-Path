# Raffinement pédagogique — Lot 03 (Architecture)

Journal de reprise, ouvert le 2026-09-17 après la clôture du lot 02. Même
méthode : relire la source avant d'écrire, faire porter le volume par les
flashcards là où `REV-001` plafonne le corps, et ajouter au smoke test de
production une aiguille discriminante par page.

## Ce qui change par rapport au lot 02

Le lot 02 était serré : six pages sur dix à moins de 100 mots de leur plafond,
`Cookies` à 3 mots. Le lot 03 ne l'est pas — les marges vont de **330 à
554 mots**. Les sections que le brief demande tiennent donc sans rien retirer,
et le corps du cours redevient un lieu d'écriture plutôt qu'un budget à défendre.

En contrepartie, c'est **15 pages au lieu de 10**, et plusieurs portent sur des
sujets dont la source est un texte de politique plutôt que du code — *Backward
compatibility promise*, *Release management*, *Official best practices*. La
vérification y est une lecture attentive, pas un `grep`.

## État par page (ordre de navigation)

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | HttpFoundation component | MINIMAL | 694 / 700 | 15 | **RAFFINÉE** (2026-09-17) |
| 2 | Symfony Flex | STANDARD | 478 / 900 | 1 | à faire |
| 3 | License | MINIMAL | 248 / 700 | 1 | à faire |
| 4 | Components and Bridges | STANDARD | 466 / 900 | 1 | à faire |
| 5 | Code organization | STANDARD | 543 / 900 | 1 | à faire |
| 6 | Request handling | DEEP | 646 / 1200 | 2 | à faire |
| 7 | Exception handling | STANDARD | 532 / 900 | 1 | à faire |
| 8 | Event dispatcher and kernel events | DEEP | 568 / 1200 | 1 | à faire |
| 9 | Official best practices | STANDARD | 517 / 900 | 1 | à faire |
| 10 | Backward compatibility promise | STANDARD | 587 / 900 | 2 | à faire |
| 11 | Deprecations best practices | STANDARD | 514 / 900 | 1 | à faire |
| 12 | Framework overloading | STANDARD | 636 / 900 | 1 | à faire |
| 13 | Release management and roadmap schedule | STANDARD | 596 / 900 | 2 | à faire |
| 14 | Framework interoperability and PSRs | STANDARD | 419 / 900 | 1 | à faire |
| 15 | Naming conventions | MINIMAL | 339 / 700 | 1 | à faire |

Chiffres relevés le 2026-09-17 par lecture du `ContentSet` chargé, pas repris
d'un rapport.

## Page 1 — HttpFoundation component, 2026-09-17

**Fait**

- 14 flashcards ajoutées (`FLC-97zy6sabcx93` … `FLC-y9tr0v3tmzyw`) ; la carte
  préexistante `FLC-p419ytejyj1a`, qui porte sur le sens de la dépendance, a
  reçu le niveau `UNDERSTANDING` et n'a pas été redoublée.
- `RequestStack.php` de la branche 8.0 relu le jour même. Trois faits que la
  page énonçait sans les fonder, et qui deviennent vérifiables :
  `getCurrentRequest()` est `end($requests)`, `getMainRequest()` est
  `$requests[0]`, `getParentRequest()` vise l'index `count - 2` — donc `null`
  sur la requête principale, ce qui est le cas **courant**.
- Deux faits que la page ne portait pas du tout : `getSession()` **lève** une
  `SessionNotFoundException` là où les trois autres accesseurs rendent `null`,
  et `getMainRequest()` comme `getParentRequest()` portent dans leur docblock
  l'avertissement « might make it un-compatible with other features of your
  framework like ESI support ».
- Une subtilité ajoutée parce qu'elle explique une classe entière de bugs :
  avec **une** sous-requête, `getMainRequest()` et `getParentRequest()` rendent
  la même valeur ; elles ne divergent qu'à partir de deux niveaux. Un code écrit
  et testé sur un seul niveau peut donc être faux sans que rien ne le montre.
- Sections ajoutées : *Les trois accesseurs, exactement*, *L'avertissement que
  portent deux de ces méthodes*, *`getSession()` ne rend pas `null`*, *Tips
  d'examen*. Corps : 370 → **694 mots** sur 700.

**Contrôles réellement exécutés le 2026-09-17**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** (à 712 puis 701 mots `REV-001` a bloqué ; réduit à 694) |
| `composer gate-full` | **exit 0** — 295 tests, 15 786 assertions ; `TOTAL VIOLATIONS: 0` |
| Aiguilles de smoke test | 4 ajoutées, vérifiées présentes dans la page construite et absentes de `master` |

## Prochaine étape

Page 2 — **Symfony Flex** (STANDARD, 478 / 900 mots, 1 flashcard, 422 mots de
marge).
