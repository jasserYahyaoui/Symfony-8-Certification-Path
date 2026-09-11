# Lot 09 — Dependency Injection, raffiné sous le cadre version 2

Cinquième lot raffiné. Douze items, quarante-cinq outcomes, quarante-trois
questions au départ. C'est le lot **transverse** par excellence : la roadmap le
révise avec `lot-01` et `lot-03` à J+45 et J+60 parce que la moitié du framework
en dépend.

## 1. Ce que le cadre a trouvé

### 1.1 Dix outcomes sans évaluation exploitable

Sept n'avaient aucune question ; trois n'étaient couverts que par le holdout.
Comme au lot 02, un outcome évalué seulement au Mock 4 n'est pas enseignable, et
les ajouts portent sur une **facette distincte** de la question holdout plutôt
que sur sa copie.

| Item | Outcome sans évaluation |
|---|---|
| Service container | ce que le conteneur contient, et quand l'objet est construit |
| Configuration parameters | `bind` et `#[Autowire]` comme facteurs communs |
| Services registration | arguments nommés · portée de `_defaults` · attributs de déclaration |
| Tags | `#[AutoconfigureTag]` sur une interface |
| Semantic configuration | `configure()` et `loadExtension()` à la compilation |
| Compiler passes | où enregistrer une passe · passe ou configuration |
| Service locators | `#[AutowireLocator]` |

### 1.2 Un fait que le cours n'énonce pas

`#[AutowireLocator]` accepte, **en plus** d'une liste explicite d'identifiants,
**un nom de tag** — sa signature le documente : `string|array $services`, « a tag
name or an explicit list of service ids ». Le cours ne montre que la forme liste,
et mentionne le locator par tag une ligne plus bas sans dire que c'est le même
attribut qui le fait. La question ajoutée porte exactement là.

Deux autres précisions relevées en source et employées comme distracteurs
vérifiables plutôt que comme affirmations du cours : `AutoconfigureTag` est
`IS_REPEATABLE` (une classe peut donc en porter plusieurs), et `PassConfig::addPass()`
lève `InvalidArgumentException` sur un type inconnu.

## 2. Ce qui a été ajouté

**Dix questions**, toutes `LEARNING`, en anglais, chacune adossée à une source
lue pendant cette unité.

| Item | Outcome couvert | Archétype |
|---|---|---|
| Service container | un service est construit une fois et réutilisé | `CONCEPT_DISTINCTION` |
| Configuration parameters | `bind` et `#[Autowire]`, le scalaire ne s'autowire pas | `CONCEPT_DISTINCTION` |
| Services registration | `$argument` nomme, il ne positionne pas | `API_SIGNATURE` |
| Services registration | `_defaults` ne traverse pas `when@` | `BEHAVIOR_DIAGNOSIS` |
| Services registration | `#[Exclude]` contre `#[When]`, `#[AsAlias]`, `#[Autoconfigure]` | `CONCEPT_DISTINCTION` |
| Tags | `#[AutoconfigureTag]` sur une interface, répétable | `API_SIGNATURE` |
| Semantic configuration | `loadExtension()` ne voit aucune requête | `BEHAVIOR_PREDICTION` |
| Compiler passes | une application enregistre par le `Kernel` | `API_SIGNATURE` |
| Compiler passes | configuration pour les siens, passe pour ceux des autres | `SCENARIO_CHOICE` |
| Service locators | `#[AutowireLocator]` accepte aussi un nom de tag | `API_SIGNATURE` |

Les quarante-trois questions préexistantes ont reçu archétype et lien
`assesses_outcomes`, choisis d'après l'`exam_skill` et la présence de
`code_language` déjà déclarés.

## 3. L'indice de longueur : le pire lot rencontré

**61,5 % (32/52)** au départ, contre un seuil de hasard de 25 %. Presque deux
fois le lot 04 avant correction, et le plus haut des cinq lots raffinés.

| | Avant | Après |
|---|---:|---:|
| bonne réponse strictement la plus longue | 32/52 = **61,5 %** | 12/52 = **23,1 %** |
| seuil du hasard pour ce lot | 25,0 % | 25,0 % |

**24 éditions sur 20 questions.** Même méthode qu'aux lots 02, 03 et 04 :
allègement du surplus d'une bonne réponse, ou renforcement d'un distracteur par
une clause fausse dérivée de la négation d'un fait vérifié.

**Douze cas laissés tels quels**, et c'est délibéré. Corriger les trente-deux
aurait amené l'indice à 2 %, très en dessous du hasard — ce qui est un autre
biais, pas une absence de biais : un candidat apprendrait que la bonne réponse
est la plus **courte**. Les douze restants sont ceux dont la bonne réponse est un
nom d'API ou une constante (`TYPE_BEFORE_OPTIMIZATION` contre `TYPE_OPTIMIZE`),
ou les trois questions `HOLDOUT`, que cette unité n'a pas touchées.

## 4. État mesuré du lot

| | Avant | Après |
|---|---:|---:|
| items | 12 | 12 |
| questions | 43 | **53** |
| dont LEARNING / VALIDATION / HOLDOUT | 26 / 11 / 6 | **36 / 11 / 6** |
| outcomes déclarés | 45 | 45 |
| outcomes portant un id `OUT` | 0 | **45** |
| outcomes évalués hors HOLDOUT | 35 | **45** |
| questions portant un `question_archetype` | 0 | **53** |
| archétypes distincts employés | 0 | **6** sur 11 |
| cours hors budget de révision | 0 | 0 |
| indice de longueur | 61,5 % | **23,1 %** |

Niveaux : 1 `MINIMAL`, 9 `STANDARD`, 2 `DEEP` — **observation, pas cible**.

Périmètre vérifié par script contre `8eb414f` : **0 question hors lot 09
modifiée**, 12 items de matrice touchés, **tous du lot 09** ; 10 ajoutées,
0 supprimée, **0 clé de réponse modifiée, 0 énoncé préexistant modifié**.

Effet une fois journalisé : **29,4 % (48/163) → 36,8 % (60/163)**, lots raffinés
**4/27 → 5/27**.

## 5. Portes

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — exit 0, 22 règles, **0 bloquant** |
| `vendor/bin/phpunit` | PASS — 245 tests, 13 743 assertions |
| `composer gate-full` | PASS — exit 0 |
| `npm --prefix website run a11y` | PASS — 22 surfaces, 0 violation |
| 11 audits `tools/audit/` | PASS — exit 0 chacun |
| `prove_framework_rules_fail.py` · `prove_flashcard_coverage_fails.py` | PASS |
| `verify-reschedule.mjs` | PASS — 76 jours, 440 créneaux identiques |
| `aud10` sur le lot 09 | **23,1 %**, sous le seuil de hasard |
| Couverture officielle | 100 % (163/163) — inchangée |

`LanguagePolicyTest` a échoué une fois : le document nommait 605 questions pour
un corpus de 615. **Le document a été corrigé, pas le test** — quatrième
occurrence.

Le plan de révision a été régénéré : dix questions de plus allongent le temps
d'étude des items du lot, et `plan.json` comme `study-calendar.md` sont calculés
depuis ce corpus.

## 6. Limites

- **Sept des douze cours ont été lus intégralement** pendant cette unité — ceux
  qui portent les outcomes sans évaluation. Les cinq autres (DI component,
  Built-in services, Decoration, Factories, Autowiring) ne l'ont pas été : leurs
  outcomes étaient déjà couverts, et l'unité n'a fait qu'y poser des archétypes.
  **C'est une lecture plus étroite que celle du lot 02**, et elle est déclarée
  comme telle : aucune contradiction n'a été cherchée dans ces cinq-là.
- Les dix questions n'ont pas été relues par un humain : faits `VERIFIED` et
  sourcés, **formulation** non éprouvée.
- Le recouvrement holdout / apprentissage a été traité à la main. Aucune règle ne
  mesure la proximité sémantique.
- Symfony n'est pas installé ici : aucun comportement exécuté, tout fait provient
  d'une source ancrée au SHA `6f841c0` ou de `symfony-docs` branche 8.0.
