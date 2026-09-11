# Lot 05 — Routing, raffiné sous le cadre version 2

Sixième lot raffiné. Douze items, quarante-cinq outcomes, trente-neuf questions
au départ.

## 1. Ce que le cadre a trouvé

**Douze outcomes sans évaluation exploitable** — neuf sans aucune question, trois
couverts seulement par le holdout.

| Item | Outcome sans évaluation |
|---|---|
| Configuration | l'équivalence YAML / attribut, et ce que chacun doit fournir |
| Restrict URL parameters | l'énumération `Requirement` |
| Set defaults | le préfixe `!` · la valeur par défaut peut violer la contrainte |
| URLs generation | comment tester qu'une route existe |
| Trigger redirects | déléguer à `RedirectController` |
| Special internal routing attributes | les formes courtes · la limite de `_fragment` |
| Conditional request matching | écrire une condition · `#[AsRoutingConditionService]` |
| User's locale guessing | `framework.default_locale` comme repli global |
| Router debugging | les options de `debug:router` |

## 2. Un fait que le cours dit approximativement

Le cours appelle `Requirement` une « énumération » et montre
`Requirement::DIGITS`. La source dit mieux : `Requirement` est **déclarée
`enum`** mais ne porte **que des `public const`, et aucun `case`**.

Ce n'est pas une subtilité de vocabulaire. Un `case` se lit par `->value` et se
compare comme objet ; une constante est directement la chaîne d'expression
régulière. C'est aussi pourquoi YAML y accède par `!php/const` et non par une
syntaxe d'énumération. Le choix de `enum` sans cas est un moyen d'obtenir un
porte-constantes qu'on ne peut ni instancier ni étendre.

La question ajoutée porte exactement là, et ses distracteurs nomment les deux
confusions naturelles — le cas d'énumération et la constante de classe finale.

## 3. Ce qui a été ajouté

**Douze questions**, toutes `LEARNING`, en anglais.

| Item | Outcome couvert | Archétype |
|---|---|---|
| Configuration | le contrôleur, implicite en attribut, explicite en YAML | `CONCEPT_DISTINCTION` |
| Restrict URL parameters | `Requirement` : un enum sans cas | `API_SIGNATURE` |
| Set defaults | `{!page}` force le défaut dans l'URL générée | `BEHAVIOR_PREDICTION` |
| Set defaults | un défaut n'a pas à satisfaire la contrainte | `CONCEPT_DISTINCTION` |
| URLs generation | `RouteNotFoundException` plutôt que `getRouteCollection()` | `SCENARIO_CHOICE` |
| Trigger redirects | `RedirectController` évite d'écrire un contrôleur | `SCENARIO_CHOICE` |
| Special attributes | `locale` pose le défaut de `_locale` | `CONCEPT_DISTINCTION` |
| Special attributes | `_fragment` est le seul exclu d'un import | `API_SIGNATURE` |
| Conditional matching | l'option `condition` et ce qu'elle prend | `API_SIGNATURE` |
| Conditional matching | `#[AsRoutingConditionService]` expose à `service()` | `API_SIGNATURE` |
| User's locale | `framework.default_locale` est le repli | `BEHAVIOR_PREDICTION` |
| Router debugging | `--show-controllers` | `API_SIGNATURE` |

## 4. L'indice de longueur

| | Avant | Après |
|---|---:|---:|
| bonne réponse strictement la plus longue | 27/50 = **54,0 %** | 12/50 = **24,0 %** |
| seuil du hasard | 25,0 % | 25,0 % |

**21 éditions sur 21 questions.** La descente a demandé trois passes : la
première (15 éditions) a laissé le lot à 30 %, la deuxième (5) à 26 %, et une
seule édition de plus l'a fait passer à 24 %. Chaque passe a été mesurée, jamais
estimée.

**Douze cas laissés tels quels** : leur bonne réponse est une **valeur d'API**
qu'on ne peut pas raccourcir sans la falsifier — `_fragment` (9 caractères
contre 7), `--show-controllers`, `/blog/{page<[0-9]+>}`,
`context, request and params`. L'écart y est de un à quatre caractères, ce qui
n'est pas un indice exploitable.

## 5. État mesuré du lot

| | Avant | Après |
|---|---:|---:|
| items | 12 | 12 |
| questions | 39 | **51** |
| dont LEARNING / VALIDATION / HOLDOUT | 24 / 9 / 6 | **36 / 9 / 6** |
| outcomes portant un id `OUT` | 0 | **45** |
| outcomes évalués hors HOLDOUT | 33 | **45** |
| questions portant un `question_archetype` | 0 | **51** |
| archétypes distincts employés | 0 | **7** sur 11 |
| cours hors budget de révision | 0 | 0 |
| indice de longueur | 54,0 % | **24,0 %** |

Niveaux : 4 `MINIMAL`, 8 `STANDARD`, 0 `DEEP` — observation, pas cible.

Périmètre vérifié par script contre `5ebe524` : **0 question hors lot 05
modifiée**, 12 items de matrice touchés, **tous du lot 05** ; 12 ajoutées,
0 supprimée, **0 clé de réponse modifiée, 0 énoncé préexistant modifié**.

Effet une fois journalisé : **36,8 % (60/163) → 44,2 % (72/163)**, lots raffinés
**5/27 → 6/27**.

## 6. Portes

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — exit 0, 22 règles, **0 bloquant** |
| `vendor/bin/phpunit` | PASS — 245 tests, 13 769 assertions |
| `composer gate-full` | PASS — exit 0 |
| `npm --prefix website run a11y` | PASS — 22 surfaces, 0 violation |
| 11 audits `tools/audit/` | PASS — exit 0 chacun |
| `verify-reschedule.mjs` | PASS — 76 jours, 440 créneaux identiques |
| `aud10` sur le lot 05 | **24,0 %**, sous le seuil de hasard |
| Couverture officielle | 100 % (163/163) — inchangée |

`LanguagePolicyTest` : corpus 615 → 627, document corrigé — cinquième
occurrence. Le plan de révision a été régénéré.

## 7. Limites

- **Neuf des douze cours ont été lus intégralement** — ceux portant les outcomes
  sans évaluation. Les trois autres (Routing component, Domain name matching,
  HTTP methods matching) ne l'ont pas été. Lecture plus étroite qu'au lot 02,
  déclarée comme telle.
- Les douze questions n'ont pas été relues par un humain.
- Une question `HOLDOUT` a été raccourcie pour l'indice de longueur : seule sa
  formulation change, la clé de réponse et l'énoncé sont intacts.
- Symfony n'est pas installé ici : tout fait provient d'une source ancrée.
