# Lots 18 à 21 — Miscellaneous, raffinés sous le cadre version 2

Dix-huitième à vingt et unième lots raffinés, traités **en une seule unité**,
pour la raison donnée au §0 du rapport des lots 14 à 17 : treize lots d'un à
trois items chacun demandaient vingt-six *pull requests*. Chaque lot garde son
entrée de journal, ses preuves de production et sa ligne de tableau de bord.

| Lot | Items |
|---|---|
| 18 | Cache |
| 19 | Clock |
| 20 | EventDispatcher · Event |
| 21 | Filesystem · Finder |

## 1. Ce que le cadre a trouvé

**Trois outcomes sur dix-neuf** n'étaient évalués par aucune question :

| Lot | Item | Outcome sans aucune question |
|---|---|---|
| 18 | Cache | nommer item, pool, adaptateur et fournisseur |
| 19 | Clock | pourquoi le composant existe |
| 21 | Finder | construire une recherche par critères chaînés |

Les seize autres l'étaient déjà. Ces lots portaient surtout sur les cas
limites — ce que garantit une écriture atomique, pourquoi un `Finder` réutilisé
doit être cloné — et peu sur les définitions de base. Les trois questions
ajoutées comblent exactement ce creux.

## 2. Le problème de dénominateur, en pire qu'au lot 16

Le rapport des lots 14 à 17 signalait qu'`aud10` ne peut pas mesurer un lot de
trois questions. Cette unité en donne un cas plus net encore.

Le **lot 19** compte cinq questions, dont **une du holdout** que l'unité n'ouvre
pas. Le seuil de 25 % autorise donc **une seule** question biaisée — et la
question du holdout l'occupe déjà entièrement. Pour passer, il fallait ramener à
zéro toutes les questions accessibles, ce qui a imposé d'éditer une bonne
réponse dont l'écart avec le distracteur le plus long était de **un caractère**.

Un caractère. Aucun lecteur humain ne peut s'en servir. L'édition n'a pas été
faite parce que la question trompait quelqu'un, mais parce que le dénominateur
est trop petit pour que la mesure veuille dire quelque chose.

Le résultat n'est pas mauvais en soi — la justification retirée du choix a
rejoint l'explication, qui est plus complète qu'avant. Mais la raison de
l'édition n'est pas pédagogique, et il faut l'écrire.

**La règle n'a pas été touchée.** La demande de décision reste celle du rapport
précédent : `aud10` devrait-il refuser de conclure sous un dénominateur minimum ?
Deux unités consécutives la posent maintenant, avec un cas où **la seule
question intouchable du lot consomme tout le budget**.

## 3. Ce qui a été ajouté

**Trois questions**, toutes `LEARNING`, en anglais : les quatre concepts du
composant Cache (item, pool, adaptateur, fournisseur), la raison d'être du
composant Clock (du code qui lit l'horloge système n'est pas testable), et la
construction d'une recherche `Finder` par critères chaînés.

## 4. L'indice de longueur

| Lot | Avant | Après |
|---|---:|---:|
| 18 | 4/5 = **80,0 %** | 1/5 = **20,0 %** |
| 19 | 4/5 = **80,0 %** | 1/5 = **20,0 %** |
| 20 | 5/7 = **71,4 %** | 1/7 = **14,3 %** |
| 21 | 5/7 = **71,4 %** | 0/7 = **0,0 %** |

**Quinze éditions**, toutes sur la bonne réponse d'une question préexistante,
toutes du même geste. Les quatre questions du holdout concernées n'ont pas été
touchées.

**Aucune clé de réponse n'a bougé, aucun énoncé n'a été modifié.**

## 5. État mesuré

| | Avant | Après |
|---|---:|---:|
| items | 6 | 6 |
| questions | 21 | **24** |
| dont LEARNING / VALIDATION / HOLDOUT | 12 / 6 / 3 | **15 / 6 / 3** |
| outcomes portant un id `OUT` | 0 | **19** |
| outcomes évalués hors HOLDOUT | 16 | **19** |
| questions portant un `question_archetype` | 0 | **24** |
| archétypes distincts employés | 0 | **7** sur 11 |

Périmètre vérifié par script contre `7e1e9d6` : 6 items de matrice touchés, tous
des lots 18 à 21 ; 3 questions ajoutées, 0 supprimée ; 15 textes de choix édités,
tous de ces lots ; **0 clé de réponse déplacée, 0 énoncé modifié**.

## 6. Portes

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — exit 0, **0 bloquant** |
| idem sous sonde « lots 18-21 raffinés » | PASS |
| `check_annotation_map.py` | PASS — 0 problème sur 21, du premier coup |
| `vendor/bin/phpunit` | PASS — 245 tests, 13 946 assertions |
| `composer gate-full` | PASS — exit 0 |
| `prove_framework_rules_fail.py` | PASS — 7 cas |
| `aud10` sur les quatre lots | PASS — voir §4 |
| Couverture officielle | 100 % (163/163) — inchangée |

## 7. Limites

- **Aucun des six cours n'a été lu intégralement** ; les trois questions viennent
  des sources amont (`components/cache.rst`, `components/clock.rst`,
  `components/finder.rst`).
- Les trois questions et les quinze éditions n'ont pas été relues par un humain.
- La question du §2 reste **ouverte**, et cette unité en renforce le cas.
