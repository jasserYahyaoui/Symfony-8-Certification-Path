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
| 4 | Configuration | STANDARD | 519 / 900 | 1 | **RAFFINÉE** (PR #291) |
| 5 | Options and arguments (using PHP attributes) | STANDARD | 711 / 900 | 1 | **RAFFINÉE** (PR #292) |
| 6 | Input and Output objects | STANDARD | 543 / 900 | 1 | **RAFFINÉE** (PR #293) |
| 7 | Built-in helpers | STANDARD | 550 / 900 | 1 | **RAFFINÉE** (PR #294) |
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

## Page 5 — *Options and arguments (using PHP attributes)* — RAFFINÉE

`CRS-q2dxjhx6d35k` · `OIT-nr9m883d15qq` · STANDARD · **711 → 834 mots** sur 900.
Aucun niveau promu. Exécutions sur Console 8.0.15 : une commande invocable à
treize paramètres dont la définition est relue mode par mode, sept
déclarations fautives, et des lancements réels pour lire les valeurs reçues.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 4 du lot 12 (PR #291, `43c804c`) | 37001609812, success | `ok  lot-12  the configuration page carries its four flashcard levels, the hidden name, the usages divergence and the lazy listing` |

### Une règle incomplète : l'ordre des arguments

La page écrivait qu'un argument **requis** est refusé après un argument
tableau. Lu dans `InputDefinition::addArgument()` : **tout** argument l'est.
Exécuté, `array $a = [], string $b = ''` : `LogicException` « Cannot add a
required argument "b" after an array argument "a". » — `$b` est pourtant
optionnel ; le message trompe.

### Une ligne de tableau inexacte : l'option tableau

`array $roles = []` était donné pour `VALUE_IS_ARRAY` seul, utilisé avec
`--role=…`. Exécuté : mode `VALUE_REQUIRED` + `VALUE_IS_ARRAY`, et l'option
s'appelle `--roles` (nom du paramètre). Ligne corrigée.

**`QST-wt0f9p2tmmnm` (LEARNING) → v2.** Elle demandait « la » constante
correspondant à une option tableau, avec `VALUE_REQUIRED` comme distracteur —
alors que les deux drapeaux sont posés. Énoncé recentré sur le drapeau
qu'**ajoute** le type tableau ; `VALUE_REQUIRED` remplacé par `VALUE_NEGATABLE`
(`CHO-vnd4s2ngkd2z`) ; la bonne réponse n'est pas la plus longue.

### Confirmé par l'exécution

| Déclaration | Résultat |
|---|---|
| `string $name` / `string $lastName = ''` / `?string $nick = null` | requis / optionnel `last-name` / optionnel |
| `bool $yell = false` | `VALUE_NONE` |
| `bool $loud = true`, `?bool $quiet2 = null` | négociables ; `--no-loud` donne `false` |
| `string\|bool $output = false` | `VALUE_OPTIONAL` ; `--output` → `true`, `--output=f.txt` → la chaîne |
| `?int $maxRetries = null` | `--max-retries`, `VALUE_REQUIRED` |
| `#[Option] string $x` sans défaut | « must declare a default value » |
| `int\|string $x = 1` | union refusée |
| `bool\|string $x = 'x'` | « must have a default value of false » |
| `?\DateTimeImmutable $x = null` | type refusé |
| `Fmt $fmt`, `--fmt=xml` | « The value "xml" is not valid for the "fmt" option », code 1 |

Lu : une énumération sans `suggestedValues` reçoit ses cas comme suggestions
(`Option::tryFrom()`).

**Questions.** `QST-svybdxxreygm`, `QST-agnyym2anznf` (LEARNING) et
`QST-p0v30fknd59q` (VALIDATION) relues : exactes, inchangées. Aucune question
holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-bdrpkzcqw1ww` reçoit le niveau TRAP. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`must have a default value of false`, `Cannot add a required argument` et `--no-loud`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 321 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 6 — *Input and Output objects* — RAFFINÉE

`CRS-0ab7kpexq9w9` · `OIT-65sswf0qhw6c` · STANDARD · **543 → 727 mots** sur 900.
Aucun niveau promu. Exécutions sur Console 8.0.15 : `ArgvInput` avec et sans
définition, `BufferedOutput` décorée et non décorée sous les trois modes de
rendu, échappement.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 5 du lot 12 (PR #292, `ec7d902`) | 37002788607, success | `ok  lot-12  the options and arguments page carries its four flashcard levels, the false default rule, the array ordering and the negatable flag` |

### Une affirmation imprécise : `OUTPUT_PLAIN` « retire les balises »

Lu dans `Output::write()` : `strip_tags($this->formatter->format($message))`.
Le message est **formaté d'abord**. Exécuté, `'<info>ok</info> <script>x</script>'` :

| Mode | Décorée | Non décorée |
|---|---|---|
| `OUTPUT_NORMAL` | `ok` en vert, `<script>x</script>` intact | `ok <script>x</script>` |
| `OUTPUT_PLAIN` | `ok` **toujours en vert**, `x` | `ok x` |

### Une erreur reprise de la page 1

Le commentaire de `isInteractive()` — « un terminal répondra-t-il ? » —
reprenait l'erreur corrigée à la page 1 : l'entrée n'est non interactive
qu'avec `-n`, `-q` ou `--silent`.

### Confirmé par l'exécution ou la lecture

- `--dry-run` déclarée et non passée : `hasOption()` `true`, `getOption()`
  `false`.
- `ArgvInput(['bin', '--env=prod'])` sans définition :
  `hasParameterOption('--env')` `true`, `getParameterOption('--env')` `'prod'`.
- Sans échappement, `<info>x</info>` venu de l'utilisateur s'affiche `x` ;
  `OutputFormatter::escape()` le garde tel quel.
- Quatre styles par défaut lus dans le constructeur d'`OutputFormatter`.

**Questions.** `QST-tp80nqtxvarw`, `QST-5wrcd9ahhth2`, `QST-m7ywj8fyrd5z`
(LEARNING) et `QST-ngh8w461wr91` (VALIDATION) relues : exactes, inchangées.
L'item n'a pas de question holdout.

**Flashcards.** 10 ajoutées ; `FLC-vrr7nk2vcdkk` reçoit le niveau APPLICATION.
L'item en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP),
décompte relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`strip_tags`, `getParameterOption` et `toujours en vert`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 331 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 7 — *Built-in helpers* — RAFFINÉE

`CRS-3p9jdkw1nd3g` · `OIT-v6wxp78gk42c` · STANDARD · **550 → 714 mots** sur 900.
Aucun niveau promu. Exécutions sur Console 8.0.15 : `ProgressBar` avec et sans
total, `QuestionHelper::ask()` sur une entrée en mémoire, interactive ou non.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 6 du lot 12 (PR #293, `e5662f7`) | 37004119926, success | `ok  lot-12  the input and output page carries its four flashcard levels, the plain mode, the raw option and the kept colours` |

### Une affirmation fausse, et une question bâtie dessus : « `ProgressBar` exige un total »

La documentation 8.0 (*Progress Bar*) montre l'inverse : sans total, la barre
s'affiche en *throbber*. Exécuté : `0 [>---…]` sans total, `0/3 [>---…]   0%`
avec. `ProgressIndicator` reste l'outil d'une attente sans rien à compter.

**`QST-h22aea8btw90` (LEARNING) → v2.** Bonne réponse juste
(`ProgressIndicator`), mais l'explication et celle du distracteur
« ProgressBar, started with a total of zero » posaient qu'une barre exige un
total. Énoncé précisé (« there is nothing to count ») pour que le distracteur
soit faux pour la bonne raison ; explications réécrites ; aucun choix modifié.

### Confirmé par l'exécution

| Situation | Résultat |
|---|---|
| `ConfirmationQuestion('?', false)`, `yeti` ou `Yes` | `true` |
| `ConfirmationQuestion('?')`, réponse vide | `true` |
| non interactive, `ChoiceQuestion(…, ['dev', 'prod'], 0)` | `'dev'` |
| non interactive, `Question('?')` sans défaut | `null` |
| interactive, sans défaut, fin de l'entrée | `MissingInputException` « Aborted. » |

- Catalogue : neuf helpers dans `map.rst.inc` (8.0), relu.

**Questions.** `QST-8z5qhxdbyjf8`, `QST-32gxwn2q6pkf` (LEARNING) et
`QST-qcgrr0jydf3t` (VALIDATION) relues : exactes, inchangées. L'item n'a pas
de question holdout.

**Flashcards.** 10 ajoutées ; `FLC-48na02d14yce` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`throbber`, `setMaxSteps` et `MissingInputException`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 341 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 8 — *Console events* — RAFFINÉE

`CRS-y0zc67f3e5zk` · `OIT-cqs3m73y3rpy` · STANDARD · **578 → 728 mots** sur 900.
Aucun niveau promu. Exécutions sur Console 8.0.15 : une `Application` munie
d'un `EventDispatcher` et de trois écouteurs, sept scénarios, puis la même
application sans dispatcher.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 7 du lot 12 (PR #294, `c5b3f2b`) | 37005342797, success | `ok  lot-12  the helpers page carries its four flashcard levels, the throbber, the max steps and the aborted question` |

### Une affirmation fausse : « quatre constantes, pas davantage »

Lu dans `ConsoleEvents` (8.0) : quatre événements **et** une cinquième
constante, `ALIASES`, qui associe chaque classe d'événement à son nom. La page
dit désormais « quatre événements » ; l'explication de `QST-ycpqw0ygx9cd`
(« exactly four constants ») est corrigée — choix inchangés, version inchangée.

### Confirmé par l'exécution

| Cas | Événements | Code |
|---|---|---|
| succès | `COMMAND`, exécution, `TERMINATE:0` | 0 |
| `disableCommand()` | `COMMAND`, `TERMINATE:113` | 113 |
| exception | `COMMAND`, exécution, `ERROR`, `TERMINATE:1` | 1 |
| exception, `setExitCode(0)` sur `ERROR` | … `TERMINATE:0` | 0 |
| succès, `setExitCode(1)` sur `TERMINATE` | … `TERMINATE:0` | 1 |
| `__invoke(): void` | … `ERROR` (`TypeError`), `TERMINATE:1` | `TypeError` relancée |
| exception, sans dispatcher | exécution seule | 1 |

Ajouté à la page : `TERMINATE` est dispatché même après `disableCommand()`.
`ConsoleErrorEvent::setExitCode(3)` écrit aussi ce code dans l'exception, par
réflexion — exécuté : code 7 devenu 3 —, ce que `QST-pb7yzzbda7w0` affirmait
déjà.

**Questions.** `QST-tmz33c01yx7r`, `QST-z4mtkneppy34`, `QST-pb7yzzbda7w0`
(LEARNING) et `QST-11513wwwhf63` (VALIDATION) relues : exactes, inchangées ;
`QST-ycpqw0ygx9cd` : explication corrigée. Aucune question holdout lue ni
modifiée.

**Flashcards.** 10 ajoutées ; `FLC-hk7rkbxfq90j` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`cinquième constante`, `TERMINATE:113` et `relancée`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 351 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 9 — *Verbosity levels* (MINIMAL, 295 / 700).
