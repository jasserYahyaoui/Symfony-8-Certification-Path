# Audit profond 8.0 : partir de la source, pas du contenu

**Date :** 2026-10-15 · **Demande du propriétaire :** « pas seulement validator mais tout »
· **Source épinglée :** `symfony/symfony` `v8.0.15` = `e8cac3037ed57dfd9fd03edc84186bc0497901e9`

Les audits précédents partaient du corpus et vérifiaient ce qu'il affirme. Celui-ci
part de **ce que Symfony 8.0 a changé** et cherche où le corpus en parle — ou se
tait, ou se trompe. Quatre étapes, chacune reproductible par un script de
`tools/audit/deep80/`.

## A. Inventaire 7.4 → 8.0

`fetch_entries.py` lit `UPGRADE-8.0.md` et la section « 8.0 » des 59 CHANGELOG
de composants, de bundles et de bridges, au tag `v8.0.15` :
**307 entrées** (155 UPGRADE, 152 CHANGELOG), **164 uniques** une fois les
doublons UPGRADE / CHANGELOG fusionnés, dont **135 dans le périmètre**. Le verdict
de chacune est dans [`inventory.json`](inventory.json).

| Verdict | Entrées | Ce que cela veut dire |
|---|---:|---|
| `TAUGHT` | 69 | le corpus enseigne l'état 8.0 — identifiant trouvé avec un marqueur « retiré / 8.0 », ou lu en concordance |
| `TAUGHT_IMPRECISE_FIXED` | 1 | enseigné, mais imprécis ; corrigé ici (`setCode()`, voir B) |
| `GAP` | 5 | un changement examinable que le corpus ne disait pas — comblés depuis, voir plus bas |
| `MINOR_GAP` | 1 | `getRetryDelay()` : le `retryDelay` est enseigné, la method d'interface non |
| `NOT_TAUGHT_NOT_CONTRADICTED` | 9 | absent du corpus, sans contradiction (formats fluent, `getTypes()` de PropertyInfo…) |
| `NOT_EXAM_RELEVANT` | 50 | interne, infrastructure de test ou API hors item (cache warmers `final`, `TestCase`, OIDC…) |
| `OUT_OF_SCOPE` | 29 | composant hors syllabus (Ldap, JsonStreamer, Webhook, Workflow, Intl…) ou exclu par `docs/syllabus/exclusions.yml` (Doctrine, Monolog) |

`NOT_EXAM_RELEVANT` est attribué par une liste de noms relue en entier, pas entrée
par entrée : c'est le verdict le moins fin du tableau.

### Contamination : aucune trouvée

Chaque symbole retiré en 8.0 et présent dans le corpus y est présenté **comme
retiré** (pages « nouveautés 8.0 », flashcards, questions). Les usages, plus
insidieux que les noms, ont été cherchés aussi : `$request->get(`, espaces
`Annotation`, `Application::add()`, `setDefault()` imbriqué, `(string)` sur un
`TranslatableMessage`, `GroupSequence` associatif, et les **63 constraints** qui
refusent un array d'options — `Callback([X::class, 'm'])` a été exécuté : il reste
valide en 8.0. Une seule explication était décalée : la question de compréhension
`QST-y2zcer0shsjy` expliquait un distracteur `$request->get('brochure')` par
« les files ne sont pas des parameters » alors que la method **n'existe plus** ;
corrigée.

Ce verdict porte sur le contenu lisible par ce script : les questions **HOLDOUT**
sont exclues par leur pool et n'ont pas été lues.

### Les lacunes, et comment elles sont comblées

| # | Changement 8.0 | Item | Preuve |
|---|---|---|---|
| G2 | un nœud de configuration ne peut pas être à la fois `isRequired()` et `defaultValue()` | Semantic configuration | lu : `NodeDefinition`, `InvalidDefinitionException`, dans les deux ordres |
| G3 | l'extracteur de constructor de PropertyInfo activé par défaut | Serializer | CHANGELOG FrameworkBundle |
| G4 | `hide_user_not_found` retiré ; `expose_security_errors` (`none` par défaut, `account_status`, `all`) | Security › Configuration | lu : `MainConfiguration`, `AuthenticatorManager::isSensitiveException()` |
| G6 | `TranslatableMessage` n'est plus `Stringable` | Translations | **exécuté** : `{{ m }}` rend `hello` en 7.4.21, lève en 8.0.15 |
| G1 *(mineure)* | `RecoverableExceptionInterface::getRetryDelay()` | Retries and failures | lu : `SendFailedMessageForRetryListener::getWaitingTime()` |

Comblées dans la livraison suivante, chacune prouvée avant d'être écrite :

| # | Check exécuté | Contenu |
|---|---|---|
| G2 | `config.required-node-has-no-default` (Config 8.0.15, `symfony/config` ajouté au banc) | section « Requis ou par défaut, pas les deux », 1 outcome, flashcard, question LEARNING + COMPREHENSION |
| G4 | — (`security-http` absent du banc : lu dans le code et la documentation 8.0) | section « Ce que l'échec révèle », 1 outcome, flashcard, question LEARNING + COMPREHENSION |
| G6 | `translation.translatable-not-stringable` (échoue en 7.4.21) | paragraphe dans « L'object traduisible », flashcard, question LEARNING |
| G3, G1 | — (lus dans le code) | une flashcard chacune ; le cours Serializer n'a plus que 75 mots de marge |

**Erratum (même jour).** La première version de ce rapport comptait une lacune
G5 — la fin de l'auto-mapping d'entité Doctrine et les *mapped route parameters*
`{slug:post}` — et rangeait DoctrineBridge et MonologBridge dans le périmètre.
Les deux sont exclus par `docs/syllabus/exclusions.yml` (`EXC-DOCTRINE`,
`EXC-MONOLOG`, Master Plan §1.5). La règle `SCOPE-001` a refusé la question écrite
pour G5 ; le contenu a été retiré plutôt qu'étiqueté `exclusion-note` pour passer,
et cinq entrées sont reclassées `OUT_OF_SCOPE`. Les constats exécutés restent
vrais — `{slug:post}` pose bien `_route_mapping` — mais ils ne sont pas à
enseigner.

## B. Exécution différentielle 7.4.21 / 8.0.15

Le même `tools/behavior/run.php` (68 faits exécutés) tourne sur deux installations.
**Seuls les faits présentés comme des nouveautés 8.0 diffèrent** :
`validator.options-array-refused`, `validator.url-requires-tld-by-default` et
`console.setcode-closure-typed-and-int`. Les 65 autres sont identiques sur les deux
versions : le corpus ne fait passer aucun comportement ancien pour une nouveauté.

Les sondes complémentaires confirment ce que le corpus dit des retraits
(`Request::get()`, `Application::add()`, `eraseCredentials()`, `AsCommand` `final`,
`voteOnAttribute()` passé de 3 à 4 parameters).

**Imprécision corrigée — `setCode()`.** La page Console disait qu'une closure
« doit être typée ». Exécuté : `setCode()` accepte tout ; c'est `run()` qui échoue —
`LogicException` pour un parameter non typé, `TypeError` si la valeur rendue n'est
pas un `int`. Aucun type de retour déclaré n'est exigé. La page porte désormais le
tableau 7.4 / 8.0, et un fait central + une flashcard en dépendent.

## C. Ancrage sur les tests de Symfony et mutation de la source

Pour le Validator, la plupart des faits ont un test amont (`testNullIsValid`,
`testEmptyStringIsValid`, `CollectionValidatorTestCase`,
`testStopsAtFirstConstraintWithViolations`, `testAddConstraintRequiresClassConstraints`).
**Aucun test amont ne fixe** : la valeur par défaut de `requireTld` (le
`UrlValidatorTest` la passe toujours explicitement ; le docblock 8.0.15 dit même
« defaults to false » quand le code dit `true`), le refus de `Choice(['a'])`, le
`validatedBy()` par défaut, la conversion d'une `UnexpectedValueException` en
violation. Pour ceux-là, notre check est la seule garde.

`mutate.py` casse la ligne de Symfony qui porte chaque comportement, vérifie que
le check **échoue**, puis restaure le fichier au SHA-256 près : **17 mutations,
17 prouvées** (14 à la première livraison, 3 avec les lacunes comblées).

**Défaut trouvé — checks Cache vacants pour la ligne citée.** Les trois faits F2
(`beta = INF`, `beta = 0`, `$save = false`) citaient `CacheTrait.php` mais
n'exécutaient qu'`ArrayAdapter`, qui a **son propre** `get()`. Muter `CacheTrait`
laissait les checks verts. Ils tournent désormais sur `ArrayAdapter` **et**
`FilesystemAdapter` (qui passe par `CacheTrait`), et citent les deux fichiers ;
les deux mutations sont prouvées.

**Une mutation équivalente, documentée.** Trois gardes lèvent une `TypeError`
quand une command ne rend pas un `int` (le `is_int()` de Symfony, puis les types
de retour `: int` de `__invoke()` et de `run()`). Le check lit donc le **message**
de Symfony, seul à distinguer sa garde des deux autres.

## D. Terminologie en contexte

Une règle « mot français → terme anglais » ne connaît pas le sens. Une passe
syntaxique (spaCy `fr_core_news_sm`, le verbe qui gouverne chaque occurrence) et
des concordances ont trouvé, puis fait corriger :

| Mot | Sens confondu | Occurrences corrigées |
|---|---|---:|
| chaîne → `string` | une *chaîne* d'appels, de proxies, de listeners | 5 (après les ~40 de #412) |
| réponse → `Response` | la *réponse* à une question, pas l'object HTTP | 15 |
| filtre → `filter` | le verbe *filtrer* (« le requirement filtre l'URL ») | 11 |
| contrainte → `constraint` | un `requirement` de route (Routing) | 54 + 4 dans la matrice |

Le dernier n'est pas qu'une affaire de style : l'option s'appelle `requirements`,
et un candidat qui a lu « constraint » cherche le mauvais mot sous la pression de
l'examen. Les règles `chaîne` et `filtre` protègent désormais leurs autres sens ;
la règle `chaîne` acceptait une apostrophe que l'outil encode en `\x02` dans les
scalaires YAML entre apostrophes, défaut corrigé.

## Ce qui reste `MISSING`

- **Statistiques d'items** (difficulté, discrimination, distracteurs jamais
  choisis) : les réponses de l'apprenant vivent dans le `localStorage` de son
  navigateur ; aucun export n'existe. `MISSING`, pas « bon ».
- **Relecture externe** par un développeur certifié Symfony : non faite.
- **Syllabus officiel** : toujours inaccessible (blocker B-1).

## Reproduire

```bash
python3 tools/audit/deep80/fetch_entries.py   # inventaire A
python3 tools/audit/deep80/kwic.py 'REGEX'     # concordance, HOLDOUT exclu par pool
php tools/behavior/run.php                     # faits exécutés (8.0)
python3 tools/audit/deep80/mutate.py           # C : 17 mutations, restauration SHA-256
python3 tools/audit/deep80/sense.py            # D : chaîne/string (spaCy requis)
```

L'exécution différentielle demande une seconde installation de `tools/behavior`
avec les contraintes `7.4.*` ; elle n'est pas versionnée.
