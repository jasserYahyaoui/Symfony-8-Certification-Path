# Raffinement pédagogique — Lot 15 (Miscellaneous)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 14 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; quand la documentation et le
code divergent, le code l'emporte et l'écart est signalé sur la page ; budget
`REV-001` respecté sans promotion de niveau ; une branche, une PR, une CI verte,
une fusion et un smoke test de production **lu** par page. Le bac à sable
d'exécution a reçu pour ce lot `symfony/web-profiler-bundle` 8.0.15 ; la
vérification « aucun paquet `symfony/*` hors 8.0 » a été refaite après l'ajout
(seuls les contrats et polyfills, versionnés à part, n'y sont pas en 8.0).

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-02 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `a82e9fa`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Deployment best practices | STANDARD | 493 / 900 | 1 | **RAFFINÉE** (PR #313) |
| 2 | Web Profiler, Web Debug Toolbar and Data collectors | STANDARD | 593 / 900 | 1 | **RAFFINÉE** (PR #314) |

## Page 1 — *Deployment best practices* — RAFFINÉE

`CRS-d0nvctfthnrp` · `OIT-6xqn9ybksrbr` · STANDARD · **493 → 685 mots** sur 900.
Aucun niveau promu. Sources relues : `deployment.rst` (8.0), `Kernel.php` et les
`composer.json` de FrameworkBundle et d'ErrorHandler (8.0).

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| reprise de la page 6 du lot 10 (PR #312, `a82e9fa`) | 37067575621, success | `ok  lot-10  the firewalls page carries its four flashcard levels, the shared context, the second firewall, the cache header and the stateless lazy` |

### Une affirmation fausse : `--no-dev` et `dump()`

La page disait que `--no-dev` « rend fatale la présence d'un `dump()` oublié :
la dépendance qui le fournit n'est pas là ». C'est l'erreur corrigée au lot 14
(*Code debugging*) : FrameworkBundle exige ErrorHandler, qui exige VarDumper en
dépendance ordinaire ; `dump()` existe donc en production. Retirée, et
remplacée par l'explication, avec un renvoi à la page qui l'exécute.

### Ce que la page n'affirmait pas

- `Kernel::getProjectDir()` remonte depuis le fichier de la classe noyau jusqu'au
  premier `composer.json` ; s'il n'en trouve aucun, il rend le répertoire du
  noyau sans erreur. Exécuté, noyau dans `app/src/` : `app` avec
  `app/composer.json`, `app/src` sans.
- L'avertissement de `deployment.rst` : sur une erreur *class not found* pendant
  `composer install`, exporter `APP_ENV=prod` pour que les scripts
  `post-install-cmd` tournent en `prod`.

**Questions.** `QST-y14y2m9jfwmr`, `QST-xe2p2fcj5ays`, `QST-t22gymwk3fge`
(LEARNING) et `QST-a3j6b774kjeb` (VALIDATION) relues contre `deployment.rst` :
exactes, inchangées. L'item n'a pas de question holdout.

**Flashcards.** 10 ajoutées ; `FLC-k4xbtfdew9m4` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`post-install-cmd`, `silencieusement faux` et `VarDumper arrive par ErrorHandler`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 491 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 2 — *Web Profiler, Web Debug Toolbar and Data collectors* — RAFFINÉE

`CRS-x8j99kkmpsng` · `OIT-d1qsxrtv25kk` · STANDARD · **593 → 777 mots** sur 900.
Aucun niveau promu. Exécutions sur WebProfilerBundle et FrameworkBundle 8.0.15 :
un noyau avec profileur et barre, cinq sortes de réponses, puis la page de profil
et l'index du profileur.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 1 du lot 15 (PR #313, `e49252f`) | 37068729973, success | `ok  lot-15  the deployment page carries its four flashcard levels, the post-install scripts, the project dir fallback and the surviving dump` |

### Une affirmation imprécise : quand la barre est injectée

« Elle n'est injectée que dans les réponses HTML » est vrai mais incomplet.
Exécuté :

| Réponse | Barre | `X-Debug-Token-Link` |
|---|---|---|
| HTML avec `</body>` | injectée | présent |
| JSON | non | présent |
| redirection 302 | non | présent |
| HTML sans `</body>` | non | présent |
| HTML en XHR | non | présent |

Lu dans `WebDebugToolbarListener` (8.0) : XHR, redirection, type non HTML et
format non HTML excluent l'injection, qui se fait avant le dernier `</body>`.

### Confirmé par la lecture du code

- `ProfilerListener::onKernelResponse()` appelle `Profiler::collect()`, donc
  chaque `collect()` ; `onKernelTerminate()` appelle `saveProfile()`, qui appelle
  chaque `lateCollect()` puis écrit le profil.
- `FileProfilerStorage` : à l'écriture d'un profil, une chance sur dix de purger
  les profils de plus de `2 * 86400` secondes — le « probabilistiquement après
  2 jours » de la documentation.

**Questions.** `QST-x86878b8vrmn`, `QST-ecx7f7257q6m`, `QST-81zjtxgm9m2d`
(LEARNING) et `QST-bmv73g3p3sgm` (VALIDATION) relues contre le code : exactes,
inchangées. L'item a une question holdout : non lue, non modifiée.

**Flashcards.** 10 ajoutées ; `FLC-q9dj4gjhz31d` reçoit le niveau APPLICATION.
L'item en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP),
décompte relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`HTML sans balise`, `2 * 86400` et `onKernelTerminate`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 501 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

# Rapport de fin de lot 15

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire. Base de comparaison : `a82e9fa`, le commit de `master`
qui précède la première page refondue (PR #313). État mesuré : `7429ec1`
(fusion de la page 2). Un second script a confronté les décomptes de cartes
écrits dans les deux entrées de page aux fichiers : **2 / 2** concordent.

## Périmètre

**2** items officiels atomiques portent `lot: lot-15` dans la matrice, tous
`STANDARD` — niveaux inchangés. Cette répartition est une **observation** :
aucune cible n'existe.

## Couverture — formule unique (§3.5)

```text
EXAM_READY atomiques officiels / total atomiques officiels
= 163 / 163 = 100,0 %
```

Ce chiffre est **cumulatif et porte sur tout le projet**. Le sous-ensemble du
lot 15 est **2 / 2**. Aucun des deux n'a bougé : **ce lot n'a pas fait
progresser la couverture**.

## Volume de cours — corps en mots, front matter exclu

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| 2 cours du lot 15 | 1 086 | **1 462** | **+376** |

Aucune page ne dépasse son budget `REV-001` (`STANDARD`, 900). La plus proche :
*Web Profiler, Web Debug Toolbar and Data collectors*, 777.

## Flashcards

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| Cartes sur les items du lot 15 | 2 | **22** | **+20** |

Répartition par niveau — **observation, jamais une cible** :
`RECALL` 6 · `UNDERSTANDING` 6 · `APPLICATION` 4 · `TRAP` 6. **Zéro carte du
lot sans niveau** ; chaque item en porte 11. Les deux cartes préexistantes ont
reçu un niveau ; aucune n'a été supprimée ni corrigée au-delà.

## Questions et pools

**9** questions portent sur les items du lot 15 — aucune ajoutée, aucune
supprimée, **aucune modifiée** :

| Pool | Nombre | Fichier |
|---|---|---|
| `LEARNING` | 6 | `lot-15-miscellaneous.yml` |
| `VALIDATION` | 2 | `lot-15-miscellaneous.yml` |
| `HOLDOUT` | 1 | `mock-04-holdout.yml` |

Les huit questions non holdout ont été relues contre `deployment.rst` et le code
8.0 : exactes. **0 question holdout modifiée** (comparaison par empreinte
SHA-256, sans lecture du contenu). `POOL-002` : 2 items `STANDARD` `EXAM_READY`,
**0** sans question `VALIDATION`. **Matrice** : aucun texte modifié.

## Affirmations corrigées sur les pages

| Page | Affirmation | Décision |
|---|---|---|
| 1 | `--no-dev` rend fatal un `dump()` oublié | fausse en 8.0 — VarDumper arrive par ErrorHandler ; corrigée, renvoi au lot 14 |
| 2 | la barre n'est injectée que dans du HTML | incomplète — exécuté : ni JSON, ni redirection, ni HTML sans `</body>`, ni XHR |

Aucune divergence documentation / code nouvelle : l'erreur de la page 1 était
celle de la page, pas de la documentation.

## Signaux pour le holdout — à revoir par l'owner

Une question holdout porte sur l'item *Web Profiler*, dans `mock-04-holdout.yml`.
Elle n'a pas été lue. Faits établis qui pourraient la concerner : la barre n'est
injectée ni dans une redirection, ni sans `</body>`, ni en XHR ; `X-Debug-Token-Link`
est toujours présent ; la purge des profils n'a lieu qu'une écriture sur dix.

## Déploiements

Les deux pages ont été fusionnées par PR (#313, #314), chacune avec CI verte,
déployée par le workflow Pages, et sa ligne de smoke test lue en production. La
page 2 : run 37070042619, success — `ok  lot-15  the web profiler page carries its four flashcard levels, the body tag, the purge threshold and the terminate save`.

## Portes, au moment du rapport

Exécutées le 2026-10-02 sur la branche du rapport, au-dessus de `7429ec1` :

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant (1 avertissement `PED-003` préexistant) |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| `composer gate-full` | exit 0 — 299 tests, 17 501 assertions ; TOTAL VIOLATIONS: 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |
| décomptes de cartes des deux entrées de page contre les fichiers | 2 / 2 concordants |

## Résumé autonome

Lot 15 (*Miscellaneous*), 2 items STANDARD : couverture projet 163/163
inchangée ; cours 1 086 → 1 462 mots (+376), aucun dépassement de budget ;
flashcards 2 → 22 (+20), toutes niveau posé ; 9 questions, aucune modifiée,
0 holdout modifiée ; deux pages déployées, smoke tests lus ; une affirmation
fausse (`dump()` et `--no-dev`) et une incomplète (injection de la barre)
corrigées.
