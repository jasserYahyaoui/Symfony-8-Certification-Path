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
| 1 | Dependency Injection component | STANDARD | 411 / 900 | 1 | **RAFFINÉE** (PR #254) |
| 2 | Service container | STANDARD | 484 / 900 | 1 | **RAFFINÉE** (PR #255) |
| 3 | Built-in services | MINIMAL | 339 / 700 | 1 | **RAFFINÉE** (PR #256) |
| 4 | Configuration parameters | STANDARD | 384 / 900 | 1 | **RAFFINÉE** (PR #257) |
| 5 | Services registration (YAML and PHP attributes) | STANDARD | 395 / 900 | 1 | **RAFFINÉE** (PR #258) |
| 6 | Service decoration | STANDARD | 450 / 900 | 1 | **RAFFINÉE** (PR #259) |
| 7 | Tags | STANDARD | 368 / 900 | 1 | **RAFFINÉE** (PR #260) |
| 8 | Semantic configuration | STANDARD | 404 / 900 | 1 | **RAFFINÉE** (PR #261) |
| 9 | Factories | STANDARD | 343 / 900 | 1 | **RAFFINÉE** (PR #262) |
| 10 | Compiler passes | DEEP | 548 / 1200 | 1 | **RAFFINÉE** (PR #263) |
| 11 | Services autowiring | DEEP | 597 / 1200 | 1 | **RAFFINÉE** (PR #264) |
| 12 | Service locators | STANDARD | 354 / 900 | 1 | **RAFFINÉE** (PR #265) |

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

## Page 6 — *Service decoration* — RAFFINÉE

`CRS-fkebqqqke5y5` · `OIT-adhs2ny9hc5f` · STANDARD · **450 → 654 mots** sur 900.
Aucun niveau promu. Exécutions avec `symfony/dependency-injection` 8.0.15, seul
et dans l'application FrameworkBundle 8.0.15.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 5 du lot 09 (PR #258, `03881f6`) | 36622484017, success | `ok  lot-09  the services registration page carries its four flashcard levels, the excluded tag, the argument count error and the private alias` |

### Un exemple qui ne compilait pas, un nom imprécis

L'exemple `LoggingMailer` appelait `$this->logger` sans l'injecter ; il reçoit
désormais un `LoggerInterface`. La page disait l'original « disponible sous
`.inner` » : exécuté, il est **renommé** `<id du décorateur>.inner`
(`App\P6\LoggingMailer.inner`, classe `Mailer`) ; `@.inner` n'est que le
raccourci depuis la définition du décorateur. L'explication de
`QST-3zf09tnf1rkj` (LEARNING) est précisée de même, version conservée.

### Une transparence qui a une condition

`QST-cg1wnyzmwgqg` (VALIDATION) dit que les consommateurs n'ont rien à changer
— exact pour l'identifiant. Exécuté : un consommateur typé avec la **classe**
décorée reçoit le décorateur et échoue, `TypeError` « must be of type
App\P6\Mailer, App\P6\LoggingMailer given » ; typé avec l'interface, il reçoit
`Logging(Mailer)`. L'explication ajoute la condition ; énoncé, choix et réponse
inchangés, version conservée.

### Confirmé ou ajouté par l'exécution

- Priorités 5 et 1 : `Baz(Bar(Foo))`, comme la page l'affirmait. À priorité
  égale, le premier déclaré est le plus interne (`baz(bar(Foo))`, puis
  l'inverse) — ce qui confirme le distracteur de `QST-zbx19jnjr7gz`.
- `decoration_on_invalid` : `exception` lève « has a dependency on a
  non-existent service "nope" » ; `ignore` **retire** le décorateur ; `null`
  garde le décorateur sous l'identifiant de la cible, avec `null` en original.
- `decoration_inner_name: foo.original` désigne bien l'original.
- `#[AsDecorator(decorates, priority, onInvalid)]` relu ; autowiring d'un
  argument typé comme la cible sur `.inner` confirmé ; `#[AutowireDecorated]`
  existe en 8.0.15.

**Questions.** `QST-zbx19jnjr7gz` (LEARNING) relue : exacte, inchangée. La
question holdout de l'item n'a été ni lue ni modifiée.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-xj1bfncctk1r` reçoit le
niveau TRAP. L'item en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
4 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`AutowireDecorated`, `LoggingMailer given` et `non-existent service`, absentes de
la version `master` de la page et de ses cartes. `decoration_inner_name`,
envisagée, figurait déjà sur la page : écartée.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions DependencyInjection et FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 021 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 7 — *Tags* — RAFFINÉE

`CRS-b4g5s2asb9ye` · `OIT-83sac57rw0xn` · STANDARD · **368 → 637 mots** sur 900.
Aucun niveau promu. Exécutions avec `symfony/dependency-injection` 8.0.15, seul
et dans l'application FrameworkBundle 8.0.15.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 6 du lot 09 (PR #259, `b5271d1`) | 36623819276, success | `ok  lot-09  the service decoration page carries its four flashcard levels, the decorated-argument attribute, the type error and the missing target` |

### Une méthode qui n'est jamais appelée

La page affirmait : « sans `index`, une méthode statique `getDefaultIndexName()`
peut fournir la clé ». Exécuté, avec une classe portant `getDefaultName()`,
`getDefaultIndexName()` et `getDefaultKeyName()` :

| Injection | Clé obtenue |
|---|---|
| itérateur simple | `0` — aucune méthode lue |
| `index_by: key` | `by-getDefaultKeyName` |
| `index_by: key` + `default_index_method: getDefaultIndexName` | `by-getDefaultIndexName` |
| localisateur | l'identifiant du service |

Le nom de la méthode **dérive de l'attribut d'index** (`TaggedIteratorArgument`),
et la méthode de priorité aussi : sous `index_by: key`, `getDefaultPriority()`
est ignorée au profit de `getDefaultKeyPriority()` — exécuté, le service perd sa
place. La page est corrigée.

### Un index qui ne transforme pas l'itérateur

La page disait que `index` « transforme l'itérateur en table de
correspondance ». Exécuté : `#[AsTaggedItem(index: 'sms')]` injecté par
`#[AutowireIterator('app.handler')]` donne les clés `0, 1, 2, 3` ; `sms`
n'apparaît qu'avec `indexAttribute` ou dans `#[AutowireLocator]`.

### Un mécanisme attribué au mauvais outil

La page et l'explication de `QST-a1avrk2v0k2t` (LEARNING) disaient que
`#[AutoconfigureTag]` est « ce que fait le framework pour ses propres
interfaces », d'où l'abonné par simple implémentation. Relu en 8.0.15 :
`EventSubscriberInterface` ne porte pas l'attribut ; `FrameworkExtension` appelle
`registerForAutoconfiguration(EventSubscriberInterface::class)`. Même effet,
autre moyen. L'explication est corrigée, version conservée.

### Confirmé ou ajouté par l'exécution

- Itérateur paresseux : aucun gestionnaire construit après construction du
  consommateur.
- Priorité haute d'abord (10, puis 5 par `getDefaultPriority()`, puis 0) ;
  `exclude: [FaxHandler::class]` rend les trois autres.
- Un tag que rien ne lit ne lève aucune erreur ; en debug, `UnusedTagsPass` écrit
  dans le journal de compilation « Tag "app.handlr" was defined on service(s) …,
  but was never used. Did you mean "app.handler"? » — à condition que le service
  ait survécu à la compilation (il a fallu le rendre public). La carte
  `FLC-tv2t8a6yt50g`, qui parlait d'« absence totale de signal », est précisée
  et reçoit le niveau TRAP.

**Questions.** `QST-t4bgmrx3q0cg`, `QST-wdn59cym1fv8` (LEARNING) et
`QST-ephbe0rvk2zx` (VALIDATION) relues : exactes, inchangées. L'item ne porte
pas de question holdout.

**Flashcards.** 10 ajoutées. L'item en porte **11** (3 RECALL, 2 UNDERSTANDING,
2 APPLICATION, 4 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `was never used`,
`getDefaultKeyName` et `registerForAutoconfiguration`, absentes de la version
`master` de la page et de ses cartes.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions DependencyInjection et FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 031 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 8 — *Semantic configuration* — RAFFINÉE

`CRS-exs5dvtqa1as` · `OIT-qj4xfkhwdrx7` · STANDARD · **404 → 657 mots** sur 900.
Aucun niveau promu. Exécutions sur un `AcmeSocialBundle` (`AbstractBundle`)
chargé dans l'application FrameworkBundle 8.0.15, composants fixés en `8.0.*`.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 7 du lot 09 (PR #260, `94ea859`) | 36625019002, success | `ok  lot-09  the tags page carries its four flashcard levels, the unused-tag log, the derived index method and the autoconfiguration call` |

### Un exemple de prepend qui n'en est pas un

La page et l'explication de `QST-qzgxkh2b2pa5` (LEARNING) donnaient le prepend
comme « la façon dont un bundle enregistre son chemin de gabarits dans Twig ».
Lu dans `TwigExtension::getBundleTemplatePaths()` (8.0.15) : TwigBundle enregistre
de lui-même `templates/` ou `Resources/views/` de chaque bundle, sous l'espace de
son nom privé du suffixe `Bundle` (`normalizeBundleName()`). L'exemple est
remplacé par celui de `prepend_extension.rst` (`framework.cache.prefix_seed`) ;
l'explication est corrigée, version conservée.

### Confirmé ou ajouté par l'exécution

| Configuration de l'application | Résultat |
|---|---|
| `timout: 5` | « Unrecognized option "timout" under "acme_social". Did you mean "timeout"? » |
| `timeout: 0` | « The value 0 is too small for path "acme_social.timeout". Should be greater than or equal to 1 » |
| aucune, `client_id` non fourni | « The child config "client_id" under "acme_social" must be configured. » |
| `label` défini par l'application et par prepend | la valeur de l'application l'emporte |
| deux bundles prepend `label` | le **premier enregistré** l'emporte (`from-first`) |

- `getContainerExtension()->getAlias()` rend `acme_social`.
- `configure()` et `loadExtension()` sont appelées au premier démarrage, jamais au
  second, cache chaud.
- La troisième ligne ajoute un piège : un nœud `isRequired()` fait échouer la
  compilation même si l'application ne configure pas le bundle.

**Questions.** `QST-2q21a2srg5hr`, `QST-996gnybse1ve` (LEARNING) et
`QST-5jr8w7tcqm2n` (VALIDATION) relues : exactes, inchangées. L'item ne porte pas
de question holdout.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-dmvk9r2pqhsg` reçoit le
niveau RECALL. L'item en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
4 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`Unrecognized option`, `from-first` et `getBundleTemplatePaths`, absentes de la
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
| `composer gate-full` | exit 0 — 299 tests, 17 041 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 9 — *Factories* — RAFFINÉE

`CRS-1bkg7pkf78c7` · `OIT-h2n7d7dbr56p` · STANDARD · **343 → 541 mots** sur 900.
Aucun niveau promu. Exécutions dans l'application FrameworkBundle 8.0.15,
composants fixés en `8.0.*`, le 2026-09-29.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 8 du lot 09 (PR #261, `ea6eec4`) | 36679606411, success | `ok  lot-09  the semantic configuration page carries its four flashcard levels, the unknown key, the first prepend and the template paths` |

### Un exemple qui ne produit pas de service

Le tableau des écritures illustrait la fonction PHP par `factory: 'strtoupper'`.
Exécuté : le mécanisme appelle bien la fonction, mais `get()` échoue —
`TypeError`, « Container::make(): Return value must be of type ?object, string
returned ». Un service est un objet ; l'exemple devient une fonction qui rend un
objet.

### Une écriture manquante

La page annonçait « quatre écritures ». `YamlFileLoader` transforme aussi
`factory: '@app.factory'` en appel de `__invoke()` — la fabrique invocable,
documentée dans `factories.rst` (8.0). Exécuté : les **cinq** écritures
produisent le service. La page est corrigée.

### Confirmé ou ajouté par l'exécution

- Avec `factory: [null, 'create']` : constructeur appelé 0 fois, `create()` une
  fois, deux `get()` rendent le même objet.
- `#[Autoconfigure(constructor: 'make')]` : le service vient de la méthode, le
  constructeur n'est pas appelé.
- Le conteneur ne vérifie pas le type retourné : une fabrique déclarée pour
  `Liar` qui rend un `stdClass` compile ; `get()` rend le `stdClass` et le
  consommateur typé `Liar` échoue, « must be of type App\P9\Liar, stdClass
  given ».

**Questions.** `QST-xw4d2n9a8hz5`, `QST-1ss1gkatj0te` (LEARNING) et
`QST-arfb4vy3eesd` (VALIDATION) relues : exactes, inchangées. L'item ne porte pas
de question holdout.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-j8n3qsftrmm5` reçoit le
niveau RECALL. L'item en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
4 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`string returned`, `stdClass given` et `__invoke`, absentes de la version
`master` de la page et de ses cartes.

**Contrôles réellement exécutés le 2026-09-30**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle 8.0.15 (2026-09-29) | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 051 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 10 — *Compiler passes* — RAFFINÉE

`CRS-d25bvr0097py` · `OIT-3y0b9gxyandm` · DEEP · **548 → 797 mots** sur 1 200.
Aucun niveau promu. Exécutions avec `symfony/dependency-injection` 8.0.15.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 9 du lot 09 (PR #262, `efdfbe6`) | 36680758843, success | `ok  lot-09  the factories page carries its four flashcard levels, the string return, the wrong class and the invokable factory` |

### Une question dont l'énoncé décrivait le mauvais moment

`QST-4nq445j72q7w` (LEARNING) disait qu'une passe `TYPE_AFTER_REMOVING` qui
référence un service privé fait « échouer la compilation ». Exécuté : `compile()`
**passe**, le vidage en PHP aussi ; c'est l'instanciation du consommateur qui lève
`ServiceNotFoundException` — « The "priv" service or alias has been removed or
inlined when the container was compiled ». La question passe en **v2** : l'énoncé
dit que le conteneur compile et que l'instanciation échoue. Bonne réponse et
choix inchangés. La page précise le moment de l'échec.

### Ajouté par l'exécution et la lecture du code

- Sept passes enregistrées dans le désordre s'exécutent dans l'ordre des cinq
  étapes, et dans l'étape par défaut par priorité décroissante (10, 0, -5) ;
  `PassConfig::sortPasses()` trie par `krsort`.
- Les passes du composant dans `TYPE_BEFORE_OPTIMIZATION` (autoconfiguration,
  `instanceof`) tournent à la priorité **100** : exécuté, une passe à 0 voit un
  tag posé par `registerForAutoconfiguration()`, une passe à 200 ne le voit pas.
- `HttpKernel\Kernel` enregistre un noyau-passe à la priorité **-10000**.
- `findTaggedServiceIds()` sur un service tagué deux fois :
  `{"x":[{"a":1},{"a":2}]}`.
- Dans une passe, `getDefinition()` rend une `Definition` et `initialized()`
  vaut `false`.
- Le tableau des étapes nomme désormais les passes du composant qui s'y
  exécutent, relues dans `PassConfig::__construct()`.

**Questions.** `QST-7ns2ad8j7z7a`, `QST-q1jqvtzhr8ga`, `QST-mkmarkb3w738`,
`QST-3wrz6qafpv3y` (LEARNING) et `QST-qcs153cscpem` (VALIDATION) relues :
exactes, inchangées. L'item ne porte pas de question holdout.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-3f3jxjqynjmj` reçoit le
niveau RECALL. L'item en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
4 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `10000`,
`initialized` et `krsort`, absentes de la version `master` de la page et de ses
cartes. `removed or inlined`, envisagée, figurait déjà dans les cartes du lot :
écartée.

**Contrôles réellement exécutés le 2026-09-30**

| Contrôle | Résultat |
|---|---|
| exécutions DependencyInjection 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 061 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 11 — *Services autowiring* — RAFFINÉE

`CRS-w6yfxm6ad4zy` · `OIT-wm3qdqemtap9` · DEEP · **597 → 873 mots** sur 1 200.
Aucun niveau promu. Exécutions dans l'application FrameworkBundle 8.0.15,
composants fixés en `8.0.*`.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 10 du lot 09 (PR #263, `36fd274`) | 36681861823, success | `ok  lot-09  the compiler passes page carries its four flashcard levels, the kernel priority, the uninitialized definition and the sort` |

### Ce que dit la documentation, ce que fait le code

`autowiring.rst` (8.0) avertit que `#[Target]` « **does not** accept service ids
or service aliases », et `QST-gscp8fh16xed` (LEARNING) en tirait un distracteur :
« The service id of the implementation to inject » — « The attribute explicitly
does not accept service ids ». Exécuté :

| `#[Target(…)]` | Résultat |
|---|---|
| `'App\P11\UppercaseTransformer'`, cible d'un alias nommé du même type | injecté |
| `'App\P11\Rot13Transformer'`, cible du seul alias par défaut | « no such target exists » |

`AutowirePass::getAutowiredReference()` accepte un identifiant **déjà visé par un
alias nommé du type**. Le distracteur devenait défendable : la question passe en
**v2**, ce choix est remplacé par `CHO-wvpdch891hrk` (« The name of a container
parameter holding the implementation's class »). Énoncé et bonne réponse
inchangés. Divergence **décidée pour le code** ; la règle d'écriture de la page
reste celle de la documentation — viser le nom de l'alias nommé.

### Un message cité de mémoire

La page citait « *argument type-hinted with interface … but no such service
exists* ». Le message réel, exécuté : « … references interface
"App\P11\TransformerInterface" but no such service exists. You should maybe
alias this interface to one of these existing services: … » — il nomme les
candidats. La page cite le message exact.

### Confirmé ou ajouté par l'exécution

- Alias par défaut, alias nommé et `#[Target]` : `rot13`, `upper`, `upper`.
- Une faute de frappe dans le **nom d'argument** retombe en silence sur l'alias
  par défaut ; la même dans `#[Target]` lève « … no such target exists. Did you
  mean to target "shoutyTransformer" instead? ».
- `#[Target('shouty.transformer')]` : le nom est normalisé en camelCase.
- `debug:autowiring Transformer` liste l'alias ordinaire et l'alias nommé.
- Un `?App\Missing\Nope $opt = null` reçoit `null`.
- Scalaire : « is type-hinted "string", you should configure its value
  explicitly » — mais seulement pour un service conservé : privé et inutilisé,
  il est retiré sans erreur. La page nuance « l'erreur apparaît au build ».

**Questions.** `QST-mg33edgvxqrc`, `QST-egkw6pqr1hvq` (LEARNING) et
`QST-959s75p98aqk` (VALIDATION) relues : exactes, inchangées. La question holdout
de l'item n'a été ni lue ni modifiée.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-yze03t40rkqr` reçoit le
niveau UNDERSTANDING. L'item en porte **11** (3 RECALL, 2 UNDERSTANDING,
2 APPLICATION, 4 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`no such target exists`, `shouty.transformer` et `configure its value
explicitly`, absentes de la version `master` de la page et de ses cartes.

**Contrôles réellement exécutés le 2026-09-30**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 071 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 12 — *Service locators* — RAFFINÉE

`CRS-0a0d5bp6769e` · `OIT-gkhcbtygef69` · STANDARD · **354 → 571 mots** sur 900.
Aucun niveau promu. Exécutions dans l'application FrameworkBundle 8.0.15,
composants fixés en `8.0.*`.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 11 du lot 09 (PR #264, `c520772`) | 36747487825, success | `ok  lot-09  the services autowiring page carries its four flashcard levels, the Target typo, the normalised name and the scalar error` |

### Rien de faux, beaucoup d'implicite

Aucune affirmation de la page n'a été infirmée par l'exécution. Elles étaient
en revanche énoncées sans preuve ; elles sont désormais montrées :

| Situation | Résultat exécuté |
|---|---|
| après construction du bus | aucun gestionnaire construit |
| `get(FooHandler::class)` | construit à ce moment, **même instance** que celle du conteneur |
| `?App\P12\Missing` absent | `has()` rend `false` |
| le même, sans `?` | la compilation échoue : « has a dependency on a non-existent service » |
| `get()` d'un service non déclaré | « … is a smaller service locator that only knows about … » |
| `ChildBus` sans `parent::` | `has(FooHandler::class)` vaut `false`, aucune erreur avant l'usage |

`getSubscribedServices()` est bien `public static` dans
`ServiceSubscriberInterface` (8.0).

### Une explication trop courte

`QST-dbs1pm3v1np1` (LEARNING) disait que la forme tag de `#[AutowireLocator]`
donne une table « keyed by the tag's index attribute ». Exécuté à la page 7 :
l'`index` quand le service en a un, **son identifiant sinon**. Explication
complétée, version conservée.

**Questions.** `QST-gkhpzxneegbr`, `QST-qh1cbg5wp55k` (LEARNING) et
`QST-zq72eg2anghn` (VALIDATION) relues : exactes, inchangées. L'item ne porte pas
de question holdout.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-zn7hhaddbzd2` reçoit le
niveau RECALL. L'item en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
4 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`smaller service locator`, `non-existent service` et `ChildBus`, absentes de la
version `master` de la page et de ses cartes.

**Contrôles réellement exécutés le 2026-09-30**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 081 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Rapport de fin de lot 09 ci-dessous ; ensuite lot 10.

# Rapport de fin de lot 09

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire (`CLAUDE.md`, « Reporting a lot »). Base de comparaison :
`a91c2ba`, le commit de `master` qui précède la première page refondue (PR #254).
État mesuré : `master` à `d561017`. Le même script a vérifié que les douze
décomptes de mots et de niveaux écrits dans les entrées de page correspondent
aux fichiers.

## Périmètre

**12** items officiels atomiques portent `lot: lot-09` dans la matrice :
1 `MINIMAL`, 9 `STANDARD`, 2 `DEEP` — niveaux inchangés pendant la campagne.
Cette répartition est une **observation** : aucune cible n'existe.

## Couverture — formule unique (§3.5)

```text
EXAM_READY atomiques officiels / total atomiques officiels
= 163 / 163 = 100,0 %
```

Ce chiffre est **cumulatif et porte sur tout le projet**. Le sous-ensemble du
lot 09 est **12 / 12**. Aucun des deux n'a bougé : **ce lot n'a pas fait
progresser la couverture** — il a approfondi et corrigé des pages déjà comptées.

## Volume de cours — corps en mots, front matter exclu

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| 12 cours du lot 09 | 5 077 | **7 815** | **+2 738** |

Aucune page ne dépasse son budget `REV-001` :

| Niveau | Budget | Pages | Plus proche du plafond |
|---|---|---|---|
| `MINIMAL` | 700 | 1 | Built-in services, 560 |
| `STANDARD` | 900 | 9 | Configuration parameters, 667 |
| `DEEP` | 1200 | 2 | Services autowiring, 873 |

## Flashcards

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| Cartes sur les items du lot 09 | 12 | **132** | **+120** |

Répartition par niveau — **observation, jamais une cible** :
`RECALL` 38 · `UNDERSTANDING` 26 · `APPLICATION` 24 · `TRAP` 44.
**Zéro carte du lot sans niveau** ; chaque item en porte 11. Les douze cartes
préexistantes ont reçu un niveau ; aucune n'a été supprimée ; deux ont été
corrigées au-delà du niveau (`FLC-6a9ngbsfteyg`, page 5 ; `FLC-tv2t8a6yt50g`,
page 7), décompte relevé par script.

## Questions et pools

**53** questions portent sur les items du lot 09 — aucune ajoutée, aucune
supprimée :

| Pool | Nombre | Fichier |
|---|---|---|
| `LEARNING` | 36 | `lot-09-dependency-injection.yml` |
| `VALIDATION` | 11 | `lot-09-dependency-injection.yml` |
| `HOLDOUT` | 6 | 2 dans `lot-09-dependency-injection.yml`, 4 dans `mock-04-holdout.yml` |

**13** questions non holdout corrigées, dont **5** passées en v2 :

| Question | Pool | Version | Correction |
|---|---|---|---|
| `QST-0qda3mr144nt` | VALIDATION | 1 → 2 | énoncé aligné sur le code ; explication de distracteur fausse |
| `QST-hp7d9gbzhfk0` | VALIDATION | 1 → 2 | distracteur défendable remplacé |
| `QST-smjza039q4hw` | LEARNING | 1 → 2 | énoncé ambigu avec `#[When]` |
| `QST-4nq445j72q7w` | LEARNING | 1 → 2 | l'échec survient à l'instanciation, pas à la compilation |
| `QST-gscp8fh16xed` | LEARNING | 1 → 2 | distracteur devenu défendable (`#[Target]` et identifiants) |
| `QST-sxw56zsgz0we` | VALIDATION | 1 | explication et source de l'échappement `%` |
| `QST-8e8zfpay4pj4` | VALIDATION | 1 | explication fondée sur un défaut supposé |
| `QST-cg1wnyzmwgqg` | VALIDATION | 1 | condition de transparence de la décoration |
| `QST-1zvtze0w6a5p` | LEARNING | 1 | explication de distracteur trop absolue |
| `QST-3zf09tnf1rkj` | LEARNING | 1 | nom réel de l'original décoré |
| `QST-qzgxkh2b2pa5` | LEARNING | 1 | exemple de prepend faux (gabarits Twig) |
| `QST-a1avrk2v0k2t` | LEARNING | 1 | mécanisme d'autoconfiguration du framework |
| `QST-dbs1pm3v1np1` | LEARNING | 1 | clés d'un locator par tag |

**0 question holdout modifiée** (comparaison par script, sans lecture du
contenu). `POOL-002` : 11 items `STANDARD`/`DEEP` `EXAM_READY`, **0** sans
question `VALIDATION`.

## Ce que dit la documentation, ce que fait le code

| Page | Documentation 8.0 | Code 8.0.15, exécuté | Décision |
|---|---|---|---|
| 11 | `autowiring.rst` : `#[Target]` « does not accept service ids » | un identifiant passe s'il est déjà la cible d'un alias nommé du type | code ; la règle d'écriture reste celle de la doc |

## Erreur de méthode, corrigée pendant la campagne

La première exécution de la page 3 a tourné avec `dependency-injection` **8.1.8**,
Composer n'ayant contraint que FrameworkBundle. Tous les composants `symfony/*`
du bac à sable ont été fixés à `8.0.*` et chaque exécution refaite, avec des
sorties identiques ; les exécutions CSRF du lot 07 ont été revérifiées de même.
Consigné à la page 3.

## Signaux pour le holdout — à revoir par l'owner

Six questions holdout portent sur des items du lot : 2 dans
`lot-09-dependency-injection.yml`, 4 dans `mock-04-holdout.yml`. Aucune n'a été
lue. Deux faits établis pendant la campagne pourraient en concerner certaines,
sans que cela soit vérifié :

- `#[Target]` accepte un identifiant de service déjà visé par un alias nommé,
  contrairement à la documentation.
- Une référence posée en `TYPE_AFTER_REMOVING` vers un service supprimé échoue à
  l'instanciation, pas à la compilation.

## Déploiements

Les douze pages ont été fusionnées par PR (#254 à #265), chacune avec CI verte,
déployée par le workflow Pages, et sa ligne de smoke test lue en production. La
page 12 : run 36749204957, success — `ok  lot-09  the service locators page carries its four flashcard levels, the restricted locator, the compile failure and the child bus`.

## Portes, au moment du rapport

Exécutées le 2026-09-30 sur la branche du rapport, au-dessus de `d561017` :

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant (1 avertissement `PED-003` préexistant, lot 07) |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| `composer gate-full` | exit 0 — 299 tests, 17 081 assertions ; TOTAL VIOLATIONS: 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |
| décomptes des douze entrées de page contre les fichiers | 12 / 12 concordants |

## Résumé autonome

Lot 09 (*Dependency Injection*), 12 items (1 MINIMAL, 9 STANDARD, 2 DEEP) :
couverture projet 163/163 inchangée ; cours 5 077 → 7 815 mots (+2 738), aucun
dépassement de budget ; flashcards 12 → 132 (+120), toutes niveau posé ;
53 questions, 13 corrigées dont 5 en v2, 0 holdout modifiée ; une divergence
documentation/code décidée pour le code ; douze pages déployées, smoke tests lus.
