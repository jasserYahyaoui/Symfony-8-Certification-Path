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

## Prochaine étape

Page 9 — **Official best practices**.
