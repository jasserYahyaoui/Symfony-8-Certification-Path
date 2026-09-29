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

## Page 2 — *Service container* — RAFFINÉE

`CRS-p1694d5f7r8c` · `OIT-79f4n087b66c` · STANDARD · **484 → 621 mots** sur 900.
Aucun niveau promu.

### Déploiements précédents, lus en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 08 (PR #253, `a91c2ba`) | 36575882478, success | lignes `practice`, `exam`, `mock-1` à `mock-5` et `course` toutes `ok` ; `mock-4` : 75 questions, tout le holdout et rien d'autre |
| page 1 du lot 09 (PR #254, `566841f`) | 36611202017, success | `ok  lot-09  the DI component page carries its four flashcard levels, the not-found exception, the frozen bag and the shared setting` |

### Une option qui n'existe pas

La page renvoyait à `debug:container --show-private`. Lu dans
`ContainerDebugCommand::configure()` (FrameworkBundle, branche 8.0) : les options
sont `show-hidden`, `tag`, `tags`, `parameter`, `parameters`, `types`,
`env-var`, `env-vars`, `format`, `raw`, `deprecations` — **aucune
`--show-private`**. Les services privés sont listés par défaut ; `--show-hidden`
ajoute les services dont l'identifiant commence par un point. La page est
corrigée et cite la commande.

### Un distracteur défendable

`QST-hp7d9gbzhfk0` (VALIDATION) demande pourquoi une modification de
`services.yaml` est sans effet en production. Le distracteur « services.yaml est
lu une fois au build puis ignoré » décrit en fait la bonne réponse, avec d'autres
mots. La question passe en **v2** : ce choix est remplacé par
`CHO-bkzc037h0mh1` — « la production lit un `services_prod.yaml` séparé et
ignore `services.yaml` ». Ce choix est faux parce que `MicroKernelTrait` charge
`services.yaml` **puis** `services_<env>.yaml`. La bonne réponse et l'énoncé
sont inchangés.

### Compléments

- Titre de source corrigé : *How to Create Service Aliases and Mark Services as
  Public* (`alias_private.rst`).
- `static::getContainer()` expose les services publics et les privés **non
  retirés** (`testing.rst`) ; un privé retiré se rend public dans
  `config/services_test.yaml`.
- `#[Autoconfigure(public: true)]` rend une classe publique.

**Questions.** Les questions LEARNING `QST-b9cxe8hzrxnz`, `QST-8cz03w4xazh4` et
`QST-k31qdkpdy92e` ont été relues et sont exactes, donc inchangées. L'alias
automatique cité par `QST-8cz03w4xazh4` a été vérifié :
`FileLoader::registerAliasesForSinglyImplementedInterfaces()` existe en 8.0.15.
L'item ne porte pas de question holdout.

**Flashcards.** 10 cartes ajoutées. La carte préexistante `FLC-cv9s7bswxrh8`
reçoit le niveau RECALL. L'item en porte **11** (4 RECALL, 2 UNDERSTANDING,
2 APPLICATION, 3 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `show-hidden`,
`services_test.yaml` et `ContainerDebugCommand`. Elles sont absentes de la
version `master` de la page et de ses cartes.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| lecture de `ContainerDebugCommand.php` et `MicroKernelTrait.php` (branche 8.0) | options et chargement cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 981 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 3 — *Built-in services* (MINIMAL, 339 / 700).
