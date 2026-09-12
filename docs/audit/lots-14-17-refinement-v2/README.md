# Lots 14 à 17 — Miscellaneous, raffinés sous le cadre version 2

Quatorzième à dix-septième lots raffinés, traités **en une seule unité**.

## 0. Pourquoi quatre lots dans une seule unité

Les treize lots restants comptent un à trois items chacun. Les traiter
séparément demandait vingt-six *pull requests* — treize de contenu, treize de
journal — pour dix-neuf items au total.

Ces quatre-là sont donc groupés. **Chaque lot garde son entrée de journal, ses
preuves de production et sa ligne dans le tableau de bord** ; seule la *pull
request* est partagée. C'est un écart délibéré à la cadence « un lot, une PR »
suivie jusqu'ici, et il est signalé comme tel pour pouvoir être refusé.

| Lot | Items |
|---|---|
| 14 | Configuration · Error handling · Code debugging |
| 15 | Deployment best practices · Web Profiler et collecteurs |
| 16 | Internationalization and localization |
| 17 | HTTP Caching |

## 1. Ce que le cadre a trouvé

**Trois outcomes sur vingt et un** n'étaient évalués par aucune question :

| Lot | Item | Outcome sans aucune question |
|---|---|---|
| 14 | Configuration | les deux modes d'ExpressionLanguage |
| 15 | Deployment best practices | les quatre étapes d'un déploiement |
| 15 | Web Profiler | atteindre un profil quand la barre n'est pas injectée |

Dix-huit des vingt et un étaient déjà couverts : ces lots étaient en bon état.

## 2. Le fait marquant : `aud10` est aveugle sur un petit lot

C'est la découverte de cette unité, et elle ne concerne pas le contenu mais la
**mesure**.

`aud10` compare la part de questions dont la bonne réponse est la plus longue à
un seuil de hasard de 25 %. Sur un lot de 3 questions, les seules valeurs
possibles sont 0 %, 33 %, 67 % et 100 % : **il n'existe aucune valeur entre 0 et
33**. Passer sous 25 % exige donc que la bonne réponse ne soit **jamais** la plus
longue — ce qui est soi-même un motif détectable, exactement l'indice inverse
que la règle est censée empêcher.

Le tableau le montre :

| Lot | Questions | Seuil effectif | Ce qu'il faut atteindre |
|---|---:|---|---|
| 14 | 12 | ≤ 3 | tolérable |
| 15 | 9 | ≤ 2 | serré |
| 16 | **3** | **0** | la bonne réponse ne doit jamais être la plus longue |
| 17 | 4 | ≤ 1 | dont une question HOLDOUT intouchable |

Sur le lot 16, deux questions étaient concernées dont une à **deux caractères**
d'écart — bien en dessous du seuil de dix caractères posé au lot 13, où j'ai
écrit qu'en dessous « la longueur n'est pas un indice exploitable ». Il a fallu
l'éditer quand même, non parce qu'elle trompait un lecteur, mais parce que le
dénominateur est trop petit pour que la mesure distingue le signal du bruit.

**Ce qui n'a pas été fait.** La règle n'a pas été assouplie, son seuil n'a pas
été déplacé, `aud10` n'a pas été modifié. Le §12 l'interdit, et à raison.

**Ce qui est demandé.** Qu'un humain tranche : `aud10` devrait-il exiger un
dénominateur minimum — dire `NOT_APPLICABLE` sous, disons, dix questions —
plutôt que de forcer des éditions que sa propre logique ne justifie pas ? Je ne
le décide pas seul ; c'est un changement de règle, donc une décision de
gouvernance (§15).

## 3. Une édition a créé une fuite, qu'une autre règle a vue

En raccourcissant une bonne réponse à `LateDataCollectorInterface`, j'ai produit
exactement la chaîne que le cours *Web Profiler* cite verbatim. `CRS-001` a
bloqué le build.

La règle a raison : le learner qui lit la page voit la réponse. Conformément au
précédent du projet, c'est la **question** qui a été corrigée — la réponse
devient `LateDataCollectorInterface, run at kernel.terminate`, qui n'est pas une
chaîne du cours et reste plus courte que son distracteur le plus long. Rien n'a
été déplacé dans un bloc fencé, aucune règle n'a été touchée.

C'est le deuxième enseignement de l'unité : **raccourcir n'est pas neutre**. Une
bonne réponse assez courte finit par coïncider avec le vocabulaire du cours.

## 4. Ce qui a été ajouté

**Trois questions**, toutes `LEARNING`, en anglais : les deux modes
d'ExpressionLanguage (évaluer contre compiler), les quatre étapes d'un
déploiement dans l'ordre, et la manière d'atteindre un profil quand la réponse
n'est pas du HTML.

## 5. L'indice de longueur

| Lot | Avant | Après |
|---|---:|---:|
| 14 | 10/12 = **83,3 %** | 3/12 = **25,0 %** |
| 15 | 8/9 = **88,9 %** | 2/9 = **22,2 %** |
| 16 | 2/3 = **66,7 %** | 0/3 = **0,0 %** |
| 17 | 4/4 = **100,0 %** | 1/4 = **25,0 %** |

**Dix-huit éditions**, toutes sur la bonne réponse d'une question préexistante,
toutes du même geste, et **chaque justification retirée a été vérifiée présente
dans l'explication** avant la coupe.

Le zéro du lot 16 n'est pas un résultat dont je me félicite : c'est la valeur que
le §2 explique.

**Aucune clé de réponse n'a bougé, aucun énoncé n'a été modifié.**

## 6. État mesuré

| | Avant | Après |
|---|---:|---:|
| items | 7 | 7 |
| questions | 26 | **29** |
| dont LEARNING / VALIDATION / HOLDOUT | 14 / 7 / 5 | **17 / 7 / 5** |
| outcomes portant un id `OUT` | 0 | **21** |
| outcomes évalués hors HOLDOUT | 18 | **21** |
| questions portant un `question_archetype` | 0 | **29** |
| archétypes distincts employés | 0 | **6** sur 11 |

Périmètre vérifié par script contre `f6b12c4` : 7 items de matrice touchés, tous
des lots 14 à 17 ; 3 questions ajoutées, 0 supprimée ; 18 textes de choix édités,
tous de ces lots ; **0 clé de réponse déplacée, 0 énoncé modifié**.

## 7. Portes

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — 0 bloquant, après la correction du §3 |
| idem sous sonde « lots 14-17 raffinés » | PASS |
| `check_annotation_map.py` | PASS — 0 problème sur 26 |
| `vendor/bin/phpunit` | PASS — 245 tests, 13 936 assertions |
| `composer gate-full` | PASS — exit 0 |
| `prove_framework_rules_fail.py` | PASS — 7 cas |
| `aud10` sur les quatre lots | PASS — voir §5 |
| Couverture officielle | 100 % (163/163) — inchangée |

## 8. Limites

- **Aucun des sept cours n'a été lu intégralement** ; les trois questions viennent
  des sources amont.
- Les trois questions et les dix-huit éditions n'ont pas été relues par un humain.
  Sur un lot de trois questions, dix-huit éditions signifient que la quasi-totalité
  du lot a été retouchée : la relecture compte plus ici qu'ailleurs.
- La question du §2 reste **ouverte** et appelle une décision.
