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
| 1 | Runtime | STANDARD | 464 / 900 | 1 | à faire |

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

## Prochaine étape

Rapport de fin de lot 25, réconcilié par script, dans sa propre PR.
