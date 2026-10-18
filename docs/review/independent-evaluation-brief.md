# Dossier du relecteur — évaluation indépendante après remédiation

La mission de remédiation du 2026-10-13 (constats F1 à F10, chantiers P0 à P3)
se termine par « une nouvelle évaluation indépendante ». Ce dossier la rend
possible ; il ne la remplace pas. **Ce projet ne s'évalue pas lui-même** : tant
qu'un relecteur extérieur à la rédaction n'a pas rendu son rapport, le statut
reste `MISSING` (`docs/remediation/README.md`).

## Qui peut relire

Une personne ou un agent qui n'a participé ni à la rédaction ni aux
corrections, en lecture seule. C'est la condition qu'ont remplie les quatre
revues du lot 02 (`docs/audit/lot-02-http-fundamentals/REVIEW.md`).

## Ce que le relecteur ne fait pas

- **Ne pas lire le pool HOLDOUT** : `content/questions/mock-04-holdout.yml` et
  `website/static/data/mock-4.json`. Ces questions forment la seule mesure sur
  des questions jamais vues que le site offre ; un relecteur qui les a lues
  les a consommées pour l'apprenant. Le rapport n'en cite ni identifiant ni
  contenu.
- Ne pas corriger : le relecteur constate, l'équipe corrige ensuite, chaque
  correction dans sa PR et au journal des corrections.

## Barème

Celui des revues précédentes, pour que les notes se comparent :

| Poste | Points |
|---|---:|
| Exactitude Symfony 8.0 | 25 |
| Couverture du syllabus officiel | 20 |
| Préparation à la certification | 20 |
| Qualité pédagogique | 15 |
| Exemples et cas pratiques | 10 |
| Sources et traçabilité | 5 |
| Cohérence et navigation | 5 |
| **Total** | **100** |

Seuil de validation : 95, et aucune anomalie `P0` ni `P1` ouverte.

## Ce qui a changé depuis l'audit

Le suivi `docs/remediation/README.md` donne, pour chaque constat, ce qui a été
reproduit, corrigé et vérifié, avec la PR, le commit et le smoke test de
production lu. Le journal des corrections publié (`/docs/corrections`) dit ce
qui était enseigné avant et ce qui l'est maintenant.

## Vérifier plutôt que croire

| Affirmation | Commande |
|---|---|
| Les faits exécutables tiennent sur Symfony 8.0.15 | `php tools/behavior/run.php` |
| Le journal ne cite que ce que ses commits ont changé | `python3 tools/corrections/derive.py --check` |
| Une question réécrite a changé de version | `python3 tools/corrections/derive.py --versions <base>` |
| Les règles de contenu passent, et échouent sur leur défaut | `php bin/cert validate` ; `python3 tools/audit/prove_framework_rules_fail.py` |
| Les payloads ne portent que les pools permis | `vendor/bin/phpunit --filter FirstExposureRulesTest` |
| Le site rendu est accessible | `composer gate-full` |
| Toute paire de questions visibles proches (cosinus ≥ 0,45) est revue, aucune ne répète la même situation | `python3 tools/audit/semantic_repetition.py` |

Un contrôle qui ne trouve rien ne prouve rien à lui seul : les scripts qui
portent `--prove` montrent qu'ils échouent sur un défaut injecté.

## L'échantillon

Le relecteur choisit une graine ; le projet ne choisit pas ce qui est relu.

```bash
python3 tools/review/draw_sample.py --seed <nombre choisi par le relecteur>
```

Dix cours, un par lot tiré au hasard, et vingt questions `LEARNING` ou
`VALIDATION` réparties sur les sujets officiels. La même graine donne le même
échantillon sur le même commit, donc le rapport se vérifie.
`draw_sample.py --prove` montre que le tirage refuse une question HOLDOUT
glissée parmi les candidates, sans en afficher l'identifiant.

Pour chaque élément, confronter l'affirmation au code de la branche 8.0 de
`symfony/symfony` — en l'exécutant quand c'est possible — puis à la
documentation 8.0.

## Le rapport

Dans `docs/audit/independent-evaluation-<date>/REVIEW.md`, comme les revues
du lot 02 :

1. le relecteur, le commit relu, la graine et la méthode (ce qui a été exécuté,
   ce qui a seulement été lu) ;
2. le verdict et la grille ;
3. chaque anomalie : gravité, la source qui tranche, et de quoi la reproduire.
   Les revues du lot 02 ont employé `P0` à `P3` sans les définir ; on propose
   `P0` faux et enseigné comme vrai, `P1` trompeur ou manquant au point de
   coûter une question, `P2` imprécision, `P3` forme ;
4. ce qui a été vérifié juste, avec la même exigence.

Un `P0` ou un `P1` ouvre une correction : canonique d'abord, journal ensuite,
version de la question relevée si son énoncé ou sa clé change.
