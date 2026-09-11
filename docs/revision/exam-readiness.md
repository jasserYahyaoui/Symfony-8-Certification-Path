# PRÊT-CANDIDAT — dates, charge, et critère de passage

Ce document répond à une seule question : **à partir de quand est-il raisonnable
de réserver l'examen ?**

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

## Dates prévisionnelles

Calculées par le générateur à partir des volumes mesurés et des budgets
journaliers déclarés. Départ : **jeudi 1er octobre 2026**.

| Jalon | Date | Semaine |
|---|---|---:|
| Premier cours | **1er octobre 2026** | S1 |
| Fin du lot 01 — PHP | 5 octobre 2026 | S1 |
| Fin du lot 03 — Symfony Architecture | 16 octobre 2026 | S3 |
| Fin du lot 09 — Dependency Injection | 3 novembre 2026 | S5 |
| Fin du lot 10 — Security | 19 novembre 2026 | S8 |
| **Tous les 163 items étudiés une fois** | **18 décembre 2026** | **S12** |
| Mock 1 | 19 décembre 2026 | S12 |
| Mock 2 | 26 décembre 2026 | S13 |
| Mock 3 | 2 janvier 2027 | S14 |
| Mock 5 | 9 janvier 2027 | S15 |
| Fin des révisions espacées (dernier J+60) | 23 janvier 2027 | S17 |
| **Mock 4 — format officiel, holdout, une seule fois** | **23 janvier 2027** | **S17** |
| **PRÊT-CANDIDAT — décision go / no-go** | **24 janvier 2027** | **S17** |

**Durée totale : 17 semaines**, du 1er octobre 2026 au 24 janvier 2027.

Les 26 assessments de lot sont placés le dimanche suivant la fin de chaque lot ;
le détail est dans [`mastery-checkpoints.md`](mastery-checkpoints.md).

---

## Charge de travail

| Poste | Heures | Origine du chiffre |
|---|---:|---|
| Première passe — cours | 12,2 h | 71 578 mots mesurés ÷ 110 mots/min (**hypothèse**) |
| Première passe — questions | 13,6 h | `estimated_time_seconds` des 519 questions × 1,6 (**champ réel × hypothèse**) |
| Première passe — flashcards | 2,3 h | 137 cartes × 45 s (**hypothèse**) |
| Révisions espacées | 57,3 h | barème par niveau, J+1 à J+30, + J+45/J+60 pour les transverses et les `DEEP` |
| Assessments de lot | 13,0 h | 26 × 30 min |
| Mocks et corrections | 12,5 h | 5 × (90 min + 60 min) |
| **Total** | **110,9 h** | |

Réparti sur 17 semaines : **6,5 h par semaine en moyenne de travail décompté**,
dans un budget planifié de 10,9 h. L'écart est la marge de rattrapage, le source
tour du samedi et la consolidation du dimanche, qui ne sont pas décomptés item
par item.

**Le poste dominant n'est pas la lecture des cours (12,2 h) mais les révisions
espacées (57,3 h).** Toute tentation d'« avancer plus vite » en sautant des
révisions attaque 52 % du plan pour gagner sur les 11 % qui coûtent le moins.

---

## Le critère PRÊT-CANDIDAT

### Ce que le projet ne sait pas

**Aucun document de ce dépôt ne contient le score minimal officiel de la
certification Symfony 8.** Le seuil n'est ni connu ni inventé ici. Le critère
ci-dessous est donc **interne** : c'est une règle de décision que vous adoptez,
pas une exigence de l'examen.

Ce que le projet connaît et cite : le format fixé par le §10 du Master Plan pour
Mock 4 — **75 questions, 90 minutes, anglais** — repris dans
`docs/policy/mock-blueprint-policy.md`.

### Les cinq conditions

PRÊT-CANDIDAT est atteint quand **les cinq** sont vraies. Aucune ne se compense.

| # | Condition | Comment elle se vérifie |
|---:|---|---|
| 1 | Les 163 items ont été étudiés et ont passé l'assessment de leur lot | aucun item n'est resté `NON ACQUIS` sans plan de correction exécuté |
| 2 | Aucun des 11 items `DEEP` n'est `FRAGILE` | ce sont les items où deviner juste est le plus facile |
| 3 | Les mocks 1, 2, 3 et 5 ont été passés **et corrigés** | un mock non analysé ne compte pas |
| 4 | Deux mocks consécutifs sans régression sur les lots structurants | 02, 03, 04, 05, 07, 08, 09, 10, 11 |
| 5 | Mock 4 passé dans les conditions officielles | 75 questions, 90 minutes, anglais, sans interruption et sans documentation |

### Sur Mock 4, une précision qui compte

Mock 4 est le **holdout** : ses 75 questions ne sont dans aucun autre support.
C'est la seule mesure non biaisée dont vous disposerez, et **elle ne se
reproduit pas** — une fois vues, les questions sont vues.

Deux conséquences pratiques :

- **Ne le passez pas en avance « pour voir ».** Le placer en S17 est le seul
  choix qui en préserve la valeur.
- **Son résultat ne sera pas inventé ici.** Ce document n'avance aucun score
  prévisionnel, ni pour Mock 4 ni pour l'examen. Seule votre épreuve réelle le
  dira.

---

## Décision go / no-go — 24 janvier 2027

| Si | Alors |
|---|---|
| Les cinq conditions sont vraies | Réserver l'examen. Le passer **dans les deux semaines** : au-delà, le bénéfice des révisions espacées commence à se dissiper. |
| Mock 4 révèle un lot structurant en échec | Ne pas réserver. Reprendre ce lot avec le protocole en quatre causes, puis se rabattre sur Mock 5 pour re-mesurer — Mock 4 est dépensé. |
| Une ou deux conditions manquent sur des items isolés | Réserver, et consacrer les deux semaines d'attente à ces items. Un `PASS` n'exige pas un sans-faute. |

---

## Ce qui ferait glisser ces dates

Elles supposent la disponibilité déclarée tenue **sans interruption pendant 17
semaines**. Aucun congé, aucune semaine chargée, aucun imprévu n'a été provisionné
— c'est ce que vous avez indiqué, et le plan le prend au mot.

| Événement | Effet |
|---|---|
| Une semaine perdue | +1 semaine sur toutes les dates suivantes, et les révisions dues pendant l'absence s'accumulent sur le dimanche de retour |
| Rythme de 3 nouveautés/jour trop rapide | passer à 2 dans le générateur : +4 semaines environ, à re-mesurer |
| Plus d'un tiers de `NON ACQUIS` sur un lot | signal de rythme, pas d'échec — voir [`mastery-checkpoints.md`](mastery-checkpoints.md) |

En cas de glissement, **régénérez le calendrier** plutôt que de décaler les dates
à la main :

```bash
python3 tools/revision/build_roadmap.py --start AAAA-MM-JJ
```

---

## Le chemin le plus court, si la date devait avancer

Détaillé dans [`study-roadmap.md`](study-roadmap.md#le-chemin-le-plus-court-vers-prêt-candidat-à-100--de-couverture).

| | Plan complet | Chemin minimal |
|---|---:|---:|
| Couverture du syllabus | 100 % (163/163) | 100 % (163/163) |
| Révisions | J+1, 3, 7, 14, 30 (+45, 60 transverses) | J+1, 3, 7 |
| Charge | 110,9 h | 90,6 h |
| Mock 4 | 23 janvier 2027 | ≈ 9 janvier 2027 |

Le raccourci ne touche **jamais** à l'étendue : les 163 items restent étudiés.
Il ne rogne que la profondeur de révision, et il coûte les deux échéances qui
font tenir une notion à un mois. À n'utiliser que sous contrainte extérieure.
