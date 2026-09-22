# Raffinement pédagogique — Lot 04 (Controllers)

Journal ouvert le 2026-09-22, après la clôture du lot 03 (15 pages, fusionnées
et déployées). Méthode inchangée : relire la source officielle sur la branche
`8.0` avant d'écrire, corriger ce qui est faux ou incomplet, tenir le budget
`REV-001` sans le dépasser ni promouvoir le niveau, faire porter le volume par
les flashcards aux quatre niveaux, et ajouter au smoke test de production une
aiguille discriminante par page.

## Ce qui change par rapport au lot 03

Le lot 03 portait en partie sur des textes de politique — *BC promise*,
*Release management*, *Official best practices* — dont la vérification est une
lecture attentive. Le lot 04 est **entièrement du code** : HttpKernel,
HttpFoundation, FrameworkBundle. Chaque affirmation se vérifie par une
signature, un chemin de fichier ou un corps de méthode, ce qui rend la
falsification plus facile et l'approximation plus visible.

Les marges sont en revanche **plus larges qu'au lot 03** : les quatorze pages
occupent 5 741 mots pour 11 900 de budget cumulé (48 %). Aucune page n'est
serrée ; le corps du cours est un lieu d'écriture, pas un budget à défendre.

Les quatorze items portaient **15 flashcards au total**, dont **aucune
n'était niveautée**. Le lot 03 en portait 46 au départ. Le travail de
flashcards est donc ici plus lourd que le travail de prose.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-09-22 par lecture des fichiers canoniques
(`syllabus-matrix.yml`, `content/**`), pas repris d'un rapport antérieur.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | HttpKernel component and FrameworkBundle | STANDARD | 717 / 900 | 16 | **RAFFINÉE** (2026-09-22) |
| 2 | Naming conventions | MINIMAL | 695 / 700 | 15 | **RAFFINÉE** (2026-09-22) |
| 3 | The base AbstractController class | STANDARD | 753 / 900 | 16 | **RAFFINÉE** (2026-09-22) |
| 4 | The request | MINIMAL | 684 / 700 | 16 | **RAFFINÉE** (2026-09-22) |
| 5 | The response | STANDARD | 812 / 900 | 16 | **RAFFINÉE** (2026-09-22) |
| 6 | The cookies | MINIMAL | 339 / 700 | 1 | à faire |
| 7 | The session | STANDARD | 385 / 900 | 2 | à faire |
| 8 | The flash messages | MINIMAL | 317 / 700 | 1 | à faire |
| 9 | HTTP redirects | MINIMAL | 344 / 700 | 1 | à faire |
| 10 | Internal redirects | STANDARD | 395 / 900 | 1 | à faire |
| 11 | Generate 404 pages | STANDARD | 423 / 900 | 1 | à faire |
| 12 | File upload | STANDARD | 404 / 900 | 1 | à faire |
| 13 | Built-in internal controllers | STANDARD | 402 / 900 | 1 | à faire |
| 14 | Argument value resolvers | DEEP | 632 / 1200 | 1 | à faire |

La colonne *Niveau* est une **observation**, pas une cible : elle est celle que
la matrice porte déjà, avec sa justification. Aucun niveau n'est promu pour
faire monter un pourcentage.

## Page 1 — HttpKernel component and FrameworkBundle, 2026-09-22

**Fait**

- 15 flashcards ajoutées (`FLC-x6n4fj9jjwhh` … `FLC-j9ay345szwmw`) ; la carte
  préexistante `FLC-4k8kx6a0rkg2` a reçu le niveau `RECALL`. L'item en porte
  **16** : RECALL 5, UNDERSTANDING 3, APPLICATION 4, TRAP 4.
- Corps : 428 → **717 mots** sur 900.
- Deux sources ajoutées au front matter : `KernelInterface.php` et
  `MicroKernelTrait.php`, toutes deux sur la branche `8.0`.

**Ce que la page ne disait pas : `KernelInterface` étend `HttpKernelInterface`**

La page énumérait les trois interfaces de `Kernel` sans dire ce que chacune
apporte, ce qui laissait deux questions sans réponse : pourquoi `Kernel` porte
`handle()` alors que ce n'est dans aucune des trois listes évidentes, et quelle
est la taille réelle de chacune.

La lecture de `KernelInterface.php` sur la branche `8.0` donne
`interface KernelInterface extends HttpKernelInterface` : un `Kernel` **est** un
`HttpKernelInterface`, et `handle()` vient par héritage. Les deux autres
interfaces n'apportent **qu'une méthode chacune** —
`reboot(?string $warmupDir): void` et `terminate(Request, Response): void`.
Le déséquilibre est le point à retenir : seize méthodes d'un côté, une de
chaque côté de l'autre. Une table le porte désormais, et deux pièges d'examen
en sont tirés.

**Ce que la page ne disait pas : `MicroKernelTrait` appelle par réflexion**

La page situait correctement `MicroKernelTrait` du côté du bundle, mais s'en
tenait à « configuration du noyau sans fichiers séparés ». La lecture de
`MicroKernelTrait.php` montre qu'il **implémente** `registerBundles()` et
`registerContainerConfiguration()` — les deux méthodes que `KernelInterface`
exige — et qu'il appelle `configureContainer()` et `configureRoutes()` **par
réflexion**.

Ce détail n'est pas une curiosité : c'est ce qui explique que le squelette
Symfony déclare ces deux méthodes `private`. Une méthode privée ne serait pas
appelable autrement. Sans cette phrase, la visibilité `private` du squelette
reste une anomalie inexpliquée que le candidat mémorise au lieu de la
comprendre.

**`AUD-04` a levé, et la correction a été faite sur la formulation**

La carte `FLC-yrdkk3y2mncr` demandait « Quelle est la signature de
`RebootableInterface::reboot()` ? ». Une fois les blocs de code normalisés,
cette question se réduit à « quelle est la signature de » — exactement ce que
donne `FLC-enc60pxerqbp` (lot 03, `EventDispatcher::dispatch()`) :

```text
[VOL-4 two flashcards ask the same question] 1
    ['FLC-enc60pxerqbp', 'FLC-yrdkk3y2mncr']: "quelle est la signature de"
```

Le recto a été reformulé pour que le mot discriminant tombe **hors** du bloc de
code : « Quel argument reçoit la méthode unique de `RebootableInterface`, et
que renvoie-t-elle ? ». Le verso est inchangé — c'est la formulation qui était
en cause, pas le contenu enseigné. `AUD-04` retourne `FINDINGS: 0`.

C'est la troisième occurrence de ce défaut depuis le lot 03, et toujours la
même cause : une question dont toute la substance vit dans un `code span`.

**Ce que la page 1 a fait tomber : `render_calendar.py` n'est dans aucune porte locale**

La CI a refusé le premier envoi :

```text
git diff --exit-code docs/revision/plan.json docs/revision/study-calendar.md
Process completed with exit code 1
```

J'avais régénéré `plan.json` et pas `study-calendar.md`. La cause n'est pas
l'oubli d'une commande dans une liste : c'est que **rien en local ne les
regénère ensemble**. `composer gate-full` ne lance ni `build_roadmap.py` ni
`render_calendar.py` ; la marche de CI *Revision plan is deterministic and up to
date* lance les deux, puis compare les deux fichiers.

Et ces deux fichiers bougent à **chaque** page raffinée, pas seulement quand on
touche au planificateur. `build_roadmap.py` lit le nombre de mots du corps de
chaque cours et le nombre de flashcards de chaque item :

```python
if cid: words[cid.group(1)] = len(re.findall(r'\S+', s.split('---',2)[-1]))
...
w  = sum(words.get(c,0) for c in i['course_refs'])
```

428 → 717 mots et 1 → 16 cartes changent la charge de l'item, donc le découpage
en créneaux, donc les 252 lignes du calendrier qui ont bougé. La régénération
des **deux** fichiers fait désormais partie de la boucle par page, au même titre
que `bin/cert coverage`.

C'est la même forme que la leçon de la page 5 du lot 03 (`verify-reschedule.mjs`
absent de `gate-full`) : une porte qui n'existe qu'en CI est une porte qu'on
franchit par un aller-retour, pas par une vérification.

**Contrôles réellement exécutés le 2026-09-22**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** — 25 règles, 163 items, 728 questions ; 1 avertissement `PED-003` préexistant |
| `php bin/cert coverage` | `100% (163/163 EXAM_READY)`, aucun écart |
| `python3 tools/audit/aud04_content_volume.py` | `FINDINGS: 0` après reformulation de `FLC-yrdkk3y2mncr` |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 444 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 16 062 assertions ; `TOTAL VIOLATIONS: 0` (23 pages auditées, axe=0 structural=0) |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour les onze ; `FINDINGS: 0` partout |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration byte-identique SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` — restauration byte-identique SHA-256 |
| `aud10_answer_length_bias.py --prove` | **exit 0**, `FINDINGS: 0` |
| `lot27_practice_audit.py --prove` | `PROOF OK` — 12 cas, fixtures synthétiques en mémoire |
| `python3 tools/revision/render_calendar.py` | `study-calendar.md : 1022 lignes` ; 252 lignes modifiées, régénérées après coup (voir ci-dessus) |
| Aiguilles de smoke test | 8 ; `RebootableInterface`, `câblage` et `extensibles` **écartées** (déjà présentes dans la version `master` de cette page) |

Le plan de révision a été régénéré avec
`--start 2026-10-01 --exam 2026-12-15 --max-new 4 --weekday 140 --weekend 200`
(`docs/revision/plan.json` modifié en conséquence).

Le déploiement et la lecture du smoke test de production sont consignés
ci-dessous une fois la fusion faite — jamais avant.

### Le déploiement de la page 1, daté

Écrit après lecture du journal d'exécution, pas par anticipation.

| Fait | Valeur |
|---|---|
| PR | #184, fusionnée en `10cc362` |
| CI de la PR | run 35692717002, **succès**, 31 étapes |
| Déploiement Pages | run 35693198194, job *Deploy* **succès** à 06:05:43 UTC |
| Smoke test de production | **succès**, ligne émise à 06:06:03 UTC |

```text
ok  lot-04  the HttpKernel page carries its levelled flashcards,
            the three-interface table and the MicroKernelTrait reflection
```

Le premier envoi avait été refusé par la CI (`study-calendar.md` non régénéré) ;
c'est le second, `8de478e`, qui est vert et qui a été fusionné.

## Page 2 — Naming conventions, 2026-09-22

Ne pas confondre avec la page *Naming conventions* du lot 03
(`CRS-9g4qrgfs7nm4`, `OIT-c9pjp03cv4bq`), déjà raffinée : celle du lot 03 porte
sur les conventions de nommage du framework, celle-ci sur celles des
contrôleurs.

**Fait**

- 14 flashcards ajoutées ; la carte préexistante `FLC-xhgvea5evak2` a reçu le
  niveau `RECALL`. L'item en porte **15** : RECALL 4, UNDERSTANDING 3,
  APPLICATION 4, TRAP 4.
- Corps : 370 → **695 mots** sur 700.
- Trois sources ajoutées : `AttributeClassLoader.php` (composant Routing),
  `AttributeRouteControllerLoader.php` (FrameworkBundle), et `templates.rst`.

**Une affirmation fausse : « la convention retenue par les générateurs officiels
est `app_<contrôleur>_<action>`, en `snake_case` »**

Deux erreurs dans une phrase.

Ce n'est pas une convention de générateur : c'est ce que **Symfony lui-même**
produit quand `name` est omis. `routing.rst` le dit — *« Symfony generates an
automatic name based on the controller and action »* — et le code le montre.

Et ce n'est **pas du `snake_case`**. `AttributeClassLoader::getDefaultRouteName()`
fait trois choses, transcrites depuis la branche `8.0` :

```php
$name = str_replace('\\', '_', $class->name).'_'.$method->name;
$name = mb_strtolower($name, 'UTF-8');
if ($this->defaultRouteIndex > 0) { $name .= '_'.$this->defaultRouteIndex; }
```

Aucun découpage de casse. `LuckyController` devient `luckycontroller`, pas
`lucky_controller`. Le nom court et lisible vient d'ailleurs : FrameworkBundle
redéfinit la méthode dans `AttributeRouteControllerLoader` et **retire** les
segments `bundle_` et `controller_`.

C'est exactement le partage établi à la page 1 :

| Méthode | Composant seul | Avec FrameworkBundle |
|---|---|---|
| `LuckyController::number` | `app_controller_luckycontroller_number` | `app_lucky_number` |
| `BlogController::showAction` | `app_controller_blogcontroller_showaction` | `app_blog_show` |

Les deux colonnes ont été obtenues en **exécutant** les deux algorithmes tels
que transcrits, pas en les lisant ; la seconde colonne concorde avec
l'exemple `app_lucky_number` que `debug:router` affiche dans `routing.rst`.

**Deux faits que la page ne portait pas**

`defaultRouteIndex` : une deuxième route sans `name` sur la même méthode reçoit
un suffixe `_1`. Les noms générés ne collisionnent pas, ils s'indexent.

Le suffixe `Action` : la page disait « n'est plus utilisé », ce qui est vrai de
la convention. Le chargeur du bundle, lui, le **retire encore** du nom généré —
ne plus être recommandé et ne plus être traité sont deux choses différentes.

**Un doublon que `AUD-04` n'a pas vu**

Ma carte neuve sur le suffixe `Controller` posait la même question que la carte
préexistante `FLC-xhgvea5evak2`. `AUD-04` ne l'a pas signalée : les deux
formulations ne se réduisent pas à la même chaîne normalisée
(« symfony le suffixe sur une classe de contrôleur est-il » contre « le suffixe
sur la classe est-il exigé par le framework »).

La carte neuve a été **retirée** et l'ancienne nivelée `RECALL`. La règle
attrape les quasi-doublons littéraux ; elle ne remplace pas la lecture de ce que
l'item porte déjà. Aucun contrôle n'a été touché.

**Contrôles réellement exécutés le 2026-09-22**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** ; 1 avertissement `PED-003` préexistant |
| `php bin/cert coverage` | `100% (163/163 EXAM_READY)`, aucun écart |
| `python3 tools/audit/aud04_content_volume.py` | `FINDINGS: 0`, **du premier coup** |
| `build_roadmap.py` puis `render_calendar.py` | les **deux** régénérés, `study-calendar.md : 1022 lignes` |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 444 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 16 076 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour les onze ; `FINDINGS: 0` partout |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration byte-identique SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | **exit 0**, `FINDINGS: 0` |
| `lot27_practice_audit.py --prove` | `PROOF OK` — 12 cas |
| Aiguilles de smoke test | 9 ; `snake_case` et `invocable` **écartées** (déjà présentes dans la version `master` de cette page) |

Le déploiement de la page 2 sera consigné après lecture du smoke test, jamais
avant.

### Le déploiement de la page 2, daté

| Fait | Valeur |
|---|---|
| PR | #185, fusionnée en `9a57a89` |
| CI de la PR | run 35694064252, **succès**, 31 étapes |
| Déploiement Pages | run 35694459484, job *Deploy* **succès** à 06:23:15 UTC |
| Smoke test de production | **succès**, ligne émise à 06:23:27 UTC |

```text
ok  lot-04  the Naming conventions page carries its levelled flashcards,
            the two-layer route-name algorithm and the template rule
```

## Page 3 — The base AbstractController class, 2026-09-22

**Fait**

- 15 flashcards ajoutées ; la carte préexistante `FLC-w5kmrvzr7a3z` a reçu son
  niveau. L'item en porte **16**.
- Corps : 490 → **753 mots** sur 900.

**Le tableau des raccourcis oubliait une méthode**

La famille *Sécurité* listait `isGranted()`, `denyAccessUnlessGranted()`,
`getUser()` et `isCsrfTokenValid()`. La classe en porte une cinquième,
`getAccessDecision()`, `protected` comme les autres. Un tableau qui se présente
comme exhaustif et qui ne l'est pas est pire qu'une liste ouverte : le lecteur
croit avoir tout vu.

**« Toutes les méthodes utilitaires sont `protected` » : vrai, mais incomplet**

L'affirmation est exacte pour les raccourcis. Elle laisse croire que rien n'est
`public`. La classe compte en réalité, sur la branche `8.0` :

| Visibilité | Nombre | Lesquelles |
|---|---|---|
| `protected` | 24 | tous les raccourcis |
| `public` | 2 | `setContainer()`, `getSubscribedServices()` |
| `private` | 2 | les deux aides internes de rendu |

Les deux méthodes publiques ne sont pas des raccourcis : ce sont les points
d'attache de l'infrastructure. C'est la distinction que la page ne faisait pas.

**La liste des services déclarés : onze, pas « une liste »**

La page énumérait « `router`, `request_stack`, `http_kernel`, `serializer`,
`twig`, `form.factory`, `parameter_bag`, les services de sécurité ». Comptée
dans le code, la liste fait **onze** entrées exactement, et deux précisions
manquaient :

- « les services de sécurité » en cache **trois**, distincts —
  `security.authorization_checker`, `security.token_storage`,
  `security.csrf.token_manager` ;
- `web_link.http_header_serializer` n'était pas mentionné du tout.

**Une signature et un comportement que la page donnait faux ou pas du tout**

`setContainer()` **retourne le conteneur précédent** (`?ContainerInterface`).
La page ne disait rien du retour, ce qui laisse supposer `void`.

`addLink()` **n'écrit rien dans la réponse**, malgré son commentaire de méthode
qui annonce le contraire. Elle dépose un fournisseur de liens dans l'attribut de
requête `_links` ; l'en-tête est produit plus tard par un écouteur. Sa signature
le dit : elle reçoit la **requête**, pas la réponse.

`sendEarlyHints()` émet ses en-têtes **immédiatement**, avec le statut **103**.
C'est le seul raccourci de la classe qui écrit sur la sortie au moment de
l'appel.

**Contrôles réellement exécutés le 2026-09-22**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant**, du premier coup ; 1 avertissement `PED-003` préexistant |
| `php bin/cert coverage` | `100% (163/163 EXAM_READY)`, aucun écart |
| `python3 tools/audit/aud04_content_volume.py` | `FINDINGS: 0`, du premier coup |
| `build_roadmap.py` puis `render_calendar.py` | les **deux** régénérés, `study-calendar.md : 1022 lignes` |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 444 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 16 091 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour les onze |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration byte-identique SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | **exit 0**, `FINDINGS: 0` |
| `lot27_practice_audit.py --prove` | **exit 0** |
| Aiguilles de smoke test | 8 ; `sendEarlyHints` et `ServiceSubscriberInterface` **écartées** (déjà présentes dans la version `master` de cette page) |

Le déploiement de la page 3 sera consigné après lecture du smoke test.

### Le déploiement de la page 3, daté

| Fait | Valeur |
|---|---|
| PR | #186, fusionnée en `03486d7` |
| CI de la PR | run 35694988159, **succès**, 31 étapes |
| Déploiement Pages | run 35726780422, job *Deploy* **succès** à 12:24:03 UTC |
| Smoke test de production | **succès**, ligne émise à 12:24:34 UTC |

```text
ok  lot-04  the AbstractController page carries its levelled flashcards,
            the visibility count and the eleven subscribed services
```

## Page 4 — The request, 2026-09-22

**Fait**

- 15 flashcards ajoutées ; la carte préexistante `FLC-jygxb3n5n15y` a reçu le
  niveau `TRAP`. L'item en porte **16**.
- Corps : 359 → **684 mots** sur 700.
- Deux sources ajoutées : `Request.php` et `RequestStack.php`, branche `8.0`.

**`getPayload()` : la page décrivait un comportement symétrique qui ne l'est pas**

La page disait : « elle retourne les données envoyées, qu'elles arrivent en
formulaire ou en JSON ». Le corps de la méthode, lu sur la branche `8.0`,
procède **en cascade** :

| Situation | Ce qu'elle rend |
|---|---|
| `$request->request` n'est pas vide | un **clone** de ce sac |
| sinon, corps brut vide | un `InputBag` **vide** |
| sinon | le corps décodé en JSON |

Trois conséquences que la formulation écrasait :

1. **Le formulaire est prioritaire.** Le JSON n'est lu que si le sac de
   formulaire est vide. Ce n'est pas une union des deux sources.
2. **C'est un clone.** Modifier l'objet rendu ne modifie pas la requête.
3. **Un JSON invalide lève.** Le décodage utilise l'option qui transforme
   l'erreur en exception : un corps mal formé donne une `JsonException`, pas un
   sac vide. Et un JSON valide qui ne décode **pas en tableau** — `42`, `"x"`,
   `true` — lève également.

La différence entre « sac vide » et « exception » n'est pas cosmétique : elle
décide du code de statut renvoyé au client. Traiter une erreur comme une absence
répond par un succès à une requête que le serveur n'a pas comprise.

**`RequestStack` n'était nommée que pour dire « injectez-la »**

La page disait d'injecter `RequestStack` dans un service et d'appeler
`getCurrentRequest()`. Elle ne disait pas que la classe porte une **pile** :

| Méthode | Rend |
|---|---|
| `getCurrentRequest()` | la requête en cours — la **sous-requête** si on est dedans |
| `getMainRequest()` | celle entrée par le serveur |
| `getParentRequest()` | `null` quand la courante **est** la principale |

Hors sous-requête, les deux premières rendent le même objet : la distinction est
invisible jusqu'au premier `forward()`, qui est justement l'item 10 de ce lot.

`getSession()` est l'exception du contrat : elle ne rend pas `null`, elle **lève**
une `SessionNotFoundException`. Une méthode qui lève au milieu de trois méthodes
nullables est exactement le genre d'asymétrie qu'une question d'examen isole.

**Contrôles réellement exécutés le 2026-09-22**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant**, du premier coup |
| `php bin/cert coverage` | `100% (163/163 EXAM_READY)`, aucun écart |
| `python3 tools/audit/aud04_content_volume.py` | `FINDINGS: 0`, du premier coup |
| `build_roadmap.py` puis `render_calendar.py` | les **deux** régénérés |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 444 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 16 106 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour les onze |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | **exit 0** |
| `lot27_practice_audit.py --prove` | **exit 0** |
| Aiguilles de smoke test | 8 ; `getPayload` **écartée** (présente deux fois dans la version `master` de cette page) |

Le déploiement de la page 4 sera consigné après lecture du smoke test.

### Le déploiement de la page 4, daté

| Fait | Valeur |
|---|---|
| PR | #187, fusionnée en `39b3bd3` |
| CI de la PR | run 35727681555, **succès** |
| Déploiement Pages | run 35740509125, job *Deploy* **succès** à 14:31:18 UTC |
| Smoke test de production | **succès**, ligne émise à 14:31:37 UTC |

```text
ok  lot-04  the request page carries its levelled flashcards,
            the RequestStack accessors and the getPayload cascade
```

## Page 5 — The response, 2026-09-22

**Fait**

- 15 flashcards ajoutées ; la carte préexistante `FLC-50qy4aqc9d2a` a reçu le
  niveau `UNDERSTANDING`. L'item en porte **16** — 4 par niveau.
- Corps : 453 → **812 mots** sur 900.

**Le 422 automatique : une condition annoncée, trois dans le code**

La page disait : « si l'un des paramètres passés au gabarit est un formulaire
invalide, `render()` retourne d'elle-même un 422 ». Le corps de `doRender()`,
lu sur la branche `8.0`, exige **trois** conditions simultanées :

1. la réponse en cours est encore en **200** ;
2. un paramètre est un `FormInterface` ;
3. ce formulaire est **soumis** *et* invalide.

Deux cas courants échappent donc à la règle telle qu'elle était écrite.

**Un statut explicite l'emporte.** Le test sur le statut précède la boucle sur
les paramètres : si le troisième argument porte une `Response` dont le statut
n'est pas 200, les formulaires ne sont même pas examinés. La page présentait les
deux mécanismes — troisième argument et 422 automatique — sans dire lequel gagne.

**Non soumis n'est pas invalide.** Un formulaire fraîchement construit ne
déclenche rien, ce qui est précisément ce qui permet à la première visite d'une
page de formulaire de sortir en 200. C'est le cas le plus fréquent en
production, et la formulation courte le donnait faux.

**Un formulaire passé au gabarit est converti pour vous**

`doRenderView()` parcourt les paramètres et remplace chaque `FormInterface` par
le résultat de son `createView()`. La page ne le disait pas. Appeler
`createView()` soi-même reste correct — c'est l'usage le plus répandu — mais
c'est redondant, et cet usage universel fait passer une commodité pour une
obligation.

**Trois précisions ajoutées**

`render()`, `renderView()`, `renderBlock()` et `stream()` lèvent une
`LogicException` **nommant la méthode appelée** et le paquet à installer quand
le bundle Twig est absent. « Service facultatif » ne veut pas dire
« dégradation silencieuse ».

Le contexte de `json()` est **fusionné par-dessus** les options d'encodage par
défaut de `JsonResponse` : c'est le levier prévu pour les changer. Et sans
Serializer, une donnée `null` est traitée à part.

`file()` écrit l'en-tête de disposition **dans tous les cas**, y compris pour le
défaut `attachment` ; le nom annoncé, quand le deuxième argument est omis, est
le nom réel du fichier sur disque.

**Contrôles réellement exécutés le 2026-09-22**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant**, du premier coup |
| `php bin/cert coverage` | `100% (163/163 EXAM_READY)`, aucun écart |
| `python3 tools/audit/aud04_content_volume.py` | `FINDINGS: 0`, du premier coup |
| `build_roadmap.py` puis `render_calendar.py` | les **deux** régénérés |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 444 créneaux |
| `composer gate-full` | **exit 0** — 295 tests, 16 121 assertions ; `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour les onze |
| `prove_framework_rules_fail.py` | `PROOF OK` — 11 cas, restauration SHA-256 |
| `prove_flashcard_coverage_fails.py` | `PROOF OK` |
| `aud10_answer_length_bias.py --prove` | **exit 0** |
| `lot27_practice_audit.py --prove` | **exit 0** |
| Aiguilles de smoke test | 8 ; `DISPOSITION_INLINE` **écartée** (présente deux fois dans la version `master` de cette page) |

Le déploiement de la page 5 sera consigné après lecture du smoke test.

## Prochaine étape

Page 6 — *The cookies* (`CRS-thmkagkjcvh3`, `OIT-pfrzrr0qcmh3`, MINIMAL,
339 / 700, 1 carte).
