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
| 1 | Security Core, CSRF and PasswordHasher components | STANDARD | 313 / 900 | 1 | en cours |
| 2 | Authentication | STANDARD | 325 / 900 | 1 | à faire |
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

## Prochaine étape

Page 2 — *Authentication* (STANDARD, 325 / 900).
