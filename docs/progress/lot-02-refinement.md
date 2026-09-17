# Raffinement pédagogique — Lot 02 (HTTP)

Journal de reprise pour la mission ouverte le 2026-09-17 : approfondir les
**pages de cours** existantes — pièges d'examen, subtilités entre notions
voisines, comportements implicites, flashcards en nombre, accès aux QCM depuis
chaque page. Un lot à la fois, dans l'ordre numérique. Le lot 01 est hors
périmètre sur instruction explicite.

## Contrainte qui structure tout le travail

La règle `REV-001` plafonne le **corps** d'une page (hors front matter) :
MINIMAL 700 mots, STANDARD 900, DEEP 1200. Six des dix pages du lot 02 étaient
déjà à moins de 100 mots de leur plafond au début de la mission :

| Page | Niveau | Mots au 2026-09-17 | Plafond | Marge |
|---|---|---|---|---|
| Cookies | STANDARD | 897 | 900 | 3 |
| Caching | STANDARD | 885 | 900 | 15 |
| HTTP request | DEEP | 1124 | 1200 | 76 |
| HTTP Specification (RFC 9110) | MINIMAL | 615 | 700 | 85 |
| HTTP response | STANDARD | 729 | 900 | 171 |

Le brief demande 1500 à 3000 mots par page : c'est **arithmétiquement
incompatible** avec `REV-001`. La règle n'est pas assouplie pour autant — elle a
déjà été recalibrée une fois, par [ADR-0008](../adr/0008-revision-budget-recalibration.md),
et l'assouplir une seconde fois pour tenir un objectif de volume reviendrait à
faire du nombre de mots une mesure de progression, ce que le plan interdit.

**Le volume va donc là où la règle ne mord pas** : les flashcards et les
questions sont des entités distinctes, sans plafond. C'est aussi là que le
manque était le plus net — **11 flashcards pour les dix items du lot** au
départ (9 dans `lot-02-http.yml`, 2 dans `golden-slice.yml`), soit à peu près le
strict minimum que `FLC-002` exige. Le total est de **26** après cette page.
Dans le corps du cours, seules les formulations qui manquaient vraiment sont
ajoutées, en restant sous le plafond.

## Axe `level` sur les flashcards (ajouté le 2026-09-17)

Le brief demande des flashcards à quatre niveaux. L'axe est désormais porté par
la donnée, pas par la mise en page : `FlashcardLevel` = `RECALL`,
`UNDERSTANDING`, `APPLICATION`, `TRAP`.

- Le champ `level` est **optionnel** : les cartes écrites avant l'axe n'ont pas
  de niveau et sont rendues en tête, sans titre. Leur en attribuer un sans les
  relire aurait été une valeur inventée.
- Une valeur inconnue est une **erreur de chargement**, pas un `null` silencieux.
- `DocsGenerator` regroupe sous un `###` par niveau **non vide**, dans l'ordre
  de l'énumération.
- `tests/Unit/FlashcardLevelTest.php` couvre les quatre comportements. La
  couverture a été **prouvée non vacue** : en retirant l'émission du titre dans
  le générateur, le test échoue (`FlashcardLevelTest.php:80`) ; le fichier a été
  restauré à l'identique (SHA-256 vérifié).
- L'audit d'accessibilité n'auditait qu'une page dont le deck n'a pas de niveau,
  donc sans `###` : la page RFC 9110 a été ajoutée à sa liste, sinon la nouvelle
  structure passait le contrôle sans jamais être regardée.

## État par page (ordre de navigation)

| # | Page | Niveau | Mots / plafond | Flashcards | QCM (pool LEARNING) | Statut |
|---|---|---|---|---|---|---|
| 1 | HTTP Specification (RFC 9110) | MINIMAL | 692 / 700 | 16 | 10 | **RAFFINÉE** (2026-09-17) |
| 2 | Status codes | MINIMAL | 535 / 700 | 2 | 3 | à faire |
| 3 | HTTP request | DEEP | 1124 / 1200 | 1 | 4 | à faire |
| 4 | HTTP response | STANDARD | 729 / 900 | 1 | 4 | à faire |
| 5 | HTTP methods | STANDARD | 608 / 900 | 1 | 3 | à faire |
| 6 | Cookies | STANDARD | 897 / 900 | 1 | 5 | à faire |
| 7 | Caching | STANDARD | 885 / 900 | 1 | 4 | à faire |
| 8 | Content negotiation | STANDARD | 473 / 900 | 1 | 3 | à faire |
| 9 | Language detection | MINIMAL | 274 / 700 | 1 | 2 | à faire |
| 10 | Symfony HttpClient component | STANDARD | 762 / 900 | 1 | 4 | à faire |

Chiffres réconciliés le 2026-09-17 depuis `syllabus-matrix.yml` et `content/**`
par lecture du `ContentSet` chargé, pas depuis un rapport antérieur.

## Page 1 — HTTP Specification (RFC 9110), 2026-09-17

**Fait**

- 15 flashcards ajoutées (`FLC-ne8ag8fv1beh` … `FLC-4p951zavvt5r`), réparties
  4 RECALL / 4 UNDERSTANDING / 3 APPLICATION / 4 TRAP ; la carte préexistante
  `FLC-jn7eqg7cdsqb` a reçu le niveau `TRAP`, après relecture.
- Chaque affirmation relevée dans `specs/rfc9110.html` le jour même : en-tête
  `Obsoletes:`/`Updates:`, §1.2 *History and Evolution*, §3.1 *Resources*,
  §3.2 *Representations*, §3.7 *Intermediaries*, §3.8 *Caches*, §5.1 *Field
  Names*, §5.3 *Field Order* (dont la note `Set-Cookie`), §6 *Message
  Abstraction*, §6.1 *Framing and Completeness*, §8.8.3 *ETag*, section 11
  *Normative References*.
- Deux citations de section ont été corrigées en cours d'écriture : la filiation
  2068 → 2616 → 7230-7235 est en **§1.2 *History and Evolution***, pas en §1.1
  *Purpose*, et le titre exact de §1.2 comporte « and Evolution ».
- Section `## Tips d'examen` ajoutée à la page (mnémonique 9110/9111/9112 ;
  `Obsoletes` contre `Updates` ; le réflexe de relecture devant un énoncé
  absolu). Corps : 615 → **692 mots**, sous le plafond de 700.

**Non fait, et pourquoi**

- Aucune question ajoutée : l'item en a déjà 10 en pool LEARNING, toutes écrites
  et revues les 2026-09-16/17, et la porte du cours vers ces questions existe.
  En ajouter pour le nombre serait du volume sans valeur (§1.4).
- Le corps du cours n'a pas été étendu au-delà des 8 mots de marge restants.

**Contrôles réellement exécutés le 2026-09-17**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 24 règles, 1 avertissement `PED-003` préexistant, **0 bloquant** |
| `vendor/bin/phpunit` | **293 tests, 15 651 assertions, OK** (dont les 4 du nouveau test) |
| Preuve de non-vacuité du nouveau test | échec provoqué obtenu, fichier restauré (SHA-256 identique) |
| `composer gate-full` (relancé après le dernier changement de rendu) | **exit 0**, 29 pages auditées, `TOTAL VIOLATIONS: 0` |
| Audit a11y de la page RFC 9110 elle-même | `item page with levelled flashcards axe=0 structural=0` |
| Jeu d'audits de la CI (11 scripts) | **tous rc=0** |
| `prove_framework_rules_fail.py` | **PROOF OK — 10 cas** |
| `prove_flashcard_coverage_fails.py` | **PROOF OK** |
| `docs/revision/plan.json` + `study-calendar.md` | régénérés avec les paramètres de la CI ; diff = le seul item RFC 9110 (615 → 692 mots, 1 → 16 flashcards) et le budget du jour |

**Non exécuté** : aucun `git push`, aucune PR, aucun déploiement — l'autorisation
de pousser n'a pas été donnée. Le travail est **commité localement** sur la
branche `content/lot-02-01-rfc9110`. Aucun statut `DEPLOYED` n'est revendiqué.

## Prochaine action

Page 2 du lot 02 — **Status codes** (MINIMAL, 535 / 700 mots, 2 flashcards) :
la marge de corps y est de **165 mots** et le deck n'a que **deux cartes** pour
un item que l'examen interroge lourdement. Les pages 8 et 9 ont des marges plus
larges encore (427 et 426 mots), mais l'ordre de navigation prime : le lot se
traite page par page, dans l'ordre.
