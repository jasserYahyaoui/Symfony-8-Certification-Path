# Raffinement pédagogique — Lot 22 (Miscellaneous)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 21 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; budget `REV-001` respecté
sans promotion de niveau ; une branche, une PR, une CI verte, une fusion et un
smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-03 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `aaf3f31`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Mailer | STANDARD | 512 / 900 | 1 | à faire |
| 2 | Mime | STANDARD | 492 / 900 | 1 | à faire |

## Page 1 — *Mailer* — RAFFINÉE

`CRS-h304h6ht87kc` · `OIT-j4vpn5bh9f27` · STANDARD · **512 → 599 mots** sur 900.
Aucun niveau promu. Exécutions sur Mailer 8.0.15 et Mime 8.0.15, transports
`null://null`, sans service tiers.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 21 (PR #329, `aaf3f31`) | 37112768423, success | `ok  lot-21  the finder page carries its four flashcard levels, the overlapping locations, the missing location and the distinct keys` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### Une correction de code : l'exemple d'envoi

L'exemple déclarait `: Response` sans rien rendre : tel quel, il lèverait une
`TypeError`. La documentation 8.0 y place `// ...` ; la page aussi.

### Confirmé par l'exécution

| Cas | Résultat |
|---|---|
| `Mailer::send($email)` | `null` |
| `$transport->send($email)` | `SentMessage`, original = l'`Email` |
| message rejeté par `MessageEvent::reject()` | `null`, aucune exception |
| `X-Transport: alt` avec `main` et `alt` | absent des octets envoyés et de l'objet d'origine |
| `X-Transport: nope` | `InvalidArgumentException` listant `main`, `alt` |

Lu dans le code 8.0 : `Transports::__construct()` retient le premier transport
comme défaut ; `MessageEvent::reject()` arrête la propagation.

**Questions.** `QST-08dprb9gp7c9`, `QST-x1q4fk8g682j`, `QST-x48my0ncdjrc`,
`QST-k8ghfqjcs3kk` (LEARNING) et `QST-42tvpnq0tkt2` (VALIDATION) relues :
exactes, inchangées. L'item n'a pas de question holdout.

**Flashcards.** 10 ajoutées ; `FLC-xnqfscd5c1cy` reçoit le niveau UNDERSTANDING.
L'item en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP),
décompte relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`Valeur rendue`, `InvalidArgumentException` et `aucune exception`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-03**

| Contrôle | Résultat |
|---|---|
| exécutions Symfony 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 591 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 2 — *Mime* (STANDARD, 492 / 900).
