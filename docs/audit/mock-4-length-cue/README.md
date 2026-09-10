# Mock 4 — correction de l'indice de longueur (F-3)

Unité de correction ciblée sur les 75 questions du pool `HOLDOUT`, qui
constituent le Mock 4. Priorité 1 du plan de correction établi par l'unité
pilote READ-ONLY (`docs/audit/pilot-readiness-length/README.md`).

**Le contenu du holdout a été lu et réécrit.** L'instruction initiale « ne
révèle aucune question du holdout » a été explicitement levée par le
propriétaire le 2026-09-10. Elle n'était de toute façon pas tenable : le
dépôt est public et `mock-4.json` est publié (ADR-0005 — le holdout est
isolé *fonctionnellement*, il n'est pas confidentiel).

## Le défaut

Dans un QCM, si la bonne réponse est systématiquement la plus longue, un
candidat peut répondre sans connaître le sujet. Le pilote a mesuré ce biais
sur les 555 questions du corpus : 50,5 % de bonnes réponses strictement les
plus longues, contre 25 % attendus par hasard. Le Mock 4 était le pire
sous-ensemble.

## Méthode

Deux opérations, et deux seulement :

1. **Allègement d'une bonne réponse** — suppression de ce qui n'était pas
   nécessaire à sa véracité ni à sa distinction des distracteurs :
   illustration, reformulation, fait répondant à une question non posée.
   *Aucune condition nécessaire n'a été retirée pour raccourcir.*
2. **Renforcement d'un distracteur** — chaque clause fausse ajoutée est
   construite comme la **négation d'une affirmation déjà vérifiée de la bonne
   réponse**. Sa fausseté découle donc de la véracité de la réponse, sans
   introduire de fait nouveau à sourcer.

Ce qui n'a **pas** été fait : aucun allongement de remplissage d'un
distracteur, aucun équilibrage mécanique des longueurs, aucune modification
d'énoncé, d'explication ou de clé de réponse.

Le taux de 25 % est une référence statistique, pas une cible par lot : une
bonne réponse peut légitimement être la plus longue quand la question exige
deux affirmations et que les distracteurs n'en portent qu'une.

## Mesures

Toutes obtenues par script sur les fichiers canoniques, `master` (`189097a`)
contre la branche.

| Indicateur (75 questions HOLDOUT)                    | Avant | Après |
| ---------------------------------------------------- | ----: | ----: |
| bonne réponse strictement la plus longue              | 53 (71 %) | 43 (57 %) |
| indice **perceptible** (marge ≥ 25 % sur le plus long distracteur) | 39 (52 %) | **0 (0 %)** |
| longueur moyenne bonne réponse / distracteur          | 123 / 77 | 101 / 83 |
| `aud11` REVIEW_REQUIRED, pool HOLDOUT                 | 38 (50,7 %) | 13 (17,3 %) |

Effet sur le corpus entier (`aud10`) : 50,5 % → **49,0 %** de bonnes réponses
strictement les plus longues ; `aud11` REVIEW_REQUIRED 173 (31,2 %) → **148
(26,7 %)**, dont « strong » 12 → **6**.

Les 13 questions encore `REVIEW_REQUIRED` portent une marge comprise entre
15 % et 25 %. Aucune n'est classée « strong » (≥ 2× le plus long
distracteur). Descendre sous ce seuil relèverait de l'équilibrage mécanique
explicitement écarté ci-dessus ; l'arrêt est délibéré, pas un abandon.

## Périmètre réellement touché

- 39 questions modifiées, **toutes du pool HOLDOUT** (vérifié par script).
- 0 clé de réponse modifiée, 0 identifiant de choix ajouté ou retiré,
  0 énoncé modifié (vérifié par script contre `master`).
- Fichiers : `lot-02-http.yml`, `lot-04-controllers.yml`,
  `lot-05-routing.yml`, `lot-12-console.yml`, `mock-04-holdout.yml`.

## Portes

| Porte | Résultat |
| ----- | -------- |
| `php bin/cert validate` | PASS — 21 règles, 0 erreur |
| `vendor/bin/phpunit` | PASS — 236 tests, 8 804 assertions |
| Couverture | 100 % (163/163 items atomiques officiels EXAM_READY) — inchangée |
| `composer gate-full` | PASS (exit 0) |
| `npm --prefix website run a11y` | PASS — 15 pages, 0 violation axe, 0 structurelle |
| `assertNoHoldoutLeak()` / `assertMockMatchesBlueprint()` | PASS — `mock-4.json` porte les 75 questions du holdout et rien d'autre ; `practice.json` et `exam.json` en portent 0 |
| Déploiement + smoke test de production | voir le rapport de lot |

## Limites

- Le biais de longueur est un **indice de surface**. Sa suppression ne dit
  rien de la qualité pédagogique des questions, mesurée séparément par le
  cadre de raffinement v2 (ADR-0007).
- Les distracteurs renforcés n'ont pas été soumis à une relecture humaine.
  Leur fausseté est démontrable par négation, mais leur **plausibilité** pour
  un candidat n'est pas mesurée.
- Les pools LEARNING (96 `REVIEW_REQUIRED`) et VALIDATION (39) ne sont pas
  traités ici. Ils sont les priorités 3 et 4 du plan et relèvent du
  raffinement lot par lot.
