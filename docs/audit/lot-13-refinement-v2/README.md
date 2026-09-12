# Lot 13 — Automated Tests, raffiné sous le cadre version 2

Onzième lot raffiné. Neuf items, vingt-cinq outcomes, vingt-neuf questions au
départ.

## 1. Ce que le cadre a trouvé

**C'est le lot le mieux tenu du projet à ce jour, et le rapport doit le dire.**

**Deux outcomes sur vingt-cinq** n'étaient évalués par aucune question :

| Item | Outcome sans aucune question |
|---|---|
| Unit tests with PHPUnit | installer et lancer la suite |
| Handling legacy deprecated code | les règles d'introduction d'une dépréciation |

Les vingt-trois autres l'étaient déjà. Deux questions suffisent donc à fermer le
déficit, contre dix-neuf au lot 06 et vingt et un au lot 07 : le travail de cette
unité porte surtout sur l'annotation et sur l'indice de longueur.

## 2. Une dette ancienne éteinte : `REV-001` ne signale plus rien

Le cours *Handling legacy deprecated code* comptait **450 mots de corps pour un
budget `MINIMAL` de 400**. `REV-001` le signalait en `WARNING` à chaque
exécution du validateur depuis l'introduction de la règle — et serait devenu
**bloquant** dès ce lot déclaré raffiné, puisque la règle passe en erreur dans un
lot raffiné.

La règle offre deux lectures : « le contenu fait plus que le niveau ne l'annonce,
ou le niveau est faux ». **Aucune des deux ne s'appliquait.** L'excédent n'était
pas de l'enseignement : c'était une section *Périmètre* de cent mots qui
expliquait ce que la page **ne** traite **pas** — le PHPUnit Bridge, hors
périmètre d'examen, et la déclaration d'une dépréciation, qui appartient à un
item prérequis.

Cette prose est du méta-contenu. Elle a été condensée en cinq lignes, et trois
paragraphes ont été resserrés sans qu'une seule affirmation disparaisse :
**450 → 396 mots**. Le niveau `MINIMAL` est inchangé, et il reste juste : la page
énonce une convention à reconnaître.

Ni le niveau n'a été promu pour faire entrer le texte, ni le texte tronqué de son
enseignement pour entrer dans le niveau.

## 3. Une règle a attrapé une question écrite dans cette unité

`SCOPE-001` a refusé `QST-3ayfnb7ae5bq` : un distracteur nommait
`symfony/phpunit-bridge`, topic **exclu** du périmètre (§1.5).

La question a été **réécrite**, conformément au précédent des lots 08, 09 et 10 :
le distracteur devient `phpunit/phpunit`, qui est faux pour une raison utile —
installer le lanceur seul laisse de côté les paquets navigateur, *crawler* et
sélecteur dont dépendent les assertions Symfony.

Ironie utile : le cours du même lot dit explicitement que le PHPUnit Bridge est
hors périmètre, et j'ai quand même écrit une question qui le nommait. La règle
l'a vu, pas moi.

## 4. Ce qui a été ajouté

**Deux questions**, toutes deux `LEARNING`, en anglais.

| Item | Outcome couvert | Archétype |
|---|---|---|
| Unit tests with PHPUnit | `symfony/test-pack` installe de quoi écrire et lancer | `SCENARIO_CHOICE` |
| Handling legacy deprecated code | une dépréciation n'arrive qu'en mineure suivante | `CONCEPT_DISTINCTION` |

La seconde s'appuie sur une convention explicite : *« Deprecations must only be
introduced on the next minor version »*, avec une exception pour les cas
critiques sur versions supportées. Deux conséquences en découlent, et
l'explication les donne : une classe ou une méthode **neuve** ne peut pas naître
dépréciée, et le message doit nommer la version de départ.

## 5. L'indice de longueur

| | Avant | Après |
|---|---:|---:|
| bonne réponse strictement la plus longue | 17/30 = **56,7 %** | 7/30 = **23,3 %** |
| seuil du hasard | 25,0 % | 25,0 % |

**Quatorze éditions, toutes sur la bonne réponse d'une question préexistante**,
et toutes du même geste qu'au lot 12 : la bonne réponse portait sa justification
(« X, parce que Y », « X, qui fait Z ») quand les distracteurs n'en portaient
pas. La justification a été vérifiée **présente dans l'explication** avant
chaque coupe — elle n'est pas perdue, elle est à sa place.

Le seuil appliqué est écrit : **un écart d'au moins dix caractères** avec le
distracteur le plus long. En dessous, la longueur n'est pas un indice qu'un
lecteur humain peut exploiter, et raccourcir fabriquerait l'indice inverse.

Sept cas subsistent : deux du holdout, que cette unité n'ouvre pas, et cinq dont
l'écart est de deux à cinq caractères.

**Aucune clé de réponse n'a bougé**, vérifié par identifiant de choix.

## 6. État mesuré du lot

| | Avant | Après |
|---|---:|---:|
| items | 9 | 9 |
| questions | 29 | **31** |
| dont LEARNING / VALIDATION / HOLDOUT | 18 / 7 / 4 | **20 / 7 / 4** |
| outcomes portant un id `OUT` | 0 | **25** |
| outcomes évalués hors HOLDOUT | 23 (mesuré après coup, aucun id n'existait) | **25** |
| questions portant un `question_archetype` | 0 | **31** |
| archétypes distincts employés | 0 | **6** sur 11 |
| cours hors budget de révision | **1** | **0** |
| indice de longueur | 56,7 % | **23,3 %** |

Niveaux : 2 `MINIMAL`, 7 `STANDARD`, 0 `DEEP` — observation, pas cible.

Périmètre vérifié par script contre `4e64860` : 9 items de matrice touchés, tous
du lot 13 ; 2 questions ajoutées, 0 supprimée ; 12 textes de choix édités, tous
du lot 13 ; **0 clé de réponse déplacée, 0 énoncé modifié**.

Le contrôle de recouvrement avec le holdout ne signale rien : aucune des deux
questions écrites ne partage son item avec une question réservée.

## 7. Portes

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — exit 0, 22 règles, **0 bloquant**, et plus aucun `REV-001` |
| idem sous sonde « lot 13 raffiné » | PASS après la réécriture du §3 |
| `vendor/bin/phpunit` | PASS — 245 tests, 13 902 assertions |
| `composer gate-full` | PASS — exit 0 |
| `npm --prefix website run a11y` | PASS — 22 surfaces, 0 violation |
| `prove_framework_rules_fail.py` | PASS — 7 cas, restauration SHA-256 vérifiée |
| `aud10` sur le lot 13 | **23,3 %**, sous le seuil de hasard |
| Couverture officielle | 100 % (163/163) — inchangée |

## 8. Limites

- **Un seul des neuf cours a été lu intégralement**, celui du §2, parce qu'il
  portait la dette `REV-001`. Les deux questions viennent des sources amont.
- Les deux questions et les quatorze éditions n'ont pas été relues par un humain.
- La condensation du §2 est un **jugement de rédaction** : j'affirme n'avoir
  retiré aucun enseignement, et c'est vérifiable en lisant le diff du cours —
  c'est la relecture qui le confirmera, pas la mesure.
