# Lot 03 — Symfony Architecture, raffiné sous le cadre version 2

**Date** : 2026-09-10 · **Branche** : `refine/lot-03-framework-v2` · **Base** : `a2e3352`
(branchée sur `189097a`, puis `master` fusionné une fois le Mock 4 livré)

Premier lot du cœur raffiné sous [ADR-0007](../../adr/0007-refinement-framework-v2.md).
Il a été choisi comme **étalonnage** : le lot 01 avait un déficit nul et ne
pouvait donc pas servir de modèle de coût.

Chaque archétype a été posé **en lisant la question**. Chaque fait affirmé par
une question nouvelle a été relevé dans une source Symfony 8.0 ancrée, lue
pendant cette unité — jamais dans la mémoire du modèle (§19).

## 1. Ce que le cadre a trouvé, qu'aucune porte antérieure ne voyait

### 1.1 Le déficit réel est de 20 questions, pas 17

Le compte arithmétique — outcomes moins questions hors HOLDOUT — annonçait 17.
En cartographiant les 49 questions existantes **une par une sur l'outcome
qu'elles évaluent réellement**, il en manquait **20**. L'écart vient de questions
qui évaluent le même outcome :

Les trois items où les deux comptes divergent :

| Item | Questions hors HOLDOUT | Outcomes distincts évalués | Outcomes déclarés | Manque arithmétique | Manque réel |
|---|---:|---:|---:|---:|---:|
| Event dispatcher and kernel events | 3 | 2 | 5 | 2 | **3** |
| Official best practices | 3 | 2 | 4 | 1 | **2** |
| Deprecations best practices | 3 | 2 | 4 | 1 | **2** |
| *les 12 autres items* | 33 | 33 | 46 | 13 | 13 |
| **Total** | **42** | **39** | **59** | **17** | **20** |

L'item *Event dispatcher* portait trois questions hors holdout dont **deux sur
la sémantique de la priorité** (`QST-y51441y0byrz`, `QST-s76mhj526gjc`), laissant
sans évaluation la distinction écouteur/abonné, l'association événement→classe
et les constantes de `KernelEvents`.

Aucune porte ne pouvait le voir : PED-002 se satisfaisait de « l'item a une
question », et le compte arithmétique suppose une répartition parfaite. **Il faut
lire.** Pour un projet de 27 lots, cela veut dire que le déficit global de 132
questions estimé par l'unité pilote est un **plancher**, pas une estimation.

### 1.2 Un cours qui se contredit lui-même

`CRS-21ec5kwrcmkm` (promesse de rétrocompatibilité) annonçait « **deux**
exceptions notables » puis tabulait **quatre** lignes répondant *non*. La source
officielle en porte bien quatre sous l'en-tête « If you extend the class and… » :
ajouter une propriété, ajouter une méthode, appeler une méthode privée par
réflexion, accéder à une propriété privée par réflexion.

Ni la prose ni le tableau n'étaient faux séparément — c'est leur cohabitation qui
l'était. Le cours distingue désormais les deux lignes qui concernent le code
ordinaire des deux qui visent la réflexion sur le privé, et la section *Pièges
d'examen* a été corrigée de même. L'outcome, qui disait « les **deux** cas non
garantis », a été recadré.

Aucune règle ne compare l'énoncé d'un outcome au cours qui le sert.

### 1.3 Trois items citaient leurs outcomes sans citer leur source primaire

L'item *Request handling* promet « énoncer la signature de
`HttpKernelInterface::handle()` » et ne citait que de la documentation narrative ;
*Event dispatcher* promet d'associer chaque événement à sa classe sans citer
`reference/events.rst` ; *Framework interoperability* promet de distinguer une
PSR implémentée d'une PSR suivie sans citer les standards de code. Les trois
sources primaires ont été ajoutées à la matrice, ancrées sur `8.0`.

## 2. Ce qui a été ajouté

**20 questions**, toutes `LEARNING`, toutes en anglais, une par outcome resté
sans évaluation. Chacune cite la source où son fait a été relevé.

| Item | Outcome couvert | Archétype |
|---|---|---|
| HttpFoundation component | ce que le composant remplace | `CONCEPT_DISTINCTION` |
| Symfony Flex | alias contre recette | `CONCEPT_DISTINCTION` |
| Code organization | rôle des répertoires par défaut | `DEFINITION_RECALL` |
| Request handling | signature de `handle()` | `API_SIGNATURE` |
| Request handling | Runtime, Kernel, ControllerResolver | `CONCEPT_DISTINCTION` |
| Exception handling | les deux leviers de `ExceptionEvent` | `API_SIGNATURE` |
| Event dispatcher | écouteur contre abonné | `CONCEPT_DISTINCTION` |
| Event dispatcher | `kernel.controller` → `ControllerEvent` | `API_SIGNATURE` |
| Event dispatcher | constantes de `KernelEvents` | `DEFINITION_RECALL` |
| Official best practices | préfixe `app.` | `DEFINITION_RECALL` |
| Official best practices | aucun bundle applicatif | `SCENARIO_CHOICE` |
| Backward compatibility | utiliser / implémenter / étendre | `CONCEPT_DISTINCTION` |
| Backward compatibility | `final` contre `@final` | `CONCEPT_DISTINCTION` |
| Deprecations | `@deprecated` contre `trigger_deprecation()` | `CONCEPT_DISTINCTION` |
| Deprecations | quel fichier porte les conséquences | `DEFINITION_RECALL` |
| Framework overloading | localiser un gabarit de surcharge | `SCENARIO_CHOICE` |
| Release management | cadence et mois de publication | `DEFINITION_RECALL` |
| Release management | développement en parallèle | `CONCEPT_DISTINCTION` |
| PSRs | implémentée contre suivie | `CONCEPT_DISTINCTION` |
| Naming conventions | préfixes `As…` et `Map…` | `CONCEPT_DISTINCTION` |

## 3. L'indice de longueur, traité dans le même passage

`aud10` gate un lot raffiné sur le seuil du hasard (25 %). Le lot 03 mesurait
**42,0 % (29/69)** après l'ajout — les questions neuves, dont la réponse porte
deux affirmations là où les distracteurs n'en portent qu'une, l'avaient aggravé.

Même méthode que pour le Mock 4 : allègement du surplus d'une bonne réponse,
renforcement d'un distracteur par une clause fausse dérivée de la négation d'une
affirmation vérifiée. Jamais de remplissage, jamais d'équilibrage mécanique.

| | Avant | Après |
|---|---:|---:|
| bonne réponse strictement la plus longue | 29/69 = 42,0 % | **16/69 = 23,2 %** |
| seuil du hasard pour ce lot | 25,0 % | 25,0 % |

**30 éditions sur 18 questions** — 12 réponses allégées, 18 distracteurs
renforcés — dont 9 questions préexistantes et 9 des 20 questions neuves, dont la
rédaction initiale avait justement aggravé l'indice. Aucune clé de réponse
modifiée.

Ce travail a été fait **avant** de déclarer le lot raffiné, parce qu'un lot
journalisé qui ferait échouer `aud10` au push suivant ne serait pas raffiné : il
serait étiqueté.

## 4. Ce qui n'a PAS été fait

- **Aucune question retirée**, y compris les deux qui évaluent le même outcome.
  Elles sont correctes et sourcées ; la redondance n'est pas un défaut.
- **Aucun niveau de contenu changé** pour faire baisser une exigence.
- **Aucun cours réécrit** au-delà de la contradiction du §1.2.
- **Le budget de révision n'a pas été touché** : les 15 cours du lot étaient déjà
  dans leur budget (mesuré, pas supposé).

## 5. État mesuré du lot

| | Avant | Après |
|---|---:|---:|
| items | 15 | 15 |
| questions | 49 | **69** |
| dont LEARNING / VALIDATION / HOLDOUT | 30 / 12 / 7 | **50 / 12 / 7** |
| outcomes déclarés | 59 | 59 |
| outcomes portant un id `OUT` | 0 | **59** |
| outcomes évalués hors HOLDOUT | 0 | **59** |
| questions portant un `question_archetype` | 0 | **69** |
| archétypes distincts employés | 0 | **8** sur 11 |
| cours hors budget de révision | 0 | 0 |

Niveaux : 3 `MINIMAL`, 10 `STANDARD`, 2 `DEEP` — **observation, pas cible**.

Effet sur la Certification Readiness une fois le lot journalisé : **5,5 %
(9/163) → 14,7 % (24/163)**, lots raffinés **1/27 → 2/27**. Mesuré en ajoutant
l'entrée à titre d'essai puis en la retirant ; l'entrée définitive n'est écrite
qu'après la fusion et la vérification en production, comme l'exige l'en-tête de
`refinement-log.yml`.

## 6. Le coût réel d'un lot du cœur

C'est ce que cette unité devait mesurer.

| | Lot 01 (v2) | Lot 03 (v2) |
|---|---:|---:|
| items | 9 | 15 |
| questions existantes à annoter | 36 | 49 |
| outcomes à identifier | 28 | 59 |
| questions à écrire | 5 | **20** |
| éditions pour l'indice de longueur (questions distinctes) | 0 | 30 (18) |
| contradictions de contenu trouvées | 2 | 1 |

Le lot 01 n'était pas un modèle de coût : il portait **plus** de questions hors
holdout que d'outcomes. Le lot 03 en porte 42 pour 59. Extrapoler les 24 lots
restants depuis un seul autre lot serait fabriquer un chiffre, et ce n'est pas
fait ici ; ce qui est établi, c'est que le plancher arithmétique du pilote
sous-estime d'environ 18 % sur le seul lot où il a été confronté à la lecture.

## 7. Portes

Chaque commande lancée seule, son code de sortie relevé (PROC-1).

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — exit 0, 21 règles, 0 erreur, 2 avertissements non bloquants |
| `vendor/bin/phpunit` | PASS — 236 tests, 8 844 assertions |
| `composer gate-full` | PASS — exit 0 |
| `npm --prefix website run a11y` | PASS — 15 pages, 0 violation axe, 0 structurelle |
| Couverture | 100 % (163/163) — inchangée |
| 10 audits `tools/audit/` | PASS — 0 finding chacun |
| Essai « lot-03 réputé raffiné » | PASS — `validate` 0 erreur, `aud10` 0 finding |
| Déploiement + smoke test de production | à relever après fusion |

## 8. Limites

- Les 20 questions neuves n'ont pas été relues par un humain. Leurs faits sont
  sourcés ligne à ligne ; leur **calibrage de difficulté** ne l'est pas.
- Symfony n'est pas installé dans cet environnement : aucun comportement du
  framework n'a été **exécuté**. Tout est lu dans les dépôts ancrés sur `8.0`.
- L'item *Event dispatcher* garde deux questions sur le même outcome. C'est
  documenté, pas corrigé : les retirer supprimerait du contenu correct.

## 9. Un incident de fusion, corrigé et enregistré

La branche a été ouverte avant la fusion de la réparation du Mock 4, et les deux
unités touchent les mêmes questions HOLDOUT. La première résolution du conflit a
été faite avec `git checkout --ours` sur `mock-04-holdout.yml` : cela reprend le
fichier **entier** du côté branche et jette donc les 36 réparations du Mock 4 que
le conflit ne signalait pas.

Le KPI l'a montré immédiatement — indice perceptible du Mock 4 remonté de **0 %
à 43 %**. La fusion a été refaite en partant du fichier de `master` et en y
ré-appliquant les six éditions du lot 03 une par une. Les deux mesures sont
revenues à leur valeur attendue :

| | attendu | après la fusion |
|---|---:|---:|
| Mock 4, indice perceptible (≥ 25 %) | 0 % | **0 %** |
| Mock 4, bonne réponse la plus longue | 43/75 | **43/75** |
| lot-03, bonne réponse la plus longue | 16/69 | **16/69** |

Cinq questions HOLDOUT du lot 03 avaient aussi perdu leur `question_archetype`
dans l'opération ; le compte 69/69 l'a révélé et elles ont été ré-annotées.

`--ours` et `--theirs` résolvent un conflit à l'échelle du **fichier**, pas du
segment. C'est enregistré ici parce que la vérification par KPI, et non la
relecture du diff, est ce qui a rattrapé la perte.
