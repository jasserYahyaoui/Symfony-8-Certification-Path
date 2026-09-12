# Lot 12 — Console, raffiné sous le cadre version 2

Dixième lot raffiné. Neuf items, vingt-cinq outcomes, vingt-neuf questions au
départ.

## 1. Ce que le cadre a trouvé

Ce lot est en meilleur état que les précédents, et le rapport doit le dire
exactement plutôt que gonfler le déficit.

**Trois outcomes sur vingt-cinq n'étaient évalués par aucune question** :

| Item | Outcome sans aucune question |
|---|---|
| Console component | le point d'entrée du framework |
| Options and arguments | le lien entre attribut et constante `InputOption` |
| Input and Output objects | les balises de style d'`OutputInterface` |

**Six autres n'étaient couverts qu'indirectement** — une question les touchait
sans porter sur ce qu'ils énoncent : le préfixe de namespace (une question sur
`list` en tenait lieu), les alias (seule la visibilité était testée), les neuf
helpers (une question sur `SymfonyStyle`), l'ordre des événements erreur comprise
(une question sur `TERMINATE` seul), l'effet de `setExitCode()` (seul
`disableCommand()` était testé), et les six niveaux de verbosité (une question
sur l'écriture à un niveau donné).

Ces six-là étaient donc *formellement* assessés au sens de `PED-003`, et le
resteraient sans cette unité. Les renforcer est un jugement, pas une obligation
de règle ; il est consigné ici pour qu'un relecteur puisse le contester.

## 2. Une règle a de nouveau attrapé mon annotation

`ARC-001` a refusé `QST-qcgrr0jydf3t`, étiquetée `CONCEPT_DISTINCTION` alors que
son `exam_skill` est `RECOGNIZE` — *« CONCEPT_DISTINCTION separates two
mechanisms; exam_skill RECOGNIZE only asks for one »*. Archétype corrigé en
`API_SIGNATURE`, qui décrit ce que la question demande réellement : la surface
d'API que `SymfonyStyle` ajoute.

C'est la **deuxième** unité consécutive où la règle attrape mon annotation
plutôt que le contenu. Le motif est stable : j'étiquette d'après ce que la
question *paraît* faire, la règle lit ce que ses deux autres champs *déclarent*.

## 3. Une question écrite a été supprimée, pas corrigée

La vérification de recouvrement avec le holdout — instaurée au lot 07, rejouée
ici — a montré qu'une des dix questions écrites, sur l'ordre du cycle de vie,
**reposait la question d'une question réservée** du même item.

Elle n'a pas été réécrite mais **retirée**. La raison est le §1.4 : son outcome
(`Ordonner initialize(), interact() et la méthode d'exécution`) était **déjà
évalué** par une question préexistante. Elle n'apportait donc rien au corpus et
coûtait une question du holdout. Une question qui ne passe pas la porte de
valeur nette ne se réécrit pas, elle disparaît.

Neuf questions ajoutées, une écrite puis supprimée, solde **+9**.

## 4. Un fait que le cours ne donne pas

`ConsoleErrorEvent::setExitCode()` ne se contente pas de retenir le code de
sortie : il **l'écrit sur l'exception elle-même**, par réflexion sur sa propriété
`code`. Un gestionnaire situé plus loin qui lit le code de l'exception voit donc
la valeur imposée par l'écouteur, et les deux ne peuvent jamais diverger. Sans
cet appel, `getExitCode()` retombe sur le code propre de l'erreur s'il est non
nul, sinon sur 1.

Vérifié dans la source, pas dans un souvenir : `symfony/symfony` branche 8.0,
SHA `6f841c0`, `Component/Console/Event/ConsoleErrorEvent.php`.

## 5. L'indice de longueur

| | Avant | Après |
|---|---:|---:|
| bonne réponse strictement la plus longue | 22/29 = **75,9 %** | 9/38 = **23,7 %** |
| seuil du hasard | 25,0 % | 25,0 % |

C'était le lot le plus biaisé du projet. **Quatorze éditions**, toutes sur la
bonne réponse d'une question préexistante, toutes du même geste : la bonne
réponse portait sa propre justification (« X, parce que Y », « X, qui fait Z »)
quand les distracteurs n'en portaient pas. La justification appartient à
l'explication, pas au choix — la question s'en trouve meilleure, pas seulement
plus courte.

**Neuf cas subsistent, laissés délibérément.** Trois appartiennent au holdout,
que cette unité n'ouvre pas. Les six autres ont un écart de un à cinq caractères
avec le distracteur le plus long : à cette échelle la longueur n'est pas un
indice exploitable par un lecteur humain, et raccourcir davantage fabriquerait
l'indice inverse — une bonne réponse systématiquement plus courte se repère
aussi bien qu'une systématiquement plus longue.

**Aucune clé de réponse n'a bougé**, vérifié par identifiant de choix.

## 6. État mesuré du lot

| | Avant | Après |
|---|---:|---:|
| items | 9 | 9 |
| questions | 29 | **38** |
| dont LEARNING / VALIDATION / HOLDOUT | 18 / 7 / 4 | **27 / 7 / 4** |
| outcomes portant un id `OUT` | 0 | **25** |
| outcomes évalués hors HOLDOUT | 22 (mesuré après coup, aucun id n'existait) | **25** |
| questions portant un `question_archetype` | 0 | **38** |
| archétypes distincts employés | 0 | **7** sur 11 |
| cours hors budget de révision | 0 | 0 |
| indice de longueur | 75,9 % | **23,7 %** |

Niveaux : 2 `MINIMAL`, 7 `STANDARD`, 0 `DEEP` — observation, pas cible. Un lot
sans item `DEEP` est complet.

Périmètre vérifié par script contre `c868d2a` : 9 items de matrice touchés, tous
du lot 12 ; **10 questions ajoutées, 1 supprimée**, 14 textes de choix édités,
**0 clé de réponse déplacée, 0 énoncé modifié**.

## 7. Portes

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — exit 0, 22 règles, **0 bloquant** |
| idem sous sonde « lot 12 raffiné » | PASS après la correction du §2 |
| `vendor/bin/phpunit` | PASS — 245 tests, 13 896 assertions |
| `composer gate-full` | PASS — exit 0 |
| `npm --prefix website run a11y` | PASS — 22 surfaces, 0 violation |
| `prove_framework_rules_fail.py` | PASS — 7 cas, restauration SHA-256 vérifiée |
| `aud10` sur le lot 12 | **23,7 %**, sous le seuil de hasard |
| Couverture officielle | 100 % (163/163) — inchangée |

## 8. Limites

- **Aucun des neuf cours n'a été lu intégralement.** Les questions viennent des
  sources amont et du code au SHA `6f841c0`. Une contradiction cours/question
  resterait invisible sauf si `CRS-001` la voit.
- Les neuf questions et les quatorze éditions n'ont pas été relues par un humain.
- Le contrôle de recouvrement avec le holdout ne compare que les **mots de la
  bonne réponse**. Il a trouvé un cas ici ; il ne prouve pas qu'il n'en reste
  aucun.
