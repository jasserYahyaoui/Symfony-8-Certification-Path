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
| 1 | Configuration (including DotEnv and ExpressionLanguage components) | STANDARD | 598 / 900 | 1 | **RAFFINÉE** (PR #308) |
| 2 | Error handling | STANDARD | 534 / 900 | 1 | **RAFFINÉE** (PR #309) |
| 3 | Code debugging | STANDARD | 589 / 900 | 1 | **RAFFINÉE** (PR #310) |

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

## Page 2 — *Error handling* — RAFFINÉE

`CRS-fppjrr6r23tk` · `OIT-kv7mbksn7m8v` · STANDARD · **534 → 807 mots** sur 900.
Aucun niveau promu. Exécutions sur Symfony 8.0.15 : un contrôleur qui lève
cinq sortes d'exceptions, avec et sans debug, deux gabarits d'erreur, et un
avertissement PHP sous `Debug::enable()` puis sous `ErrorHandler::register()`.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 1 du lot 14 (PR #308, `a6bed87`) | 37061457470, success | `ok  lot-14  the configuration page carries its four flashcard levels, the variables order, the last loaded file and the processor order` |

### Une affirmation fausse : le code de statut

La page disait que le statut vient de l'exception « d'une seule façon » :
`HttpExceptionInterface`, sinon 500. C'est ce que dit `error_pages.rst` (8.0) ;
le code en connaît davantage. Exécuté :

| Exception levée | Statut |
|---|---|
| `RuntimeException` | 500 |
| `#[WithHttpStatus(404)]` | 404 |
| `BadRequestException` (`RequestExceptionInterface`) | 400 |
| déclarée sous `framework.exceptions`, `status_code: 418` | 418 |
| route absente (`NotFoundHttpException`) | 404 |

Ordre lu dans `ErrorListener::logKernelException()` et
`FlattenException::createFromThrowable()`. Écart documentation / code signalé
sur la page. **Corrigé** : la page, la carte `FLC-63tca08n26r1`, et
l'explication de `QST-3bbtms3dk9ca` (LEARNING) — sa bonne réponse, « 500, for any
plain exception », reste exacte ; version inchangée, seule l'explication change.

### Une affirmation imprécise : les erreurs PHP

« Les erreurs PHP elles-mêmes sont converties en exceptions » : seulement si
`framework.php_errors.throw` est vrai, défaut `%kernel.debug%`. Exécuté sur une
clé de tableau absente : 500 `ErrorException` en debug, 200 sans debug.

### Confirmé par l'exécution

Sans debug, avec `error404.html.twig` et `error.html.twig` : le 404 rend le
premier, 500, 400 et 418 le second ; le gabarit reçoit `status_code` et
`status_text`. En debug, les gabarits sont ignorés.

**Questions.** `QST-cs8r5jz7rb07` (LEARNING) et `QST-s7m8f73zn04x`
(VALIDATION) relues : exactes, inchangées. L'item a une question holdout : non
lue, non modifiée — **signal pour l'owner** : si elle repose sur « interface,
sinon 500 », elle est à revoir.

**Flashcards.** 10 ajoutées ; `FLC-63tca08n26r1` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`WithHttpStatus`, `framework.exceptions` et `php_errors.throw`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 471 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 3 — *Code debugging* — RAFFINÉE

`CRS-wf523wdsg2xp` · `OIT-xgrbftkj67ds` · STANDARD · **589 → 765 mots** sur 900.
Aucun niveau promu. Exécutions sur Symfony 8.0.15 : un noyau `prod` servi par le
serveur web intégré de PHP, sans DebugBundle ; `router:match` et `debug:config`
lancés sur le même noyau.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 2 du lot 14 (PR #309, `b5c9f8c`) | 37062784234, success | `ok  lot-14  the error handling page carries its four flashcard levels, the status attribute, the exception mapping and the debug-only warning` |

### Une affirmation fausse : `dump()` en production

La page disait qu'un `dump()` oublié appelle en production une fonction absente,
d'où une `Error` fatale, puisque `symfony/var-dumper` s'installe en `--dev`. Lu
dans les `composer.json` de la branche 8.0 : FrameworkBundle exige
`symfony/error-handler`, qui exige `symfony/var-dumper` en dépendance ordinaire.
Exécuté :

| Observation | Résultat |
|---|---|
| `function_exists('dump')`, noyau `prod` | `true` |
| route qui appelle `dump()` | 200, dump HTML avant le contenu |
| en-têtes, comparés à une route sans dump | `Cache-Control` de Symfony absent ; `Content-type` de PHP |

**Corrigé** : la page, la carte `FLC-1f4raf4m8wng` (son verso affirmait l'erreur
fatale ; sa source citait l'installation `--dev` comme preuve), et l'explication
du distracteur « dd() works in production, dump() does not » de
`QST-btrt51f6nnnv` (LEARNING), qui disait les deux fonctions issues d'un paquet
de développement ; version inchangée.

### Une affirmation imprécise : `router:match`

« Elle montre les routes essayées et la raison de leur échec » : seulement avec
`-v`. Exécuté sur un chemin inconnu : sans `-v`, *None of the routes match* ;
avec, chaque route et sa raison.

**Questions.** `QST-1er6x1ts9xk4` (LEARNING) et `QST-qay1zj2m10me`
(VALIDATION) relues : exactes, inchangées. L'item a une question holdout : non
lue, non modifiée — **signal pour l'owner** : si elle repose sur l'erreur fatale
d'un `dump()` en production, elle est fausse.

**Flashcards.** 10 ajoutées ; `FLC-1f4raf4m8wng` reçoit le niveau TRAP. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`function_exists`, `Cache-Control` et `réponse corrompue`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 481 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

# Rapport de fin de lot 14

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire. Base de comparaison : `4a2a2c4`, le commit de `master`
qui précède la première page refondue (PR #308). État mesuré : `12e6bab`
(fusion de la page 3). Le script de réconciliation est celui des lots 10 à 13.
Un second script a confronté les décomptes de cartes écrits dans les trois
entrées de page aux fichiers : **3 / 3** concordent.

## Périmètre

**3** items officiels atomiques portent `lot: lot-14` dans la matrice, tous
`STANDARD` — niveaux inchangés pendant la campagne. Cette répartition est une
**observation** : aucune cible n'existe.

## Couverture — formule unique (§3.5)

```text
EXAM_READY atomiques officiels / total atomiques officiels
= 163 / 163 = 100,0 %
```

Ce chiffre est **cumulatif et porte sur tout le projet**. Le sous-ensemble du
lot 14 est **3 / 3**. Aucun des deux n'a bougé : **ce lot n'a pas fait
progresser la couverture** — il a approfondi et corrigé des pages déjà comptées.

## Volume de cours — corps en mots, front matter exclu

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| 3 cours du lot 14 | 1 721 | **2 469** | **+748** |

Aucune page ne dépasse son budget `REV-001` (`STANDARD`, 900). La plus proche du
plafond : *Configuration (including DotEnv and ExpressionLanguage components)*,
897.

## Flashcards

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| Cartes sur les items du lot 14 | 3 | **33** | **+30** |

Répartition par niveau — **observation, jamais une cible** :
`RECALL` 9 · `UNDERSTANDING` 9 · `APPLICATION` 6 · `TRAP` 9.
**Zéro carte du lot sans niveau** ; chaque item en porte 11. Les trois cartes
préexistantes ont reçu un niveau ; aucune n'a été supprimée ; deux ont été
corrigées au-delà du niveau, toutes deux parce qu'elles portaient une
affirmation fausse : `FLC-63tca08n26r1` (page 2, statut HTTP) et
`FLC-1f4raf4m8wng` (page 3, `dump()` en production).

## Questions et pools

**13** questions portent sur les items du lot 14 — aucune ajoutée, aucune
supprimée :

| Pool | Nombre | Fichier |
|---|---|---|
| `LEARNING` | 7 | `lot-14-miscellaneous.yml` |
| `VALIDATION` | 3 | `lot-14-miscellaneous.yml` |
| `HOLDOUT` | 3 | `mock-04-holdout.yml` |

**3** questions non holdout corrigées, dont **1** passée en v2 :

| Question | Pool | Version | Page | Correction |
|---|---|---|---|---|
| `QST-hs62x7n8hq15` | VALIDATION | 1 → 2 | 1 | distracteur « paramètre typé, variable non » en partie vrai ; remplacé |
| `QST-3bbtms3dk9ca` | LEARNING | 1 | 2 | explication « seule l'interface donne un statut » — fausse en 8.0 |
| `QST-btrt51f6nnnv` | LEARNING | 1 | 3 | explication « paquet de développement seulement » — fausse en 8.0 |

**0 question holdout modifiée** (comparaison par empreinte SHA-256, sans lecture
du contenu). `POOL-002` : 3 items `STANDARD` `EXAM_READY`, **0** sans question
`VALIDATION`. **Matrice** : aucun texte modifié dans ce lot.

## Ce que dit la documentation, ce que fait le code

| Page | Documentation 8.0 | Code 8.0, exécuté | Décision |
|---|---|---|---|
| 2 | `error_pages.rst` : le statut vient de `HttpExceptionInterface`, sinon 500 | aussi `#[WithHttpStatus]`, `framework.exceptions`, `RequestExceptionInterface` (400) — lu dans `ErrorListener`, `FlattenException` | le code l'emporte, l'écart est signalé sur la page |
| 3 | `var_dumper.rst` installe VarDumper en `--dev` | FrameworkBundle → ErrorHandler → VarDumper, dépendance ordinaire : `dump()` existe en production | l'installation documentée n'est pas niée ; la conséquence qu'en tirait la page est corrigée |

## Erreurs de méthode, corrigées pendant la campagne

- **Sonde d'avertissement PHP** (page 2) : sous le client de test, aucun
  gestionnaire d'erreurs n'était enregistré, et la première mesure ne prouvait
  rien ; refaite sous `Debug::enable()` puis `ErrorHandler::register()`.
- **Sonde `dump()`** (page 3) : en CLI, le dump part sur la sortie standard et
  non dans la réponse ; refaite sous le serveur web intégré de PHP.
- **Référence de version** (page 1) : une carte nommait « 7.4 » ; reformulée
  avant application, pour ne citer que la branche 8.0.

## Signaux pour le holdout — à revoir par l'owner

Trois questions holdout portent sur les items du lot, toutes dans
`mock-04-holdout.yml`, une par item. Aucune n'a été lue. Des faits établis
pendant la campagne pourraient en concerner certaines, sans que cela soit
vérifié :

- en `dev`, `.env.dev` l'emporte sur `.env.local` ; un `.env.local.php` coupe
  la lecture des fichiers `.env` ;
- le statut HTTP d'une exception vient aussi de `#[WithHttpStatus]`, de
  `framework.exceptions` et de `RequestExceptionInterface` ;
- un avertissement PHP n'est une exception qu'en debug, par défaut ;
- `dump()` existe en production dans une application FrameworkBundle : pas
  d'erreur fatale, une réponse corrompue.

## Déploiements

Les trois pages ont été fusionnées par PR (#308 à #310), chacune avec CI verte,
déployée par le workflow Pages, et sa ligne de smoke test lue en production. La
page 3 : run 37064126582, success — `ok  lot-14  the code debugging page carries its four flashcard levels, the dump in production, the lost header and the corrupted response`.

## Portes, au moment du rapport

Exécutées le 2026-10-02 sur la branche du rapport, au-dessus de `12e6bab` :

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant (1 avertissement `PED-003` préexistant) |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| `composer gate-full` | exit 0 — 299 tests, 17 481 assertions ; TOTAL VIOLATIONS: 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |
| décomptes de cartes des trois entrées de page contre les fichiers | 3 / 3 concordants |

## Résumé autonome

Lot 14 (*Miscellaneous*), 3 items STANDARD : couverture projet 163/163
inchangée ; cours 1 721 → 2 469 mots (+748), aucun dépassement de budget ;
flashcards 3 → 33 (+30), toutes niveau posé ; 13 questions, 3 corrigées dont
1 VALIDATION en v2, 0 holdout modifiée ; trois pages déployées, smoke tests
lus ; deux divergences documentation/code (statut HTTP, `dump()` en production)
signalées sur les pages.
