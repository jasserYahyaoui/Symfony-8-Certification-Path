# Raffinement pédagogique — Lot 14 (Miscellaneous)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 13 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; quand la documentation et le
code divergent, le code l'emporte et l'écart est signalé sur la page ; budget
`REV-001` respecté sans promotion de niveau ; une branche, une PR, une CI verte,
une fusion et un smoke test de production **lu** par page. Bac à sable
d'exécution : celui du lot 13 (paquets `symfony/*` en 8.0), plus un serveur web
PHP intégré pour observer une réponse réelle.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-02 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `4a2a2c4`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Configuration (including DotEnv and ExpressionLanguage components) | STANDARD | 598 / 900 | 1 | à faire |
| 2 | Error handling | STANDARD | 534 / 900 | 1 | à faire |
| 3 | Code debugging | STANDARD | 589 / 900 | 1 | à faire |

## Page 1 — *Configuration (including DotEnv and ExpressionLanguage components)* — RAFFINÉE

`CRS-b7wpm5anh7c6` · `OIT-tvc5rjv6qvse` · STANDARD · **598 → 897 mots** sur 900.
Aucun niveau promu. Exécutions sur Dotenv, DependencyInjection et
ExpressionLanguage 8.0.15 : quatre fichiers `.env` chargés en `dev` puis en
`test`, un `.env.local.php`, un conteneur compilé avec des processeurs, et
`lint()` sous ses drapeaux.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 13 (PR #307, `4a2a2c4`) | 37059772856, success | `ok  lot-13  the deprecated code page carries its four flashcard levels, the markers recap, the debug toolbar and the silenced notice` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### Une question VALIDATION au distracteur à moitié vrai

`QST-hs62x7n8hq15` proposait « the parameter is typed, the environment variable
is not » comme distracteur, expliqué par « parameters are not typed either ».
Or un paramètre garde le type PHP de sa valeur, et une variable d'environnement
est une chaîne tant qu'un processeur ne la convertit pas : le distracteur était
en partie vrai. **→ v2** : remplacé par « Only the variable can differ per
environment », faux — exécuté : un paramètre surchargé sous `when@prod` vaut
`prod-value` en `prod` et `base` en `dev`. Nouvel identifiant de choix ;
énoncé et bonne réponse inchangés.

### Ce que la page n'affirmait pas

| Exécuté | Résultat |
|---|---|
| clé dans `.env`, `.env.local`, `.env.dev` | `dev` : `.env.dev` ; `test` : `.env` |
| clé dans `.env`, `.env.local` | `dev` : `.env.local` ; `test` : `.env` |
| `.env.local.php` présent | les fichiers `.env` ne sont plus lus |
| `SQ='lit ${E}'`, `DQ="int ${E}"`, `${NOPE:-def}` | `lit ${E}`, `int x`, `def` |
| `%env(json:base64:CONFIG)%` | tableau ; ordre inversé : `RuntimeException` |
| `'a %%s b'` | `a %s b` |
| `lint('foo + 1', [])` | `SyntaxError`, levée sous `IGNORE_UNKNOWN_VARIABLES` |
| `lint('1 +', null)` | `TypeError` : `$names` est un `array` en 8.0 |

La condition `variables_order` (qui doit contenir `E`) est reprise de
`configuration.rst` (8.0).

**Questions.** `QST-8h7ynmfznjpe`, `QST-bftzbt0t3q8y`, `QST-2qp5mxyfj5n7`
(LEARNING) relues : exactes, inchangées. L'item a une question holdout : non lue,
non modifiée.

**Flashcards.** 10 ajoutées ; `FLC-nb0mh2t8j7r9` reçoit le niveau TRAP. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`variables_order`, `le dernier chargé gagne` et `Invalid JSON in env var`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions Symfony 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 461 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 2 — *Error handling* (STANDARD, 534 / 900).
