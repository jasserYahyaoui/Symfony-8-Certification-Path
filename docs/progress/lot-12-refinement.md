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
| 1 | Console component | STANDARD | 454 / 900 | 1 | **RAFFINÉE** (PR #288) |
| 2 | Built-in commands | MINIMAL | 302 / 700 | 1 | **RAFFINÉE** (PR #289) |
| 3 | Custom commands | STANDARD | 443 / 900 | 1 | **RAFFINÉE** (PR #290) |
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

## Page 2 — *Built-in commands* — RAFFINÉE

`CRS-rk0fkn1q5byc` · `OIT-zgq6w4jqamvb` · MINIMAL · **302 → 491 mots** sur 700.
Aucun niveau promu. Exécutions sur une application FrameworkBundle 8.0.15
(SecurityBundle, TwigBundle, Messenger) : liste complète des commandes,
résolution d'abréviations, options globales.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 1 du lot 12 (PR #288, `4d8403f`) | 36997726911, success | `ok  lot-12  the console component page carries its four flashcard levels, the interactivity options, the interact attribute and the missing return` |

### Une affirmation contredite par la question de l'item : le préfixe

La page écrivait « le préfixe dit d'où elle vient » ; la question
`QST-nd25jj6mfmtj` a pour bonne réponse « the area it acts on » et pour
distracteur « the bundle that registered it ». La question a raison : relevé
sur l'application, `debug:firewall` est déclarée par SecurityBundle et
`debug:router` par FrameworkBundle, sous le même préfixe. La page dit
désormais que le préfixe nomme le domaine.

### Confirmé par l'exécution ou la lecture

- `bin/console` sans argument : la sortie de `list` (« Usage: »…), commande
  par défaut lue dans `Application`.
- Relevé : 9 `debug:*`, 7 `cache:*`, 3 `lint:*`, 7 `secrets:*`, et `about`,
  `completion`, `help`, `list`, `_complete` sans espace de noms. Pas de
  `lint:xliff` : `FrameworkExtension` retire la commande sans le composant
  Translation (lu).
- Abréviations : `c:c` → `cache:clear`, `d:r` → `debug:router`, `l:y` →
  `lint:yaml` ; `deb:c` → « Command "deb:c" is ambiguous. Did you mean one of
  these? » avec `debug:config` et `debug:container`.
- Options globales : `--env|-e` et `--no-debug` parmi elles ; `APP_ENV` vaut
  `dev` par défaut, lu dans `SymfonyRuntime` (non exécuté : le composant
  Runtime n'est pas dans le bac à sable).

**Questions.** `QST-snpbvmx5e280`, `QST-wgvc9x0rjh0r` et `QST-nd25jj6mfmtj`
(LEARNING) relues : exactes, inchangées. L'item n'a ni question VALIDATION
(MINIMAL) ni question holdout.

**Flashcards.** 10 ajoutées ; `FLC-p7xbhq67h80a` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`deb:c`, `lint:xliff` et `SymfonyRuntime`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + Console 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 291 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 3 — *Custom commands* — RAFFINÉE

`CRS-cb51hcng9dh5` · `OIT-kbg00jqxxwhq` · STANDARD · **443 → 715 mots** sur 900.
Aucun niveau promu. Exécutions sur FrameworkBundle + Console 8.0.15 : quatre
classes de commande dans le dossier chargé par `services.yaml`, puis une
commande invocable avec `#[Argument, Ask]` lancée dans un vrai processus.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 2 du lot 12 (PR #289, `17e257a`) | 36998885316, success | `ok  lot-12  the built-in commands page carries its four flashcard levels, the ambiguous abbreviation, the installed components and the runtime environment` |

### Deux affirmations fausses, et deux questions bâties dessus

**1. « Sans l'attribut, il faut poser le tag `console.command` à la main. »**
Lu dans `FrameworkExtension` (8.0) : l'autoconfiguration pose le tag sur une
classe `#[AsCommand]` **et** sur toute sous-classe de `Command`. Exécuté :

| Classe | Attribut | Résultat |
|---|---|---|
| étend `Command`, nom posé dans `configure()` | non | enregistrée, exécutable |
| invocable, n'étend rien | non | absente |
| invocable, `hidden: true`, alias `app:h` | oui | absente de `list`, exécutable par nom et alias |
| étend `Command` et définit `__invoke()` | oui | `initialize()` puis `__invoke()` |

**`QST-tvce4echjywq` (LEARNING) → v2.** Bonne réponse juste (« registering it
as a service tagged console.command »), mais l'explication et celle d'un
distracteur posaient que l'autoconfiguration n'agit qu'avec l'attribut ; et ce
distracteur — « placing a copy of the class in src/Command/ » — devenait
défendable pour une sous-classe de `Command`. Distracteur remplacé
(`CHO-tzzapj63ax5k`), explications réécrites.

**2. « `initialize()` et `interact()` supposent l'héritage de `Command`. »**
Vrai pour `initialize()`, faux pour l'interaction : `#[Ask]` et `#[Interact]`
(documentation 8.0, « Interactive Input »). Exécuté,
`__invoke(#[Argument, Ask('Name?')] string $name)` : question posée si
l'argument manque, sautée s'il est fourni, « Not enough arguments (missing:
"name"). » et code 1 avec `-n`.

**`QST-sp2y1cwdz94v` (VALIDATION) → v2.** Sa bonne réponse était « extending
Command, because interact() is one of its methods » pour inviter l'utilisateur
à saisir un argument manquant. Réécrite sur l'acquis visé par la question
(`OUT-npjsrz7new8m`, « dire quand étendre Command reste nécessaire ») : la
bonne réponse est désormais `initialize()`, et l'interaction devient un
distracteur. Quatre choix nouveaux (`CHO-2tjfe21d00ad`, `CHO-p38a0cbjxcaz`, `CHO-m5c74q89ff2x`, `CHO-20ss7w9dq9k8`, la bonne réponse en premier), la bonne réponse n'est pas la
plus longue.

**Carte `FLC-czhm5c0vv1sv`** : son verso répétait l'erreur 2 ; corrigé, niveau
TRAP.

### Confirmé par la lecture

- `AsCommand::__construct()` : `name` seul obligatoire ; `description`,
  `aliases`, `hidden`, `help`, `usages` facultatifs.
- Constructeur de `Command` : une sous-classe invocable qui ne redéfinit pas
  `execute()` passe par `__invoke()`.

**Questions.** `QST-gbzpgycr1r6g` (LEARNING) relue : exacte, inchangée. Aucune
question holdout sur cet item.

**Flashcards.** 10 ajoutées ; `FLC-czhm5c0vv1sv` corrigée et classée TRAP.
L'item en porte **11** (2 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 4 TRAP),
décompte relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`sous-classe de`, `hidden: true` et `Not enough arguments`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + Console 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 301 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 4 — *Configuration* — RAFFINÉE

`CRS-sf3701ncg2va` · `OIT-1hdmw4gm819r` · STANDARD · **519 → 693 mots** sur 900.
Aucun niveau promu. Exécutions sur FrameworkBundle + Console 8.0.15 : des
commandes dont le constructeur se signale, lancées par `list` ; une commande
cachée par son nom ; les usages lus par `getUsages()` sous FrameworkBundle et
dans une `Application` autonome.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 3 du lot 12 (PR #290, `f9b37f8`) | 37000292405, success | `ok  lot-12  the custom commands page carries its four flashcard levels, the subclass autoconfiguration, the hidden command and the ask run` |

### La documentation et le code divergent : les `usages`

La documentation 8.0 montre `usages` dans `#[AsCommand]`. Lu dans
`FrameworkExtension` (8.0, version installée et branche amont) :
l'autoconfiguration ne transmet que `name`, `description` et `help` au tag
`console.command`. Exécuté :

| Commande | `getUsages()` |
|---|---|
| invocable, application FrameworkBundle | `[]` |
| invocable, `Application` autonome | `["app:usage bob", "app:usage alice --x"]` |
| sous-classe de `Command`, application FrameworkBundle | `["app:usage-sub carol"]` |

Le code l'emporte ; l'écart est signalé sur la page.

### Une affirmation retirée

« C'est l'unique cas de la documentation Symfony où l'on est invité à ne pas
appeler le constructeur parent en premier » : superlatif invérifiable, retiré.
La règle elle-même — propriétés avant `parent::__construct()` — reste,
confirmée par l'ordre exécuté `ctor-start`, `configure`, `ctor-end`.

### Confirmé par l'exécution ou la lecture

- Description dans l'attribut : la classe n'est **pas** construite par `list` ;
  description dans `configure()` : elle l'est.
- `name: '|app:secret'` : premier segment vide, commande cachée — absente de
  `list`, exécutable (code 0) ; lu dans `AddConsoleCommandPass`.
- `Command::addUsage()` préfixe le nom à tout usage qui ne commence pas par lui.

**Questions.** `QST-bw6j8k17fx9c`, `QST-y48awnf4nfnx`, `QST-mra5j6bq70sr`
(LEARNING) et `QST-8rb0j5e7v90a` (VALIDATION) relues : exactes, inchangées.
L'item n'a pas de question holdout.

**Flashcards.** 10 ajoutées ; `FLC-c51027n1sspw` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`|app:secret`, `getUsages()` et `construite par`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions Console 8.0.15 (et FrameworkBundle quand cité) | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 311 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 5 — *Options and arguments (using PHP attributes)* (STANDARD, 711 / 900).
