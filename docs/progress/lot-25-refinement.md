# Raffinement pédagogique — Lot 25 (Miscellaneous)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 24 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; budget `REV-001` respecté
sans promotion de niveau ; une branche, une PR, une CI verte, une fusion et un
smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-03 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `6df6bf9`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Runtime | STANDARD | 464 / 900 | 1 | **RAFFINÉE** (PR #337) |

## Page 1 — *Runtime* — RAFFINÉE

`CRS-pm5kj5kh3gt2` · `OIT-r3qcmsehzex1` · STANDARD · **464 → 755 mots** sur 900.
Aucun niveau promu. Exécutions sur Runtime 8.0.14 : `autoload_runtime.php`
produit par le plugin Composer, contrôleurs frontaux qui journalisent leurs
étapes, `GenericRuntime` et `SymfonyRuntime`.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 24 (PR #336, `6df6bf9`) | 37118325510, success | `ok  lot-24  the property access page carries its four flashcard levels, the getter called by isReadable, the nullsafe operator and the adder preference` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### Une affirmation trop large : « le script tourne deux fois »

La page disait que tout effet de bord du script se produit deux fois. Exécuté :
une ligne au-dessus du `require_once` tourne deux fois, une ligne au-dessous une
seule — la première exécution se termine par `exit` dans `autoload_runtime.php`.
Corrigé sur la page et dans la carte préexistante `FLC-0p88y5fvnr6d`, dont le
verso et l'explication sont réécrits (correction au-delà du niveau).

### Ce que dit la documentation, ce que fait le code

| Documentation 8.0 | Code 8.0, exécuté | Décision |
|---|---|---|
| le runtime est instancié, puis le script inclus | `autoload_runtime.template` inclut le script, puis instancie le runtime ; journal : *script returns closure*, puis *runtime constructed* | le code l'emporte, l'écart est signalé sur la page |
| `array $context` = `$_SERVER` + `$_ENV` | `$_ENV` ajouté seulement si `$_SERVER` ne porte pas `PATH` | précisé sur la page |

### Confirmé par l'exécution

| Cas | Résultat |
|---|---|
| `vendor/autoload.php` chargé avant `autoload_runtime.php` | rien ne tourne, code `0`, aucun message |
| `array $ctx` | `InvalidArgumentException` énumérant `$context`, `$argv`, `$request` |
| le script rend `42` / la fonction rend une chaîne | `TypeError` / `TypeError` |
| la fonction rend une `Response` | réponse affichée |
| `SymfonyRuntime` sans `.env` / avec `disable_dotenv` | `PathException` / `APP_ENV` = `dev`, `APP_DEBUG` = `1` |
| `composer dump-autoload --no-plugins`, fichier supprimé | non recréé |

**`QST-rez80g9m6w88` (VALIDATION) → v2.** L'énoncé plaçait le compteur
« avant le retour de la fonction », ce qui inclut le cas d'une ligne sous le
`require_once`, exécutée une seule fois ; la bonne réponse affirmait que tout ce
que fait le script se produit deux fois. L'énoncé place désormais le compteur
au-dessus du `require_once` ; la bonne réponse est réécrite, plus courte que le
plus long distracteur (nouvel identifiant de choix).

**Questions.** `QST-ymqf2vmf7842`, `QST-qvrh3419534q`, `QST-vq3xt0e6a4qr`
(LEARNING) relues : exactes, inchangées. L'item n'a pas de question holdout.

**Flashcards.** 10 ajoutées ; `FLC-0p88y5fvnr6d` reçoit le niveau TRAP et la
correction ci-dessus. L'item en porte **11** (3 RECALL, 3 UNDERSTANDING,
2 APPLICATION, 3 TRAP), décompte relevé par script sur tous les fichiers de
cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`runtime constructed`, `au-dessous` et `aucun message`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-03**

| Contrôle | Résultat |
|---|---|
| exécutions Symfony 8.0 (versions citées ci-dessus) | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 631 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |


# Rapport de fin de lot 25

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire. Base de comparaison : `6df6bf9`, le commit de `master`
qui précède la page refondue (PR #337). État mesuré : `3a1d66f`. Le décompte de
cartes écrit dans l'entrée de page concorde avec les fichiers : **1 / 1**.

## Périmètre et couverture

**1** item officiel atomique porte `lot: lot-25` : `STANDARD`, niveau inchangé —
une **observation**, aucune cible.

```text
EXAM_READY atomiques officiels / total atomiques officiels
= 163 / 163 = 100,0 %
```

Chiffre **cumulatif, sur tout le projet** ; sous-ensemble du lot : **1 / 1**.
Aucun des deux n'a bougé : **ce lot n'a pas fait progresser la couverture**.

## Volume, cartes, questions

| | Avant | Après | Nouveau |
|---|---|---|---|
| Cours, corps en mots (plafond 900) | 464 | **755** | **+291** |
| Cartes sur l'item | 1 | **11** | **+10** |

Niveaux des cartes — **observation, jamais une cible** : `RECALL` 3 ·
`UNDERSTANDING` 3 · `APPLICATION` 2 · `TRAP` 3 ; aucune sans niveau. La carte
préexistante `FLC-0p88y5fvnr6d` a reçu un niveau **et** une correction au-delà :
son verso disait que tout effet de bord du script se produit deux fois.

**4** questions (3 `LEARNING`, 1 `VALIDATION`, aucune `HOLDOUT`), aucune
ajoutée ni supprimée. **1** corrigée : `QST-rez80g9m6w88` (VALIDATION),
**v1 → v2** — l'énoncé place le compteur au-dessus du `require_once`, la bonne
réponse est réécrite et n'est plus le plus long des choix (un nouvel
identifiant de choix). **0 question holdout modifiée**.
`POOL-002` : **0** item sans `VALIDATION`. **Matrice** : aucun texte modifié.

## Affirmations corrigées

| Affirmation | Exécuté ou lu | Décision |
|---|---|---|
| le script tourne deux fois, donc tout effet de bord se produit deux fois | au-dessus du `require_once` : deux fois ; au-dessous : une fois | page, carte `FLC-0p88y5fvnr6d` et `QST-rez80g9m6w88` corrigées |

## Ce que dit la documentation, ce que fait le code

| Documentation 8.0 | Code 8.0, exécuté | Décision |
|---|---|---|
| le runtime est instancié, puis le script inclus | `autoload_runtime.template` inclut le script, puis instancie le runtime | le code l'emporte, l'écart est signalé sur la page |
| `array $context` = `$_SERVER` + `$_ENV` | `$_ENV` ajouté seulement si `$_SERVER` ne porte pas `PATH` | précisé sur la page |

## Signal pour le holdout

Aucun : le lot n'a pas de question holdout.

## Déploiement

Page fusionnée par PR (#337), CI verte, déployée, smoke test lu : run 37119076909,
success — `ok  lot-25  the runtime page carries its four flashcard levels, the real include order, the line run once and the silent early autoload`.

## Portes, au moment du rapport

Exécutées le 2026-10-03 sur la branche du rapport, au-dessus de `3a1d66f` :

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant (1 avertissement `PED-003` préexistant) |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| `composer gate-full` | exit 0 — 299 tests, 17 631 assertions ; TOTAL VIOLATIONS: 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Résumé autonome

Lot 25 (*Runtime*), 1 item STANDARD : couverture projet 163/163 inchangée ;
cours 464 → 755 mots (+291) ; cartes 1 → 11, toutes niveau posé, la carte
préexistante corrigée ; 4 questions, 1 corrigée (v2), aucune holdout sur le
lot ; page déployée, smoke test lu ; une affirmation trop large corrigée (seul
ce qui précède le `require_once` tourne deux fois), deux écarts
documentation/code signalés.
