# Raffinement pédagogique — Lot 03 (Architecture)

Journal de reprise, ouvert le 2026-09-17 après la clôture du lot 02. Même
méthode : relire la source avant d'écrire, faire porter le volume par les
flashcards là où `REV-001` plafonne le corps, et ajouter au smoke test de
production une aiguille discriminante par page.

## Ce qui change par rapport au lot 02

Le lot 02 était serré : six pages sur dix à moins de 100 mots de leur plafond,
`Cookies` à 3 mots. Le lot 03 ne l'est pas — les marges vont de **330 à
554 mots**. Les sections que le brief demande tiennent donc sans rien retirer,
et le corps du cours redevient un lieu d'écriture plutôt qu'un budget à défendre.

En contrepartie, c'est **15 pages au lieu de 10**, et plusieurs portent sur des
sujets dont la source est un texte de politique plutôt que du code — *Backward
compatibility promise*, *Release management*, *Official best practices*. La
vérification y est une lecture attentive, pas un `grep`.

## État par page (ordre de navigation)

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | HttpFoundation component | MINIMAL | 694 / 700 | 15 | **RAFFINÉE** (2026-09-17) |
| 2 | Symfony Flex | STANDARD | 802 / 900 | 15 | **RAFFINÉE** (2026-09-17) |
| 3 | License | MINIMAL | 542 / 700 | 10 | **RAFFINÉE** (2026-09-17) |
| 4 | Components and Bridges | STANDARD | 466 / 900 | 1 | à faire |
| 5 | Code organization | STANDARD | 543 / 900 | 1 | à faire |
| 6 | Request handling | DEEP | 646 / 1200 | 2 | à faire |
| 7 | Exception handling | STANDARD | 532 / 900 | 1 | à faire |
| 8 | Event dispatcher and kernel events | DEEP | 568 / 1200 | 1 | à faire |
| 9 | Official best practices | STANDARD | 517 / 900 | 1 | à faire |
| 10 | Backward compatibility promise | STANDARD | 587 / 900 | 2 | à faire |
| 11 | Deprecations best practices | STANDARD | 514 / 900 | 1 | à faire |
| 12 | Framework overloading | STANDARD | 636 / 900 | 1 | à faire |
| 13 | Release management and roadmap schedule | STANDARD | 596 / 900 | 2 | à faire |
| 14 | Framework interoperability and PSRs | STANDARD | 419 / 900 | 1 | à faire |
| 15 | Naming conventions | MINIMAL | 339 / 700 | 1 | à faire |

Chiffres relevés le 2026-09-17 par lecture du `ContentSet` chargé, pas repris
d'un rapport.

## Page 1 — HttpFoundation component, 2026-09-17

**Fait**

- 14 flashcards ajoutées (`FLC-97zy6sabcx93` … `FLC-y9tr0v3tmzyw`) ; la carte
  préexistante `FLC-p419ytejyj1a`, qui porte sur le sens de la dépendance, a
  reçu le niveau `UNDERSTANDING` et n'a pas été redoublée.
- `RequestStack.php` de la branche 8.0 relu le jour même. Trois faits que la
  page énonçait sans les fonder, et qui deviennent vérifiables :
  `getCurrentRequest()` est `end($requests)`, `getMainRequest()` est
  `$requests[0]`, `getParentRequest()` vise l'index `count - 2` — donc `null`
  sur la requête principale, ce qui est le cas **courant**.
- Deux faits que la page ne portait pas du tout : `getSession()` **lève** une
  `SessionNotFoundException` là où les trois autres accesseurs rendent `null`,
  et `getMainRequest()` comme `getParentRequest()` portent dans leur docblock
  l'avertissement « might make it un-compatible with other features of your
  framework like ESI support ».
- Une subtilité ajoutée parce qu'elle explique une classe entière de bugs :
  avec **une** sous-requête, `getMainRequest()` et `getParentRequest()` rendent
  la même valeur ; elles ne divergent qu'à partir de deux niveaux. Un code écrit
  et testé sur un seul niveau peut donc être faux sans que rien ne le montre.
- Sections ajoutées : *Les trois accesseurs, exactement*, *L'avertissement que
  portent deux de ces méthodes*, *`getSession()` ne rend pas `null`*, *Tips
  d'examen*. Corps : 370 → **694 mots** sur 700.

**Contrôles réellement exécutés le 2026-09-17**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** (à 712 puis 701 mots `REV-001` a bloqué ; réduit à 694) |
| `composer gate-full` | **exit 0** — 295 tests, 15 786 assertions ; `TOTAL VIOLATIONS: 0` |
| Aiguilles de smoke test | 4 ajoutées, vérifiées présentes dans la page construite et absentes de `master` |

## Page 2 — Symfony Flex, 2026-09-17

**Fait**

- 14 flashcards ajoutées (`FLC-g50ga9cn60xf` … `FLC-pf6cfqws97ac`) ; la carte
  préexistante `FLC-7gcgff6wmgq8` a reçu le niveau `RECALL`.
- **Une imprécision corrigée.** La page opposait les deux dépôts de recettes par
  la qualité : « le dépôt principal est revu, le dépôt contrib ne l'est pas ».
  `setup.rst` dit l'inverse des recettes contrib — « All of them are guaranteed
  to work » — et place le doute sur le **paquet** associé, qui « could be
  unmaintained ». Les deux dépôts sont alimentés par la communauté ; le principal
  est une liste *curée*, et c'est le seul que Flex consulte sans demander.
- Deux sections ajoutées, toutes deux tirées d'exemples de la documentation :
  *Ce qu'une recette fait, concrètement* — les trois fichiers exacts que la
  recette Twig dépose, dont une configuration **de test** distincte — et
  *Les packs, et pourquoi ils disparaissent*, où `composer.json` ne montre jamais
  `symfony/debug-pack` puisque Flex le dépaquette.
- Un enchaînement que la page ne montrait pas : `composer require api` résout un
  **alias** vers un **pack** tiers, dont l'installation déclenche **cinq
  recettes**. Trois mécanismes derrière une commande d'un mot.
- Section `## Tips d'examen` ajoutée. Corps : 478 → **802 mots** sur 900.

**Contrôles réellement exécutés le 2026-09-17**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `composer gate-full` | **exit 0** — 295 tests, 15 800 assertions ; `TOTAL VIOLATIONS: 0` |
| Aiguilles de smoke test | 5 ajoutées, vérifiées présentes dans la page construite et absentes de `master` |

## Page 3 — License, 2026-09-17

**Fait**

- 9 flashcards ajoutées (`FLC-q56067d1yze3` … `FLC-v7t03wr8rvym`) ; la carte
  préexistante `FLC-pyh6wnkg7dc5` a reçu le niveau `TRAP`.
- Le fichier `LICENSE` de `symfony/symfony` relu ligne à ligne. Ce que la page
  n'énonçait pas : les **huit verbes** de la concession — dont `sublicense`, qui
  porte la différence juridique avec le copyleft ; le fait que les deux clauses
  finales couvrent **deux** risques distincts, l'absence de **garantie** et
  l'absence de **responsabilité** ; et que le titulaire est une **personne
  physique**, « Copyright (c) 2004-present Fabien Potencier ».
- Une comparaison MIT / copyleft en quatre lignes, parce que c'est exactement la
  confusion qu'un QCM teste.
- Corps : 248 → **542 mots** sur 700.

**Ce que je n'ai pas fait, et pourquoi**

La marge autorisait 700 mots ; la page s'arrête à 542. L'item porte **deux**
objectifs d'apprentissage et un périmètre étroit — la licence de Symfony et son
unique obligation. Ajouter une théorie générale des licences libres aurait
rempli la page sans améliorer la probabilité de répondre juste, ce que le
§1.4 interdit. Une marge disponible n'est pas une marge à consommer.

**Contrôles réellement exécutés le 2026-09-17**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `composer gate-full` | **exit 0** — 295 tests, 15 809 assertions ; `TOTAL VIOLATIONS: 0` |
| Aiguilles de smoke test | 4 ajoutées ; `substantial portions` **écartée** — déjà présente une fois sur `master` |

## Page 4 — Components and Bridges, 2026-09-18

**Fait**

- 15 flashcards ajoutées (`FLC-tysgghm0xyx0` … `FLC-6m7k069wqxwq`) ; la carte
  préexistante `FLC-396ywyjsv9pb` a reçu le niveau `UNDERSTANDING`. L'item en
  porte **16**, quatre par niveau.
- Corps : 466 → **836 mots** sur 900.

**Une affirmation retirée : « cinq bridges »**

La page annonçait cinq bridges. Aucune source ne l'établit. Ce que les sources
établissent, et qui est désormais écrit :

- la clé `replace` du `composer.json` du mono-dépôt liste exactement **trois**
  bridges publiés comme paquets — `doctrine-bridge`, `monolog-bridge`,
  `twig-bridge` ;
- un quatrième répertoire, `src/Symfony/Bridge/PhpUnit/`, existe et est
  **absent** de `replace`.

L'énumération exhaustive des répertoires de `src/Symfony/Bridge/` n'a pas pu
être faite : `api.github.com` est bloqué par le proxy et l'appel MCP
`get_file_contents` sur `symfony/symfony` a été refusé. Des sondages d'URL
brutes ont renvoyé 200 pour `Doctrine`, `Monolog`, `Twig` et `PhpUnit`, et 404
pour `ProxyManager`, `Propel1` et `Swiftmailer` — ce qui ne prouve pas une
liste complète. La page n'affirme donc **pas** de nombre total de répertoires.

**Une section ajoutée : le bridge PHPUnit dément la définition**

`symfony/phpunit-bridge` porte le type `symfony-bridge` et vit dans
`src/Symfony/Bridge/`, mais son `require` ne contient que `php >=8.1.0` —
aucun composant Symfony, aucune bibliothèque tierce. Il n'a pas les « deux
côtés » que la définition générale suppose. Comparé au bridge Twig
(`php >=8.4`, `symfony/translation-contracts ^2.5|^3`, `twig/twig ^3.21|^4.0`),
l'écart est frappant, et la contrainte PHP plus basse s'explique : le bridge
sert à tester, il doit tourner sur des PHP plus anciens.

**Une section ajoutée : `replace` et `provide`**

Le `composer.json` du mono-dépôt porte deux clés voisines que la page
ignorait. `replace` compte **65** entrées et ne contient pas que des
composants — cinq bundles y figurent. `provide` ne contient **aucun** paquet
Symfony : quinze noms terminés par `-implementation`, qui déclarent les
interfaces implémentées. Le `composer.json` était déjà cité en front matter
pour ses deux clés ; seule `replace` était exploitée.

**Un doublon corrigé avant commit**

`AUD-04` a levé `VOL-4` sur `FLC-077chfqb2wvg` et `FLC-96yjsk63bse0` : une fois
les segments de code normalisés, les deux fronts se réduisaient à « que déclare
la clé du de ». Le front de la première a été reformulé pour que le mot
distinctif tombe hors segment de code. Un premier doublon avait déjà été écarté
plus tôt : la carte que j'avais écrite sur le critère bridge/bundle reprenait
`FLC-396ywyjsv9pb`, elle a été remplacée par la carte `provide`.

**Contrôles réellement exécutés le 2026-09-18**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `php bin/cert coverage` | aucun écart — rapport inchangé |
| `composer gate-full` | **exit 0** — 295 tests, 15 824 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour tous |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` — restauration SHA-256 |
| `aud10_answer_length_bias.py --prove` | exit 0, `FINDINGS: 0` |
| Aiguilles de smoke test | 7 ajoutées ; « cinq bundles y figurent » **écartée** — coupée par le retour à la ligne, elle ne survit pas comme chaîne unique dans la page rendue |

## Page 5 — Code organization, 2026-09-18

**Fait**

- 15 flashcards ajoutées (`FLC-srxd7601p8cb` … `FLC-a2gc9fn1n43v`) ; la carte
  préexistante `FLC-82hpxh6bv6y3` a reçu le niveau `UNDERSTANDING`. L'item en
  porte **16**.
- Corps : 543 → **848 mots** sur 900.

**Une erreur corrigée dans une carte existante**

`FLC-82hpxh6bv6y3` affirmait que « `bin/`, `config/`, `src/`, `public/`,
`templates/`, `translations/` et `vendor/` se déplacent par `extra` ». C'est
faux, et la page de cours disait déjà le contraire : `templates/` et
`translations/` se déplacent par configuration de bundle,
`vendor/` par la clé `config` de Composer. L'explication a été réécrite.

**Une affirmation corrigée dans le cours : « la liste est close »**

La page affirmait que `extra` ne porte que quatre clés et que « la liste est
close ». `configuration/override_dir_structure.rst` en documente une
cinquième, imbriquée : `extra.runtime.dotenv_path`, qui déplace le fichier
`.env`. Le piège a été réécrit pour énoncer ce que `extra` ne déplace **pas**,
plutôt qu'une liste close qui ne l'est pas.

**Deux sections ajoutées, tirées de la source et non de la documentation**

`Kernel.php` et `MicroKernelTrait.php` relus sur la branche 8.0. Ce que la
documentation ne dit pas, ou dit autrement :

- `Kernel` expose **quatre** accesseurs, pas deux : `getCacheDir()`,
  `getBuildDir()`, `getShareDir()`, `getLogDir()`. Les deux du milieu délèguent
  à `getCacheDir()` par compatibilité ascendante.
- Les défauts sont **asymétriques** : `var/cache/<environnement>` d'un côté,
  `var/log` **sans** environnement de l'autre.
- `getShareDir()` est le seul de type `?string` ; quand il rend `null`, le
  paramètre `%kernel.share_dir%` n'est pas enregistré.
- Les `APP_*_DIR` sont lues par **`MicroKernelTrait`**, pas par `Kernel` — un
  noyau sans le trait les ignore. Il y en a **quatre**, `APP_BUILD_DIR`
  comprise, que la page de documentation ne mentionne pas.
- `APP_CACHE_DIR`, `APP_BUILD_DIR` et `APP_SHARE_DIR` passent par
  `getEnvDir()`, qui **ajoute l'environnement** au chemin et résout un chemin
  relatif depuis la racine du projet. `APP_LOG_DIR` est prise telle quelle. La
  documentation les décrit comme « le chemin complet du dossier » ; sur ce
  point la source prime, et c'est elle qui est écrite.

**Un doublon attrapé par l'audit**

`AUD-04` a levé `VOL-4` sur `FLC-vpyxr6rxb8j5` et `FLC-1v32rnyqb3bf` : les deux
fronts se réduisaient à « quelle est la valeur par défaut de ». Le second a été
reformulé.

**Contrôles réellement exécutés le 2026-09-18**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `php bin/cert coverage` | aucun écart — rapport inchangé |
| `composer gate-full` | **exit 0** — 295 tests, 15 845 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour tous |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` — restauration SHA-256 |
| `aud10_answer_length_bias.py --prove` | exit 0, `FINDINGS: 0` |
| Aiguilles de smoke test | 8 ajoutées ; « sans l'environnement » **écartée** — l'apostrophe est échappée au rendu, la chaîne littérale n'apparaît pas dans les octets servis |

### Ce que la page 5 a fait tomber : une divergence latente du planificateur

La CI de la PR #172 a échoué sur une étape que `composer gate-full` **ne couvre
pas** — `node website/tools/verify-reschedule.mjs`, qui compare le
planificateur du navigateur à `build_roadmap.py`. Message :

```text
2026-10-08 slot 6 title: "Nouveau — Code organization" vs "Nouveau (suite) — Code organization"
```

`a` est `plan.json` (Python), `b` est le port TypeScript. La cause est une
divergence d'une ligne dans le cas où un item **déborde** de son créneau :

| | branche « morceau partiel » |
|---|---|
| `build_roadmap.py` | `D['new'].append((it, free, left == it['total']))` |
| `reschedule.ts` | `D.neu.push({id, minutes: free, full: false})` |

`full` marque l'**entrée** d'un item, pas le fait de le finir : c'est ce
drapeau que compte `maxNew`, et c'est lui qui écrit « Nouveau » plutôt que
« Nouveau (suite) ». Un premier morceau partiel est donc bien une entrée, ce
que `left === item.total` exprime. Le port le figeait à `false`, ce qui
mislabellisait le premier morceau **et** sous-comptait `maxNew` côté
navigateur.

Le port a été corrigé, pas le contrôle. La divergence était **latente** :
elle n'apparaît que lorsqu'un item déborde de son premier créneau, ce que
l'agrandissement de la page 5 (543 → 848 mots) a provoqué pour la première
fois. Le contrôle vient donc de prouver qu'il n'est pas vide.

Leçon opérationnelle, à ajouter à celle de `CLAUDE.md` sur les audits :
`gate-full` ne lance pas non plus `verify-reschedule.mjs`. Il faut le lancer
quand un changement de contenu modifie la durée estimée d'un item.

## Page 6 — Request handling, 2026-09-18

**Fait**

- 18 flashcards ajoutées (`FLC-wtj0r3jza7sb` … `FLC-rfbc1zdyg3xr`) ; les deux
  cartes préexistantes ont reçu un niveau. L'item en porte **20**.
- Corps : 646 → **1181 mots** sur 1200.

**Le trou le plus large : la page ne mentionnait nulle part `kernel.exception`**

Une page sur le traitement d'une requête qui ne décrit que le chemin nominal
laisse de côté la moitié du comportement observable. `HttpKernel.php` relu ligne
à ligne, trois sections ajoutées.

**« La pile de requêtes, poussée et dépilée par `handle()` »** — `handle()`
empile la requête à l'entrée et la dépile dans un `finally`, donc quoi qu'il
arrive. C'est tout le mécanisme derrière `getCurrentRequest()` et
`getParentRequest()`. Une `StreamedResponse` voit sa fonction de rendu
**réenveloppée** pour réempiler la requête le temps du flux : sans cela,
`getCurrentRequest()` rendrait `null` à l'intérieur du `callback`, qui s'exécute
après la sortie de `handle()`.

**« La branche que le trajet nominal ne montre pas »** — `$catch` décide si
`kernel.exception` est dispatché. Le fait à retenir, et qui n'était écrit nulle
part :

| Chemin | `kernel.exception` | `kernel.response` | `kernel.finish_request` |
|---|---|---|---|
| Succès | non | **oui** | **oui** |
| Exception, `$catch = true`, un écouteur pose une réponse | **oui** | **oui** | **oui** |
| Exception, `$catch = true`, aucune réponse posée | **oui** | non | **oui** |
| Exception, `$catch = false` | **non** | non | **oui** |

`kernel.finish_request` a donc lieu sur **tous** les chemins. C'est la seule
garantie de ce type du cycle, et c'est ce qui rend l'événement fiable pour
restaurer un état global.

**« Trois façons d'échouer, trois exceptions »** — `NotFoundHttpException` quand
le résolveur ne trouve aucun contrôleur, `ControllerDoesNotReturnResponseException`
quand `kernel.view` n'a rien donné (l'événement **a eu lieu** ; il n'a rien
produit — la distinction est la question), et `BadRequestHttpException` pour une
requête malformée.

**Deux précisions tirées de la source**

- `MAIN_REQUEST` vaut **1** et `SUB_REQUEST` **2**.
- `kernel.controller_arguments` relit le contrôleur **et** les arguments après
  le dispatch : l'étape 5 n'est donc pas la dernière occasion de changer de
  contrôleur.

**Contrôles réellement exécutés le 2026-09-18**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `php bin/cert coverage` | aucun écart |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 437 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 15 863 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour tous |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | exit 0, `FINDINGS: 0` |
| Aiguilles de smoke test | 7 ajoutées ; « Trois façons » et « StreamedResponse » **écartées** — déjà présentes sur `master`, 3 et 17 fois |

## Page 7 — Exception handling, 2026-09-21

**Fait**

- 15 flashcards ajoutées (`FLC-cmjnpw30mh3f` … `FLC-ncttmnm3hg2w`) ; la carte
  préexistante `FLC-79nmwswd5a50` a reçu le niveau `RECALL`. L'item en porte
  **16**.
- Corps : 532 → **882 mots** sur 900.

**Une règle du cours était incomplète**

La page énonçait trois règles pour le statut, dont « sinon, 500 ». C'est vrai du
noyau **seul**, et faux d'une application Symfony : `ErrorListener::logKernelException`
s'exécute à la priorité **0**, donc **avant** que la règle du statut ne
s'applique, et peut remplacer l'exception par une `HttpException` — via
l'attribut `#[WithHttpStatus(…)]` ou via la table `exceptions` de la
configuration du framework, qui associe une classe à un `status_code`, un
`log_level` et un `log_channel`. Une `\InvalidArgumentException` annotée
`#[WithHttpStatus(422)]` sort donc en **422**.

La page dit désormais « sans intervention, elle donne 500 » et consacre une
sous-section aux deux façons de faire entrer une exception dans l'interface.

**`ErrorListener` s'abonne deux fois au même événement**

| Événement | Méthode | Priorité |
|---|---|---|
| `kernel.exception` | `logKernelException` | **0** |
| `kernel.exception` | `onKernelException` | **−128** |
| `kernel.controller_arguments` | `onControllerArguments` | — |
| `kernel.response` | `removeCspHeader` | **−128** |

Quatre hooks, pas un. Et l'écart de priorité explique un comportement que la
page présentait sans cause : `setResponse()` devance la **construction de la
page d'erreur**, pas la **journalisation**, qui a déjà eu lieu.

**La page d'erreur est une vraie sous-requête**

`onKernelException` duplique la requête et rappelle le noyau :
`handle($request, SUB_REQUEST, false)`. Ce `false` est `$catch` — une exception
levée *dans* le contrôleur d'erreur n'est **pas** rattrapée une seconde fois ;
elle remonte, l'originale chaînée en `previous`. Et en production, une exception
survenue pendant `kernel.terminate` ne produit aucune page d'erreur :
`onKernelException` renonce.

**Une contradiction introduite puis corrigée**

Après un premier jet, la page affirmait à la fois « elle donne donc 500 » et
« elle sort en 422 ». Le corps dépassait aussi le budget `REV-001` — 1000 mots
pour 900. Les deux ont été réglés par resserrement de la prose et
réorganisation : la sous-section 422 a été déplacée **après**
`HttpExceptionInterface`, qu'elle présuppose. Le niveau n'a pas été promu et
aucun contenu vérifié n'a été supprimé.

**Contrôles réellement exécutés le 2026-09-21**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `php bin/cert coverage` | aucun écart |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 437 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 15 878 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour tous |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | exit 0, `FINDINGS: 0` |
| Aiguilles de smoke test | 8 ajoutées ; « deux » **écartée** — 440 occurrences sur `master` |

## Page 8 — Event dispatcher and kernel events, 2026-09-21

**Fait**

- 18 flashcards ajoutées (`FLC-enc60pxerqbp` … `FLC-pk83wg9jz0br`) ; la carte
  préexistante `FLC-4hjvn6s53v7y` a reçu le niveau `RECALL`. L'item en porte
  **19**.
- Corps : 568 → **1038 mots** sur 1200.

**Une affirmation non qualifiée dans le cours**

La page écrivait « un écouteur peut appeler `$event->stopPropagation()` », sans
condition. `EventDispatcher::callListeners()` ne consulte
`isPropagationStopped()` **que si** l'événement implémente
`StoppableEventInterface` (PSR-14). Un objet quelconque dispatché sans elle voit
**tous** ses écouteurs appelés, quoi qu'ils fassent.

En pratique `Symfony\Contracts\EventDispatcher\Event` l'implémente et
`KernelEvent` l'étend — les huit événements du noyau sont donc arrêtables. C'est
l'événement maison bâti sur un simple objet qui ne l'est pas, et c'est
exactement le cas où l'on se demande pourquoi l'arrêt ne fonctionne pas.

**Trois sections ajoutées, tirées de `EventDispatcher.php`**

*« Ce que fait `dispatch()`, exactement. »* La signature dit trois choses que la
page passait sous silence :

- le nom est facultatif et **son défaut est le nom de la classe**
  (`$eventName ??= $event::class`) — dispatcher un `OrderPlaced` sans second
  argument l'enregistre sous son FQCN, et un écouteur branché sur
  `'order.placed'` ne sera jamais appelé ;
- **l'événement est retourné**, d'où l'idiome `$event = $dispatcher->dispatch(…)` ;
- **chaque écouteur reçoit trois arguments** — `$listener($event, $eventName, $this)`.

*« `KernelEvents::ALIASES`. »* La classe ne porte pas que huit constantes : elle
porte une table associant **chaque classe d'événement à son nom**, lue par
`RegisterListenersPass`. C'est elle qui permet `#[AsEventListener]` **sans**
paramètre `event` — le nom est déduit du type de l'argument, et retirer le type
rend l'attribut inopérant.

*« Le reste de l'API. »* `removeListener()`, `removeSubscriber()`,
`hasListeners()`, `getListenerPriority()`. Et l'ordre est établi par un
`krsort()` — tri par clé décroissante, ce qui est la formulation la plus directe
de « plus le nombre est grand, plus tôt ».

**Une vérification qui a changé la méthode des aiguilles**

Jusqu'ici je comparais une aiguille candidate à **tout** `content/` sur
`master`. C'est le mauvais dénominateur : le smoke test interroge **une** page.
Une chaîne présente ailleurs sur le site mais absente de cette page-ci est
parfaitement discriminante. `ALIASES` apparaissait 3 fois dans `content/` sur
`master` et **0 fois** dans la version `master` de cette page et du fichier de
flashcards du lot — elle est donc retenue. Les quatre aiguilles ont été
vérifiées de cette façon.

**Contrôles réellement exécutés le 2026-09-21**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `php bin/cert coverage` | aucun écart |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 443 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 15 932 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour tous |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | exit 0, `FINDINGS: 0` |
| Aiguilles de smoke test | 7 ajoutées, chacune vérifiée absente de la version `master` **de cette page** |

## Page 9 — Official best practices, 2026-09-21

**Fait**

- 16 flashcards ajoutées (`FLC-knxr0c9z2t9r` … `FLC-45sf7844wgck`) ; la carte
  préexistante `FLC-g0kpwp6w0xg6` a reçu le niveau `TRAP`. L'item en porte
  **17**.
- Corps : 517 → **869 mots** sur 900.

**« Une trentaine » est devenu « exactement trente »**

`best_practices.rst` relu section par section, titres de niveau 3 comptés un à
un : **30** recommandations, **10** sections. La page disait « une trentaine…
dix sections », ce qui était juste mais invérifiable pour le lecteur. Le nombre
exact est maintenant écrit, et un tableau donne **les trente**, groupées par
section.

**Ce que la page couvrait, et ce qu'elle laissait de côté**

Elle traitait environ huit recommandations sur trente. Manquaient notamment
`AbstractController`, l'autowiring, les attributs pour le mapping Doctrine et
pour le routage, l'injection de dépendances plutôt que le conteneur,
`EntityValueResolver`, les formulaires en classes PHP, les contraintes sur
l'objet sous-jacent, le hacheur `auto`, les voteurs, AssetMapper et le *smoke
test* des URL.

**Quatre points développés, avec leur raison**

- **Les boutons d'un formulaire vont dans le gabarit**, ni dans la classe ni
  dans le contrôleur : la classe doit rester agnostique quant à l'endroit où
  elle sert — le même formulaire affiche « Ajouter » à la création et
  « Enregistrer » à l'édition.
- **`EntityValueResolver` est facultatif.** Son titre même porte la condition :
  « si c'est commode ». Dès que la logique se complique, la recommandation est
  de faire la requête dans le contrôleur.
- **`auto` n'est pas un algorithme** mais un sélecteur ; son défaut actuel est
  `bcrypt`. Répondre « bcrypt » à « quel hacheur recommander ? » est inexact.
- **Deux recommandations distinctes portent sur les URL en test** : le *smoke
  test* qui les parcourt toutes via un fournisseur de données PHPUnit, et l'URL
  **en dur** dans le test fonctionnel. La page les confondait implicitement.

**Un dépassement de budget, réglé par déduplication**

Le premier jet faisait 914 mots pour 900. La liste « les recommandations qui
surprennent » répétait cinq entrées que le nouveau tableau énonce déjà. Elles en
ont été retirées ; ne restent dans la liste que celles qui portent une
**raison** que le tableau ne peut pas contenir. Le niveau n'a pas été promu.

**Contrôles réellement exécutés le 2026-09-21**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `php bin/cert coverage` | aucun écart |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 443 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 15 948 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour tous |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | exit 0, `FINDINGS: 0` |
| Aiguilles de smoke test | 8, chacune vérifiée absente de la version `master` **de cette page** et du fichier de flashcards du lot |

## Page 10 — Backward compatibility promise, 2026-09-21

**Fait**

- 16 flashcards ajoutées (`FLC-xhbtz865fgsr` … `FLC-h0fydgt8ycjv`) ; les deux
  cartes préexistantes ont reçu le niveau `TRAP`. L'item en porte **18**.
- Corps : 587 → **890 mots** sur 900.

**Une section entière du document était absente : les traits**

`bc.rst` leur consacre une section et un tableau propre, qui ne répond **jamais
non** — propriétés et méthodes **privées** comprises. Le contraste avec les
classes est net et structurel :

| | Privé garanti ? |
|---|---|
| Membre privé d'une **classe** Symfony étendue | **non** |
| Membre privé d'un **trait** Symfony utilisé | **oui** |

Un trait importé fait partie de *votre* classe ; la classe étendue reste celle
de Symfony. Seule exception, le trait marqué `@internal`.

**Le procédé d'ajout d'argument, qui explique un motif du framework**

Un argument ne s'ajoute à une méthode publique **que s'il est le dernier**. En
version mineure, il reste **en commentaire** dans la signature, documenté en
PHPDoc, et lu par `func_num_args()` / `func_get_arg()`.

Détail à ne pas manquer : le défaut retenu dans le corps est celui qui
**préserve** le comportement actuel — le document montre
`/* bool $stripWhitespace = true */` dans la signature et `false` comme repli.
Le commentaire décrit la signature **future**, pas le comportement présent.

**L'expérimental, chiffré**

La page mentionnait « les fonctionnalités expérimentales » sans plus. Le
document est précis : `@experimental`, **une seule version mineure**
(prolongeable d'**une** au cas par cas), **jamais** en LTS, et le `CHANGELOG`
doit expliquer chaque rupture tant que le statut dure.

**Un dépassement de budget, réglé par resserrement**

Premier jet à 1016 mots pour 900. Réglé en deux passes, sans rien supprimer de
vérifié : le tableau des traits est devenu une phrase, et quatre paragraphes
redondants avec les pièges ont été condensés. Le niveau n'a pas été promu.

**Contrôles réellement exécutés le 2026-09-21**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `php bin/cert coverage` | aucun écart |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 443 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 15 964 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour tous |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | exit 0, `FINDINGS: 0` |
| Aiguilles de smoke test | 8 ; `LTS` **écartée** — déjà présente dans le fichier de flashcards du lot sur `master`, sur les cartes d'autres items |

## Page 11 — Deprecations best practices, 2026-09-21

**Fait**

- 16 flashcards ajoutées (`FLC-wwvyvhv5zbma` … `FLC-vb8q7pbvtk3e`) ; la carte
  préexistante `FLC-9m8p1pfmbs1b` a reçu le niveau `RECALL`. L'item en porte
  **17**.
- Corps : 514 → **838 mots** sur 900, **sans dépassement** cette fois.

**Une section entière de `conventions.rst` manquait : les règles du CHANGELOG**

La page nommait le fichier sans dire comment on l'écrit. Le document est
normatif :

- titre principal **toujours** `CHANGELOG` ;
- entrées sous une section de **version mineure**, comme éléments de liste ;
- **aucune section de troisième niveau** ;
- message court, **capitalisé**, **sans point final**, verbe à l'impératif ;
- nouvelles entrées **en haut**.

Et une note que la page ignorait : les `CHANGELOG-*` à la racine de
`symfony/symfony` sont **générés** à la préparation des versions et ne doivent
jamais être modifiés à la main. Celui qu'on édite est celui **du composant**.

**La trace écrite compte quatre moments, pas trois**

La page énonçait trois fichiers à documenter au moment de la dépréciation. Il
en manque un quatrième, décalé dans le temps : quand la suppression arrive, deux
ans plus tard, ses **conséquences** doivent être ajoutées au `CHANGELOG.md` du
composant, dans la même pull request que la suppression. Le document qualifie la
tâche d'obligatoire.

**Contrôles réellement exécutés le 2026-09-21**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `php bin/cert coverage` | aucun écart |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 443 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 15 980 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour tous |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | exit 0, `FINDINGS: 0` |
| Aiguilles de smoke test | 8, chacune vérifiée absente de la version `master` de cette page et du fichier de flashcards du lot |

**Note pour la page 15**

`conventions.rst` porte aussi une section « Naming a Method » — les douze verbes
normalisés (`get`, `set`, `has`, `all`, `replace`, `remove`, `clear`,
`isEmpty`, `add`, `register`, `count`, `keys`), leur variante `XXX` quand il n'y
a pas de relation principale, et la différence entre `setXXX()` et
`replaceXXX()` (le second **ne peut pas ajouter** et doit lever une exception
sur une clé inconnue). C'est le matériau de la page **Naming conventions**, pas
de celle-ci.

## Page 12 — Framework overloading, 2026-09-21

**Fait**

- 16 flashcards ajoutées (`FLC-bg28kgf029js` … `FLC-06jf9jywescz`) ; la carte
  préexistante `FLC-y51trsyr41em` a reçu le niveau `TRAP`. L'item en porte
  **17**.
- Corps : 636 → **887 mots** sur 900.

**Une condition que la page sautait : le contrôleur a deux réponses**

La section « Controllers » du document **ouvre** par une condition :
« si le contrôleur est un service, voir la section suivante » — c'est-à-dire la
**décoration**. La route de même chemin n'est que la branche **sinon**.

La page ne donnait que la seconde. Or dans une application moderne, les
contrôleurs sont des services autoconfigurés : c'est la **première** branche qui
s'applique le plus souvent. Le tableau distingue désormais les deux cas.

**Le principe derrière la surcharge des traductions**

Le document le dit en une phrase : « les traductions ne sont pas liées aux
bundles, mais aux **domaines** ». La page appliquait la règle — « même
domaine » — sans énoncer le principe. Conséquence pratique désormais écrite :
aucune déclaration n'est nécessaire, et le nom du bundle dans
`AcmeUserBundle.es.yaml` n'est là que parce qu'il **est** le nom du domaine.

**Précision sur le mapping**

Quand une *mapped superclass* existe, la surcharge porte sur les **attributs**
et les **associations** — la page disait seulement que c'était possible.

**Trois passes de resserrement**

Premier jet à 1003 mots pour 900. Réglé en trois passes : condensation des
sections « gabarits », « services » et « validation » dont les pièges reprenaient
déjà la substance, puis fusion de la section « mapping » dans la ligne de tableau
correspondante. Rien de vérifié n'a été supprimé, le niveau n'a pas été promu.

**Contrôles réellement exécutés le 2026-09-21**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `php bin/cert coverage` | aucun écart |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 444 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 16 002 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour tous |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | exit 0, `FINDINGS: 0` |
| Aiguilles de smoke test | 6 ; `mapped superclass` **écartée** — présente dans la version `master` de cette page même ; `deux réponses` écartée — présente sur les cartes d'autres items |

## Page 13 — Release management and roadmap schedule, 2026-09-21

**Fait**

- 15 flashcards ajoutées (`FLC-sgwqw3p540fc` … `FLC-rsbd14ed4kkf`) ; les deux
  cartes préexistantes ont reçu le niveau `RECALL`. L'item en porte **17**.
- Corps : 596 → **894 mots** sur 900.

**Des chiffres que la page n'avait pas : les délais de migration**

La page donnait les **durées de maintenance** — 8 mois en standard, 3 et 4 ans
en LTS. L'exposé des motifs du document donne un autre couple, que la page
ignorait : le **délai dont on dispose pour migrer**.

| | Nouvelle version tous les… | Délai pour migrer |
|---|---|---|
| Standard | six mois | **deux mois** |
| LTS | deux ans | **un an** |

Les deux couples répondent à des questions différentes, et rien dans les
chiffres ne le signale : c'est exactement le genre d'assimilation qu'un QCM
exploite. Une carte `TRAP` est dédiée à la confusion.

**Une exception qui invalide une formulation absolue**

La page écrivait « la version minimale de PHP est fixée pour chaque version
majeure de Symfony ». C'est la règle, et le document y attache une note :
**relever la version *mineure* minimale de PHP est possible dans une version
*mineure* de Symfony**, quand cela aide à corriger des problèmes importants.

Ajouté aussi : le minimum est décidé **par consensus de l'équipe centrale** ; et
pour une version de Symfony hors support, la dernière version de PHP supportée
est celle en vigueur à la fin de vie — les suivantes « peuvent fonctionner ou
non ».

**Une précision de date**

La limite de cinq mineures par branche vaut **depuis la branche 3.x**. La page
l'énonçait comme une règle intemporelle.

**Deux passes de resserrement**

Premier jet à 953 mots pour 900. Réglé en condensant les sections « six mois »,
« développement en double » et le modèle temporel, dont les pièges et les points
clés reprenaient déjà la substance. Le niveau n'a pas été promu.

**Contrôles réellement exécutés le 2026-09-21**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `php bin/cert coverage` | aucun écart |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 444 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 16 017 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour tous |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | exit 0, `FINDINGS: 0` |
| Aiguilles de smoke test | 8, chacune vérifiée absente de la version `master` de cette page et du fichier de flashcards du lot |

## Page 14 — Framework interoperability and PSRs, 2026-09-21

**Fait**

- 16 flashcards ajoutées (`FLC-tq9e37tkngem` … `FLC-75qhxd0cfawg`) ; la carte
  préexistante `FLC-f6phwa9qmmek` a reçu le niveau `TRAP`. L'item en porte
  **17**.
- Corps : 419 → **709 mots** sur 900.

**`provide` ne contient pas que des PSR**

La page présentait la clé `provide` comme « la réponse autoritative » et n'en
montrait que les huit entrées `psr/*`. Relue entrée par entrée, la clé compte
**quinze** entrées :

| Famille | Nombre |
|---|---|
| `psr/*` | **8** |
| contrats Symfony (`cache`, `event-dispatcher`, `http-client`, `service`, `translation`) | **5** |
| HTTPlug (`php-http/client-implementation`, `php-http/async-client-implementation`) | **2** |

Sept entrées sur quinze passaient donc à la trappe. Les contrats Symfony sont
directement sur le sujet de la page — ce sont des abstractions en paquets
minuscules, qu'une bibliothèque peut exiger **sans dépendre de Symfony**. Et
HTTPlug est l'abstraction client HTTP antérieure à PSR-18, que HttpClient
satisfait aussi.

**Les versions déclarées ne sont pas uniformes**

`provide` ne nomme pas seulement l'interface, il dit quelles **versions
majeures** Symfony satisfait — et elles diffèrent : trois pour PSR-3 et PSR-16,
deux pour PSR-6 (`2.0|3.0`, **pas** `1.0`), PSR-11 et PSR-13, une seule pour
PSR-14, PSR-18 et PSR-20. La page laissait croire à une couverture uniforme.

**`CRS-001` a levé, et la correction a été faite dans le contenu**

Mon premier jet illustrait la logique des contrats en citant le paquet qui
fournit `trigger_deprecation()`. Cette chaîne est la **réponse correcte** d'une
question `LEARNING` rattachée à un **autre** item — celui des dépréciations.
`bin/cert validate` a refusé :

```text
[ERROR] CRS-001: Course reproduces the correct answer of question QST-qyf1tg8cm0w6 verbatim (§4.3). (CRS-f74jcpnrrkhz)
```

Le nom du paquet a été **retiré** du corps, remplacé par un renvoi à la page qui
le traite. Conformément à `CLAUDE.md`, il n'a pas été déplacé dans un bloc de
code : « moving the string into a fence is gaming the check, not passing it ».

**Contrôles réellement exécutés le 2026-09-21**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** après correction de `CRS-001` |
| `php bin/cert coverage` | aucun écart |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 444 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 16 033 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour tous |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | exit 0, `FINDINGS: 0` |
| Aiguilles de smoke test | 7 ; `nyholm` **écartée** (présente dans la version `master` de cette page) et `quinze` **écartée** (présente sur les cartes d'autres items) |

## Page 15 — Naming conventions, 2026-09-21

**Fait**

- 14 flashcards ajoutées (`FLC-435p71j9g13k` … `FLC-n8wtgz598t9a`) ; la carte
  préexistante `FLC-00w816qz6wh8` a reçu le niveau `RECALL`. L'item en porte
  **15**.
- Corps : 339 → **585 mots** sur 700 (`MINIMAL`).

**Un second document, absent de la page**

La page ne citait que `standards.rst`. La section « Naming a Method » de
`conventions.rst` — repérée à la page 11 et réservée pour ici — normalise
**douze** noms de méthodes quand un objet a une relation « principale » :
`get`, `set`, `has`, `all`, `replace`, `remove`, `clear`, `isEmpty`, `add`,
`register`, `count`, `keys`.

La convention ne s'applique **que** si la relation principale est évidente. Le
document oppose trois cas : un `CookieJar` (une seule relation, elle
s'applique), un conteneur de services (deux relations mais les services
dominent, elle s'applique) et un `Input` de console (arguments *et* options,
aucune ne domine, elle ne s'applique pas).

**Quatre lignes du tableau ne se déduisent pas**

| Relation principale | Autre relation |
|---|---|
| `all()` | **`getXXXs()`** — pas `allXXX()` |
| `replace()` | **`setXXXs()`** — pas `replaceXXXs()` |
| `keys()` | **aucune** |
| *aucune* | **`replaceXXX()`** |

Et `setXXX()` peut **ajouter** un élément quand `replaceXXX()` ne le peut pas :
ce dernier doit **lever une exception** sur une clé inconnue. Deux noms
voisins, un droit différent.

**Contrôles réellement exécutés le 2026-09-21**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `php bin/cert coverage` | aucun écart |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 444 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 16 047 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour tous |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | exit 0, `FINDINGS: 0` |
| Aiguilles de smoke test | 7 ; `TestCase` **écartée** — présente dans la version `master` de cette page |

---

# Rapport de fin de lot 03

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire (`CLAUDE.md`, « Reporting a lot »).

## Périmètre

**15** items officiels atomiques portent `lot: lot-03` dans la matrice.

## Couverture — formule unique (§3.5)

```text
EXAM_READY atomiques officiels / total atomiques officiels
= 163 / 163 = 100,0 %
```

Ce chiffre est **cumulatif et porte sur tout le projet**, pas sur ce lot. Le
sous-ensemble du lot 03 est **15 / 15**. Aucun des deux n'a bougé pendant la
campagne : les quinze items étaient déjà `EXAM_READY`. **Ce lot n'a pas fait
progresser la couverture** — il a approfondi des pages déjà comptées.

## Volume de cours — corps en mots, front matter exclu

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| 15 cours du lot 03 | 7 459 | **12 495** | **+5 036** |

La base de comparaison est `c977490`, le commit précédant la première page
refondue du lot. Les pages 1 et 2 (HttpFoundation, Symfony Flex) ont été
livrées par la PR #169 avant l'ouverture de ce journal ; elles sont incluses
dans le total ci-dessus.

Aucune page ne dépasse son budget `REV-001` :

| Niveau | Budget | Pages | Plus proche du plafond |
|---|---|---|---|
| `MINIMAL` | 700 | 3 | HttpFoundation, 694 |
| `STANDARD` | 900 | 10 | Release management, 894 |
| `DEEP` | 1200 | 2 | Request handling, 1181 |

## Flashcards

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| Cartes sur les items du lot 03 | 18 | **245** | **+227** |

Répartition par niveau — **observation, jamais une cible** :
`RECALL` 71 · `TRAP` 59 · `UNDERSTANDING` 58 · `APPLICATION` 57.
**Zéro carte du lot sans niveau.**

## Questions et pools

**69** questions portent sur les items du lot 03 :

| Pool | Nombre | Fichier |
|---|---|---|
| `LEARNING` | 50 | `lot-03-architecture.yml` |
| `VALIDATION` | 12 | `validation-pool.yml` |
| `HOLDOUT` | 7 | 5 dans `mock-04-holdout.yml`, 2 dans `lot-03-architecture.yml` |

**`POOL-002` : 0 manquant.** Les douze items `STANDARD`/`DEEP` `EXAM_READY`
portent chacun au moins une question `VALIDATION`.

> Une première version de mon script de réconciliation ne lisait que
> `lot-03-architecture.yml` et annonçait **12 items manquants**. C'était faux :
> les questions `VALIDATION` vivent dans `validation-pool.yml`. Le chiffre
> corrigé concorde avec `bin/cert validate`, qui ne signale rien sur `POOL-002`.

### Holdout — isolation fonctionnelle, pas confidentialité

Les 7 questions `HOLDOUT` du lot sont **absentes de `practice.json` et de
`exam.json`** — `PayloadBuilder::assertNoHoldoutLeak()` l'assure à la
construction, et le smoke test de production le revérifie sur les octets
servis. C'est une **isolation fonctionnelle**.

Ce n'est **pas** de la confidentialité : `mock-4.json` est publié et porte les
réponses correctes. Quiconque ouvre ce payload voit le holdout.

## Contrôles — état réel

| Contrôle | Résultat | Preuve |
|---|---|---|
| `php bin/cert validate` | **PASS** | 0 bloquant sur le dernier état |
| `php bin/cert coverage` | **PASS** | aucun écart, à chaque page |
| `node website/tools/verify-reschedule.mjs` | **PASS** | 76 jours, 444 créneaux |
| `composer gate-full` | **PASS** | 295 tests, 16 047 assertions, `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **PASS** | exit 0, à chaque page |
| `prove_framework_rules_fail.py` | **PASS** | `PROOF OK`, 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | **PASS** | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | **PASS** | `FINDINGS: 0` |
| Accessibilité (§13, §17) | **PASS** | incluse dans `gate-full`, 0 violation axe et structurelle |
| Branche + PR par page (§15) | **PASS** | PR #169 à #182, une par page, CI verte avant chaque fusion |
| Déploiement + smoke test de production | **PASS pour les pages 1 à 14** | lignes `ok lot-03 …` lues dans les journaux d'exécution |
| Déploiement de la page 15 | **PASS** | PR #182 fusionnée dans `master` (`e98604a`) ; smoke test de production le 2026-09-22 à 05:24:06 UTC |

## Défauts trouvés dans le corpus existant

Neuf affirmations fausses, incomplètes ou non qualifiées, corrigées avec leur
source :

| Page | Défaut |
|---|---|
| 04 Components and Bridges | « cinq bridges » — aucune source ne l'établit ; `replace` en liste trois |
| 05 Code organization | « la liste [des clés `extra`] est close » — il en existe une cinquième, imbriquée |
| 05 (carte `FLC-82hpxh6bv6y3`) | rangeait `templates/`, `translations/` et `vendor/` sous `extra` |
| 06 Request handling | `kernel.exception` n'était mentionné **nulle part** |
| 07 Exception handling | « sinon, 500 » — vrai du noyau seul, faux d'une application |
| 08 Event dispatcher | `stopPropagation()` énoncé sans sa condition (`StoppableEventInterface`) |
| 09 Official best practices | « une trentaine » ; 8 recommandations couvertes sur 30 |
| 12 Framework overloading | la condition « si le contrôleur est un service » était sautée |
| 13 Release management | « minimum PHP par majeure » énoncé sans son exception |

## Deux défauts introduits par moi, et attrapés par les contrôles

- **`AUD-04` / `VOL-4`** a levé deux fois sur des quasi-doublons de flashcards
  que j'avais écrits (pages 4 et 5). Fronts reformulés avant commit.
- **`CRS-001`** a levé sur la page 14 : mon brouillon reproduisait la réponse
  correcte d'une question rattachée à un **autre** item. La chaîne a été
  **retirée**, pas masquée dans un bloc de code.

## Un défaut latent révélé par la campagne

`verify-reschedule.mjs` a échoué sur la page 5. Le port TypeScript du
planificateur divergeait de `build_roadmap.py` sur le premier morceau partiel
d'un item (`full: false` figé au lieu de `left === item.total`). La divergence
n'apparaissait que si un item débordait de son créneau — ce que
l'agrandissement de la page 5 a provoqué pour la première fois. **Le port a été
corrigé, pas le contrôle.**

Leçon opérationnelle, à ajouter à celle de `CLAUDE.md` sur les audits :
`composer gate-full` **ne lance pas** `verify-reschedule.mjs`.

## Cinq dépassements de budget, cinq résolutions

Pages 7, 9, 10, 12 et 13 ont dépassé `REV-001` au premier jet (1000, 914, 1016,
1003 et 953 mots). Chaque fois réglé par **resserrement de la prose**,
**déduplication** de ce que les pièges reprenaient déjà, ou **fusion** d'une
section dans une ligne de tableau. **Aucune promotion de niveau, aucun contenu
vérifié supprimé.**

## Résumé auditable

> Le lot 03 compte **15** items officiels atomiques, tous `EXAM_READY` avant
> comme après. La couverture du projet — `EXAM_READY / total`, la seule formule
> admise — vaut **163/163 = 100,0 %** et **n'a pas bougé** : cette campagne
> approfondit des pages déjà comptées, elle n'en ajoute pas.
>
> Les quinze cours passent de **7 459** à **12 495** mots de corps (+5 036),
> aucun au-dessus de son budget. Les flashcards passent de **18** à **245**
> (+227), toutes niveléees, réparties 71/59/58/57 — une **observation**, pas une
> cible. **69** questions portent sur le lot (50 `LEARNING`, 12 `VALIDATION`,
> 7 `HOLDOUT`) et `POOL-002` ne signale **aucun** manquant.
>
> Le holdout est **fonctionnellement isolé** des payloads d'apprentissage, ce
> que le smoke test de production revérifie ; il n'est **pas confidentiel**,
> `mock-4.json` étant publié avec ses réponses.
>
> Neuf défauts du corpus existant ont été corrigés avec leur source. Trois
> défauts introduits en cours de route ont été attrapés par `AUD-04` et
> `CRS-001` avant commit. Un défaut latent du port navigateur du planificateur,
> invisible jusque-là, a été révélé et corrigé — le contrôle n'a pas été touché.
>
> **Les quinze pages** sont déployées et vérifiées en production par un smoke
> test dont la ligne de sortie est citée dans ce journal. La dernière à l'avoir
> été, le 2026-09-22 à 05:24:06 UTC :
>
> ```text
> ok  lot-03  the Naming conventions page carries its levelled flashcards,
>             the twelve method names and the set/replace distinction
> ```

### Le déploiement de la page 15, daté

Ce rapport a d'abord été écrit alors que la page 15 était encore en pull
request, et il portait deux mentions provisoires : `EN ATTENTE` dans le tableau
des contrôles, et « quatorze pages sur quinze » dans le résumé. Les deux ont été
corrigées **après** lecture de la ligne de sortie, pas par anticipation.

| | |
|---|---|
| PR #182 fusionnée | `e98604a` |
| Déploiement Pages | run `35690376739`, succès à 05:23:50 UTC |
| Smoke test de production | succès, ligne émise à **05:24:06 UTC** |

Les seize lignes `ok lot-03 …` du journal d'exécution couvrent les quinze pages
du lot — HttpFoundation et Symfony Flex partagent un bloc, les treize autres ont
le leur.

## Prochaine étape

Déterminer le lot suivant à refondre.
