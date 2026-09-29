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

## Page 3 — *Built-in services* — RAFFINÉE

`CRS-jtkmpfp3nwzk` · `OIT-dy7108w6bf4z` · MINIMAL · **339 → 560 mots** sur 700.
Aucun niveau promu. Exécutions sur une application minimale FrameworkBundle +
TwigBundle 8.0.15 (`MicroKernelTrait`, autowiring activé).

**Correction de méthode, avant fusion.** La première exécution a tourné avec
`dependency-injection` **8.1.8** : Composer avait contraint FrameworkBundle à
`8.0.*` mais pas ses dépendances. Tous les composants `symfony/*` du bac à sable
ont été fixés à `8.0.*` (`dependency-injection`, `http-kernel`, `config` en
v8.0.15 par `git describe`), et chaque exécution de cette page a été **refaite** :
sorties identiques. Même vérification pour le bac à sable des lots 07–08, qui
embarquait `security-core` 8.1.6 par transitivité : les exécutions CSRF du lot
07 (page 7), relancées après le passage en 8.0, rendent une sortie identique.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 2 du lot 09 (PR #255, `e7e7764`) | 36616450038, success | `ok  lot-09  the service container page carries its four flashcard levels, the hidden option, the test services file and the debug command` |

### Une règle trop générale

La page posait « **on injecte une interface**, pas une implémentation », puis
citait dans la même liste `RequestStack`, `Filesystem` et `Environment`, trois
classes. Exécuté : les trois s'injectent par leur classe, parce que le bundle les
a aliasées telles quelles (`->alias(Filesystem::class, 'filesystem')` dans
`services.php` de FrameworkBundle). La règle devient : on injecte **le type que
le bundle a aliasé** — le plus souvent une interface, parfois une classe. Un
piège d'examen est ajouté.

### Confirmé par l'exécution

- Les treize types cités figurent dans la sortie de `debug:autowiring`.
- Typer `Symfony\Component\HttpKernel\Log\Logger`, la classe de `logger`,
  échoue : « no such service exists. Try changing the type-hint to
  "Psr\Log\LoggerInterface" instead. »
- Sans TwigBundle, `Twig\Environment` échoue alors que `twig/twig` est installé.
- `debug:autowiring` : un argument `search` (filtre partiel, erreur « No
  autowirable classes or interfaces found matching … » sans résultat) et une
  option `--all`, qui ajoute les services non aliasés comme `RedirectController`.
- « La recette active le bundle » : cité depuis `quick_tour/flex_recipes.rst`
  (« automatically enabling the feature in `config/bundles.php` »).

**Questions.** `QST-0mgw5r64r7pf` et `QST-8ejeqdapg6aq` (LEARNING) relues :
exactes, inchangées. L'item ne porte ni question VALIDATION ni question
holdout ; il est MINIMAL, donc `POOL-002` ne s'applique pas.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-7xkwp2t1hefj` reçoit le
niveau RECALL. L'item en porte **11** (4 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
3 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`RedirectController`, `DebugAutowiringCommand` et `Try changing the type-hint`,
absentes de la version `master` de la page et de ses cartes.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + TwigBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 991 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 4 — *Configuration parameters* — RAFFINÉE

`CRS-8zmj5ntgdhjv` · `OIT-ar4h3zfskjsp` · STANDARD · **384 → 667 mots** sur 900.
Aucun niveau promu. Exécutions avec `symfony/dependency-injection` 8.0.15 seul,
et sur l'application FrameworkBundle 8.0.15 aux composants fixés en `8.0.*`.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 3 du lot 09 (PR #256, `cdaa5f7`) | 36619553085, success | `ok  lot-09  the built-in services page carries its four flashcard levels, the unaliased service, the autowiring command and the type-hint suggestion` |

### Une règle d'échappement mal énoncée

La page et l'explication de `QST-sxw56zsgz0we` (VALIDATION) affirmaient qu'un
pourcent seul « commence une référence de paramètre ». Lu dans
`ParameterBag::resolveString()` : une référence est `%([^%\s]+)%`, un texte
**sans espace** entre deux pourcents. Exécuté :

| Valeur | Résultat |
|---|---|
| `'100%%'` | `100%` |
| `'50% off'` | inchangée |
| `'from 5% to 10% off'` | inchangée |
| `'5%off%now'` | `ParameterNotFoundException`, paramètre « off » |

La page montre les quatre cas ; doubler reste la règle sûre. L'explication de la
question est corrigée et sa source passe d'`EnvVarProcessor.php`, qui ne traite
pas de l'échappement, à `ParameterBag.php`. Énoncé, choix et bonne réponse
inchangés : version conservée.

### Une explication de distracteur trop absolue

`QST-1zvtze0w6a5p` (LEARNING) : « autowiring resolves by type, **never** by
argument name ». Un alias nommé (`ContainerBuilder::registerAliasForArgument()`,
présent en 8.0.15) associe un type **et** un nom d'argument. L'explication dit
désormais qu'un tel alias reste indexé par un type, et qu'un scalaire n'est
jamais autowiré. Version conservée.

### Confirmé ou ajouté par l'exécution

- `getProvidedTypes()` compte **21** processeurs.
- Le conteneur PHP généré écrit la valeur d'un paramètre en dur ; instancié deux
  fois avec deux valeurs de `DSN`, il rend les deux : `%env()%` est lu à
  l'exécution.
- `%env(PORT)%` rend la chaîne `'6379'` ; `int:` l'entier.
- `json:base64:` rend le tableau ; `base64:json:` lève « Invalid JSON ».
- `default:app.fallback:` rend le paramètre, `default::` rend `null`, une
  variable absente sans défaut lève `EnvNotFoundException`.
- `bind` et les trois formes de `#[Autowire]` (`'%…%'`, `param:`, `env:`)
  injectent leur valeur ; un `bind` inutilisé lève une `InvalidArgumentException`
  (`ResolveBindingsPass`).

**Questions.** `QST-p6eftemqgsfx` et `QST-cb9sywaj1nde` (LEARNING) relues :
exactes, inchangées. L'item ne porte pas de question holdout.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-kbnpt2sv2dss` reçoit le
niveau RECALL. L'item en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION,
3 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`EnvNotFoundException`, `ResolveBindingsPass` et `non-existent parameter`,
absentes de la version `master` de la page et de ses cartes.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions DependencyInjection et FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 001 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 5 — *Services registration* — RAFFINÉE

`CRS-g8fteyd38nrt` · `OIT-stze9x4aydp3` · STANDARD · **395 → 637 mots** sur 900.
Aucun niveau promu. Exécutions sur l'application FrameworkBundle 8.0.15, aux
composants fixés en `8.0.*`.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 4 du lot 09 (PR #257, `32cd29f`) | 36620997465, success | `ok  lot-09  the configuration parameters page carries its four flashcard levels, the missing variable, the bindings pass and the percent reference` |

### Une configuration « par défaut » qui n'est pas celle de la documentation

La page présentait la section `services` « d'une application neuve » avec un
`exclude` de `DependencyInjection/`, `Entity/` et `Kernel.php`. La configuration
par défaut que montre `service_container.rst` (8.0) n'en a pas : `_defaults`,
puis `App\: { resource: '../src/' }`, et `exclude` n'apparaît que comme option.
La page reprend la version documentée. Exécuté : une classe placée dans `src/`
avec un argument `string` non résolu, jamais injectée, n'empêche pas le
démarrage — `exclude` est une option, pas une obligation.

L'explication de `QST-8e8zfpay4pj4` (VALIDATION) reposait sur le même défaut
supposé (« Entities and the Kernel are excluded… ») ; elle est corrigée, version
conservée.

### Un énoncé que l'exécution rend ambigu

`QST-smjza039q4hw` (LEARNING) demandait quel attribut « garde une classe hors
de la découverte ». Exécuté : `#[When(env: 'prod')]` le fait aussi, hors de son
environnement — en `dev`, l'injecter lève « needs an instance of … but this type
has been excluded ». L'explication du distracteur `#[When]` affirmait au
contraire que la classe « is still discovered ». La question passe en **v2** :
l'énoncé précise « **in every environment** », bonne réponse `#[Exclude]`,
choix inchangés, explications corrigées.

### Confirmé par l'exécution

- `_defaults` ne traverse pas `when@dev` : sans `_defaults` dans le bloc, le
  service n'est pas autowiré, et l'erreur n'arrive qu'à l'instanciation —
  `ArgumentCountError`, « Too few arguments … 0 passed ». Avec un `_defaults`
  local, il reçoit le logger. La carte `FLC-6a9ngbsfteyg`, qui parlait d'une
  omission « sans message », est précisée et reçoit le niveau TRAP.
- Une classe exclue puis déclarée explicitement est enregistrée, avec la
  visibilité demandée.
- `#[AsAlias('app.sms')]` crée un alias **privé** ; `#[AutoconfigureTag]` sur une
  interface tague ses implémentations ; `#[Exclude]` fait échouer toute
  injection, dans tous les environnements, avec le même message.
- `#[Autoconfigure]` : arguments relus dans le constructeur 8.0.15 (`tags`,
  `calls`, `bind`, `lazy`, `public`, `shared`, `autowire`, `properties`,
  `configurator`, `constructor`, `resourceTags`).

**Questions.** `QST-gfqb0xrb7tbx`, `QST-q2p4t3hjc5x0`, `QST-c12z17ez0ss5` et
`QST-r73ye7dqg80n` (LEARNING) relues : exactes, inchangées. L'item ne porte pas
de question holdout.

**Flashcards.** 10 ajoutées ; `FLC-6a9ngbsfteyg` précisée, niveau TRAP.
L'item en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP),
décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`container.excluded`, `ArgumentCountError` et `private alias`, absentes de la
version `master` de la page et de ses cartes.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 011 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 6 — *Service decoration* (STANDARD, 450 / 900).
