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
| 3 | Authorization | STANDARD | 370 / 900 | 1 | **RAFFINÉE** (PR #269) |
| 4 | Configuration | STANDARD | 357 / 900 | 1 | **RAFFINÉE** (PR #270) |
| 5 | Providers | STANDARD | 347 / 900 | 1 | **RAFFINÉE** (PR #271) |
| 6 | Firewalls | STANDARD | 350 / 900 | 1 | **RAFFINÉE** (PR #272) |
| 7 | Users | STANDARD | 354 / 900 | 1 | **RAFFINÉE** (PR #273) |
| 8 | Password hashers | STANDARD | 333 / 900 | 1 | **RAFFINÉE** (PR #274) |
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

## Page 4 — *Configuration* — RAFFINÉE

`CRS-yvhg325ax38y` · `OIT-xh63g15rz6n3` · STANDARD · **357 → 588 mots** sur 900.
Aucun niveau promu. Exécutions sur l'application FrameworkBundle +
SecurityBundle 8.0.15, configurations de sécurité variées.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 3 du lot 10 (PR #269, `a3860e8`) | 36756807039, success | `ok  lot-10  the authorization page carries its four flashcard levels, the short-circuited vote, the Basic realm and the affirmative strategy` |

### Une erreur de fond : `security: false` et `access_control`

La page posait en piège d'examen : « Un pare-feu `security: false` ne désactive
pas `access_control` ». `QST-0xvabx1zmvhh` (LEARNING) avait pour bonne réponse
« access_control, which still applies to those URLs ». C'est faux. Lu dans
`SecurityExtension::createFirewall()` (8.0.15) : pour `security: false`, retour
anticipé avec une **liste d'écouteurs vide** — or c'est l'`AccessListener` du
pare-feu qui applique `access_control`. Exécuté : pare-feu `free` (`^/free`,
`security: false`) et règle `ROLE_ADMIN` sur `^/free` ; `/free/page` répond
**200** à un anonyme, sans jeton.

La page est corrigée. La question passe en **v2** avec un nouvel énoncé
concret (« … What does an anonymous visitor get on /free/page? ») et **quatre
nouveaux choix** (`CHO-vnpfy5kfwswh` correct, `CHO-8h3a974rcxvd`,
`CHO-ggxfrefk4zxe`, `CHO-xqkp79x1scv1`) ; la bonne réponse change de sens.

### Confirmé par l'exécution

- Pare-feu sans motif déclaré avant `api` : aucune erreur, `/api/fw` pris par
  `main`, qui pose un cookie de session.
- `^/rules` en `PUBLIC_ACCESS` avant `^/rules/a` en `ROLE_ADMIN` : `/rules/a`
  répond 200 à un anonyme.
- Deux fournisseurs sans clé `provider` : « … is ambiguous as there is more than
  one registered provider ».
- `debug:firewall` liste les pare-feux (`main`, `api`).

**Questions.** `QST-xpq3ys8psrac`, `QST-2eztey3aja7g` (LEARNING) et
`QST-h788rvr0rp3d` (VALIDATION) relues : exactes, inchangées. L'item ne porte pas
de question holdout dans le fichier du lot.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-paer6jdxzr95` reçoit le
niveau RECALL. L'item en porte **11** (3 RECALL, 2 UNDERSTANDING, 3 APPLICATION,
3 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `free/page`,
`more than one registered provider` et `SecurityExtension`, absentes de la
version `master` de la page et de ses cartes. `debug:firewall`, envisagée,
figurait déjà sur la page : écartée.

**Contrôles réellement exécutés le 2026-09-30**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + SecurityBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 121 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 5 — *Providers* — RAFFINÉE

`CRS-enbbsykx22xe` · `OIT-45p7535dk4s2` · STANDARD · **347 → 659 mots** sur 900.
Aucun niveau promu. Exécutions sur l'application FrameworkBundle +
SecurityBundle 8.0.15 (security-http 8.0.14), fournisseur maison
`App\P13\FileUserProvider` qui journalise ses appels.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 4 du lot 10 (PR #270, `f8a4745`) | 36758266008, success | `ok  lot-10  the configuration page carries its four flashcard levels, the security-false page, the ambiguous provider and the extension` |

### Trois erreurs de fond

**1. Un rechargement raté n'invalide pas la session.** `QST-f49jfgm58qr0`
(LEARNING) avait pour bonne réponse « Refreshing the user fails and the session
is invalidated ». Lu dans `ContextListener` : le jeton est retiré par
`$session->remove($this->sessionKey)` ; la session et ses autres données
restent. Exécuté : utilisateur supprimé entre deux requêtes → `refreshUser`
lève `UserNotFoundException`, journal « Username could not be found in the
selected user provider », **401**. La question passe en **v2** : bonne réponse
réécrite (`CHO-sp46qkhktamm`, « The refresh fails and the user is no longer
authenticated »), explication corrigée, source `ContextListener` ajoutée.

**2. Un rôle modifié en base déconnecte.** L'ancienne explication de la même
question disait que le rechargement fait « prendre effet immédiatement » les
changements de rôle ; la carte `FLC-6xnwnjg0fb3n` de la **page 2** (déjà
déployée) demandait « pourquoi un utilisateur … voit-il ses rôles modifiés sans
se reconnecter ? ». Les deux sont faux. Lu dans `ContextListener::hasUserChanged()` :
rôles, mot de passe ou identifiant différents de ceux du jeton → jeton
abandonné (ou `isEqualTo()` si la classe implémente `EquatableInterface`).
Exécuté : `ROLE_EDITOR` ajouté à `alice` entre deux requêtes → `refreshUser(alice)`
puis jeton `null`, `alice` anonyme ; rôles inchangés → jeton conservé. La carte
de la page 2 est réécrite (« que devient un utilisateur connecté dont on modifie
les rôles en base ? » → déconnecté). Le corps de la page 2 disait seulement
« rechargé à chaque requête » : exact, inchangé.

**3. `entity` n'est pas une clé de SecurityBundle.** `QST-f5z345sfdga7`
(LEARNING) donnait « entity, memory, ldap, chain » comme les quatre clés de
Symfony 8.0. Exécuté avec SecurityBundle seul : « Unrecognized option "entity"
under "security.providers.users". Available options are "chain", "id", "ldap",
"memory". » `SecurityBundle::build()` enregistre `memory` et `ldap` ; `entity`
est ajouté par un autre bundle (`EntityFactory` du pont Doctrine). La question
passe en **v2**, énoncé « … define on its own … with no other bundle adding
one? », **quatre nouveaux choix** (`CHO-58t5pm92pgea` correct,
`CHO-d6zqq8rmytq9`, `CHO-76c9f5tzvja2`, `CHO-x95prbbmhgq4`). Une première
rédaction nommait Doctrine dans l'énoncé : `SCOPE-001` l'a refusée (terme exclu,
§1.5). L'énoncé a été reformulé sans le terme ; l'étiquette `exclusion-note`
n'a **pas** été utilisée, puisque les points dépendent de la réponse.
Le résultat d'apprentissage `OUT-18068akbjm47` « Nommer les quatre types
fournis » devient « Nommer les clés de fournisseur reconnues, entity comprise
avec Doctrine ».

### Confirmé par l'exécution

- Connexion : `loadUserByIdentifier(alice)` ; requête suivante avec cookie :
  `refreshUser(alice)`.
- Pare-feu sans état : `loadUserByIdentifier` à chaque requête, jamais
  `refreshUser`.
- Fournisseur `memory` : `alice` et `bob` s'authentifient ; `roles` accepte une
  chaîne séparée par des virgules (`InMemoryFactory::addConfiguration()`).
- Fournisseur `chain` sur `a` et `b`, désigné par `provider: both` : `bob`,
  connu du seul `b`, s'authentifie.

### Erreur détectée hors de cette page

La page 7 (*Users*, `CRS-n9ngfssrq1bt`) écrit « Par défaut la comparaison porte
sur l'identifiant et le mot de passe » et présente `EquatableInterface` comme le
moyen de déconnecter sur changement de rôle. Faux d'après `hasUserChanged()` :
les rôles sont déjà comparés par défaut. Corrigé à la page 7.

### Écart de méthode

Une commande de nettoyage du cache du bac à sable a été refusée par le contrôle
de sécurité de l'outil (chemin relatif non résolu). Elle n'a pas été
contournée : les exécutions suivantes utilisent un environnement de noyau
distinct (`p5c`), donc un cache neuf, vérifié par la présence d'un conteneur
compilé `App_KernelP5cContainer.php`.

**Questions.** `QST-b10yfhxnfhh6` (LEARNING) et `QST-qr5kt3yxsge7` (VALIDATION)
relues : exactes, inchangées. L'item ne porte pas de question holdout dans le
fichier du lot.

**Flashcards.** 10 ajoutées ; `FLC-zpyvxwvf1x8a` reçoit le niveau RECALL et
précise « d'un pare-feu avec état ». L'item en porte **11** (4 RECALL,
2 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte relevé par script. Une carte
UNDERSTANDING du brouillon (« pourquoi un changement de rôle est-il vu sans
reconnexion ») reprenait l'erreur 2 : remplacée avant publication. Une carte
APPLICATION du brouillon, sur l'entité Doctrine, a été remplacée par le
fournisseur `memory`, exécuté.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `hasUserChanged`,
`ROLE_EDITOR` et `Available options are`, absentes de la version `master` de la
page et du fichier de cartes.

**Contrôles réellement exécutés le 2026-09-30**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + SecurityBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant (après correction du `SCOPE-001` décrit plus haut) |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 131 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

Une première exécution de la suite a été lancée avec sa sortie redirigée vers
`/dev/null` : ses résultats sont perdus et ne sont pas comptés. Les chiffres
ci-dessus viennent de la seconde exécution, complète.

## Page 6 — *Firewalls* — RAFFINÉE

`CRS-x5frtpg07mmd` · `OIT-rwa6m06crs1h` · STANDARD · **350 → 554 mots** sur 900.
Aucun niveau promu. Exécutions sur l'application FrameworkBundle +
SecurityBundle 8.0.15, deux pare-feux `main` et `admin` (`^/admin`), fournisseur
qui compte ses appels.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 5 du lot 10 (PR #271, `87088f7`) | 36761496766, success | `ok  lot-10  the providers page carries its four flashcard levels, the user comparison, the role change and the option list` |

### Une erreur de fond : `security: false` dans la question de validation

`QST-0qfww09cp9hf` (VALIDATION) donnait pour bonne réponse « The security system
does not authenticate there » et, en explication d'un distracteur,
« access_control still applies separately ». La seconde affirmation est fausse,
et la bonne réponse ne disait que la moitié. C'est la même erreur que celle
corrigée à la page 4 : `SecurityExtension::createFirewall()` rend une liste
d'écouteurs vide pour `security: false`, et c'est l'`AccessListener` du pare-feu
qui applique `access_control`. Exécuté à la page 4 : une règle `ROLE_ADMIN` sur
un tel pare-feu laisse un anonyme obtenir 200.

La question passe en **v2** : bonne réponse réécrite (`CHO-qkb426j3943q`,
« Nothing runs there, access_control included »), le distracteur devient
l'affirmation fausse elle-même (`CHO-7z4dvejd34jh`, « Its URLs are still checked
against access_control rules »), explications corrigées, source
`SecurityExtension` ajoutée. L'énoncé ne change pas.

### Confirmé par l'exécution

| Configuration | `alice` connectée sur `main`, puis `/admin/whoami` avec le même cookie |
|---|---|
| deux pare-feux, sans `context` | utilisateur `null` |
| `context: shared` sur les deux | `alice` |

Lu dans `SecurityExtension` : sans `context`, la clé de contexte est le nom du
pare-feu lui-même (`$firewall['context'] ?? $id`).

| Pare-feu | Appels au fournisseur sur `/plain` avec cookie |
|---|---|
| `lazy: true` | aucun |
| sans `lazy` | `refreshUser(alice)` à chaque requête |

L'en-tête `Cache-Control` de `/plain` est identique dans les deux modes ; la
page le dit, plutôt que de reprendre l'effet de cache annoncé par la
documentation (`security.rst`, conseil sur le mode `lazy`) sans l'avoir
observé ici.

**Questions.** `QST-qyh60jqm5etd`, `QST-rpz1syn7rpsv` et `QST-tmpjmx8yr7j0`
(LEARNING) relues : exactes, inchangées. Aucune question holdout lue ni
modifiée.

**Flashcards.** 10 ajoutées ; `FLC-9j0hj0sv3c6j` reçoit le niveau RECALL.
L'item en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION, 4 TRAP),
décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `context: shared`,
`admin/whoami` et `Cache-Control`, absentes de la version `master` de la page et
du fichier de cartes. `refreshUser(alice)` et `aucun écouteur`, envisagées,
figuraient déjà dans les cartes : écartées.

**Contrôles réellement exécutés le 2026-09-30**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + SecurityBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 141 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 7 — *Users* — RAFFINÉE

`CRS-n9ngfssrq1bt` · `OIT-gkh08ztwdme1` · STANDARD · **354 → 693 mots** sur 900.
Aucun niveau promu. Exécutions sur l'application FrameworkBundle +
SecurityBundle 8.0.15 (security-core 8.0.15, security-http 8.0.14), deux classes
utilisateur de sonde — l'une simple, l'autre implémentant `EquatableInterface`
avec un `isEqualTo()` qui ne compare que l'identifiant — et un user checker qui
journalise ses appels.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 6 du lot 10 (PR #272, `4417530`) | 36763346667, success | `ok  lot-10  the firewalls page carries its four flashcard levels, the shared context, the second firewall and the cache header` |

### Deux erreurs de fond

**1. La comparaison par défaut inclut les rôles.** La page écrivait « Par défaut
la comparaison porte sur l'identifiant et le mot de passe. Pour changer la
règle — déconnecter l'utilisateur si ses rôles changent, par exemple — la classe
implémente `EquatableInterface` ». C'est l'inverse. Lu dans
`ContextListener::hasUserChanged()` : sans `EquatableInterface`, mot de passe,
**rôles** et identifiant sont comparés. Exécuté avec la classe simple : rôle
ajouté → déconnectée ; mot de passe changé → déconnectée ; rien → connectée.
Avec l'`isEqualTo()` réduit à l'identifiant : rôle ajouté → connectée, mais le
jeton garde `ROLE_USER` seul et `isGranted('ROLE_EDITOR')` est faux, alors que
`getUser()->getRoles()` contient le nouveau rôle. Erreur signalée dès la page 5.

**2. `getRoles()` n'a pas de minimum.** La page écrivait « `getRoles()` doit
toujours retourner au moins un rôle ». La documentation (`security.rst`) montre
une classe qui ajoute `ROLE_USER` — « guarantee every user at least has
ROLE_USER » — : c'est une convention de cette classe. Exécuté, `getRoles()`
rendant `[]` : connexion **200**, `IS_AUTHENTICATED_FULLY` vrai, `ROLE_USER`
faux.

### Précisions vérifiées

- `eraseCredentials()` : dépréciée en **7.3**, retirée en **8.0** avec
  `TokenInterface::eraseCredentials()` (CHANGELOG de Security Core, sections 7.3
  et 8.0) ; le CHANGELOG 8.0 propose « e.g. using `__serialize()` ». Recherche
  dans les paquets installés : plus aucun appel, seul un paramètre de
  constructeur `$eraseCredentials` subsiste dans `AuthenticatorManager`, sans
  usage.
- User checker (`UserCheckerListener`) : `checkPreAuth()` sur
  `CheckPassportEvent` en priorité 256, avant la vérification du mot de passe ;
  `checkPostAuth()` sur `AuthenticationSuccessEvent`, avec le jeton en second
  argument depuis 8.0 (CHANGELOG). Exécuté : compte refusé → 401 après `pre`
  seul ; mauvais mot de passe → `pre` appelé puis 401 ; connexion réussie →
  `pre` puis `post` ; requêtes suivantes avec cookie → aucun appel.

**Questions.** `QST-qzmh0dtgpccg` (VALIDATION) passe en **v2** : l'énoncé demande
« what replaced its role? » et la bonne réponse n'en nommait aucun. Nouveau
choix correct `CHO-x06beaxd2755` (« eraseCredentials(); erase secrets in
__serialize() instead »), explication fondée sur le CHANGELOG, source ajoutée.
`QST-62t90ghtbqzj`, `QST-fy4096tmhna5`, `QST-sftrxannmn21` et
`QST-jwxp5dskf6ed` (LEARNING) relues : exactes, inchangées — le distracteur
« getRoles() returning an empty array … would still leave the user
authenticated » est confirmé par l'exécution. Aucune question holdout lue ni
modifiée.

**Flashcards.** 10 ajoutées ; `FLC-ygn9w70dncp1` reçoit le niveau RECALL.
L'item en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION, 4 TRAP),
décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `__serialize`,
`CheckPassportEvent` et `aucun minimum`, absentes de la version `master` de la
page et du fichier de cartes. `hasUserChanged`, envisagée, figurait déjà dans
les cartes : écartée.

**Contrôles réellement exécutés le 2026-09-30**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + SecurityBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 151 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 8 — *Password hashers* — RAFFINÉE

`CRS-g4sth72ww979` · `OIT-ta627mbwfz7m` · STANDARD · **333 → 611 mots** sur 900.
Aucun niveau promu. Exécutions avec password-hasher 8.0.8 (sodium disponible)
en direct sur `PasswordHasherFactory`, puis de bout en bout sur l'application
FrameworkBundle + SecurityBundle 8.0.15, `http_basic`, avec un fournisseur
`PasswordUpgraderInterface` qui journalise `upgradePassword()`.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 7 du lot 10 (PR #273, `f8b46e0`) | 36765388073, success | `ok  lot-10  the users page carries its four flashcard levels, the serialize hint, the checker event and the roleless user` |

### Deux erreurs de fond

**1. `auto` ne choisit pas selon l'installation.** La page écrivait « aujourd'hui
bcrypt ou sodium/argon2 selon l'installation ». Lu dans
`PasswordHasherFactory::getHasherConfigFromAlgorithm()` : `auto` construit la
chaîne `native`, `sodium` (si disponible), `pbkdf2`, dont le premier maillon
hache ; `NativePasswordHasher` utilise `PASSWORD_BCRYPT`, coût 13. La
documentation 8.0 dit « currently Bcrypt ». Exécuté avec sodium disponible :
`$2y$13$`. Un hachage argon2id existant est vérifié, marqué `needsRehash()`, et
rehaché en `$2y$13$` à la connexion (exécuté de bout en bout).

**2. Changer d'algorithme peut casser.** La page, le piège d'examen,
`QST-cqx6wvrkts6w` (LEARNING), la carte `FLC-y8dwv3bw5s2b` et le résultat
d'apprentissage `OUT-0vbk74q4f9dg` affirmaient que changer d'algorithme « ne
casse rien » parce que le hachage stocké porte son algorithme, et que
`migrate_from` « governs upgrading, not verification ». C'est vrai pour bcrypt,
argon2 et pbkdf2 par défaut, faux pour un condensat maison. Exécuté : condensat
`sha256` hexadécimal à une itération, `auto` sans `migrate_from` → **401** ;
avec `migrate_from: [legacy]` → 200. `migrate_from` sert donc aussi à
**vérifier**.

Corrections : `QST-cqx6wvrkts6w` passe en **v2**, énoncé concret (« Stored
passwords were hashed by a custom sha256 hasher … without migrate_from. What
happens when those users log in? »), **quatre nouveaux choix**
(`CHO-se5e1tsbhze0` correct, `CHO-bvgdy7f5ph81`, `CHO-2x28smef62ex`,
`CHO-edrghbm8n8c2`), source `PasswordHasherFactory` ajoutée. La carte est
réécrite et reçoit le niveau TRAP. Dans la matrice, `OUT-0vbk74q4f9dg` devient
« Prévoir quels hachages existants restent vérifiables après un changement
d'algorithme », et la justification du niveau est corrigée dans le même sens
(niveau inchangé).

### Confirmé par l'exécution

| Fournisseur | `migrate_from` | Résultat |
|---|---|---|
| implémente `PasswordUpgraderInterface` | oui | 200, `upgradePassword()` reçoit un `$2y$13$…` |
| ne l'implémente pas | oui | 200, hachage inchangé |
| l'un ou l'autre | non | 401 |

- `PasswordMigratingListener` exige un `PasswordUpgradeBadge` ;
  `FormLoginAuthenticator`, `JsonLoginAuthenticator` et `HttpBasicAuthenticator`
  en ajoutent un.
- `cost: 4` : préfixe `$2y$04$`.
- La configuration `bcrypt` vérifie aussi un pbkdf2 et un condensat sha512 par
  défaut (hacheurs de secours ajoutés par la fabrique) ; non retenu dans la
  page, faute de place utile à l'examen.

**Questions.** `QST-k73jk5cstrtv` (LEARNING) et `QST-vz4hmhw240zg` (VALIDATION)
relues : exactes, inchangées — la seconde est confirmée par la ligne « ne
l'implémente pas » ci-dessus. Aucune question holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-y8dwv3bw5s2b` réécrite, niveau TRAP. L'item
en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION, 4 TRAP), décompte
relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`currently Bcrypt`, `PasswordMigratingListener` et `encode_as_base64`, absentes
de la version `master` de la page et du fichier de cartes.

**Contrôles réellement exécutés le 2026-09-30**

| Contrôle | Résultat |
|---|---|
| exécutions password-hasher 8.0.8 et SecurityBundle 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 161 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 9 — *Roles* — RAFFINÉE

`CRS-2s6e4qgkcqza` · `OIT-fw6db7ryrk4q` · MINIMAL · **281 → 415 mots** sur 700.
Aucun niveau promu. Exécutions sur l'application FrameworkBundle +
SecurityBundle 8.0.15, `http_basic`, une route qui rend `isGranted($a)` et
`in_array($a, getRoles())`.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 8 du lot 10 (PR #274, `904d02a`) | 36767156202, success | `ok  lot-10  the password hashers page carries its four flashcard levels, the auto algorithm, the migrating listener and the legacy hasher` |

### Une question qui pénalisait une réponse exacte

`QST-1cerp5ba1czy` (LEARNING) demandait « Can role_hierarchy be computed from
the database at runtime? » ; le distracteur « Yes, by implementing a custom role
hierarchy service » était écarté par « this is not the documented behaviour ».
Exécuté : un service qui décore `security.role_hierarchy` et implémente
`RoleHierarchyInterface` ajoute `ROLE_WRITER` à un `ROLE_EDITOR`, et
`isGranted('ROLE_WRITER')` devient vrai. Le distracteur décrivait donc quelque
chose qui fonctionne. Ce que la documentation dit est plus étroit : les
**valeurs** de `role_hierarchy` sont statiques, et une hiérarchie en base
relève d'un voter.

La question passe en **v2** : l'énoncé porte sur les valeurs de la clé de
configuration, et le distracteur devient « Yes, by giving a service id as the
value of a role » (`CHO-391kdfwb79gc`). Exécuté : `ROLE_ADMIN: '@app.db_role_hierarchy'`
compile, et le conteneur stocke la chaîne telle quelle comme nom de rôle, sans
appeler de service. L'explication cite les deux faits. Bonne réponse inchangée.
Dans la matrice, `OUT-se3aqxhdbf9t` « Reconnaître rôle_hierarchy comme
statique » devient « Reconnaître les valeurs de la hiérarchie des rôles comme
statiques ». La page est corrigée dans le même sens.

**Incident de contrôle.** Une première formulation, « … les valeurs de
role_hierarchy … », a fait lever `fr2_second_audit` (FR2-1 : `role` non
accentué, le découpage en mots coupant `role_hierarchy`). L'audit n'a pas été
modifié : le texte a été reformulé en français sans l'identifiant.

### Redémarrage du conteneur

Le conteneur a été recyclé entre la préparation de cette page et sa
publication : clone neuf, `vendor/` et `node_modules/` absents, répertoire de
travail vidé — bac à sable Symfony, scripts d'outillage, brouillons. Rien
n'était encore commité pour cette page ; `master` (jusqu'à `904d02a`) était
intact.

- Brouillons, script de correction, définitions des cartes et sondes :
  **restaurés depuis le journal de session**, en rejouant les appels d'écriture
  dans leur ordre ; corps de page identique (281 → 415 mots, comme avant).
- Générateur de cartes : **reconstruit**, puis validé en régénérant les dix
  cartes de la page 8 avec leurs identifiants — sortie **identique octet pour
  octet** au fichier de `master`.
- Compteur de mots : reconstruit sur la règle de `Course::wordCount()`, validé
  sur les pages 7 (354 → 693) et 8 (333 → 611).
- `gates.sh` : reconstruit ; mêmes commandes.
- `CHO-391kdfwb79gc`, frappé avant le redémarrage et jamais commité, est
  réutilisé (absent du dépôt, vérifié) ; les dix identifiants de cartes ont été
  frappés à nouveau.
- Les exécutions citées ci-dessus ont eu lieu avant le redémarrage, sur le bac
  à sable perdu ; leurs sorties sont dans le journal de session. Une seconde
  exécution de la suite de contrôles, lancée avant le redémarrage, s'est
  terminée sans que sa sortie soit conservée : elle n'est pas comptée.
- Première exécution après restauration : `aud08_technical_production` lève
  deux constats (TECH-2, TECH-3 : « run `php bin/cert build` first ») — l'arbre
  généré, ignoré par git, n'existait pas dans le clone neuf ; l'ancien
  conteneur le gardait d'une construction antérieure. L'audit n'a pas été
  touché : `php bin/cert build` est désormais lancé avant les audits, et toute
  la suite relancée. Seule cette dernière exécution est retenue ci-dessous.

### Confirmé par l'exécution

| Utilisateur | Attribut | `isGranted` | `in_array(getRoles())` |
|---|---|---|---|
| `ROLE_ADMIN`, hiérarchie `ROLE_ADMIN: ROLE_USER` | `ROLE_USER` | vrai | **faux** |
| `ADMIN` sans préfixe | `ADMIN` | **faux** | vrai |

- `RoleVoter` : préfixe `ROLE_`, abstention sur les autres attributs.
- `SecurityExtension::createRoleHierarchy()` : sans `role_hierarchy`, le votant
  de hiérarchie est retiré et le simple `RoleVoter` reste ; avec, l'inverse.
- `AuthenticatedVoter` 8.0 : six attributs, dont `IS_AUTHENTICATED`,
  `IS_REMEMBERED` et `IS_IMPERSONATOR` que la page ne nommait pas.
- `debug:security:role-hierarchy` : sortie Mermaid
  (`SecurityRoleHierarchyDumpCommand`), citée par la documentation 8.0.

**Questions.** `QST-n2z3zbszfmvt` et `QST-d1c0few45tt7` (LEARNING) relues :
exactes, inchangées. Aucune question holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-dxqgyh6g3w9z` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION, 4 TRAP), décompte
relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `role-hierarchy`,
`IS_IMPERSONATOR` et `RoleHierarchyInterface`, absentes
de la version `master` de la page et du fichier de cartes.

**Contrôles réellement exécutés le 2026-09-30**

| Contrôle | Résultat |
|---|---|
| exécutions SecurityBundle 8.0.15 (avant le redémarrage) | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 171 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 10 — *Access Control Rules* (STANDARD, 359 / 900).
