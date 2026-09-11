# Lot 10 — Security, raffiné sous le cadre version 2

Septième lot raffiné. Douze items, quarante-six outcomes, quarante et une
questions au départ.

## 1. Ce que le cadre a trouvé

**Onze outcomes sans évaluation exploitable** — huit sans aucune question, trois
couverts seulement par le holdout.

| Item | Outcome sans évaluation |
|---|---|
| Authentication | le trajet complet d'une authentification |
| Configuration | ce que `security: false` laisse intact |
| Providers | les quatre types fournis |
| Firewalls | placer un pare-feu sans `pattern` |
| Users | `getPassword()` sur une interface séparée · `getUserIdentifier()` n'est pas la clé primaire |
| Roles | `IS_AUTHENTICATED_*` n'est pas un rôle |
| Authenticators | les cinq méthodes · un badge sans effet |
| Voters | `supports()` contre `voteOnAttribute()` · les trois valeurs de vote |

## 2. Deux règles ont attrapé mes propres questions

C'est le fait marquant de cette unité, et il mérite d'être écrit tel quel : sur
les onze questions écrites, **deux ont été refusées par les règles du projet**.

**`CRS-001`** — la bonne réponse de la question sur `getPassword()` reproduisait
verbatim `PasswordAuthenticatedUserInterface`, que le cours *Users* cite. Le
learner qui lit la page voit donc la réponse.

La règle du projet est explicite : on corrige le contenu, on ne déplace pas la
chaîne dans un bloc fencé. Et le précédent des lots 08, 09 et 10 est de réécrire
la **question**, pas le cours. Elle demande désormais **pourquoi** le mot de
passe n'est pas sur `UserInterface` — un raisonnement que la page n'énonce pas
sous forme de réponse — au lieu du nom de l'interface.

**`SCOPE-001`** — la question sur les types de fournisseurs nommait *Doctrine*,
qui est un topic **exclu** du périmètre (§1.5). Elle porte maintenant sur les
**clés de configuration** acceptées sous `security.providers`, et son
explication ne nomme plus la technologie sous-jacente.

Aucune règle n'a été assouplie ; aucun cours n'a été touché. Les deux prises
sont exactement ce pour quoi ces règles existent, et elles ont porté sur du
contenu écrit **dans cette unité**, pas sur du legacy.

## 3. Un fait que le cours ne donne pas

`VoterInterface` déclare `ACCESS_GRANTED = 1`, `ACCESS_ABSTAIN = 0` et
`ACCESS_DENIED = -1`. Le cours énumère les trois valeurs sans donner leurs
entiers.

Les signes ne sont pas décoratifs : **un refus est truthy en PHP, une abstention
ne l'est pas**. Tester un vote par `if ($vote)` lit donc un refus comme un accord
et une abstention comme un refus — exactement à l'envers sur les deux cas qui
comptent. La question ajoutée porte là.

## 4. Ce qui a été ajouté

**Onze questions**, toutes `LEARNING`, en anglais.

| Item | Outcome couvert | Archétype |
|---|---|---|
| Authentication | l'ordre du trajet d'authentification | `SEQUENCE_ORDER` |
| Configuration | `security: false` ne désactive pas `access_control` | `CONCEPT_DISTINCTION` |
| Providers | les quatre clés de fournisseur | `DEFINITION_RECALL` |
| Firewalls | un pare-feu sans `pattern` déclaré en premier | `SCENARIO_CHOICE` |
| Users | pourquoi `getPassword()` n'est pas sur `UserInterface` | `CONCEPT_DISTINCTION` |
| Users | `getUserIdentifier()` n'est pas la clé primaire | `CONCEPT_DISTINCTION` |
| Roles | `IS_AUTHENTICATED_FULLY` n'est pas un rôle | `CONCEPT_DISTINCTION` |
| Authenticators | `createToken()`, pas `createAuthenticatedToken()` | `API_SIGNATURE` |
| Authenticators | `RememberMeBadge` rend éligible, n'active rien | `BEHAVIOR_DIAGNOSIS` |
| Voters | `supports()` filtre, `voteOnAttribute()` décide | `API_SIGNATURE` |
| Voters | les valeurs entières des trois votes | `API_SIGNATURE` |

## 5. L'indice de longueur

| | Avant | Après |
|---|---:|---:|
| bonne réponse strictement la plus longue | 24/51 = **47,1 %** | 11/51 = **21,6 %** |
| seuil du hasard | 25,0 % | 25,0 % |

**16 éditions sur 16 questions.** Deux éditions prévues sur des questions
`HOLDOUT` n'ont pas été appliquées — le texte visé ne correspondait pas
exactement — et le lot passe sous le seuil sans elles. L'état a été vérifié
après cette interruption : tous les fichiers se parsent, et **aucune clé de
réponse n'a bougé**.

## 6. État mesuré du lot

| | Avant | Après |
|---|---:|---:|
| items | 12 | 12 |
| questions | 41 | **52** |
| dont LEARNING / VALIDATION / HOLDOUT | 25 / 11 / 5 | **36 / 11 / 5** |
| outcomes portant un id `OUT` | 0 | **46** |
| outcomes évalués hors HOLDOUT | 35 | **46** |
| questions portant un `question_archetype` | 0 | **52** |
| archétypes distincts employés | 0 | **7** sur 11 |
| cours hors budget de révision | 0 | 0 |
| indice de longueur | 47,1 % | **21,6 %** |

Niveaux : 1 `MINIMAL`, 9 `STANDARD`, 2 `DEEP` — observation, pas cible.

Périmètre vérifié par script contre `da13988` : **0 question hors lot 10
modifiée**, 12 items de matrice touchés, **tous du lot 10** ; 11 ajoutées,
0 supprimée, **0 clé de réponse modifiée, 0 énoncé préexistant modifié**.

Effet une fois journalisé : **44,2 % (72/163) → 51,5 % (84/163)**, lots raffinés
**6/27 → 7/27**.

## 7. Portes

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — exit 0, 22 règles, **0 bloquant** |
| `vendor/bin/phpunit` | PASS — 245 tests, 13 793 assertions |
| `composer gate-full` | PASS — exit 0 |
| `npm --prefix website run a11y` | PASS — 22 surfaces, 0 violation |
| 11 audits `tools/audit/` | PASS — exit 0 chacun |
| `verify-reschedule.mjs` | PASS — 76 jours, 440 créneaux identiques |
| `aud10` sur le lot 10 | **21,6 %**, sous le seuil de hasard |
| Couverture officielle | 100 % (163/163) — inchangée |

## 8. Limites

- **Huit des douze cours ont été lus intégralement** — ceux portant les outcomes
  sans évaluation. Les quatre autres (Security Core, Authorization, Password
  hashers, Access Control Rules) ne l'ont pas été.
- Les onze questions n'ont pas été relues par un humain.
- Symfony n'est pas installé ici : tout fait provient d'une source ancrée au SHA
  `6f841c0` ou de `symfony-docs` branche 8.0.
