# Lot 08 — Data Validation, raffiné sous le cadre version 2

Douzième lot raffiné. Huit items, vingt-sept outcomes, vingt-neuf questions au
départ.

## 1. Ce que le cadre a trouvé

**Quatre outcomes sur vingt-sept** n'étaient évalués par aucune question :

| Item | Outcome sans aucune question |
|---|---|
| Validator component | nommer les trois méthodes d'entrée |
| Built-in validation constraints | situer une contrainte dans sa famille |
| Custom callback validators | reconnaître un appelable statique externe |
| Violations builder | distinguer `addViolation()` de `buildViolation()` |

Les vingt-trois autres l'étaient déjà.

## 2. `ARC-001` a attrapé mon annotation pour la troisième fois d'affilée

Et **toujours de la même façon** : `CONCEPT_DISTINCTION` posé sur une question
dont l'`exam_skill` est `RECOGNIZE`. Lot 07, lot 12, lot 08 — trois unités
consécutives, une seule erreur répétée.

Ce n'est plus un incident, c'est un défaut de méthode : j'étiquette d'après ce que
la question *paraît* faire, alors que la règle lit ce que ses deux autres champs
*déclarent*.

**Ce qui a changé cette fois** : `tools/audit/check_annotation_map.py` rejoue
localement les six contraintes d'`ARC-001` sur une table d'annotation **avant**
qu'elle soit appliquée. Sur ce lot il a signalé exactement la question que CI
aurait refusée, et rien d'autre — 1 problème sur 29 questions annotées.

Le script ne remplace pas la règle et ne la duplique pas dans le build : il
déplace la découverte de l'erreur de CI vers le poste de travail. La règle reste
seule juge.

## 3. Une question écrite recouvrait le holdout, et a dû être réécrite

Le contrôle de recouvrement — comparaison des **bonnes réponses**, sans afficher
le contenu réservé — a donné `0,31` entre la question ajoutée sur l'appelable
externe et la question réservée du même item. C'était la même question.

Contrairement au lot 12, **elle ne pouvait pas être supprimée** : son outcome
n'était évalué par rien d'autre, et une question du holdout ne libère jamais un
outcome. Elle a donc été **réécrite sur une autre facette** : non plus la forme
de déclaration d'un appelable externe, mais l'endroit où un nom de méthode nu est
résolu — la classe validée elle-même, ce qui est précisément la raison pour
laquelle une méthode statique extérieure ne peut pas être atteinte ainsi.

Recouvrement après réécriture : **0,07**.

La règle de décision, désormais stable sur trois lots : si l'outcome est couvert
ailleurs, la question part ; s'il ne l'est pas, elle change de facette.

## 4. Ce qui a été ajouté

**Quatre questions**, toutes `LEARNING`, en anglais.

| Item | Outcome couvert | Archétype |
|---|---|---|
| Validator component | les trois méthodes d'entrée du validateur | `API_SIGNATURE` |
| Built-in validation constraints | ce que dit la famille d'une contrainte | `CONCEPT_DISTINCTION` |
| Custom callback validators | où un nom de méthode nu est résolu | `API_SIGNATURE` |
| Violations builder | signaler tout de suite, ou construire puis signaler | `CONCEPT_DISTINCTION` |

## 5. L'indice de longueur

| | Avant | Après |
|---|---:|---:|
| bonne réponse strictement la plus longue | 18/29 = **62,1 %** | 8/33 = **24,2 %** |
| seuil du hasard | 25,0 % | 25,0 % |

**Onze bonnes réponses raccourcies et deux distracteurs allongés.**

Ce lot a obligé à préciser la méthode, parce que le seuil des dix caractères posé
au lot 13 **ne suffisait pas ici** : les écarts restants étaient petits et
nombreux. Plutôt que de déplacer le seuil pour atteindre le chiffre — ce qui
l'aurait vidé de son sens — deux leviers distincts ont été employés :

- **au-dessus de dix caractères**, la bonne réponse portait sa justification ;
  elle est retirée du choix, et vérifiée présente dans l'explication ;
- **en dessous**, on n'y touche pas ; à la place, un distracteur trop maigre gagne
  la substance qui lui manquait — `true` devient *« true, as a boolean success
  flag »*, ce qui en fait un distracteur plus honnête, pas seulement plus long.

Huit cas subsistent. **Quatre sont irréductibles** : leur bonne réponse est une
signature (`validatePropertyValue(...)`), un appel (`setParameter(...)`), une
liste de noms de méthodes ou un nom de type. Les raccourcir détruirait
l'information. Deux appartiennent au holdout, deux ont un écart de un à deux
caractères.

**Aucune clé de réponse n'a bougé**, vérifié par identifiant de choix.

## 6. État mesuré du lot

| | Avant | Après |
|---|---:|---:|
| items | 8 | 8 |
| questions | 29 | **33** |
| dont LEARNING / VALIDATION / HOLDOUT | 17 / 8 / 4 | **21 / 8 / 4** |
| outcomes portant un id `OUT` | 0 | **27** |
| outcomes évalués hors HOLDOUT | 23 (mesuré après coup, aucun id n'existait) | **27** |
| questions portant un `question_archetype` | 0 | **33** |
| archétypes distincts employés | 0 | **5** sur 11 |
| indice de longueur | 62,1 % | **24,2 %** |

Niveaux : 7 `STANDARD`, 1 `DEEP`, 0 `MINIMAL` — observation, pas cible.

Périmètre vérifié par script contre `55f07da` : 8 items de matrice touchés, tous
du lot 08 ; 4 questions ajoutées, 0 supprimée ; 13 textes de choix édités, tous
du lot 08 ; **0 clé de réponse déplacée, 0 énoncé modifié**.

## 7. Portes

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — exit 0, 22 règles, **0 bloquant** |
| `check_annotation_map.py` | PASS — 0 problème sur 29, après la correction du §2 |
| `vendor/bin/phpunit` | PASS — 245 tests, 13 910 assertions |
| `composer gate-full` | PASS — exit 0 |
| `npm --prefix website run a11y` | PASS — 22 surfaces, 0 violation |
| `prove_framework_rules_fail.py` | PASS — 7 cas, restauration SHA-256 vérifiée |
| `aud10` sur le lot 08 | **24,2 %**, sous le seuil de hasard |
| Couverture officielle | 100 % (163/163) — inchangée |

## 8. Limites

- **Aucun des huit cours n'a été lu intégralement** ; les quatre questions
  viennent des sources amont et de `ValidatorInterface` au SHA `6f841c0`.
- Les quatre questions et les treize éditions n'ont pas été relues par un humain.
- Le pré-contrôle du §2 vérifie les contraintes d'`ARC-001`, pas la **justesse**
  d'un archétype. Il aurait accepté sans rien dire un `SCENARIO_CHOICE` posé sur
  une question qui n'a pas de scénario.
