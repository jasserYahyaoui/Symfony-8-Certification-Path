# Lot 02 — HTTP, raffiné sous le cadre version 2

Quatrième lot raffiné, premier du cœur HTTP. Dix items, trente-quatre outcomes,
trente-deux questions au départ.

Ce rapport est écrit avant la fusion. Les colonnes de preuve qui dépendent de la
production — commit fusionné, job de smoke test — sont remplies après, et le lot
n'entre dans `refinement-log.yml` qu'à ce moment-là.

## 1. Ce que le cadre a trouvé

### 1.1 Le cadre v2 n'était pas amorcé

Aucun des trente-quatre outcomes ne portait d'identifiant, aucune des
trente-deux questions ne déclarait d'archétype ni de lien vers ce qu'elle
évalue. C'est l'état attendu d'un lot écrit avant ADR-0007 ; il est rappelé ici
parce qu'il fixe le point de départ.

### 1.2 Cinq outcomes n'étaient évalués que par le holdout

Le constat qui a décidé du travail. Cinq outcomes étaient couverts — mais
uniquement par une question `HOLDOUT`, c'est-à-dire par une question que le
candidat ne verra qu'une fois, au Mock 4, et jamais pendant l'étude :

| Item | Outcome | Seule couverture |
|---|---|---|
| HTTP Specification | ressource contre représentation | `HOLDOUT` |
| HTTP request | `getClientIp()` et les trusted proxies | `HOLDOUT` |
| HTTP response | `isOk()` contre `isSuccessful()` | `HOLDOUT` |
| Caching | `max-age` contre `s-maxage` | `HOLDOUT` |
| Status codes · HTTP methods · Content negotiation | trois outcomes sans aucune question | — |

Un outcome évalué seulement par le holdout est un outcome **non enseignable** :
la seule vérification existe dans un examen blanc qui ne se passe qu'une fois,
en fin de parcours. Le cadre v2 compte donc les outcomes évalués **hors
holdout**, et c'est ce décompte qui a guidé les ajouts.

### 1.3 Écrire la jumelle d'une question holdout aurait été le geste facile

Pour quatre des cinq, la tentation évidente était de recopier la question
holdout en `LEARNING`. Cela aurait satisfait `PED-003` **en détruisant la valeur
du holdout** : le candidat aurait rencontré la même question pendant l'étude.

Chaque ajout porte donc sur une **facette distincte**, relevée en source :

| Outcome | Ce que teste le holdout | Ce que teste la nouvelle question |
|---|---|---|
| ressource / représentation | ce qui **sépare** les deux notions | pourquoi deux GET sur le même URI peuvent différer et être corrects tous deux |
| `getClientIp()` | **pourquoi** l'adresse du balanceur revient | **quel élément** de la chaîne `X-Forwarded-For` est renvoyé une fois les proxys déclarés |
| `isOk()` / `isSuccessful()` | le cas **204** | le cas **201** |
| `max-age` / `s-maxage` | la **durée** de fraîcheur vue d'un CDN | le fait que `setSharedMaxAge()` appelle **lui-même** `setPublic()` |

## 2. Deux faits que les cours n'énoncent pas

Relevés en lisant `symfony/symfony` au SHA épinglé `6f841c0`, pas en relisant la
prose des cours :

**`setSharedMaxAge()` n'est pas un simple setter.** Il appelle `setPublic()`
avant d'ajouter la directive, ce qui retire `private`. Le cours enseigne
correctement que `private` est le défaut de Symfony et qu'il faut `setPublic()`
pour les caches partagés — mais pas que cet appel-là le fait pour vous. Un
candidat qui a retenu « private par défaut » répondra faux.

**`isNotModified()` commence par une garde sur la méthode.** Si la requête n'est
pas cacheable, la méthode retourne `false` **avant** de comparer quoi que ce
soit : sur un `POST`, un `If-None-Match` pourtant identique ne produit jamais de
`304`. Aucun cours du lot ne le dit.

Ces deux faits sont désormais chacun couverts par une question.

## 3. Ce qui a été ajouté

**Onze questions**, toutes `LEARNING`, en anglais, chacune adossée à une source
lue pendant cette unité.

| Item | Outcome couvert | Archétype |
|---|---|---|
| HTTP Specification | une ressource, plusieurs représentations | `CONCEPT_DISTINCTION` |
| Status codes | le premier chiffre attribue la faute | `SCENARIO_CHOICE` |
| HTTP request | quel maillon de `X-Forwarded-For` est renvoyé | `API_SIGNATURE` |
| HTTP response | `isOk()` contre `isSuccessful()` sur 201 | `BEHAVIOR_PREDICTION` |
| HTTP methods | effet sur l'état contre code renvoyé | `CONCEPT_DISTINCTION` |
| Cookies | `Cookie` est immuable, le retour est jeté | `CODE_DIAGNOSIS` |
| Cookies | les défauts de `Cookie::create()` | `API_SIGNATURE` |
| Caching | `isNotModified()` sur une méthode non cacheable | `BEHAVIOR_PREDICTION` |
| Caching | `setSharedMaxAge()` appelle `setPublic()` | `BEHAVIOR_PREDICTION` |
| Content negotiation | la spécificité départage à qualité égale | `SCENARIO_CHOICE` |
| HttpClient | `json` et `body` sont exclusifs | `API_SIGNATURE` |

Les trente-deux questions préexistantes ont reçu leur archétype et leur lien
`assesses_outcomes`. L'archétype est choisi d'après le `exam_skill` et la
présence de `code_language` **déjà déclarés** sur la question, jamais posé au
jugé : `ARC-001` vérifie ensuite la cohérence des deux.

## 4. L'indice de longueur, traité avant la journalisation

Le lot mesurait **38,5 % (15/39)** contre un seuil de hasard de 25 % : la bonne
réponse était la plus longue une fois sur trois de trop.

Même méthode qu'aux lots 03 et 04 : allègement du surplus d'une bonne réponse,
ou renforcement d'un distracteur par une clause fausse dérivée de la négation
d'un fait vérifié. Jamais de remplissage, jamais d'équilibrage mécanique.

| | Avant | Après |
|---|---:|---:|
| bonne réponse strictement la plus longue | 15/39 = 38,5 % | **8/39 = 20,5 %** |
| seuil du hasard pour ce lot | 25,0 % | 25,0 % |

**12 éditions sur 7 questions.** Quatre cas n'ont pas été touchés : ce sont des
questions dont la bonne réponse est un **nom d'API** plus long que les
distracteurs — `$request->getPayload()`, `stale-while-revalidate`,
`$response->getStatusCode()`, `getPreferredLanguage(['en','de'])`. Les
raccourcir aurait falsifié l'API ; les allonger artificiellement aurait été le
remplissage que la méthode interdit. L'indice reste sous le seuil sans eux.

Fait **avant** de déclarer le lot raffiné : un lot journalisé qui ferait échouer
`aud10` au push suivant serait étiqueté, pas raffiné.

## 5. État mesuré du lot

| | Avant | Après |
|---|---:|---:|
| items | 10 | 10 |
| questions | 32 | **43** |
| dont LEARNING / VALIDATION / HOLDOUT | 20 / 7 / 5 | **31 / 7 / 5** |
| outcomes déclarés | 34 | 34 |
| outcomes portant un id `OUT` | 0 | **34** |
| outcomes évalués hors HOLDOUT | 29 | **34** |
| questions portant un `question_archetype` | 0 | **43** |
| archétypes distincts employés | 0 | **7** sur 11 |
| cours hors budget de révision | 0 | 0 |
| indice de longueur | 38,5 % | **20,5 %** |

Niveaux : 3 `MINIMAL`, 7 `STANDARD`, 0 `DEEP` — **observation, pas cible**. Un
lot sans item `DEEP` est complet ; aucun item n'a été promu pour équilibrer une
répartition.

Les trois items `MINIMAL` ne portent aucune question `VALIDATION`, et c'est
conforme : `POOL-002` ne l'exige que des items `STANDARD` et `DEEP`. Les sept
items `STANDARD` en ont chacun une.

Périmètre vérifié par script contre `0a7fa91` : **0 question hors lot 02
modifiée**, 10 items de matrice touchés, **tous du lot 02** ; 11 ajoutées,
0 supprimée, **0 clé de réponse modifiée, 0 énoncé préexistant modifié**.

Effet sur la Certification Readiness une fois le lot journalisé : **23,3 %
(38/163) → 29,4 % (48/163)**, lots raffinés **3/27 → 4/27**. Mesuré en ajoutant
l'entrée à titre d'essai puis en la retirant, le journal restauré byte-identique
sous SHA-256 ; l'entrée définitive n'est écrite qu'après la fusion et la
vérification en production.

## 6. Portes

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — exit 0, 22 règles, **0 bloquant** |
| `vendor/bin/phpunit` | PASS — 245 tests, 13 721 assertions |
| `composer gate-full` | PASS — exit 0 |
| `npm --prefix website run a11y` | PASS — 22 surfaces, 0 violation |
| 11 audits `tools/audit/` | PASS — exit 0 chacun |
| `prove_framework_rules_fail.py` | PASS |
| `prove_flashcard_coverage_fails.py` | PASS |
| `aud10` sur le lot 02 | **20,5 %**, sous le seuil de hasard |
| Couverture officielle | 100 % (163/163) — inchangée |

`LanguagePolicyTest` a échoué une fois : le document nommait un corpus de 594
questions quand il en compte 605. **Le document a été corrigé, pas le test** —
troisième occurrence, aux lots 03, 04 et ici.

## 7. Limites

- **Les dix cours ont été lus intégralement** pendant cette unité, et aucune
  contradiction n'a été trouvée entre eux et les sources consultées. Mais les
  sources n'ont pas toutes été relues de bout en bout : ce sont les passages
  portant les faits engagés par les questions qui l'ont été, plus les quatre
  fichiers `HttpFoundation` ouverts en entier pour vérifier les signatures.
  « Rien trouvé » n'est pas « rien à trouver ».
- Les onze questions n'ont pas été relues par un humain : faits `VERIFIED` et
  sourcés, **formulation** non éprouvée.
- La détection de doublon reste **syntaxique** (`DUP-001`). Le recouvrement
  holdout / apprentissage a été traité **à la main**, facette par facette, et
  c'est le seul rempart : aucune règle ne mesure la proximité sémantique entre
  une question holdout et une question d'apprentissage.
- Symfony n'est pas installé dans cet environnement : aucun comportement n'a été
  exécuté. Tout fait provient d'une source ancrée au SHA `6f841c0`.
