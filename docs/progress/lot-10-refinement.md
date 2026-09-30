# Raffinement pédagogique — Lot 10 (Security)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 09 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; quand la documentation et le
code divergent, le code l'emporte et l'écart est signalé sur la page ; budget
`REV-001` respecté sans promotion de niveau ; une branche, une PR, une CI verte,
une fusion et un smoke test de production **lu** par page. Les bacs à sable
d'exécution ont tous leurs composants `symfony/*` fixés en `8.0.*` (leçon du
lot 09, page 3).

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-09-30 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `dba35da`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Security Core, CSRF and PasswordHasher components | STANDARD | 313 / 900 | 1 | **RAFFINÉE** (PR #267) |
| 2 | Authentication | STANDARD | 325 / 900 | 1 | **RAFFINÉE** (PR #268) |
| 3 | Authorization | STANDARD | 370 / 900 | 1 | à faire |
| 4 | Configuration | STANDARD | 357 / 900 | 1 | à faire |
| 5 | Providers | STANDARD | 347 / 900 | 1 | à faire |
| 6 | Firewalls | STANDARD | 350 / 900 | 1 | à faire |
| 7 | Users | STANDARD | 354 / 900 | 1 | à faire |
| 8 | Password hashers | STANDARD | 333 / 900 | 1 | à faire |
| 9 | Roles | MINIMAL | 281 / 700 | 1 | à faire |
| 10 | Access Control Rules | STANDARD | 359 / 900 | 1 | à faire |
| 11 | Authenticators, Passports and Badges | DEEP | 677 / 1200 | 0 | à faire |
| 12 | Voters and voting strategies | DEEP | 538 / 1200 | 1 | à faire |

## Page 1 — Security Core, CSRF and PasswordHasher components, 2026-09-30

`CRS-awb7sd999c5j` · `OIT-942znmbjwvad` · STANDARD · **313 → 523 mots** sur 900.
Aucun niveau promu. Lectures des `composer.json` de la branche 8.0, résolutions
Composer et exécution de `password-hasher` 8.0.8 seul.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 09 (PR #266, `dba35da`) | 36751275725, success | lignes `practice`, `exam`, `mock-1` à `mock-5` et `course` toutes `ok` ; `mock-4` : 75 questions, tout le holdout et rien d'autre |

### Une indépendance qui n'existe pas au niveau des paquets

La page affirmait « Chacun s'installe seul », le matériel d'évaluation aussi :
l'explication de `QST-xe6gkw0420ba` (« The three security packages are
independent »), celle de `QST-g2w9we1mx2we` (VALIDATION, « security-csrf is a
standalone component »), la carte `FLC-pd0dmdcagt1k` (« s'installe et s'utilise
seul, comme security-csrf ») et l'objectif `OUT-3xr6hfb46rq2` de la matrice. Lu
dans les `composer.json` 8.0 :

| Paquet | `require` (hors PHP et contrats) |
|---|---|
| `password-hasher` | rien |
| `security-core` | `password-hasher` |
| `security-csrf` | `security-core` |

Exécuté, `composer update --dry-run` : `require symfony/password-hasher`
n'installe que lui ; `require symfony/security-csrf` tire `security-core` et
`password-hasher`. `security-csrf` n'importe de `security-core` que deux
exceptions. Ce qui reste vrai : CSRF sans pare-feu, hachage sans système de
sécurité. Tout est corrigé ; versions conservées, sauf ci-dessous.

### Une question sans réponse unique

`QST-wfkk6ayjt3qb` (LEARNING) demandait « Which package knows nothing about
HTTP? », réponse `security-core`. Or `security-csrf` n'exige pas plus
HttpFoundation, et `security-core` l'utilise dans trois classes optionnelles
(`UsageTrackingTokenStorage`, `LazyResponseException`, `ExpressionVoter`) ; il ne
le liste qu'en `require-dev`. La question passe en **v2** : « Which package holds
the user model, tokens and voters without requiring the HttpFoundation
component? » — choix et bonne réponse inchangés, explications précisées.
L'objectif `OUT-sk1ndg394haz` devient « Situer security-core hors de toute
dépendance à HttpFoundation ».

### Confirmé par l'exécution

`password-hasher` seul dans un projet vide : `PasswordHasherFactory` avec
`auto` rend un hachage `$2y$13$…` (via `MigratingPasswordHasher`), `verify()`
vrai pour le bon mot de passe, faux sinon, aucune classe de `security-core`
chargée.

**Questions.** Les trois questions non holdout de l'item sont relues et
corrigées (une v2). L'item ne porte pas de question holdout dans le fichier du
lot.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-pd0dmdcagt1k` est
corrigée et reçoit le niveau TRAP. L'item en porte **11** (3 RECALL,
2 UNDERSTANDING, 2 APPLICATION, 4 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `require-dev`,
`UsageTrackingTokenStorage` et `PasswordHasherFactory`, absentes de la version
`master` de la page et de ses cartes.

**Contrôles réellement exécutés le 2026-09-30**

| Contrôle | Résultat |
|---|---|
| résolutions Composer et exécution `password-hasher` 8.0.8 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 091 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 2 — *Authentication* — RAFFINÉE

`CRS-rdzx4ka72saj` · `OIT-z9c24d68et6w` · STANDARD · **325 → 573 mots** sur 900.
Aucun niveau promu. Exécutions sur une application FrameworkBundle +
SecurityBundle 8.0.15 (composants `symfony/*` fixés en `8.0.*`) : pare-feu `main`
en `http_basic`, pare-feu `api` sans état, utilisateurs en mémoire.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 1 du lot 10 (PR #267, `e9a3241`) | 36753994976, success | `ok  lot-10  the security components page carries its four flashcard levels, the dev-only HTTP dependency, the tracking storage and the hasher factory` |

### Une affirmation d'avant Symfony 6

La page posait en piège d'examen : « Un jeton existe même sans utilisateur
connecté sur un pare-feu qui l'exige ; c'est son contenu qui change. » C'est la
description du jeton anonyme, supprimé depuis. Exécuté : `/public` sans
identifiants — `getToken()` `null`, `getUser()` `null`, 200 ; `/whoami`
(`ROLE_USER` exigé) sans identifiants — 401 via le point d'entrée, « Access
denied, the user is not fully authenticated ». Le piège est retourné : **pas
d'utilisateur, pas de jeton**.

### Une portée exagérée

« `stateless: true` supprime la session » : exécuté, le pare-feu sans état
n'écrit pas le jeton en session — aucun cookie posé par l'authentification, 401
à la requête suivante sans identifiants —, mais il ne supprime pas la session.
Formulation corrigée.

### Ajouté par l'exécution

- Pare-feu avec état : « Stored the security token in the session », puis, à la
  requête suivante, « Read existing security token from the session » et « User
  was reloaded from a user provider » — l'utilisateur est rechargé à chaque
  requête (`ContextListener`).
- Fixation de session : `session_fixation_strategy` vaut `migrate` par défaut
  (`MainConfiguration`) ; exécuté, une connexion portant un cookie de session en
  reçoit un nouveau, d'identifiant différent.
- `alice`, authentifiée avec `ROLE_USER`, a `isGranted('ROLE_ADMIN')` faux.

**Questions.** `QST-472n92c01v8z`, `QST-eazddxd7m5ht`, `QST-380hndjwn2t0`
(LEARNING) et `QST-dm95708qd8nc` (VALIDATION) relues : exactes, inchangées.
L'item ne porte pas de question holdout dans le fichier du lot.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-y9jp85bpbh2v` reçoit le
niveau RECALL. L'item en porte **11** (4 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
3 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`not fully authenticated`, `reloaded from a user provider` et
`session_fixation_strategy`, absentes de la version `master` de la page et de
ses cartes.

**Contrôles réellement exécutés le 2026-09-30**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + SecurityBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 101 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 3 — *Authorization* — RAFFINÉE

`CRS-8hwpzyk1hrq9` · `OIT-3qgn13f7zvqx` · STANDARD · **370 → 529 mots** sur 900.
Aucun niveau promu. Exécutions sur l'application FrameworkBundle +
SecurityBundle 8.0.15 : deux votants de test, pare-feux `http_basic` et
`form_login`.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 2 du lot 10 (PR #268, `5d738b1`) | 36755255509, success | `ok  lot-10  the authentication page carries its four flashcard levels, the entry point, the user reload and the fixation strategy` |

### « Tous les votants » ne votent pas tous

La page disait que l'`AccessDecisionManager` « interroge **tous les votants** ».
Lu en 8.0.15 : `collectResults()` rend les votes **un par un** (générateur), et
`AffirmativeStrategy::decide()` rend `true` au premier `ACCESS_GRANTED`. Exécuté,
stratégie par défaut, deux votants supportant `EDIT` : le journal ne contient
que `A.supports(EDIT)`, `A.vote` — le second votant n'est pas consulté. La page
est corrigée ; la règle « un rôle passe par un votant » reste exacte.

### Une question qui supposait un point d'entrée

`QST-71pfa2rprjj4` (LEARNING) demandait ce qu'obtient un anonyme sur une page
protégée, réponse « une redirection vers la connexion ». Exécuté, sur la même
action : **401** avec `WWW-Authenticate: Basic realm="Secured Area"` sous
`http_basic`, **302** vers la connexion sous `form_login`, **403** pour `alice`
connue sans `ROLE_ADMIN`. La question passe en **v2** : l'énoncé précise « on a
firewall that uses form_login » ; choix et bonne réponse inchangés, explication
du distracteur 401 corrigée. La page montre les trois cas.

**Questions.** `QST-ty80h8rawm9h` (LEARNING) et `QST-f698yhb20693` (VALIDATION)
relues : exactes, inchangées. L'item ne porte pas de question holdout dans le
fichier du lot.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-qtz62rzb36q3` reçoit le
niveau TRAP. L'item en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
4 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`A.supports(EDIT)`, `Secured Area` et `AffirmativeStrategy`, absentes de la
version `master` de la page et de ses cartes.

**Contrôles réellement exécutés le 2026-09-30**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + SecurityBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 111 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 4 — *Configuration* (STANDARD, 357 / 900).
