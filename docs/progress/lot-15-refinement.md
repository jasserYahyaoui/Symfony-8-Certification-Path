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
| 2 | Web Profiler, Web Debug Toolbar and Data collectors | STANDARD | 593 / 900 | 1 | à faire |

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

## Prochaine étape

Rapport de fin de lot 15, réconcilié par script, dans sa propre PR.
