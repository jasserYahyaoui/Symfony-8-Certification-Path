# Raffinement pédagogique — Lot 13 (Automated tests)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 12 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; quand la documentation et le
code divergent, le code l'emporte et l'écart est signalé sur la page ; budget
`REV-001` respecté sans promotion de niveau ; une branche, une PR, une CI verte,
une fusion et un smoke test de production **lu** par page. Le bac à sable
d'exécution a reçu pour ce lot PHPUnit 11.5.56 et `symfony/phpunit-bridge`
8.0.14 ; la vérification « aucun paquet `symfony/*` hors 8.0 » a été refaite
après l'ajout. Le comportement de PHPUnit lui-même est cité comme **exécuté**,
jamais comme source : son dépôt ne figure pas dans `source-map.yml`.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-02 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `f2d8281`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Unit tests with PHPUnit | STANDARD | 421 / 900 | 1 | **RAFFINÉE** (PR #298) |
| 2 | Functional tests with PHPUnit | STANDARD | 410 / 900 | 1 | **RAFFINÉE** (PR #299) |
| 3 | Client object | STANDARD | 524 / 900 | 1 | **RAFFINÉE** (PR #300) |
| 4 | Crawler object (CssSelector and DomCrawler components) | STANDARD | 538 / 900 | 1 | **RAFFINÉE** (PR #301) |
| 5 | Profiler object (WebProfiler bundle) | MINIMAL | 285 / 700 | 1 | **RAFFINÉE** (PR #302) |
| 6 | Framework objects access | STANDARD | 431 / 900 | 1 | **RAFFINÉE** (PR #303) |
| 7 | Client configuration | STANDARD | 406 / 900 | 1 | **RAFFINÉE** (PR #304) |
| 8 | Request and response objects introspection | STANDARD | 424 / 900 | 1 | **RAFFINÉE** (PR #305) |
| 9 | Handling legacy deprecated code | MINIMAL | 396 / 700 | 1 | **RAFFINÉE** (PR #306) |

## Page 1 — *Unit tests with PHPUnit* — RAFFINÉE

`CRS-tt9yn0a06ngb` · `OIT-c6wd3f444qjn` · STANDARD · **421 → 579 mots** sur 900.
Aucun niveau promu. Exécutions sur PHPUnit 11.5.56 avec FrameworkBundle
8.0.15 : une suite de trois classes, puis les trois noms de configuration
posés ensemble et retirés un à un.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 12 (PR #297, `f2d8281`) | 37022826907, success | `ok  lot-12  the verbosity page carries its four flashcard levels, the silent test, the quiet threshold and the variable precedence` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### Une affirmation retirée, faute de source admise

La page écrivait « `php bin/phpunit`, pas `vendor/bin/phpunit` : Flex installe
ce lanceur ». Le lanceur relève de la recette Flex, dont le dépôt n'est pas
dans `source-map.yml` ; la documentation 8.0 dit seulement d'utiliser
`php bin/phpunit`. La page s'en tient à cela.

### Une affirmation imprécise : les noms de configuration

« À partir de PHPUnit 10 c'est `phpunit.dist.xml` » est ce que dit la
documentation, mais ne dit pas que l'ancien nom est encore lu. Exécuté :

| Fichiers présents | Configuration retenue |
|---|---|
| `phpunit.xml`, `phpunit.dist.xml`, `phpunit.xml.dist` | `phpunit.xml` |
| `phpunit.dist.xml`, `phpunit.xml.dist` | `phpunit.dist.xml` |
| `phpunit.xml.dist` seul | `phpunit.xml.dist` |

### Confirmé par l'exécution

- Suffixe : `CalcTest.php` et `CalcTests.php` dans un même répertoire — le
  parcours n'exécute que le premier ; le second tourne s'il est désigné.
- Frontière unitaire / intégration : un `TestCase` passe sans configuration ;
  un `KernelTestCase` qui appelle `bootKernel()` sans `KERNEL_CLASS` lève
  `LogicException` (« You must set the KERNEL_CLASS environment variable… »),
  lu dans `KernelTestCase::getKernelClass()`.

**Questions.** `QST-rsbg20z3fzdk`, `QST-vfkmqvxkehvn`, `QST-3ayfnb7ae5bq`
(LEARNING) et `QST-3p3j00y1dgsg` (VALIDATION) relues : exactes, inchangées.
L'item n'a pas de question holdout.

**Flashcards.** 10 ajoutées ; `FLC-8p0tpa6x1krf` reçoit le niveau
UNDERSTANDING. L'item en porte **11** (3 RECALL, 3 UNDERSTANDING,
2 APPLICATION, 3 TRAP), décompte relevé par script sur tous les fichiers de
cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `KERNEL_CLASS`,
`CalcTests.php` et `encore lu`, absentes de la version `master` de la page et
des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions PHPUnit 11.5 + FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 371 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 2 — *Functional tests with PHPUnit* — RAFFINÉE

`CRS-1en96h22twv7` · `OIT-favt42nvgdqh` · STANDARD · **410 → 571 mots** sur 900.
Aucun niveau promu. Exécutions sur PHPUnit 11.5.56 avec FrameworkBundle
8.0.15 : un `WebTestCase` réel contre deux routes du bac à sable, puis Dotenv
8.0.15 sur quatre fichiers `.env*` et une variable du shell.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 1 du lot 13 (PR #298, `b4494b4`) | 37038950760, success | `ok  lot-13  the unit tests page carries its four flashcard levels, the kernel class, the file suffix and the config precedence` |

### Ce que la page affirmait sans le montrer

La page était exacte ; elle ne donnait aucune des conséquences observables.
Exécuté :

| Cas | Résultat |
|---|---|
| `GET /hello`, `assertResponseIsSuccessful()`, `assertRouteSame('hello')` | succès |
| réponse 201, `assertResponseIsSuccessful()` | succès — tout le 2xx |
| `bootKernel()` puis `createClient()` | `LogicException` « Booting the kernel before calling … is not supported » |
| 404, assertion par défaut | en-têtes **et** corps dans le message d'échec |
| 404, après `setBrowserKitAssertionsAsVerbose(false)` | en-têtes seulement |

Dotenv, lu dans `Dotenv::loadEnv()` puis exécuté :

| Situation | Valeur de `A` |
|---|---|
| `APP_ENV=test`, les quatre fichiers | `.env.test.local` |
| `APP_ENV=test`, sans `.env.test.local` | `.env.test` |
| `APP_ENV=dev` | `.env.local` |
| `APP_ENV=test`, `A` dans le shell | le shell |

Une variable présente seulement dans `.env.local` est absente en `test`.
Le conseil « URL en dur » est relu dans `testing.rst` (8.0).

**Questions.** `QST-b4g2ascr897e`, `QST-9d4z5adb9sez` (LEARNING) et
`QST-cppkfczac4h1` (VALIDATION) relues : exactes, inchangées. L'item n'a pas
de question holdout.

**Flashcards.** 10 ajoutées ; `FLC-2g536z94cx54` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`should only be`, `setBrowserKitAssertionsAsVerbose` et `celle du shell`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions PHPUnit 11.5 + FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 381 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 3 — *Client object* — RAFFINÉE

`CRS-k5wg55q2mg0p` · `OIT-bc3brs4a4mtt` · STANDARD · **524 → 762 mots** sur 900.
Aucun niveau promu. Exécutions sur PHPUnit 11.5.56 avec FrameworkBundle et
SecurityBundle 8.0.15 : six scénarios du client contre les routes du bac à
sable, puis un noyau dédié, avec un pare-feu en mémoire, pour `loginUser()`.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 2 du lot 13 (PR #299, `7009892`) | 37040183505, success | `ok  lot-13  the functional tests page carries its four flashcard levels, the double boot, the verbose failure and the env precedence` |

### Une question VALIDATION vraie dans un seul cas : la connexion perdue

`QST-kyyb7h7x6z2b` demandait pourquoi un utilisateur connecté n'est plus
authentifié à la deuxième requête, réponse « le client redémarre le noyau ».
Lu dans `KernelBrowser::loginUser()` : le jeton va dans le stockage de jetons
**et** dans la session. Exécuté :

| Pare-feu | 1ʳᵉ requête | 2ᵉ requête |
|---|---|---|
| avec état | `alice` | `alice` |
| `stateless: true` | `alice` | anonyme |

**→ v2** : l'énoncé précise le pare-feu `stateless` ; choix inchangés,
explications réécrites avec l'exécution. La page dit désormais la même chose.

### Un défaut qui n'est pas celui de BrowserKit

« Le client ne suit pas les redirections par défaut » est exact, mais la
raison manquait. Lu dans le code 8.0 : `AbstractBrowser` déclare
`$followRedirects = true` ; `HttpKernelBrowser`, parent de `KernelBrowser`, le
passe à `false` dans son constructeur. Exécuté : `GET /go` → 302,
`isFollowingRedirects()` faux ; `followRedirect()` → 200 sur `/hello`.

### Confirmé par l'exécution

- `request()` retourne un `Crawler`.
- `back()` saute la redirection : `/created`, puis `/go` suivi jusqu'à
  `/hello` ; `back()` ramène à `/created`. Une première sonde, sans suivre les
  redirections, ne prouvait rien ; refaite.
- Deux requêtes, deux objets conteneur ; après `disableReboot()`, le même.
- `catchExceptions(false)` : 500 par défaut, puis `RuntimeException('kaboom')`.
- `xmlHttpRequest()` : `isXmlHttpRequest()` vrai côté serveur.

**Questions.** `QST-qznc9nx72jdq`, `QST-b27q0jdvhz16` (LEARNING) relues :
exactes, inchangées. `QST-kyyb7h7x6z2b` (VALIDATION) en v2, ci-dessus. Aucune
question holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-pdapm9phgtmc` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`HttpKernelBrowser`, `stateless: true` et `deux objets conteneur`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions PHPUnit 11.5 + FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 391 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 4 — *Crawler object (CssSelector and DomCrawler components)* — RAFFINÉE

`CRS-zg5jg6hqp2mj` · `OIT-se21g2xv6h4r` · STANDARD · **538 → 786 mots** sur 900.
Aucun niveau promu. Exécutions sur DomCrawler et CssSelector 8.0.15 : un
document de test parcouru, extrait, puis un formulaire à deux boutons
transformé en `Form` de trois façons.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 3 du lot 13 (PR #300, `923ae44`) | 37041787873, success | `ok  lot-13  the client page carries its four flashcard levels, the redirect default, the stateless login and the container reboot` |

### La documentation se contredit, le code tranche : sélectionner un bouton

`testing.rst` (8.0) : « you select form buttons and not forms… you must look
for a button ». `components/dom_crawler.rst` (8.0) montre pourtant
`$crawler->filter('.form-vertical')->form()`. Lu dans `Form::setNode()` : un
`<button>`, un `<input type="submit">` **ou** un `<form>` sont acceptés.
Exécuté :

| Origine du `Form` | Valeurs envoyées |
|---|---|
| `selectButton('Delete')->form()` | `b=B`, `q=1` |
| le `<form>` | `q=1` |
| un `<input>` ordinaire | `LogicException` « Unable to submit on a "input" tag. » |

La page présente les deux, et dit ce que le `<form>` seul perd.

**`QST-7v7dsbmdfdgt` (LEARNING) → v2.** Sa bonne réponse — « a button » — se
heurtait à un distracteur valide, « the form element itself ». Réécrite sur
le cas où seul le bouton convient : deux boutons, soumettre comme si Delete
était cliqué. Bonne réponse nouvelle (`CHOID`), ni la plus longue ni la plus
courte ; explications des distracteurs réécrites avec l'exécution.

### Une ligne de code trompeuse : `text(null, true)`

Commentée « en normalisant les espaces », elle laissait croire que la
normalisation est optionnelle. Lu dans `Crawler::text()` :
`bool $normalizeWhitespace = true`. Exécuté : `"Hello World"` par défaut,
texte brut avec `text(null, false)`. Ligne remplacée.

### Confirmé par l'exécution

- `text()` et `attr()` sur une sélection vide : `InvalidArgumentException`
  « The current node list is empty. » ; avec une valeur par défaut, la valeur.
- `text()` sur trois `<li>` : le premier ; `siblings()` exclut le nœud
  courant ; `ancestors()` : `ul`, `body`, `html` ; la sélection d'origine reste
  à 3 nœuds après `first()`.
- `selectButton()` trouve un bouton par son texte, son `id`, son `name`, ou la
  valeur d'un `<input type="submit">`.

**Questions.** `QST-k2pbvcnet7mh` (LEARNING) et `QST-aany4cpa165m`
(VALIDATION) relues : exactes, inchangées. Aucune question holdout lue ni
modifiée.

**Flashcards.** 10 ajoutées ; `FLC-0cvzxn60pazy` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`Unable to submit on a`, `The current node list is empty` et `text(null, false)`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions PHPUnit 11.5 + FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 401 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 5 — *Profiler object (WebProfiler bundle)* — RAFFINÉE

`CRS-0x4v9jaz4cnq` · `OIT-t0qx5z264tyf` · MINIMAL · **285 → 424 mots** sur 700.
Aucun niveau promu. Exécutions sur FrameworkBundle 8.0.15 et PHPUnit 11.5.56 :
un `WebTestCase` sous trois configurations du profileur, sans
WebProfilerBundle — le service `profiler` vient de FrameworkBundle.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 4 du lot 13 (PR #301, `c3cec05`) | 37043497027, success | `ok  lot-13  the crawler page carries its four flashcard levels, the form node, the empty list and the raw text` |

### Ce que la page affirmait sans le montrer

La page était exacte. Exécuté :

| Configuration | Requête | `getProfile()` |
|---|---|---|
| aucune clé `profiler` | après `enableProfiler()` | `false` |
| `enabled: true, collect: false` | sans `enableProfiler()` | `null` |
| idem | juste après `enableProfiler()` | un `Profile` |
| idem | la requête suivante | `null` |
| `enabled: true, collect: true` | chacune | un `Profile` |

Lu dans `KernelBrowser` : `enableProfiler()` ne fait rien sans service
`profiler` ; `getProfile()` rend `false` dans ce cas, sinon ce que rend
`loadProfileFromResponse()` — `null` quand rien n'a été collecté. Le profil
exécuté porte les collecteurs `request`, `command`, `time`, `memory`… ;
`getCollector('request')->getRoute()` rend `hello`.

**Questions.** `QST-vnr67m55bz2h` et `QST-dc8hsmx14h1s` (LEARNING) relues :
exactes, inchangées. L'item n'a ni question VALIDATION (MINIMAL) ni question
holdout.

**Flashcards.** 10 ajoutées ; `FLC-57vzb0a87say` reçoit le niveau TRAP. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`pas de profileur`, `rien collecté` et `collect: true`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions PHPUnit 11.5 + FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 411 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 6 — *Framework objects access* — RAFFINÉE

`CRS-g8v45328cg95` · `OIT-mtbcyaax2xsk` · STANDARD · **431 → 612 mots** sur 900.
Aucun niveau promu. Exécutions sur FrameworkBundle 8.0.15 et PHPUnit 11.5.56 :
un `KernelTestCase` sur trois services autoconfigurés — un utilisé, son
alias, un inutilisé — et une doublure posée avant puis après usage.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 5 du lot 13 (PR #302, `6bcb3e0`) | 37045105925, success | `ok  lot-13  the profiler page carries its four flashcard levels, the missing profiler, the uncollected response and the global collection` |

### Une question VALIDATION au symptôme inexact

`QST-kyaxpk3c138c` décrivait un test où, la doublure posée trop tard, « the
tested service still receives the real one ». Exécuté : `get(Greeter)` puis
`set(ClockInterface, …)` ne passe pas en silence — `InvalidArgumentException`
« …service is already initialized, you cannot replace it. », levée par
`TestContainer::set()`. **→ v2** : l'énoncé décrit ce message ; bonne réponse et
choix inchangés, explication complétée par l'exécution.

### Confirmé par l'exécution ou la lecture

| `get()` sur… | Conteneur de test | Conteneur du noyau |
|---|---|---|
| `ClockInterface`, `Clock` (privés, utilisés) | l'instance | `ServiceNotFoundException` (exécuté sur `Clock`) |
| `Unused` (privé, inutilisé) | `ServiceNotFoundException` « …removed or inlined… » | idem |

- `getContainer()` démarre le noyau s'il ne l'est pas et rend
  `test.service_container`, un `TestContainer` ; sans `framework.test`,
  `LogicException` (lu).
- `set()` avant `get()` : `hello at fake`.
- Deux tests, deux conteneurs : le noyau redémarre à chaque test.
- `debug: false` : la documentation dit « disables clearing the cache » ; la
  page reprend cette formulation et la mise en garde qui l'accompagne.

**Questions.** `QST-tc71xv48p476`, `QST-qa5pm7hv1d2a` (LEARNING) relues :
exactes, inchangées. `QST-kyaxpk3c138c` (VALIDATION) en v2, ci-dessus. Aucune
question holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-mkp9h3yfzm3x` reçoit le niveau
UNDERSTANDING. L'item en porte **11** (3 RECALL, 3 UNDERSTANDING,
2 APPLICATION, 3 TRAP), décompte relevé par script sur tous les fichiers de
cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`TestContainer`, `removed or inlined` et `already initialized`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions PHPUnit 11.5 + FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 421 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 7 — *Client configuration* — RAFFINÉE

`CRS-4x4ec26c9bfc` · `OIT-bswvewkkkj1q` · STANDARD · **406 → 517 mots** sur 900.
Aucun niveau promu. Exécutions sur FrameworkBundle 8.0.15 et PHPUnit 11.5.56 :
un contrôleur qui renvoie ce qu'il lit dans la requête et la session, appelé
avec plusieurs nommages d'en-têtes et plusieurs ordres d'écriture en session.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 6 du lot 13 (PR #303, `212d8c6`) | 37046560960, success | `ok  lot-13  the framework objects page carries its four flashcard levels, the test container, the removed service and the late double` |

### Ce que la page affirmait sans le montrer

La page était exacte. Exécuté :

| Cas | Résultat côté serveur |
|---|---|
| `HTTP_X_SESSION_TOKEN` | `X-Session-Token` = `abc` |
| `X-Session-Token`, `X_SESSION_TOKEN` | `null` |
| `CONTENT_TYPE` | `Content-Type` présent |
| `HTTP_HOST` dans `createClient()` | encore valable à la deuxième requête |
| session, `set()` sans `save()` | `null` |
| session, `set()` + `save()` | la valeur |
| session écrite et sauvée avant toute requête | lue par la première requête |

L'agent utilisateur par défaut vaut `Symfony BrowserKit`. L'exemple CSRF est
relu dans `testing.rst` (8.0), « Accessing the Session ».

**Questions.** `QST-vtbv0rcpscbs`, `QST-0j3a50p7fbtn` (LEARNING) et
`QST-d4rhfyhajr7w` (VALIDATION) relues : exactes, inchangées. Aucune question
holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-31xxh9cs2ah8` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`arrive bien comme`, `Symfony BrowserKit` et `unsaved`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions PHPUnit 11.5 + FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 431 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 8 — *Request and response objects introspection* — RAFFINÉE

`CRS-xkp7v8142jt1` · `OIT-bzkq4e7wks9a` · STANDARD · **424 → 767 mots** sur 900.
Aucun niveau promu. Exécutions sur FrameworkBundle 8.0.15, BrowserKit 8.0.14 et
PHPUnit 11.5.56 : chaque accesseur appelé avant et après une requête, puis sur
une route qui redirige.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 7 du lot 13 (PR #304, `cf87652`) | 37048125367, success | `ok  lot-13  the client configuration page carries its four flashcard levels, the cgi header, the default agent and the unsaved session` |

### Ce que la page affirmait sans le montrer

La page était exacte. Une phrase restait vague : « ces méthodes n'ont pas de
sens avant » une requête. Exécuté :

| Cas | Résultat |
|---|---|
| `getRequest()`, `getResponse()`, `getInternal*()`, `getCrawler()` avant requête | `BadMethodCallException` (`AbstractBrowser.php`, 8.0) |
| `getHistory()`, `getCookieJar()` avant requête | objets utilisables (historique vide, cookie posé) |
| classes rendues après requête | `HttpFoundation\Request` / `Response`, `BrowserKit\Request` / `Response`, `History`, `CookieJar`, `DomCrawler\Crawler` |
| `getInternalRequest()->getUri()` | `http://localhost/hello` ; `getPathInfo()` rend `/hello` |
| `request('GET', '/go')` puis `followRedirect()` | 302 et `_route` = `go`, puis 200 et `hello` |
| `assertSame()` sur le statut brut contre `assertResponseStatusCodeSame(404)` | deux nombres contre la réponse entière |

La phrase vague est remplacée par le tableau ; la page gagne une section
*Après une redirection*.

### Une erreur de méthode, corrigée avant le commit

La première version du tableau d'assertions écrivait l'appel brut avec une
variable `$response`. `CRS-001` a bloqué : la chaîne reproduisait la bonne
réponse d'une question VALIDATION d'un autre item (HttpClient). La ligne dit
désormais « `assertSame()` sur le code de statut brut » ; la carte
correspondante a été reformulée de même. Aucun déplacement dans un bloc de code.

**Questions.** `QST-m9kesqa3bb7j`, `QST-0y00gjhre739` (LEARNING) et
`QST-mmz7113w0hqb` (VALIDATION) relues : exactes, inchangées. Aucune question
holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-80h962pnszqx` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`Avant la première requête`, `Après une redirection` et `la requête qui a redirigé`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions PHPUnit 11.5 + FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 441 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 9 — *Handling legacy deprecated code* — RAFFINÉE

`CRS-fak8bf9014br` · `OIT-vj24gwq6r1r4` · MINIMAL · **396 → 688 mots** sur 700.
Aucun niveau promu. Sources relues sur la branche 8.0 (`upgrade_minor.rst`,
`upgrade_major.rst`, `framework.rst`, `conventions.rst`, `function.php`,
`ErrorHandler.php`) ; exécutions sur PHP 8.4, ErrorHandler 8.0.15 et
PHPUnit 11.5.56.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 8 du lot 13 (PR #305, `8000aab`) | 37056286069, success | `ok  lot-13  the introspection page carries its four flashcard levels, the early exception, the unfollowed redirect and the redirecting route` |

### Une affirmation fausse

La page disait d'une dépréciation : « rien ne s'affiche spontanément, **même en
développement** ». `upgrade_major.rst` (8.0) dit l'inverse pour l'environnement
dev : *these notices are shown in the web dev toolbar*. La notice est bien
silencée — rien n'apparaît dans la sortie — mais le gestionnaire d'erreurs de
Symfony la recueille, et la barre de débogage la montre.

Exécuté :

| Situation | Résultat |
|---|---|
| `trigger_deprecation()`, aucun gestionnaire, `display_errors` actif | rien ; le script continue |
| `trigger_error(…, E_USER_DEPRECATED)` sans `@` | `Deprecated: …` dans la sortie |
| `ErrorHandler` 8.0 + journal | entrée `info` : `User Deprecated: Since acme/pkg 1.2: …` |
| test PHPUnit 11.5 avec `failOnDeprecation="true"` | `OK (1 test, 1 assertion)` |

**Corrigé.** La page, la carte `FLC-1qhdkw1e4nfy` (« rien ne s'affiche » →
« rien n'apparaît dans la sortie » ; sa source citait une phrase absente de
`upgrade_minor.rst`, remplacée par une citation réelle et par `function.php`),
et la question `QST-qspg3f4b1epq` (LEARNING) → **v2** : la bonne réponse
« Nothing visible » devient « A silenced E_USER_DEPRECATED notice; the call
works », nouvel identifiant de choix ; l'explication du distracteur « dev »
précise le rôle de la barre de débogage.

### Un objectif non enseigné

L'item porte deux objectifs : reconnaître les deux marqueurs d'une dépréciation,
et énoncer ses règles d'introduction. La page renvoyait les deux à *Deprecations
best practices* sans les redire, alors que `QST-7q4ebzsrj45x` les évalue sur cet
item. Ajout d'un rappel court : `@deprecated` et `trigger_deprecation()`, mineure
seulement, jamais sur du code nouveau, suppression à la majeure — relu dans
`conventions.rst` (8.0).

**Questions.** `QST-2b9q5n3vadem` et `QST-7q4ebzsrj45x` (LEARNING) relues :
exactes, inchangées. Aucune question holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-1qhdkw1e4nfy` reçoit le niveau TRAP. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`reconnaître une dépréciation`, `barre de débogage` et `failOnDeprecation`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions PHPUnit 11.5 + FrameworkBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 451 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

# Rapport de fin de lot 13

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire. Base de comparaison : `f2d8281`, le commit de `master`
qui précède la première page refondue (PR #298). État mesuré : `f12885f`
(fusion de la page 9). Le script de réconciliation est celui des lots 10 à 12.
Un second script a confronté les décomptes de cartes écrits dans les neuf
entrées de page aux fichiers : **9 / 9** concordent.

## Périmètre

**9** items officiels atomiques portent `lot: lot-13` dans la matrice :
2 `MINIMAL`, 7 `STANDARD` — niveaux inchangés pendant la campagne. Cette
répartition est une **observation** : aucune cible n'existe.

## Couverture — formule unique (§3.5)

```text
EXAM_READY atomiques officiels / total atomiques officiels
= 163 / 163 = 100,0 %
```

Ce chiffre est **cumulatif et porte sur tout le projet**. Le sous-ensemble du
lot 13 est **9 / 9**. Aucun des deux n'a bougé : **ce lot n'a pas fait
progresser la couverture** — il a approfondi et corrigé des pages déjà comptées.

## Volume de cours — corps en mots, front matter exclu

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| 9 cours du lot 13 | 3 835 | **5 706** | **+1 871** |

Aucune page ne dépasse son budget `REV-001` :

| Niveau | Budget | Pages | Plus proche du plafond |
|---|---|---|---|
| `MINIMAL` | 700 | 2 | Handling legacy deprecated code, 688 |
| `STANDARD` | 900 | 7 | Crawler object (CssSelector and DomCrawler components), 786 |

## Flashcards

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| Cartes sur les items du lot 13 | 9 | **99** | **+90** |

Répartition par niveau — **observation, jamais une cible** :
`RECALL` 27 · `UNDERSTANDING` 27 · `APPLICATION` 18 · `TRAP` 27.
**Zéro carte du lot sans niveau** ; chaque item en porte 11. Les neuf cartes
préexistantes ont reçu un niveau ; aucune n'a été supprimée ; une a été
corrigée au-delà du niveau (`FLC-1qhdkw1e4nfy`, page 9 : « rien ne s'affiche »,
et une source qui citait une phrase absente de `upgrade_minor.rst`).

## Questions et pools

**31** questions portent sur les items du lot 13 — aucune ajoutée, aucune
supprimée :

| Pool | Nombre | Fichier |
|---|---|---|
| `LEARNING` | 20 | `lot-13-automated-tests.yml` |
| `VALIDATION` | 7 | `lot-13-automated-tests.yml` |
| `HOLDOUT` | 4 | 2 dans `lot-13-automated-tests.yml`, 2 dans `mock-04-holdout.yml` |

**4** questions non holdout corrigées, toutes passées en v2 :

| Question | Pool | Version | Page | Correction |
|---|---|---|---|---|
| `QST-kyyb7h7x6z2b` | VALIDATION | 1 → 2 | 3 | la connexion perdue dépend d'un pare-feu `stateless` ; l'énoncé ne le disait pas |
| `QST-7v7dsbmdfdgt` | LEARNING | 1 → 2 | 4 | un `Form` construit depuis le nœud `<form>` est valide ; réécrite sur deux boutons |
| `QST-kyaxpk3c138c` | VALIDATION | 1 → 2 | 6 | une doublure posée trop tard lève « already initialized », elle ne passe pas en silence |
| `QST-qspg3f4b1epq` | LEARNING | 1 → 2 | 9 | « Nothing visible » — la barre de débogage les montre en dev |

**0 question holdout modifiée** (comparaison par empreinte SHA-256, sans lecture
du contenu). `POOL-002` : 7 items `STANDARD` `EXAM_READY`, **0** sans question
`VALIDATION`. **Matrice** : aucun texte modifié dans ce lot.

## Affirmations retirées ou corrigées sur les pages

| Page | Affirmation | Décision |
|---|---|---|
| 1 | « Flex installe `bin/phpunit` » | retirée : la recette Flex n'est pas une source admise |
| 9 | une dépréciation ne s'affiche pas « même en développement » | corrigée : `upgrade_major.rst` (8.0) dit l'inverse |
| 9 | la page renvoyait ses deux objectifs à une autre page | rappel ajouté, relu dans `conventions.rst` (8.0) |

Aucune divergence documentation / code n'a été relevée dans ce lot : les écarts
trouvés opposaient la page à la documentation, pas la documentation au code.

## Erreurs de méthode, corrigées pendant la campagne

- **Script d'application** (page 1) : il a écrit les cartes avant qu'une
  assertion sur le cours n'échoue ; le fichier de cartes a été restauré, le
  contrôle avancé avant toute écriture.
- **Sondes** (page 3) : une première sonde de `back()`, sans suivre les
  redirections, ne prouvait rien ; une sonde XHR appelait une méthode
  inexistante. Les deux ont été refaites avant d'en citer le résultat.
- **Message de commit** (page 5) : il citait WebProfilerBundle, absent de
  l'exécution ; corrigé sur la branche avant l'ouverture de la PR.
- **`CRS-001`** (page 8) : une ligne de tableau reproduisait la bonne réponse
  d'une question VALIDATION d'un autre item ; reformulée, sans recours à un bloc
  de code. Depuis, les brouillons sont vérifiés par un script qui reprend la
  logique de la règle avant application.

## Signaux pour le holdout — à revoir par l'owner

Quatre questions holdout portent sur des items du lot : 2 dans
`lot-13-automated-tests.yml`, 2 dans `mock-04-holdout.yml`. Aucune n'a été lue.
Des faits établis pendant la campagne pourraient en concerner certaines, sans
que cela soit vérifié :

- une connexion survit à la requête suivante sur un pare-feu avec état, pas sur
  un pare-feu `stateless` ;
- `KernelBrowser` ne suit pas les redirections (`HttpKernelBrowser` les
  désactive ; `AbstractBrowser` les active) ;
- un `Form` construit depuis le nœud `<form>` n'envoie aucun bouton ;
- `set()` après `get()` sur le conteneur de test lève « already initialized » ;
- un en-tête se passe au client sous la forme `HTTP_…` ; `CONTENT_TYPE` sans
  préfixe ;
- avant toute requête, les accesseurs de requête, de réponse et le crawler lèvent
  `BadMethodCallException`.

## Reprise identifiée hors du lot courant

Toujours ouverte, venue du lot 10 : la page 6 (*Firewalls*) n'indique pas que
`lazy` est ignoré sur un pare-feu `stateless`.

## Déploiements

Les neuf pages ont été fusionnées par PR (#298 à #306), chacune avec CI verte,
déployée par le workflow Pages, et sa ligne de smoke test lue en production. La
page 9 : run 37057912148, success — `ok  lot-13  the deprecated code page carries its four flashcard levels, the markers recap, the debug toolbar and the silenced notice`.

## Portes, au moment du rapport

Exécutées le 2026-10-02 sur la branche du rapport, au-dessus de `f12885f` :

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant (1 avertissement `PED-003` préexistant) |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| `composer gate-full` | exit 0 — 299 tests, 17 451 assertions ; TOTAL VIOLATIONS: 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |
| décomptes de cartes des neuf entrées de page contre les fichiers | 9 / 9 concordants |

## Résumé autonome

Lot 13 (*Automated tests*), 9 items (2 MINIMAL, 7 STANDARD) : couverture projet
163/163 inchangée ; cours 3 835 → 5 706 mots (+1 871), aucun dépassement de
budget ; flashcards 9 → 99 (+90), toutes niveau posé ; 31 questions, 4 corrigées
et passées en v2 (dont 2 VALIDATION), 0 holdout modifiée ; neuf pages déployées,
smoke tests lus ; aucune divergence documentation/code, deux affirmations de
page fausses ou sans source admise corrigées.
