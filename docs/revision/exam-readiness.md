# PRÊT-CANDIDAT — dates, charge, et critère de passage

**Date d'examen visée : mardi 15 décembre 2026.** Ce document répond à une seule
question : cette date est-elle tenable, et à quelles conditions ?

---

## `EXAM_READY` ne parle pas de vous

| Indicateur | Ce qu'il mesure | Valeur au 11 septembre 2026 |
|---|---|---|
| `EXAM_READY` (item) | le **contenu** existe : cours, questions, sources vérifiées | **163/163 = 100 %** |
| Certification Readiness | le contenu a passé un **audit de raffinement** | **23,3 % (38/163)**, 3 lots sur 27 |
| **PRÊT-CANDIDAT** | **votre** préparation | défini ci-dessous — à ce jour, **non commencé** |

Les deux premiers sont des états du dépôt. Ils étaient déjà atteints, ou en
cours, avant que vous n'ouvriez un cours. **Ne les lisez jamais comme une mesure
de votre préparation.**

---

## Verdict : la date tient, mais elle coûte deux choses

**Le volume passe.** Du 1er octobre au 15 décembre, il y a 53 jours de semaine et
22 jours de week-end. À 2 h en semaine et 3 h le week-end, cela fait **134,5 h de
capacité** pour **110,9 h de charge mesurée**. La marge est réelle.

**Ce qui ne passe pas, c'est la queue de révision.** Le J+30 d'un item étudié
après le 15 novembre tombe après l'examen. Aucun rythme ne corrige cela : c'est
une conséquence arithmétique de la date, pas un défaut du plan.

### Ce que la date coûte exactement, mesuré

Le plan complet programme **901 révisions espacées**. Avec un examen le
15 décembre :

| Rythme | Fin des lots | Révisions perdues | Détail |
|---|---|---:|---|
| 3 nouveautés/jour, 1 h 30 | 18 déc. | **190 / 901** | dont 13 J+1 et 13 J+3 |
| 3 nouveautés/jour, 2 h | 16 déc. | 166 / 901 | dont 7 J+1 et 7 J+3 |
| **4 nouveautés/jour, 2 h** | **27 nov.** | **59 / 901** | **35 J+30, 24 J+45/J+60** |
| 5 nouveautés/jour, 2 h | 20 nov. | 41 / 901 | 19 J+30, 22 J+45/J+60 |
| 6 nouveautés/jour, 2 h | 18 nov. | 32 / 901 | 12 J+30, 20 J+45/J+60 |

**Le plan retient 4 nouveautés par jour à 2 h.** C'est le rythme le plus doux qui
ne perd **aucun J+1, J+3, J+7 ni J+14** — uniquement le J+30 de 35 items et le
renfort J+45/J+60 des items transverses. Passer à 5 ou 6 ne rachète que 18 à
27 J+30 de plus, au prix d'un rythme nettement plus dur pour un profil 4/10.

Les deux premières lignes sont écartées pour une raison précise : elles
sacrifient des **J+1 et J+3**, les échéances qui font la différence entre une
notion vue et une notion apprise. Perdre un J+30 coûte de la consolidation ;
perdre un J+1 coûte la notion.

---

## Les sept semaines qui dépassent votre disponibilité déclarée

Vous avez déclaré 1 h à 2 h en semaine et 2 h à 3 h le week-end, soit **12,5 h**
au milieu de la fourchette. Le plan demande davantage sur sept semaines, et vous
avez indiqué vouloir vous adapter là où c'est nécessaire. Les voici, pour que
vous sachiez **à l'avance** lesquelles bloquer.

| Semaine du | Charge planifiée | Au-dessus des 12,5 h | Sujets |
|---|---:|---:|---|
| 28 septembre | 8,1 h | — | PHP |
| 5 octobre | 12,4 h | — | HTTP, PHP, Symfony Architecture |
| **12 octobre** | **13,8 h** | **+1,3 h** | Controllers, Symfony Architecture |
| **19 octobre** | **13,7 h** | **+1,2 h** | Dependency Injection, Routing |
| **26 octobre** | **14,1 h** | **+1,6 h** | Data Validation, Dependency Injection, Forms |
| **2 novembre** | **14,4 h** | **+1,9 h** | Forms, Messenger, Security |
| **9 novembre** | **14,6 h** | **+2,0 h** | Console, Messenger, Templating with Twig |
| **16 novembre** | **14,7 h** | **+2,2 h** | Automated Tests, Console, Miscellaneous |
| **23 novembre** | **14,6 h** | **+2,0 h** | Miscellaneous |
| 30 novembre | 6,3 h | — | révisions et mocks |
| 7 décembre | 5,8 h | — | révisions et mocks |
| 14 décembre | 3,9 h | — | l'examen |

Le pic est à **14,7 h**, soit 2 h 12 de plus qu'une semaine nominale — environ
**25 minutes de plus par jour**. Ce n'est pas un doublement : c'est un
ajustement, et il est concentré sur sept semaines identifiées.

Les trois dernières semaines retombent à 4–6 h : la charge d'étude est terminée,
il ne reste que les révisions dues et les mocks.

---

## Dates prévisionnelles

| Jalon | Date | Semaine |
|---|---|---:|
| Premier cours | **1er octobre 2026** | S1 |
| Fin du lot 01 — PHP | 5 octobre 2026 | S1 |
| Fin du lot 03 — Symfony Architecture | 13 octobre 2026 | S3 |
| Fin du lot 09 — Dependency Injection | 26 octobre 2026 | S4 |
| Fin du lot 10 — Security | 6 novembre 2026 | S6 |
| **Tous les 163 items étudiés une fois** | **27 novembre 2026** | **S9** |
| Mock 1 | 28 novembre 2026 | S9 |
| Mock 2 | 29 novembre 2026 | S9 |
| Mock 3 | 5 décembre 2026 | S10 |
| Mock 5 | 6 décembre 2026 | S10 |
| **Mock 4 — holdout, format §10, une seule fois** | **12 décembre 2026** | **S11** |
| **Décision go / no-go** | **13 décembre 2026** | **S11** |
| **Examen** | **15 décembre 2026** | **S11** |

**Deux mocks par week-end pour les deux premiers.** Il ne reste que trois
week-ends entre la fin des lots et l'examen. Plutôt que d'entamer la période
d'étude — ce que la règle « aucun mock avant la fin de tous les lots » interdit —
les mocks 1 et 2 partagent un week-end, et les mocks 3 et 5 le suivant. **Mock 4
reste seul**, le dernier samedi.

---

## Charge de travail

| Poste | Heures | Origine du chiffre |
|---|---:|---|
| Première passe — cours | 12,2 h | 71 578 mots mesurés ÷ 110 mots/min (**hypothèse**) |
| Première passe — questions | 13,6 h | `estimated_time_seconds` des 519 questions × 1,6 (**champ réel × hypothèse**) |
| Première passe — flashcards | 2,9 h | **174** cartes × 45 s (**hypothèse**) — 137 avant PED-010 |
| Révisions espacées | 57,3 h | barème par niveau, J+1 à J+30, + J+45/J+60 transverses |
| Assessments de lot | 13,0 h | 26 × 30 min |
| Mocks et corrections | 12,5 h | 5 × (90 min + 60 min) |
| **Total** | **110,9 h** | |

**Le poste dominant n'est pas la lecture des cours (12,2 h) mais les révisions
espacées (57,3 h).** Toute tentation d'« avancer plus vite » en sautant des
révisions attaque 52 % du plan pour gagner sur les 11 % qui coûtent le moins.

---

## Le critère PRÊT-CANDIDAT

### Ce que le projet ne sait pas

**Aucun document de ce dépôt ne contient le score minimal officiel de la
certification Symfony 8.** Le seuil n'est ni connu ni inventé ici. Le critère
ci-dessous est **interne** : une règle de décision que vous adoptez, pas une
exigence de l'examen.

Ce que le projet connaît et cite : le format fixé par le §10 du Master Plan pour
Mock 4 — **75 questions, 90 minutes, anglais** — repris dans
`docs/policy/mock-blueprint-policy.md`.

### Les cinq conditions

PRÊT-CANDIDAT est atteint quand **les cinq** sont vraies. Aucune ne se compense.

| # | Condition | Comment elle se vérifie |
|---:|---|---|
| 1 | Les 163 items ont été étudiés et ont passé l'assessment de leur lot | aucun item resté `NON ACQUIS` sans plan de correction exécuté |
| 2 | Aucun des 11 items `DEEP` n'est `FRAGILE` | ce sont les items où deviner juste est le plus facile |
| 3 | Les mocks 1, 2, 3 et 5 ont été passés **et corrigés** | un mock non analysé ne compte pas |
| 4 | Deux mocks consécutifs sans régression sur les lots structurants | 02, 03, 04, 05, 07, 08, 09, 10, 11 |
| 5 | Mock 4 passé dans les conditions officielles | 75 questions, 90 minutes, anglais, sans interruption ni documentation |

### Sur Mock 4

Mock 4 est le **holdout** : ses 75 questions ne sont dans aucun autre support.
C'est la seule mesure non biaisée dont vous disposerez, et **elle ne se reproduit
pas**. Le placer le 12 décembre, trois jours avant l'examen, est le seul choix
qui en préserve la valeur et laisse le temps d'agir sur ce qu'il révèle.

**Son résultat ne sera pas inventé ici.** Ce document n'avance aucun score
prévisionnel, ni pour Mock 4 ni pour l'examen.

---

## Décision go / no-go — 13 décembre 2026

| Si | Alors |
|---|---|
| Les cinq conditions sont vraies | Passer l'examen le 15. |
| Mock 4 révèle un lot structurant en échec | **Reporter l'examen.** Deux jours ne suffisent pas à reprendre un lot, et Mock 4 est dépensé : la re-mesure passera par Mock 5. |
| Une ou deux conditions manquent sur des items isolés | Passer. Un `PASS` n'exige pas un sans-faute, et les deux jours restants servent à ces items. |

**Le 13 décembre est tard pour découvrir un problème de fond.** C'est le prix de
la date choisie, et c'est pourquoi les assessments de lot comptent plus ici que
dans un calendrier long : ils sont le seul endroit où un lot faible peut encore
être rattrapé.

---

## Ce qui ferait glisser ces dates

Elles supposent la disponibilité tenue **sans interruption pendant onze
semaines**, avec sept semaines à 13,7–14,7 h. Aucun congé, aucun imprévu n'est
provisionné.

| Événement | Effet |
|---|---|
| Une semaine perdue | les lots finissent le 4 décembre ; il ne reste plus que deux week-ends pour cinq mocks — le plan ne tient plus sans réduire le nombre de mocks |
| Rythme de 4 nouveautés/jour trop rapide | passer à 3 repousse la fin des lots au 16 décembre : **l'examen du 15 devient impossible** |
| Plus d'un tiers de `NON ACQUIS` sur un lot | signal de rythme — voir [`mastery-checkpoints.md`](mastery-checkpoints.md) |

**La marge est mince.** Une semaine perdue ne se rattrape pas dans ce calendrier.
Si cela arrive, la décision honnête est de reculer l'examen, pas de comprimer les
mocks.

En cas de glissement, **régénérez** plutôt que de décaler à la main :

```bash
python3 tools/revision/build_roadmap.py --start AAAA-MM-JJ --exam AAAA-MM-JJ \
    --max-new 4 --weekday 120 --weekend 180
python3 tools/revision/render_calendar.py
```

Le générateur **refuse** de produire un plan où les mocks ne tiennent pas après
la fin des lots : il s'arrête avec un message nommant le nombre de week-ends
manquants, plutôt que d'avancer un mock avant que tout soit étudié.
