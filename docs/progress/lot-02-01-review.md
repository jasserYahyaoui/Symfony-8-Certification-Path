# 02-01 — revue indépendante : **NON EXÉCUTÉE**

La revue lancée le 2026-09-16 s'est arrêtée sur une **limite de session de l'API**
(HTTP 429, `rate_limit`), avant d'avoir lu la moindre question. Elle n'a produit
aucun verdict.

**Aucune des six questions n'a donc été revue par un tiers.** Rien ne remplace ce
verdict manquant : ni les portes automatiques, qui ne jugent pas l'exactitude
d'une affirmation, ni ma propre relecture, qui n'est pas indépendante.

## Ce qui est établi malgré tout, et par quoi

- `php bin/cert validate` — 24 règles, 0 bloquant. `ARC-001` a refusé deux de mes
  questions (archétype `CONCEPT_DISTINCTION` déclaré sur une compétence
  `RECOGNIZE`) ; l'archétype a été corrigé en `DEFINITION_RECALL`, ce qui est
  leur forme réelle, plutôt que de gonfler la compétence pour contenter la règle.
- `aud10` — lot-02 à **16,0 %** de bonnes réponses strictement les plus longues
  (P = 0,95). Aucune des six n'est la plus longue.
- Les faits sur lesquels reposent les six questions ont été vérifiés par le Tech
  Lead **dans le fichier RFC récupéré**, pas sur la foi du registre de
  SOURCE_ANALYST : champ `Obsoletes` complet, caractère partiel de 7230,
  astérisque de la Table 1 du §1.4, présence de 2818, `Updates: 3864`.

## Statut de chaque question

| Question | Statut |
|---|---|
| QST-mfj1a2ba5h07 | `PENDING_REVIEW` |
| QST-0vv6vvfv4han | `PENDING_REVIEW` |
| QST-tcvpfdg7b3j7 | `PENDING_REVIEW` |
| QST-s5dq5r2mg18c | `PENDING_REVIEW` |
| QST-c8phy0j4j0fq | `PENDING_REVIEW` |
| QST-43wvxedwe7wz | `PENDING_REVIEW` |

## Une erreur du registre, relevée au passage

SOURCE_ANALYST affirmait qu'« aucun outcome n'est encore adossé à
`OUT-y8177jvv3m21` ». C'est faux : `QST-s1btydv64pas` l'évalue. Le registre est
une base de travail, pas une autorité — il se vérifie comme le reste.

## Prochaine action

Relancer INDEPENDENT_REVIEWER sur ces six questions et sur le cours, quand la
limite de session est levée. **Le sous-topic 02-01 ne peut pas être déclaré
terminé avant.**
