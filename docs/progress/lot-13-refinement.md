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
| 7 | Client configuration | STANDARD | 406 / 900 | 1 | à faire |
| 8 | Request and response objects introspection | STANDARD | 424 / 900 | 1 | à faire |
| 9 | Handling legacy deprecated code | MINIMAL | 396 / 700 | 1 | à faire |

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

## Prochaine étape

Page 8 — *Request and response objects introspection* (STANDARD, 424 / 900).
