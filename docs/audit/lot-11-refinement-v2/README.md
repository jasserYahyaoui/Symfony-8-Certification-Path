# Lot 11 — Messenger, raffiné sous le cadre version 2

Treizième lot raffiné. Sept items, vingt-huit outcomes, vingt-cinq questions au
départ.

## 1. Ce que le cadre a trouvé

**Huit outcomes sur vingt-huit** n'étaient évalués par aucune question :

| Item | Outcome sans aucune question |
|---|---|
| Transports | router un message vers un transport · viser plusieurs transports |
| Messages and handlers | écrire un message et son handler sans interface |
| Workers | les services sont partagés entre messages |
| Retries and failures | l'enchaînement complet d'un échec · `messenger:failed:*` |
| Middleware | `HandleMessageMiddleware` en bout de chaîne |
| Events | situer les événements dans le cycle de vie |

Le déficit portait donc surtout sur les **fondations** — comment on route, comment
on déclare un handler — alors que les cas limites étaient déjà couverts. C'est
l'inverse du profil habituel.

## 2. Le pré-contrôle ajouté au lot 08 a servi dès ce lot

`check_annotation_map.py`, écrit au lot 08 après trois refus consécutifs
d'`ARC-001`, a refusé une annotation **avant** qu'elle soit appliquée :

```
ARC-001 would reject: QST-bzvvb66w5ftd:
  BEHAVIOR_DIAGNOSIS requires exam_skill DIAGNOSE, has DISTINGUISH
```

C'est la première fois de la session que cette classe d'erreur ne va pas jusqu'à
CI. Le mécanisme est le même qu'aux lots 07, 12 et 08 — un archétype choisi
d'après l'allure de la question — mais le coût est tombé d'un cycle
d'intégration à une commande locale.

## 3. Une seconde erreur d'annotation, attrapée par une vérification différente

La vérification de périmètre a refusé `QST-ep0z3tq9s4wk`, qui réclamait un
outcome de *Messages and handlers* alors qu'elle appartient à *Messenger
component*. `PED-003` l'aurait refusée, et le pré-contrôle du §2 **ne l'aurait
pas vue** : il vérifie les contraintes d'archétype, pas l'appartenance d'un
outcome à son item.

Les deux contrôles sont donc complémentaires, et aucun ne remplace le validateur.

## 4. Ce qui a été ajouté

**Huit questions**, toutes `LEARNING`, en anglais.

| Item | Outcome couvert | Archétype |
|---|---|---|
| Transports | attribut sur la classe, ou clé de routage | `CONFIG_BEHAVIOR` |
| Transports | une liste de transports pour un même message | `CONFIG_BEHAVIOR` |
| Messages and handlers | attribut plus `__invoke()` typé, sans interface | `API_SIGNATURE` |
| Workers | la même instance sert tous les messages ; réinitialiser | `BEHAVIOR_DIAGNOSIS` |
| Retries and failures | réessais croissants, puis transport d'échec | `SEQUENCE_ORDER` |
| Retries and failures | `messenger:failed:*` pour inspecter puis rejouer | `SCENARIO_CHOICE` |
| Middleware | le middleware d'appel est en bout de chaîne | `CONCEPT_DISTINCTION` |
| Events | ce qu'un écouteur voit et qu'un middleware ne voit pas | `CONCEPT_DISTINCTION` |

La question sur les workers porte le fait le plus coûteux du lot en production :
un service à état fuit d'un message au suivant parce que le processus est long,
et seule l'implémentation de l'interface de réinitialisation le nettoie — que
l'option `--no-reset` désactive par ailleurs.

## 5. L'indice de longueur

| | Avant | Après |
|---|---:|---:|
| bonne réponse strictement la plus longue | 12/25 = **48,0 %** | 7/32 = **21,9 %** |
| seuil du hasard | 25,0 % | 25,0 % |

**Sept éditions**, toutes sur la bonne réponse d'une question préexistante,
toutes au-dessus du seuil de dix caractères posé au lot 13, et toutes du même
geste : retirer du choix la justification qu'il portait, après avoir vérifié
qu'elle figure dans l'explication.

Une exception assumée : `WorkerMessageFailedEvent, through willRetry()` garde son
écart de dix-sept caractères. La bonne réponse **est** une paire de noms d'API ;
la raccourcir supprimerait l'information que la question teste.

**Aucune clé de réponse n'a bougé**, vérifié par identifiant de choix.

## 6. État mesuré du lot

| | Avant | Après |
|---|---:|---:|
| items | 7 | 7 |
| questions | 25 | **33** |
| dont LEARNING / VALIDATION / HOLDOUT | 15 / 7 / 3 | **23 / 7 / 3** |
| outcomes portant un id `OUT` | 0 | **28** |
| outcomes évalués hors HOLDOUT | 20 (mesuré après coup, aucun id n'existait) | **28** |
| questions portant un `question_archetype` | 0 | **33** |
| archétypes distincts employés | 0 | **7** sur 11 |
| indice de longueur | 48,0 % | **21,9 %** |

Niveaux : 6 `STANDARD`, 1 `DEEP` — observation, pas cible.

Périmètre vérifié par script contre `d6c6f46` : 7 items de matrice touchés, tous
du lot 11 ; 8 questions ajoutées, 0 supprimée ; 7 textes de choix édités, tous du
lot 11 ; **0 clé de réponse déplacée, 0 énoncé modifié**.

Le contrôle de recouvrement avec le holdout donne au plus `0,08` : aucune des
huit questions écrites ne repose la question d'une question réservée.

## 7. Portes

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — exit 0, 22 règles, **0 bloquant** |
| idem sous sonde « lot 11 raffiné » | PASS |
| `check_annotation_map.py` | PASS — 0 problème sur 25, après la correction du §2 |
| `composer gate-full` | PASS — exit 0 |
| `aud10` sur le lot 11 | **21,9 %**, sous le seuil de hasard |
| Couverture officielle | 100 % (163/163) — inchangée |

## 8. Limites

- **Aucun des sept cours n'a été lu intégralement** ; les huit questions viennent
  de `messenger.rst` branche 8.0.
- Les huit questions et les sept éditions n'ont pas été relues par un humain.
- Le lot compte **trois** questions HOLDOUT, contre quatre pour la plupart des
  lots de taille voisine. Ce n'est pas un défaut de cette unité — la répartition
  du holdout a été fixée par le lot 27 — mais c'est une asymétrie qu'un relecteur
  doit connaître.
