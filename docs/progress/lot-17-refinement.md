# Raffinement pédagogique — Lot 17 (Miscellaneous)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 16 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; quand la documentation et le
code divergent, le code l'emporte et l'écart est signalé sur la page ; budget
`REV-001` respecté sans promotion de niveau ; une branche, une PR, une CI verte,
une fusion et un smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-02 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `141c56b`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | HTTP Caching (reverse proxies, expiration, validation) | STANDARD | 785 / 900 | 1 | **RAFFINÉE** (PR #318) |

## Page 1 — *HTTP Caching (reverse proxies, expiration, validation)* — RAFFINÉE

`CRS-1k0ce9dvdr99` · `OIT-zezwg9nya501` · STANDARD · **785 → 898 mots** sur 900.
Aucun niveau promu. Exécutions sur HttpKernel 8.0.15 : `HttpCache` et `Store`
devant un noyau à quatre routes, avec et sans debug.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 16 (PR #317, `141c56b`) | 37074324598, success | `ok  lot-16  the internationalization page carries its four flashcard levels, the literal strtr, the unescaped tag and its parameters` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### Une affirmation imprécise : « les mêmes clés »

La page disait que `#[Cache]` accepte « les mêmes clés » que `setCache()`. Lu
dans `Attribute/Cache.php` (8.0) : l'attribut prend des arguments camelCase
(`maxage`, `smaxage`, `mustRevalidate`, `lastModified`), `setCache()` des clés
snake_case. Corrigé.

### Exécuté et lu dans le code

| Exécuté | Résultat |
|---|---|
| deux `GET`, `max-age=60`, debug | `miss, store` puis `fresh` |
| idem sans debug | servie du cache, sans `X-Symfony-Cache` (`trace_level` : `none`) |
| `#[Cache(maxage: 60)]` + `setMaxAge(10)` | `max-age=10` |
| deux `QUERY` de même corps, puis un autre corps | `fresh`, puis `miss` |
| `POST` à `max-age=60` | `pass, invalidate` à chaque fois |

Lu dans le code 8.0 : `Request::isMethodCacheable()` retient `GET`, `HEAD` et
`QUERY` ; `Store::generateCacheKey()` ajoute le corps à l'URI pour `QUERY` ;
`CacheAttributeListener::onKernelResponse()` ne pose `max-age` que si la réponse
n'en a pas. `http_cache.rst` (8.0) ne cite que `GET` et `HEAD` : écart signalé
sur la page.

**Erreur de méthode, corrigée avant le commit.** La première explication écrite
pour `QST-478vmvbdbme2` contenait `#[Cache(maxage: 60)]` dans une valeur YAML non
quotée : PyYAML l'a acceptée en tronquant au `#`, le chargeur de Symfony l'a
refusée (`php bin/cert validate`). Le fichier a été restauré, l'explication
réécrite sans `#` ni `:`, les portes relancées sur l'arbre corrigé.

**Questions.** `QST-713sjezq7b99`, `QST-a9rqdpxw13gm` (LEARNING) relues :
exactes, inchangées. `QST-478vmvbdbme2` (VALIDATION) : le distracteur « 3600,
because the attribute is evaluated after the controller returns » repose sur un
fait vrai (l'écouteur tourne après le contrôleur) ; son explication dit
désormais pourquoi la valeur du contrôleur reste — explication seule, version
inchangée. L'item a une question holdout : non lue, non modifiée.

**Flashcards.** 10 ajoutées ; `FLC-tgvah1kzef9y` reçoit le niveau TRAP. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`isMethodCacheable`, `camelCase` et `pass, invalidate`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 521 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |


# Rapport de fin de lot 17

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire. Base de comparaison : `141c56b`, le commit de `master`
qui précède la page refondue (PR #318). État mesuré : `c79a0e6`. Le décompte de
cartes écrit dans l'entrée de page concorde avec les fichiers : **1 / 1**.

## Périmètre et couverture

**1** item officiel atomique porte `lot: lot-17` : `STANDARD`, niveau inchangé —
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
| Cours, corps en mots (plafond 900) | 785 | **898** | **+113** |
| Cartes sur l'item | 1 | **11** | **+10** |

Niveaux des cartes — **observation, jamais une cible** : `RECALL` 3 ·
`UNDERSTANDING` 3 · `APPLICATION` 2 · `TRAP` 3 ; aucune sans niveau. La carte
préexistante a reçu un niveau, sans autre modification.

**4** questions (2 `LEARNING`, 1 `VALIDATION`, 1 `HOLDOUT` dans
`mock-04-holdout.yml`), aucune ajoutée ni supprimée. **1** corrigée :
`QST-478vmvbdbme2` (VALIDATION), explication d'un distracteur seule, version 1
inchangée. **0 question holdout modifiée**.
`POOL-002` : **0** item sans `VALIDATION`. **Matrice** : aucun texte modifié.

## Affirmations corrigées

| Affirmation | Exécuté ou lu | Décision |
|---|---|---|
| `#[Cache]` prend « les mêmes clés » que `setCache()` | arguments camelCase contre clés snake_case | page corrigée |

## Ce que dit la documentation, ce que fait le code

| Documentation 8.0 | Code 8.0, exécuté | Décision |
|---|---|---|
| `http_cache.rst` : cacher `GET` et `HEAD` | `isMethodCacheable()` retient aussi `QUERY`, clé = URI + corps | le code l'emporte, l'écart est signalé sur la page |

## Erreur de méthode

Une explication YAML contenait `#[Cache(maxage: 60)]` non quoté : tronquée par
PyYAML, refusée par Symfony. Fichier restauré, explication réécrite, portes
relancées.

## Signal pour le holdout — à revoir par l'owner

Une question holdout porte sur l'item, dans `mock-04-holdout.yml` ; non lue.
Faits qui pourraient la concerner : `QUERY` cachable en 8.0, clé incluant le
corps ; `X-Symfony-Cache` absent hors debug.

## Déploiement

Page fusionnée par PR (#318), CI verte, déployée, smoke test lu : run 37103821912,
success — `ok  lot-17  the HTTP caching page carries its four flashcard levels, the cacheable methods, the attribute naming and the passed POST`.

## Portes, au moment du rapport

Exécutées le 2026-10-03 sur la branche du rapport, au-dessus de `c79a0e6` :

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant (1 avertissement `PED-003` préexistant) |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| `composer gate-full` | exit 0 — 299 tests, 17 521 assertions ; TOTAL VIOLATIONS: 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Résumé autonome

Lot 17 (*HTTP Caching*), 1 item STANDARD : couverture projet 163/163
inchangée ; cours 785 → 898 mots (+113) ; cartes 1 → 11, toutes niveau posé ;
4 questions, 1 explication corrigée, 0 holdout modifiée ; page déployée, smoke
test lu ; une affirmation corrigée (arguments de `#[Cache]`) et un écart
documentation/code signalé (`QUERY` cachable).
