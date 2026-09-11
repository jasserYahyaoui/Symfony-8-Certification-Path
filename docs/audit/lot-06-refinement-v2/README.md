# Lot 06 — Templating with Twig, raffiné sous le cadre version 2

Huitième lot raffiné. Quatorze items, cinquante-huit outcomes, quarante-cinq
questions au départ.

## 1. Ce que le cadre a trouvé

**Dix-neuf outcomes sans évaluation** — aucun n'était couvert, même par le
holdout.

| Item | Outcome sans évaluation |
|---|---|
| TwigBundle | déplacer le répertoire par défaut · `lint:twig` |
| Twig syntax | `?:` contre `??` · le contrôle des espaces |
| Auto escaping | associer chaque stratégie à son contexte |
| Template inheritance | la réutilisation horizontale |
| Global variables | le contenu des propriétés de `app` |
| Filters and functions | l'ordre d'un enchaînement · l'origine des extensions Symfony |
| Template includes | la fonction contre la balise |
| Loops and conditions | la clause `else` · les tests `is` / `is not` |
| URLs generation | pourquoi ne jamais écrire une URL à la main |
| Controller rendering | exécuter un contrôleur **qui a** une route |
| Translations | la balise et son domaine par défaut · `%nom%` contre `{nom}` · l'objet traduisible |
| String interpolation | échapper une interpolation |
| Debugging variables | ce que `lint:twig` ne voit pas |

## 2. Le fait marquant : deux sources officielles divergent

Le cours *Twig syntax* énonçait **sept** étapes de résolution de `foo.bar`, dans
l'ordre tableau, propriété, méthode, *getter*, *isser*, *hasser*, puis `null`.

C'est ce que dit la page Symfony 8.0 `templates.rst`. Ce n'est pas ce que fait
le moteur. À la version que le syllabus nomme — *Twig syntax up to 3.22
version* — `CoreExtension::getAttribute()` teste `\defined($object::class.'::'.$item)`
**entre la propriété et les méthodes**, et la documentation Twig v3.22.0 énumère
cette étape. Une classe qui déclare à la fois `const STATUS` et `getStatus()`
verra donc `{{ order.status }}` lire la **constante**, jamais le *getter*.

Vérifié dans le code, pas dans un souvenir : `twigphp/Twig` tag `v3.22.0`,
`src/Extension/CoreExtension.php`, SHA `5079583`.

**Ce qui a été fait.** Le cours énonce maintenant les huit étapes *et* nomme la
divergence, avec ses deux citations, plutôt que de trancher en silence : l'item
porte sur la syntaxe Twig, donc le moteur fait foi, mais un énoncé d'examen
rédigé depuis la page Symfony peut n'en citer que sept, et le candidat doit
savoir lequel il lit.

**Ce qui n'a pas été fait.** Aucune question ne repose sur cette étape. Une
question dont la bonne réponse contredit la documentation officielle de Symfony
échoue le test d'admission du §1.2 : elle ne rend pas plus probable une bonne
réponse le jour de l'examen. Décision `DO_NOT_ADD`, motivée, plutôt qu'un point
marqué contre une source.

L'explication de `QST-2xamk5ahfyyn`, qui récitait l'ordre à sept étapes, a été
corrigée. C'est la **seule** modification apportée à une question préexistante.

## 3. Ce qui a été ajouté

**Dix-neuf questions**, toutes `LEARNING`, en anglais.

| Item | Outcome couvert | Archétype |
|---|---|---|
| TwigBundle | `twig.default_path` déplace, `twig.paths` ajoute | `CONFIG_BEHAVIOR` |
| TwigBundle | `lint:twig` en intégration continue | `SCENARIO_CHOICE` |
| Twig syntax | `?:` retombe sur `0`, `??` non | `CONCEPT_DISTINCTION` |
| Twig syntax | ce que le modificateur `-` consomme | `BEHAVIOR_DIAGNOSIS` |
| Auto escaping | la stratégie `js` dans un littéral JavaScript | `SCENARIO_CHOICE` |
| Template inheritance | importer des blocs sans second parent | `CONCEPT_DISTINCTION` |
| Global variables | `app.user` vaut `null`, pas un objet anonyme | `BEHAVIOR_DIAGNOSIS` |
| Filters and functions | un enchaînement s'applique de gauche à droite | `BEHAVIOR_DIAGNOSIS` |
| Filters and functions | ce qui vient du pont Symfony, pas du moteur | `CONCEPT_DISTINCTION` |
| Template includes | la fonction **retourne**, la balise **affiche** | `CONCEPT_DISTINCTION` |
| Loops and conditions | `else` se déclenche sur zéro itération | `BEHAVIOR_DIAGNOSIS` |
| Loops and conditions | `is` introduit un test nommé | `CONCEPT_DISTINCTION` |
| URLs generation | la route est la source unique de vérité | `SCENARIO_CHOICE` |
| Controller rendering | un contrôleur routé s'adresse par son URL | `SCENARIO_CHOICE` |
| Translations | `trans_default_domain` ne franchit pas un `include` | `BEHAVIOR_DIAGNOSIS` |
| Translations | `{nom}` ICU contre `%nom%` | `CONCEPT_DISTINCTION` |
| Translations | l'objet traduisible porte paramètres et domaine | `CONCEPT_DISTINCTION` |
| String interpolation | l'antislash devant `#{` | `BEHAVIOR_DIAGNOSIS` |
| Debugging variables | le linter analyse sans exécuter | `BEHAVIOR_DIAGNOSIS` |

## 4. L'indice de longueur

| | Avant | Après |
|---|---:|---:|
| bonne réponse strictement la plus longue | 13/45 = **28,9 %** | 13/64 = **20,3 %** |
| seuil du hasard | 25,0 % | 25,0 % |

**Zéro édition.** Le lot passe sous le seuil parce qu'aucune des dix-neuf
questions ajoutées ne place la bonne réponse en tête de longueur, pas parce
qu'un choix existant a été rallongé ou raccourci. Le numérateur est inchangé.

## 5. État mesuré du lot

| | Avant | Après |
|---|---:|---:|
| items | 14 | 14 |
| questions | 45 | **64** |
| dont LEARNING / VALIDATION / HOLDOUT | 29 / 10 / 6 | **48 / 10 / 6** |
| outcomes portant un id `OUT` | 0 | **58** |
| outcomes évalués hors HOLDOUT | 39 (mesuré après coup, aucun id n'existait) | **58** |
| questions portant un `question_archetype` | 0 | **64** |
| archétypes distincts employés | 0 | **7** sur 11 |
| cours hors budget de révision | 0 | 0 |
| indice de longueur | 28,9 % | **20,3 %** |

Niveaux : 4 `MINIMAL`, 9 `STANDARD`, 1 `DEEP` — observation, pas cible.

Périmètre vérifié par script contre `f87846d` : 14 items de matrice touchés,
**tous du lot 06** ; 19 questions ajoutées, 0 supprimée, **0 clé de réponse
modifiée, 0 énoncé modifié**, une explication corrigée — celle du §2.

## 6. Ce qui n'a pas reçu d'id d'outcome

Les **six questions HOLDOUT** du lot portent un `question_archetype` mais
**aucun** `assesses_outcomes`. Une question du holdout ne libère de toute façon
jamais un outcome — `PED-003` le refuse explicitement — et lier celles-ci aurait
demandé de deviner l'outcome visé à partir de métadonnées. Un lien faux est une
affirmation fausse ; l'absence de lien n'en est pas une.

## 7. Portes

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — exit 0, 22 règles, **0 bloquant** |
| idem, lot 06 marqué raffiné (sonde) | PASS après annotation des 6 HOLDOUT — 0 bloquant |
| `vendor/bin/phpunit` | PASS — 245 tests, 13 833 assertions |
| `composer gate-full` | PASS — exit 0 |
| `npm --prefix website run a11y` | PASS — 22 surfaces, 0 violation |
| 11 audits `tools/audit/` | PASS — exit 0 chacun |
| `prove_framework_rules_fail.py` | PASS — 7 cas, restauration SHA-256 vérifiée |
| `aud10 --prove` | PASS — exit 0 |
| `verify-reschedule.mjs` | PASS — 76 jours, 440 créneaux identiques |
| `aud10` sur le lot 06 | **20,3 %**, sous le seuil de hasard |
| Couverture officielle | 100 % (163/163) — inchangée |

## 8. Limites

- **Un seul cours a été lu intégralement**, celui de *Twig syntax*, parce que
  c'est lui qui portait l'erreur du §2. Les treize autres n'ont été consultés que
  par recherche ciblée, et leur longueur a été mesurée contre le budget `REV-001`.
  Les dix-neuf questions ont donc été écrites depuis les sources amont, pas depuis
  une lecture exhaustive du cours correspondant : une contradiction cours/question
  y resterait invisible sauf si `CRS-001` la voit.
- Les dix-neuf questions n'ont pas été relues par un humain.
- Symfony et Twig ne sont pas installés ici : tout fait provient de
  `symfony-docs` branche 8.0, de `twigphp/Twig` tag `v3.22.0` ou du code de ce
  tag. Aucun n'a été exécuté.
- La divergence du §2 est **documentée, non arbitrée** : elle mérite une
  décision humaine si le candidat veut qu'une question porte dessus.
