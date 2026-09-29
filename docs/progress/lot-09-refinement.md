# Raffinement pédagogique — Lot 09 (Dependency Injection)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 08 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; quand la documentation et le
code divergent, le code l'emporte et l'écart est signalé sur la page ; budget
`REV-001` respecté sans promotion de niveau ; une branche, une PR, une CI verte,
une fusion et un smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-09-29 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `a91c2ba`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Dependency Injection component | STANDARD | 411 / 900 | 1 | en cours |
| 2 | Service container | STANDARD | 484 / 900 | 1 | à faire |
| 3 | Built-in services | MINIMAL | 339 / 700 | 1 | à faire |
| 4 | Configuration parameters | STANDARD | 384 / 900 | 1 | à faire |
| 5 | Services registration (YAML and PHP attributes) | STANDARD | 395 / 900 | 1 | à faire |
| 6 | Service decoration | STANDARD | 450 / 900 | 1 | à faire |
| 7 | Tags | STANDARD | 368 / 900 | 1 | à faire |
| 8 | Semantic configuration | STANDARD | 404 / 900 | 1 | à faire |
| 9 | Factories | STANDARD | 343 / 900 | 1 | à faire |
| 10 | Compiler passes | DEEP | 548 / 1200 | 1 | à faire |
| 11 | Services autowiring | DEEP | 597 / 1200 | 1 | à faire |
| 12 | Service locators | STANDARD | 354 / 900 | 1 | à faire |

## Page 1 — Dependency Injection component, 2026-09-29

`CRS-htvm3b1gas0d` · `OIT-92ctf3sy3ddk` · STANDARD · **411 → 600 mots** sur 900.
Aucun niveau promu. Exécutions avec `symfony/dependency-injection` 8.0.15.

### Un exemple incomplet et une explication fausse

L'exemple de la page enregistrait `mailer` puis appelait `compile()`. Exécuté :
une définition créée par `register()` est **privée** par défaut ; après
`compile()`, `get('mailer')` lève une `ServiceNotFoundException`, « removed or
inlined when the container was compiled ». L'exemple porte désormais
`setPublic(true)`, et la page montre l'échec.

`QST-0qda3mr144nt` (VALIDATION) demandait ce qui « termine la construction d'un
`ContainerBuilder` avant qu'il puisse servir », et l'explication d'un
distracteur affirmait que `get()` ne résout pas les paramètres. Exécuté : sur un
`ContainerBuilder` non compilé, `get()` construit le service et résout
`%smtp_host%`. La question passe en **v2** : l'énoncé demande quel appel exécute
les passes de compilation et fige le conteneur — bonne réponse `compile()`,
choix inchangés, explication du distracteur corrigée.

### Compléments, exécutés

- Après `compile()` : `register()` lève une `BadMethodCallException`,
  `setParameter()` une `LogicException` sur un ParameterBag figé ;
  `has('transport')` vaut `false` pour un service privé inliné.
- Deux `get()` rendent la même instance ; `setShared(false)` en donne deux.
- Injection par propriété : `Definition::setProperty()`.

**Questions.** `QST-bkff08xmt369` et `QST-ttahda62q0me` (LEARNING) relues :
exactes, inchangées. L'item ne porte pas de question holdout.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-1y9kmkwvmjg7` reçoit le
niveau TRAP. L'item en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
4 TRAP), décompte relevé par script avant rédaction.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`ServiceNotFoundException`, `frozen ParameterBag` et `setShared`, absentes de la
version `master` de la page et de ses cartes, présentes dans le build local.
`ContainerBuilder`, envisagée, figurait déjà sur la page : écartée.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions DependencyInjection 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 971 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 2 — *Service container* (STANDARD, 484 / 900).
