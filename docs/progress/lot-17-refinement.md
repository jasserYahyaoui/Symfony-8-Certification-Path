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
| 1 | HTTP Caching (reverse proxies, expiration, validation) | STANDARD | 785 / 900 | 1 | à faire |

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

## Prochaine étape

Rapport de fin de lot 17, réconcilié par script, dans sa propre PR.
