# Lot 07 — Forms, raffiné sous le cadre version 2

Neuvième lot raffiné. Treize items, cinquante-huit outcomes, quarante-trois
questions au départ.

## 1. Ce que le cadre a trouvé

**Vingt et un outcomes sur cinquante-huit sans évaluation** — aucun n'était
couvert, même par le holdout. C'est le plus gros déficit rencontré jusqu'ici.

| Item | Outcome sans évaluation |
|---|---|
| Form component | le problème que le composant résout · `createForm()`/`getData()` sur la couche modèle |
| Forms creation | contrôleur contre classe de type · la signature de `add()` |
| Forms handling | l'action unique · l'effet de `clearMissing` sur la validation |
| Form types | un formulaire **est** un type · hériter contre imbriquer |
| Forms rendering | choisir la granularité |
| Forms theming | la chaîne de recherche des blocs · les parties d'un champ |
| CSRF protection | ce qui est posé et vérifié automatiquement · un `csrf_token_id` par formulaire |
| Built-in form types | `ChoiceType` parent de la famille des choix · les types hors périmètre |
| Data transformers | le cas de `null` dans `transform()` |
| Form events | les cinq événements en deux phases |
| Form type extensions | étendre le type racine · le tag `form.type_extension` |
| Form options | le normaliseur · l'option inconnue |

## 2. Une règle a attrapé mon annotation

`ARC-001` a refusé `QST-f67fd9fny984` : je l'avais étiquetée `DEFINITION_RECALL`
alors que son `cognitive_level` est `APPLY`. La règle dit exactement pourquoi —
*« DEFINITION_RECALL is recall; cognitive_level APPLY contradicts it (§4.1) »* —
et elle a raison : deux champs affirmaient des choses incompatibles sur la même
question. Archétype corrigé en `API_SIGNATURE`. Aucune règle assouplie.

C'est le second cas du projet où une règle attrape le travail de cette unité
plutôt que du legacy, et le premier qui porte sur l'**annotation** et non sur le
contenu.

## 3. Une première : une question d'exclusion

`OUT-pesfrvtg5qj0` demande d'*identifier les types hors périmètre d'examen*.
L'évaluer suppose de nommer un topic exclu, ce que `SCOPE-001` refuse dans une
question notée — sauf si elle porte le tag `exclusion-note`, que le §1.5 prévoit
pour « une explication clairement étiquetée d'une exclusion ».

Ce tag existait depuis l'origine et **n'avait jamais servi**. La question ajoutée
est la première à l'employer : son énoncé commence par *« Exclusion note »*, son
explication dit qu'elle marque une frontière et n'enseigne pas le topic exclu.

C'est un précédent, pas une routine. Il mérite une relecture humaine : l'autre
issue possible était de reformuler l'outcome, ce qui aurait fait disparaître la
frontière au lieu de l'enseigner.

## 4. Ce qui a été ajouté

**Vingt et une questions**, toutes `LEARNING`, en anglais. Quelques-unes valent
d'être citées :

| Item | Outcome couvert | Archétype |
|---|---|---|
| Forms handling | `clearMissing` à `false` ne valide que les champs soumis | `BEHAVIOR_DIAGNOSIS` |
| Forms theming | la chaîne va du plus spécifique au type parent | `SEQUENCE_ORDER` |
| Form events | les cinq événements, deux phases qui ne s'entrelacent pas | `SEQUENCE_ORDER` |
| Data transformers | `transform(null)` rend la valeur vide du type cible | `BEHAVIOR_DIAGNOSIS` |
| Form type extensions | étendre le type racine touche presque tous les champs | `BEHAVIOR_DIAGNOSIS` |
| Form options | le normaliseur s'exécute **après** la validation | `BEHAVIOR_DIAGNOSIS` |
| Form options | une option inconnue lève une exception qui liste les options définies | `BEHAVIOR_DIAGNOSIS` |
| CSRF protection | un `csrf_token_id` par formulaire confine un jeton volé | `CONCEPT_DISTINCTION` |

## 5. L'indice de longueur

| | Avant | Après |
|---|---:|---:|
| bonne réponse strictement la plus longue | 19/43 = **44,2 %** | 13/64 = **20,3 %** |
| seuil du hasard | 25,0 % | 25,0 % |

**Onze choix de questions préexistantes ont été édités**, en deux passes
mesurées, contre zéro au lot 06 : ici les vingt et une questions ajoutées ne
suffisaient pas à faire passer le lot sous le seuil. Le compte est établi par
comparaison choix par choix avec `ff6714f`, pas par le nombre de commandes
lancées.

**Sept allongent un distracteur**, dont chaque énoncé reste faux et non
trompeur ; **quatre raccourcissent la formulation de la bonne réponse** sans en
changer le sens. Trois questions écrites dans cette unité ont par ailleurs vu
leur bonne réponse raccourcie avant d'être livrées.

**Aucune clé de réponse n'a bougé** : vérifié par identifiant de choix, pas par
texte.

Treize questions restent au-dessus, et **elles y restent délibérément** : trois
appartiennent au holdout, que cette unité n'ouvre pas ; les autres ont pour
bonne réponse une valeur d'API ou une énumération dont la longueur *est*
l'information — `setAllowedValues()`, `addModelTransformer()`,
`_user_email_widget`, la liste des accesseurs acceptés. Les raccourcir
fabriquerait un indice inverse.

## 6. État mesuré du lot

| | Avant | Après |
|---|---:|---:|
| items | 13 | 13 |
| questions | 43 | **64** |
| dont LEARNING / VALIDATION / HOLDOUT | 27 / 10 / 6 | **48 / 10 / 6** |
| outcomes portant un id `OUT` | 0 | **58** |
| outcomes évalués hors HOLDOUT | 37 (mesuré après coup, aucun id n'existait) | **58** |
| questions portant un `question_archetype` | 0 | **64** |
| archétypes distincts employés | 0 | **7** sur 11 |
| cours hors budget de révision | 0 | 0 |
| indice de longueur | 44,2 % | **20,3 %** |

Niveaux : 3 `MINIMAL`, 9 `STANDARD`, 1 `DEEP` — observation, pas cible.

Périmètre vérifié par script contre `ff6714f` : 13 items de matrice touchés,
**tous du lot 07** ; 21 questions ajoutées, 0 supprimée ; **0 clé de réponse
déplacée, 0 énoncé modifié** ; 11 textes de choix édités, tous du lot 07.

## 7. Ce qui n'a pas reçu d'id d'outcome

Les **six questions HOLDOUT** du lot portent un `question_archetype` mais aucun
`assesses_outcomes`, pour la raison donnée au lot 06 : une question du holdout
ne libère jamais un outcome, et un lien deviné serait une affirmation fausse.

## 8. Portes

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — exit 0, 22 règles, **0 bloquant** |
| idem, lot 07 marqué raffiné (sonde) | PASS après correction de l'archétype du §2 |
| `vendor/bin/phpunit` | PASS — 245 tests, 13 876 assertions |
| `composer gate-full` | PASS — exit 0 |
| `npm --prefix website run a11y` | PASS — 22 surfaces, 0 violation |
| `aud10` sur le lot 07 | **20,3 %**, sous le seuil de hasard |
| Couverture officielle | 100 % (163/163) — inchangée |

## 9. Limites

- **Aucun des treize cours n'a été lu intégralement.** Les vingt et une questions
  sont écrites depuis les sources amont (`forms.rst`, `form/*.rst`,
  `components/options_resolver.rst`, `security/csrf.rst`), pas depuis le cours
  correspondant. Une contradiction cours/question resterait invisible sauf si
  `CRS-001` la voit. C'est la limite la plus sérieuse de cette unité.
- Les vingt et une questions et les seize éditions n'ont pas été relues par un
  humain. Les éditions de distracteurs méritent la relecture la plus attentive :
  allonger un distracteur sans le rendre trompeur est un jugement, pas une
  mesure.
- La question d'exclusion du §3 introduit un précédent qui appelle une décision
  humaine.
- Symfony n'est pas installé ici : aucun fait n'a été exécuté, tous sont lus dans
  `symfony-docs` branche 8.0.
