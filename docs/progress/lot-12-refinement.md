# Raffinement pédagogique — Lot 12 (Console)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 11 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; quand la documentation et le
code divergent, le code l'emporte et l'écart est signalé sur la page ; budget
`REV-001` respecté sans promotion de niveau ; une branche, une PR, une CI verte,
une fusion et un smoke test de production **lu** par page. Le bac à sable
d'exécution a tous ses composants `symfony/*` fixés en `8.0.*` ; Console y est
en 8.0.15. Les exécutions passent par l'`Application` du composant, dans le
processus, ou par un vrai processus PHP quand le code de sortie compte.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-02 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `2435827`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Console component | STANDARD | 454 / 900 | 1 | à faire |
| 2 | Built-in commands | MINIMAL | 302 / 700 | 1 | à faire |
| 3 | Custom commands | STANDARD | 443 / 900 | 1 | à faire |
| 4 | Configuration | STANDARD | 519 / 900 | 1 | à faire |
| 5 | Options and arguments (using PHP attributes) | STANDARD | 711 / 900 | 1 | à faire |
| 6 | Input and Output objects | STANDARD | 543 / 900 | 1 | à faire |
| 7 | Built-in helpers | STANDARD | 550 / 900 | 1 | à faire |
| 8 | Console events | STANDARD | 578 / 900 | 1 | à faire |
| 9 | Verbosity levels | MINIMAL | 295 / 700 | 1 | à faire |

## Page 1 — *Console component* — RAFFINÉE

`CRS-brn6frfqpxxj` · `OIT-481gmkgbksnr` · STANDARD · **454 → 750 mots** sur 900.
Aucun niveau promu. Exécutions sur Console 8.0.15 : une commande classique qui
journalise son cycle, une commande invocable avec `#[Interact]`, puis un vrai
processus PHP pour lire les codes de sortie et le comportement sans terminal.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 11 (PR #287, `2435827`) | 36996388255, success | `ok  lot-11  the events page carries its four flashcard levels, the refused handling, the eleventh event and the retry priority` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### Une affirmation fausse et une question bâtie dessus : « sous cron, `interact()` est sautée »

La page écrivait que `interact()` n'est pas appelée avec `--no-interaction` « ou
une exécution par `cron` ». Lu dans `Application::configureIO()` (8.0) : seules
les options `-n`, `-q` et `--silent` rendent l'entrée non interactive — aucun
test du terminal. Exécuté sans TTY, entrée standard vide :

| Lancement | `interact()` | Valeur obtenue |
|---|---|---|
| sans option, entrée standard vide | appelée | la valeur par défaut de la question |
| sans option, entrée standard `bob` | appelée | `bob` |
| `-n` | sautée | rien |
| `-q` | sautée | rien |

**`QST-zh43s7yzg7nr` (LEARNING) → v2.** Son énoncé posait « run from cron, it
uses an empty value instead of asking » : la situation décrite ne se produit
pas. Énoncé réécrit avec `--no-interaction` ; le distracteur sur `cron`
remplacé (``CHO-25fmfr5nrntq``) ; la bonne réponse, juste, est conservée et n'est pas la
plus longue.

### Une affirmation incomplète : l'interaction réservée à `Command`

La page disait qu'une commande invocable qui n'étend pas `Command` ne dispose
ni d'`initialize()` ni d'`interact()`. Vrai pour ces méthodes, mais la
documentation 8.0 (« Interactive Input ») et `InvokableCommand` lui donnent
`#[Ask]` et `#[Interact]`. Exécuté : la méthode `#[Interact]` tourne avant
`__invoke()`, et elle est sautée avec `-n`.

### Confirmé par l'exécution

| La commande… | Code de sortie, vrai processus |
|---|---|
| retourne `300` | 255 |
| lève une exception de code `3` | 3 |
| lève une exception de code `0` ou `-5` | 1 |
| `__invoke(): void` | 255 — `TypeError` non rattrapée |

- `configure()` tourne dans le constructeur : `ctor-start`, `configure`,
  `ctor-end`.
- Ordre interactif : `initialize`, `interact`, `execute` ; avec `-n` :
  `initialize`, `execute`.

**Questions.** `QST-s9dqp9mfqfrx`, `QST-kk4evk9seycg` (LEARNING) et
`QST-4101g4r1pw8x` (VALIDATION) relues : exactes, inchangées. Aucune question
holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-1geh3bkk61mh` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`configureIO`, `Interact]` et `TypeError`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions Console 8.0.15 (dans le processus et vrai processus PHP) | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 281 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 2 — *Built-in commands* (MINIMAL, 302 / 700).
