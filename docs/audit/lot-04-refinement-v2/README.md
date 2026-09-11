# Lot 04 — Controllers, raffiné sous le cadre version 2

**Date** : 2026-09-11 · **Branche** : `refine/lot-04-framework-v2` · **Base** : `cb241ef`

Deuxième lot du cœur raffiné sous [ADR-0007](../../adr/0007-refinement-framework-v2.md).
Il avait un but précis : **vérifier si l'écart mesuré sur le lot 03 entre le
déficit arithmétique et le déficit réel se reproduit.**

## 1. La réponse de l'étalonnage : non, il ne se reproduit pas

| | Plancher arithmétique | Déficit réel (par lecture) | Écart |
| --- | ---: | ---: | ---: |
| **Lot 03** | 17 | 20 | **+17,6 %** |
| **Lot 04** | 18 | 19 | **+5,6 %** |

Sur le lot 03, trois items portaient deux questions sur le même outcome. Sur le
lot 04, **un seul** le fait : *The base AbstractController class*, dont
`QST-2vj8n6hfss62` et `QST-0p9e7tp6k2k1` évaluent tous deux le mécanisme de
`ServiceSubscriberInterface` et son préfixe `?`.

**Ce que cela établit, et ce que cela n'établit pas.** Le compte arithmétique est
un **plancher** — il l'est sur les deux lots. Mais le facteur de dépassement
n'est pas stable : 17,6 % puis 5,6 %. **Extrapoler 132 × 1,18 aux 24 lots
restants serait donc faux**, et le chiffre ne sera pas avancé. Ce qu'on peut
dire, mesuré : sur les deux seuls lots du cœur vérifiés par lecture, le
dépassement va de +1 à +3 questions par lot, soit un ordre de grandeur de
**+5 % à +18 %** — à confirmer lot par lot, jamais par multiplication.

## 2. Ce que le cadre a trouvé

### 2.1 Un item promettait sa source primaire sans la citer

*HttpKernel component and FrameworkBundle* déclare l'outcome « situer
`AbstractController` et `MicroKernelTrait` du côté du bundle » et ne citait que
`Kernel.php` et la documentation du micro-kernel. La preuve de l'appartenance
d'`AbstractController` est sa déclaration de namespace. La source a été ajoutée à
la matrice, ancrée sur `8.0`.

C'est le même défaut que sur le lot 03, où trois items étaient concernés. Aucune
règle ne compare l'énoncé d'un outcome aux sources de son item.

### 2.2 Aucune contradiction trouvée — mais la recherche était plus étroite

Sur le lot 03, la contradiction du cours de rétrocompatibilité a été trouvée en
lisant le cours parce qu'une **question** m'avait conduit à sa source. Ici aucune
des 44 questions relues n'a conduit à une contradiction de ce genre.

**Ce constat vaut ce que vaut la méthode, et il faut le dire :** les 14 cours du
lot 04 **n'ont pas été relus intégralement** contre leurs sources. Ce qui a été
lu, c'est l'intégralité des questions et les passages de source que chacune
engage. Une contradiction logée dans un cours qu'aucune question n'approche
serait passée inaperçue. « Rien trouvé » n'est donc pas « rien à trouver ».

## 3. Ce qui a été ajouté

**19 questions**, toutes `LEARNING`, en anglais, une par outcome resté sans
évaluation. Chaque fait a été relevé dans une source Symfony 8.0 ancrée, lue
pendant cette unité.

| Item | Outcome couvert | Archétype |
| --- | --- | --- |
| HttpKernel and FrameworkBundle | où vivent `AbstractController` et `MicroKernelTrait` | `CONCEPT_DISTINCTION` |
| Naming conventions | notation `controller` pour une classe invocable | `API_SIGNATURE` |
| The base AbstractController class | la classe de base est optionnelle | `CONCEPT_DISTINCTION` |
| The base AbstractController class | `setContainer()` et `#[Required]` | `API_SIGNATURE` |
| The request | l'attribut `_route` et le sac qui le porte | `API_SIGNATURE` |
| The request | `getPayload()` contre le sac `request` | `CONCEPT_DISTINCTION` |
| The response | pourquoi un retour non-Response fonctionne parfois | `BEHAVIOR_DIAGNOSIS` |
| The response | choisir entre `json()`, `file()` et le streaming | `SCENARIO_CHOICE` |
| The session | atteindre la session depuis un service | `SCENARIO_CHOICE` |
| The flash messages | où il est stocké et ce qui le fait disparaître | `BEHAVIOR_PREDICTION` |
| HTTP redirects | `redirect()` contre `redirectToRoute()` | `SCENARIO_CHOICE` |
| Internal redirects | `forward()` contre `redirectToRoute()` | `CONCEPT_DISTINCTION` |
| Generate 404 pages | variables du gabarit d'erreur | `API_SIGNATURE` |
| Generate 404 pages | prévisualiser par la route `_error` | `SCENARIO_CHOICE` |
| File upload | sac `files` ou `#[MapUploadedFile]` | `CONCEPT_DISTINCTION` |
| File upload | pourquoi `getClientOriginalName()` n'est pas sûr | `BEHAVIOR_DIAGNOSIS` |
| Built-in internal controllers | nommer un contrôleur du framework dans une route | `CONFIG_BEHAVIOR` |
| Argument value resolvers | quel résolveur intégré remplit quoi | `CONCEPT_DISTINCTION` |
| Argument value resolvers | ce qu'est un résolveur « ciblé » | `CONCEPT_DISTINCTION` |

## 4. L'indice de longueur, traité avant la journalisation

Après l'ajout, le lot mesurait **52,4 % (33/63)** — les questions neuves l'avaient
nettement aggravé, leur réponse portant deux affirmations là où les distracteurs
n'en portent qu'une.

Même méthode que pour le Mock 4 et le lot 03 : allègement du surplus d'une bonne
réponse, renforcement d'un distracteur par une clause fausse dérivée de la
négation d'une affirmation vérifiée. Jamais de remplissage, jamais d'équilibrage
mécanique.

| | Avant | Après |
| --- | ---: | ---: |
| bonne réponse strictement la plus longue | 33/63 = 52,4 % | **15/63 = 23,8 %** |
| seuil du hasard pour ce lot | 25,0 % | 25,0 % |

**36 éditions sur 23 questions** — 13 réponses allégées, 23 distracteurs
renforcés — dont 11 des 19 questions neuves et 12 préexistantes. Aucune clé de
réponse modifiée.

Fait **avant** de déclarer le lot raffiné : un lot journalisé qui ferait échouer
`aud10` au push suivant serait étiqueté, pas raffiné.

## 5. État mesuré du lot

| | Avant | Après |
| --- | ---: | ---: |
| items | 14 | 14 |
| questions | 44 | **63** |
| dont LEARNING / VALIDATION / HOLDOUT | 29 / 9 / 6 | **48 / 9 / 6** |
| outcomes déclarés | 56 | 56 |
| outcomes portant un id `OUT` | 0 | **56** |
| outcomes évalués hors HOLDOUT | 0 | **56** |
| questions portant un `question_archetype` | 0 | **63** |
| archétypes distincts employés | 0 | **7** sur 11 |
| cours hors budget de révision | 0 | 0 |

Niveaux : 5 `MINIMAL`, 8 `STANDARD`, 1 `DEEP` — **observation, pas cible**.

Périmètre vérifié par script contre `cb241ef` : 44 questions modifiées, **toutes
du lot 04** ; 19 ajoutées, 0 supprimée ; **0 clé de réponse modifiée, 0 énoncé
modifié**.

Effet sur la Certification Readiness une fois le lot journalisé : **14,7 %
(24/163) → 23,3 % (38/163)**, lots raffinés **2/27 → 3/27**. Mesuré en ajoutant
l'entrée à titre d'essai puis en la retirant ; l'entrée définitive n'est écrite
qu'après la fusion et la vérification en production.

## 6. Portes

Chaque commande lancée seule, son code de sortie relevé (PROC-1).

| Porte | Résultat |
| --- | --- |
| `php bin/cert validate` | PASS — exit 0, 21 règles, 0 erreur |
| `vendor/bin/phpunit` | PASS — 236 tests, 8 884 assertions |
| `composer gate-full` | PASS — exit 0 |
| `npm --prefix website run a11y` | PASS — 15 pages, 0 violation axe, 0 structurelle |
| Couverture | 100 % (163/163) — inchangée |
| 10 audits `tools/audit/` | PASS — 0 finding chacun |
| `prove_framework_rules_fail.py` | PASS — exit 0 |
| Essai « lot-04 réputé raffiné » | PASS — `validate` 0 erreur, `aud10` 0 finding |
| Déploiement + smoke test de production | à relever après fusion |

## 7. Limites

- Les 19 questions neuves n'ont pas été relues par un humain. Leurs faits sont
  sourcés ligne à ligne ; leur **calibrage de difficulté** ne l'est pas.
- Symfony n'est pas installé dans cet environnement : aucun comportement du
  framework n'a été **exécuté**. Tout est lu dans les dépôts ancrés sur `8.0`.
- L'item *The base AbstractController class* garde deux questions sur le même
  outcome. C'est documenté, pas corrigé : les retirer supprimerait du contenu
  correct.
