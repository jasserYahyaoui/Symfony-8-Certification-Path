# Raffinement pédagogique — Lot 05 (Routing)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 et 04 : chaque affirmation vérifiée contre le code
ou la documentation de la branche 8.0, jamais de mémoire ; budget `REV-001`
respecté sans promotion de niveau ; une branche, une PR, une CI verte, une
fusion et un smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-09-24 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`), avant la première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Routing component and FrameworkBundle | STANDARD | 460 / 900 | 1 | **RAFFINÉE** (PR #201) |
| 2 | Configuration (YAML and PHP attributes) | STANDARD | 551 / 900 | 1 | **RAFFINÉE** (PR #202) |
| 3 | Restrict URL parameters | STANDARD | 392 / 900 | 1 | **RAFFINÉE** (PR #203) |
| 4 | Set default values to URL parameters | STANDARD | 417 / 900 | 2 | **RAFFINÉE** (PR #204) |
| 5 | URLs generation | STANDARD | 422 / 900 | 1 | **RAFFINÉE** (PR #205) |
| 6 | Trigger redirects | STANDARD | 432 / 900 | 1 | **RAFFINÉE** (PR #206) |
| 7 | Special internal routing attributes | STANDARD | 391 / 900 | 1 | **RAFFINÉE** (PR #207) |
| 8 | Domain name matching | MINIMAL | 268 / 700 | 1 | **RAFFINÉE** (PR #208) |
| 9 | Conditional request matching | STANDARD | 392 / 900 | 1 | **RAFFINÉE** (PR #209) |
| 10 | HTTP methods matching | MINIMAL | 307 / 700 | 1 | **RAFFINÉE** (PR #210) |
| 11 | User's locale guessing | STANDARD | 390 / 900 | 1 | **RAFFINÉE** (PR #211) |
| 12 | Router debugging | MINIMAL | 279 / 700 | 1 | **RAFFINÉE** (PR #212) |

Base de comparaison pour le rapport de fin de lot : `3bb0479`, le commit qui
précède la première page de ce lot.

La colonne *Niveau* est une **observation** : c'est celle que la matrice porte
déjà. Aucun niveau n'est promu pour faire monter un pourcentage.

## Page 1 — Routing component and FrameworkBundle, 2026-09-24

`CRS-2nwv1v57j7ef` · `OIT-ceaw3ewfsw85` · STANDARD · **460 → 668 mots** sur 900.
Aucun niveau promu.

### Une attribution fausse : `RouterListener` n'est pas une classe du bundle

La page rangeait le `RouterListener` sous « ce que FrameworkBundle ajoute ». Son
fichier, sur la branche 8.0, déclare `namespace Symfony\Component\HttpKernel\EventListener`.
La classe appartient au composant **HttpKernel** ; FrameworkBundle l'enregistre
comme service `router_listener` dans `Resources/config/routing.php`. Le bundle
câble, il ne définit pas.

Lu dans la même classe : écoute de `kernel.request` à la priorité **32** ; routage
**sauté** si la requête porte déjà `_controller` (« routing is already done ») —
c'est ce qui évite de router à nouveau la sous-requête d'un `forward()`, vu au
lot 04 ; `_route_params` reçoit les variables sauf `_route` et `_controller` ;
`ResourceNotFoundException` devient un **404**, `MethodNotAllowedException` un
**405** avec l'en-tête `Allow`.

### Une imprécision : les routes d'attributs ne viennent pas d'un répertoire

La page disait « le chargeur d'attributs qui lit `#[Route]` dans
`src/Controller/` ». La documentation (`routing.rst`) configure la ressource
`routing.controllers`. Le chargeur qui la supporte, `AttributeServicesLoader`
(composant Routing), itère sur des **classes étiquetées** ; `FrameworkExtension`
pose les étiquettes `controller.service_arguments` et `routing.controller` sur
toute classe portant `#[Route]`, par autoconfiguration. C'est l'enregistrement
comme service qui compte, pas l'emplacement.

### Un complément : le matcher réellement déclaré

`router.default` reçoit `RedirectableCompiledUrlMatcher` et `CompiledUrlGenerator`,
compilés dans `router.cache_dir`. Le matcher du bundle sait rediriger : sa
méthode `redirect()` renvoie vers `RedirectController::urlRedirectAction`, avec
`permanent` à `true`. `MicroKernelTrait::configureRoutes()` importe
`config/routes/{env}/`, `config/routes/`, `config/routes.yaml`, et les attributs
du fichier du noyau lui-même.

**`CRS-001` a levé, et la page a été corrigée, pas masquée.** Un premier jet
nommait le paquet de contrats dont dépend le composant. Ce nom est la bonne
réponse d'une question `LEARNING` rattachée à un **autre** item (lot 03,
`trigger_deprecation()`). La page dit de nouveau « un paquet de contrats » sans
le nommer — ce que l'ancienne version faisait déjà, pour la même raison.

**Flashcards.** 14 ajoutées ; la carte préexistante `FLC-6dw96ps91ywe` reçoit le
niveau RECALL. L'item en porte **15** (5 RECALL, 4 UNDERSTANDING, 3 APPLICATION,
3 TRAP). Aucune ne double la carte existante sur les dépendances du composant.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`RedirectableCompiledUrlMatcher`, `router_listener` et `AttributeServicesLoader`,
absentes de la version `master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** après correction ; `CRS-001` bloquant au premier jet (ci-dessus) |
| `php bin/cert coverage` | `100% (163/163 EXAM_READY)` |
| `build_roadmap.py` (paramètres de la CI) puis `render_calendar.py` | les **deux** régénérés |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour les onze, `FINDINGS: 0` partout |
| `bash -n` sur les 34 blocs `run:` des workflows | tous parsent |
| `composer gate-full` | **exit 0** — 299 tests, 16 515 assertions ; `TOTAL VIOLATIONS: 0` |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` / `prove_flashcard_coverage_fails.py` | `PROOF OK` / `PROOF OK` |
| `aud10 --prove` / `lot27_practice_audit.py --prove` | **exit 0** / **exit 0** |

**Déploiement de la page 1, lu dans le journal d'exécution.** PR #201 fusionnée
en squash (`c1be3cf`). Run Pages 35980787003 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-05  the routing component page carries its four
flashcard levels, the bundle matcher, the listener service and the attribute
loader` est écrite à **09:24:26 UTC** le 2026-09-24.

## Page 2 — Configuration (YAML and PHP attributes), 2026-09-24

`CRS-kqq96z5y9r2f` · `OIT-egy2wn3z7gb7` · STANDARD · **551 → 746 mots** sur 900.
Aucun niveau promu.

### Une affirmation fausse : « le même jeu d'options »

La page disait : « L'attribut `#[Route]` et la clé YAML acceptent le même jeu
d'options », puis listait `priority`, `env` et `alias` parmi elles. Elle
conseillait même de ne pas « utiliser `priority` en YAML pour réordonner ».

`YamlFileLoader` (8.0) déclare sa propre liste :

```php
private const AVAILABLE_KEYS = [
    'resource', 'type', 'prefix', 'path', 'host', 'schemes', 'methods', 'defaults', 'requirements', 'options', 'condition', 'controller', 'name_prefix', 'trailing_slash_on_root', 'locale', 'format', 'utf8', 'exclude', 'stateless',
];
```

`priority` n'y figure pas, et une clé hors liste fait **lever** une
`InvalidArgumentException` : écrire `priority:` en YAML ne réordonne rien, cela
casse le chargement. `env` s'exprime par un bloc `when@<env>:` au premier niveau
(`loadContent()`), `alias` par une entrée qui ne porte que `alias` et
`deprecated` (`validateAlias()`). La documentation (« Priority Parameter ») est
cohérente avec le code : « In YAML or PHP config files you can move the route
definitions up or down […]. In routes defined as PHP attributes this is much
harder to do, so you can set the optional `priority` parameter ».

### Deux précisions sur la priorité

- `RouteCollection::all()` trie par priorité décroissante puis, à égalité, par
  ordre d'insertion : la priorité départage, elle ne remplace pas l'ordre.
- Dans l'attribut, l'argument vaut `null`, pas `0` : `AttributeClassLoader`
  le remplace par la priorité posée au niveau de la classe, et à défaut par `0`.

### Un complément

`path` est typé `string|array|null` : un tableau indexé par locale produit une
route par locale, nommée `nom.locale` (`AttributeClassLoader::addRoute()`).

**Flashcards.** 14 ajoutées ; la carte préexistante `FLC-t45m07frz2sv`
(`golden-slice.yml`) reçoit le niveau RECALL. L'item en porte **15** (5 RECALL,
4 UNDERSTANDING, 3 APPLICATION, 3 TRAP). Deux défauts de brouillon corrigés avant
commit : un exemple `#[Route(path: /x)]` qui n'était pas du PHP valide, et une
carte citant le symbole `priority` au lieu de `addRoute`.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `AVAILABLE_KEYS`,
`when@prod` et `blog_list.fr`, absentes de la version `master` de la page.
`stateless` a été **écartée** : elle y figure déjà.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** ; 1 avertissement `PED-003` préexistant |
| `php bin/cert coverage` | `100% (163/163 EXAM_READY)` |
| `build_roadmap.py` (paramètres de la CI) puis `render_calendar.py` | les **deux** régénérés |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour les onze, `FINDINGS: 0` partout |
| `bash -n` sur les 34 blocs `run:` des workflows | tous parsent |
| `composer gate-full` | **exit 0** — 299 tests, 16 529 assertions ; `TOTAL VIOLATIONS: 0` |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` / `prove_flashcard_coverage_fails.py` | `PROOF OK` / `PROOF OK` |
| `aud10 --prove` / `lot27_practice_audit.py --prove` | **exit 0** / **exit 0** |

**Déploiement de la page 2, lu dans le journal d'exécution.** PR #202 fusionnée
en squash (`3ec8690`). Run Pages 35982554060 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-05  the configuration page carries its four
flashcard levels, the YAML key list, the when@ block and the localized route
names` est écrite à **09:41:42 UTC** le 2026-09-24.

## Page 3 — Restrict URL parameters, 2026-09-24

`CRS-wg3j0t5pm7w1` · `OIT-1cj08dhtp9hj` · STANDARD · **392 → 578 mots** sur 900.
Aucun niveau promu.

### Une affirmation fausse : « la contrainte ne vaut qu'à l'appariement »

La page, dans ses pièges d'examen : « Elle ne valide pas ce que l'on passe au
générateur d'URL. » `UrlGenerator::doGenerate()` (8.0) relit chaque contrainte :

```php
if (null !== $this->strictRequirements && !preg_match(/* la contrainte */)) {
    if ($this->strictRequirements) {
        throw new InvalidParameterException(/* … must match … */);
    }
    $this->logger?->error($message, /* … */);
    return '';
}
```

Et `framework.router.strict_requirements` a pour défaut `true`
(`Configuration.php`, `->defaultTrue()`). Sans configuration, générer une route
avec une valeur non conforme **lève**.

**Un écart interne au framework.** Le texte d'aide de l'option annonce, pour
`false`, « return null instead » ; le code retourne `''`. La page suit le code et
signale l'écart.

### Un complément : `DIGITS` n'est pas `POSITIVE_INT`

`Requirement` est une énumération **sans cas**, qui ne porte que des constantes
chaînes. `DIGITS` vaut `[0-9]+` et accepte `0` ; `POSITIVE_INT` vaut
`[1-9][0-9]*` et l'exclut.

**Flashcards.** 12 ajoutées ; la carte préexistante `FLC-s7es8e4pq9vf` reçoit le
niveau RECALL. L'item en porte **13** (5 RECALL, 3 UNDERSTANDING, 3 APPLICATION,
2 TRAP). Une carte retirée avant commit : elle doublait le piège sur le retour de
`strict_requirements: false`.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`strict_requirements`, `InvalidParameterException` et `POSITIVE_INT`, absentes
de la version `master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** ; 1 avertissement `PED-003` préexistant |
| `php bin/cert coverage` | `100% (163/163 EXAM_READY)` |
| `build_roadmap.py` (paramètres de la CI) puis `render_calendar.py` | les **deux** régénérés |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour les onze |
| `bash -n` sur les 34 blocs `run:` des workflows | tous parsent |
| `composer gate-full` | **exit 0** — 299 tests, 16 541 assertions ; `TOTAL VIOLATIONS: 0` |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` / `prove_flashcard_coverage_fails.py` | `PROOF OK` / `PROOF OK` |
| `aud10 --prove` / `lot27_practice_audit.py --prove` | **exit 0** / **exit 0** |

**Déploiement de la page 3, lu dans le journal d'exécution.** PR #203 fusionnée
en squash (`94712e5`). Run Pages 35984210635 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-05  the URL parameters page carries its four
flashcard levels, strict_requirements, the generator exception and POSITIVE_INT`
est écrite à **09:57:47 UTC** le 2026-09-24.

## Page 4 — Set default values to URL parameters, 2026-09-24

`CRS-vkgr06y3x72g` · `OIT-ff0kghjbzvpm` · STANDARD · **417 → 609 mots** sur 900.
Aucun niveau promu.

### Une affirmation fausse : « le `!` est une décision de génération, pas d'appariement »

La page et l'explication de la carte `FLC-5j4yp6wp9yzk` le disaient, après la
documentation, qui ne décrit du `!` que son effet sur l'URL générée.
`RouteCompiler` (8.0) :

```php
// variable is optional when it is not important and has a default value
if ('variable' === $token[0] && !($token[5] ?? false) && $route->hasDefault($token[3])) {
```

Le jeton d'une variable `{!page}` porte `important = true` en position 5 ; elle
n'entre donc jamais dans la zone facultative. `/blog/{!page}` ne correspond plus
à `/blog`. La page et l'explication de la carte sont corrigées ; la
documentation n'est pas fausse, elle est incomplète.

### Deux précisions

- **D'où vient le défaut en attributs.** `AttributeClassLoader::addRoute()` ne
  copie la valeur par défaut d'un argument que si le chemin porte un paramètre
  **du même nom** et qu'aucun défaut n'est déjà posé, et seulement pour une
  valeur scalaire, `null`, ou la `value` d'un cas d'énumération adossée.
- **L'omission à la génération ne vaut qu'en fin de chemin.** `UrlGenerator`
  parcourt les jetons depuis la fin et repasse `$optional` à `false` au premier
  segment de texte ou à la première valeur différente du défaut.

Et `{page?}` sans valeur donne `null` (`Route::extractInlineDefaultsAndRequirements()`),
ce que la documentation accompagne du conseil de rendre l'argument nullable.

**Flashcards.** 11 ajoutées ; les deux cartes préexistantes reçoivent un niveau —
`FLC-5j4yp6wp9yzk` RECALL, `FLC-x8ga0twzt6g1` APPLICATION — et l'explication de
la première est corrigée. L'item en porte **13** (4 RECALL, 3 UNDERSTANDING,
3 APPLICATION, 3 TRAP). Une carte retirée avant commit : elle répétait, sous forme
d'application, ce que la carte corrigée et le piège disent déjà.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `RouteCompiler`,
`AttributeClassLoader` et `nullable`, absentes de la version `master` de la page.
`important` a été **écartée** : trop courante pour prouver quoi que ce soit.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** ; 1 avertissement `PED-003` préexistant |
| `php bin/cert coverage` | `100% (163/163 EXAM_READY)` |
| `build_roadmap.py` (paramètres de la CI) puis `render_calendar.py` | les **deux** régénérés |
| Jeu d'audits de CI (11 scripts) | **exit 0** pour les onze, `FINDINGS: 0` partout |
| `bash -n` sur les 34 blocs `run:` des workflows | tous parsent |
| `composer gate-full` | **exit 0** — 299 tests, 16 552 assertions ; `TOTAL VIOLATIONS: 0` |
| `node website/tools/verify-reschedule.mjs` | **exit 0** — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` / `prove_flashcard_coverage_fails.py` | `PROOF OK` / `PROOF OK` |
| `aud10 --prove` / `lot27_practice_audit.py --prove` | **exit 0** / **exit 0** |

**Un résultat périmé, lu puis rejeté.** L'attente de la gate guettait la ligne
`DONE` dans `/tmp/gatefull.log` ; le fichier de la page 3 la contenait encore, et
l'attente a rendu la main avant que la nouvelle exécution ne l'écrase. Le
tableau aurait porté les chiffres de la page précédente — 16 541 assertions,
identiques à la page 3 malgré onze cartes de plus. C'est cette égalité qui a
trahi la lecture. Les horodatages des fichiers (10:03-10:04, postérieurs aux
modifications) et le nouveau compte, **16 552**, ont été vérifiés avant d'écrire
ce tableau. Leçon : effacer le fichier de résultats avant de relancer, ou
attendre la fin du processus, jamais un marqueur qu'une exécution antérieure a
pu laisser.

**Déploiement de la page 4, lu dans le journal d'exécution.** PR #204 fusionnée
en squash (`b4872d1`). Run Pages 35986719198 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-05  the default values page carries its four
flashcard levels, the compiler rule, the attribute loader and the nullable
argument` est écrite à **10:23:29 UTC** le 2026-09-24.

## Page 5 — URLs generation, 2026-09-24

`CRS-sx61as7r7p0d` · `OIT-81b2c0jmv2j3` · STANDARD · **422 → 642 mots** sur 900.
Aucun niveau promu.

### Une affirmation fausse, héritée de la documentation — et d'une question

La page : « Un objet utilisé comme paramètre supplémentaire […] n'est **pas**
[converti]. Il faut le convertir soi-même ». La documentation 8.0 dit la même
chose (`routing.rst`, avertissement sur l'Uuid). Et l'explication de la question
`LEARNING` `QST-0y1zbrpb67fw`, rattachée à cet item, aussi.

`UrlGenerator::doGenerate()` (8.0) parcourt les paramètres en trop et, pour
chaque objet, prend ses propriétés publiques s'il en a, sinon le convertit en
chaîne s'il est `Stringable`, et lève une `InvalidParameterException` sur une
référence circulaire. `AbstractUid` n'a qu'une propriété `protected` et
implémente `Stringable` : l'Uuid de l'avertissement documentaire **est**
converti.

**Corrections.** La page énonce le comportement du code et signale l'écart avec
la documentation. L'explication de `QST-0y1zbrpb67fw` est corrigée — sa bonne
réponse, qui porte sur la chaîne de requête, ne change pas ; `reviewed_at` passe
au 2026-09-24. Aucune question holdout n'a été lue.

### Deux compléments

- Un paramètre en trop **égal à la valeur par défaut** de même nom est retiré
  (`array_udiff_assoc` contre `$defaults`).
- La clé `_query` doit être un tableau — sinon `InvalidParameterException` — et
  ses entrées l'emportent sur les paramètres en trop de même nom.

Et les valeurs des constantes : `ABSOLUTE_URL` vaut `0`, le défaut `ABSOLUTE_PATH`
vaut `1`.

**Flashcards.** 11 ajoutées ; la carte préexistante `FLC-rrt3hj10ddp2` reçoit le
niveau RECALL. L'item en porte **12** (4 RECALL, 4 UNDERSTANDING, 2 APPLICATION,
2 TRAP). Une carte retirée avant commit : elle reprenait mot pour mot le scénario
de la question `LEARNING` de l'item.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `Stringable`,
`AbstractUid` et `_query`, absentes de la version `master` de la page.
`NETWORK_PATH` a été **écartée** : elle y figure déjà.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` | régénérés |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 563 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |

## Page 6 — Trigger redirects, 2026-09-24

`CRS-tgbv3wp66rcc` · `OIT-d51jbkfs21pt` · STANDARD · **432 → 823 mots** sur 900.
Aucun niveau promu.

### Une affirmation incomplète, héritée de la documentation — et d'une question

La page : « une requête en HTTP vers `/login` est **automatiquement redirigée**
vers la même URL en HTTPS ». La documentation 8.0 dit la même chose, sans
condition. La question `LEARNING` `QST-np471a1fkmv6` aussi : son énoncé parle
d'« une requête HTTP » sans en donner la méthode.

`CompiledUrlMatcherTrait::match()` (8.0) ne tente **aucune** redirection — ni de
schéma ni de barre finale — si la méthode n'est pas `GET` ou `HEAD` : un `POST`
en HTTP vers une route `schemes: ['https']` finit en
`ResourceNotFoundException`, donc en 404. Le distracteur « 404 » de la question
devenait correct pour un `POST`.

**Corrections.** La page énonce la restriction et signale le silence de la
documentation. L'énoncé de `QST-np471a1fkmv6` précise désormais une requête
`GET` ; son explication mentionne le 404 d'un `POST`. Sa bonne réponse ne change
pas ; `reviewed_at` passe au 2026-09-24. Aucune question holdout n'a été lue.

### Compléments, lus dans le code et les tests 8.0

- **Qui redirige.** Le `UrlMatcher` du composant ne redirige jamais. Il faut un
  matcher `RedirectableUrlMatcherInterface` ; le composant n'en fournit qu'une
  classe abstraite, FrameworkBundle l'implémente
  (`RedirectableCompiledUrlMatcher`). Sa méthode `redirect()` désigne
  `RedirectController::urlRedirectAction` avec `permanent: true` : statut 301,
  chaîne de requête recopiée, `_route` conservé.
- **Barre finale.** La route doit accepter `GET` ; la racine n'est pas
  concernée ; la redirection l'emporte sur une route générique déclarée plus
  loin (`testFallbackPage`).
- **Schéma.** Cible = premier schéma listé ; aucune redirection si l'un d'eux
  correspond ; barre et schéma corrigés en une seule redirection
  (`testMissingTrailingSlashAndScheme`).
- **Raison de la restriction.** RFC 9110, section 15.4.2 : un client peut
  rejouer en `GET` un `POST` redirigé par un 301. La page s'appuyait sur cette
  raison sans la sourcer ; elle la cite désormais.

**Flashcards.** 11 ajoutées ; la carte préexistante `FLC-qtcf6sp62jpq` reçoit le
niveau RECALL. L'item en porte **12** (4 RECALL, 4 UNDERSTANDING, 2 APPLICATION,
2 TRAP).

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`RedirectableCompiledUrlMatcher`, `RedirectableUrlMatcherInterface` et
`testFallbackPage`, absentes de la version `master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 574 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Un échec de lancement, relancé.** Le premier `composer gate-full` s'est arrêté
avant toute vérification (Composer refuse l'utilisateur root sans
`COMPOSER_ALLOW_SUPERUSER=1`) : exit 1, aucun test exécuté. Relancé avec la
variable ; le résultat ci-dessus est celui de la relance.

**Déploiement de la page 5, lu dans le journal d'exécution.** PR #205 fusionnée
en squash (`116a6a0`). Run Pages 35996557717 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-05  the URL generation page carries its four
flashcard levels, the Stringable cast, the Uid class and the _query key` est
écrite à **12:04:32 UTC** le 2026-09-24.

## Page 7 — Special internal routing attributes, 2026-09-24

`CRS-nhebf7arqvn4` · `OIT-4pgc74ctc3vc` · STANDARD · **391 → 758 mots** sur 900.
Aucun niveau promu.

### Une affirmation fausse, héritée de la documentation — et de trois questions

La page montrait `#[Route(..., query: ['page' => 1])]` et listait `query` parmi
les formes courtes de l'attribut. La documentation 8.0 fait de même, à
l'attribut comme en YAML. Les explications des questions `LEARNING`
`QST-m5x0wprr2kvf` et `QST-0negxbfbe972` aussi.

Le code 8.0 le contredit à trois endroits :

- le constructeur de `Attribute\Route` n'a **pas** d'argument `query` ; ses
  arguments courts sont `locale`, `format`, `stateless` (valeurs par défaut) et
  `utf8` (option). PHP rejette un argument nommé inconnu par une `Error` — test
  php-src `Zend/tests/named_params/unknown_named_param.phpt` (PHP-8.4) ;
- `query` ne figure pas dans `YamlFileLoader::AVAILABLE_KEYS` : clé refusée par
  une `InvalidArgumentException` ;
- `UrlGenerator::doGenerate()` ne lit `_query` que dans les paramètres passés à
  `generate()` : une valeur par défaut `_query` n'a aucun effet sur l'URL.

### Une justification inventée, dans une question

L'explication de `QST-f0k6jqb9twcj` affirmait qu'un import « rejette »
`_fragment`, parce qu'un fragment n'a de sens que pour une route. Aucune source
ne donne cette raison, et le code 8.0 ne rejette rien : `RouteCollection::addDefaults()`
recopie les valeurs par défaut d'un import sur chaque route sans examiner leur
nom. L'exclusion de `_fragment` est une règle **documentaire** ; la bonne réponse
des deux questions qui la testent reste `_fragment`, puisque c'est la règle que
l'examen peut citer.

**Corrections.** La page énonce le code, signale les deux écarts avec la
documentation et garde la règle documentaire en la qualifiant. Les explications
des trois questions sont corrigées ; leurs bonnes réponses ne changent pas ;
`reviewed_at` passe au 2026-09-24. Aucune question holdout n'a été lue.

**Constat laissé en l'état.** `QST-m5x0wprr2kvf` et `QST-f0k6jqb9twcj` testent
le même fait, avec presque les mêmes choix. Supprimer l'une est une décision de
banque de questions, hors du périmètre de ce raffinement : signalé, non traité.

### Compléments, lus dans le code 8.0

- `_format` : `Response::prepare()` ne pose le `Content-Type` déduit du format
  que si la réponse n'en a pas.
- `_locale` : appliqué par `LocaleListener` ; un `_locale` venu d'un import est
  ignoré pour une route localisée (`Route::addDefaults()`).
- `_fragment` : la valeur par défaut de la route, remplacée par un paramètre de
  génération.

**Flashcards.** 11 ajoutées ; la carte préexistante `FLC-z20216cjkhsp` reçoit le
niveau RECALL. L'item en porte **12** (4 RECALL, 4 UNDERSTANDING, 2 APPLICATION,
2 TRAP).

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `_stateless`,
`LocaleListener` et `Response::prepare()`, absentes de la version `master` de la
page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 585 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 6, lu dans le journal d'exécution.** PR #206 fusionnée
en squash (`e099d0f`). Run Pages 35998180671 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-05  the trigger redirects page carries its four
flashcard levels, the bundle matcher, the redirectable interface and the
fallback test` est écrite à **12:20:49 UTC** le 2026-09-24.

## Page 8 — Domain name matching, 2026-09-24

`CRS-2b1wzm3rwdrn` · `OIT-21m4pmtymygn` · MINIMAL · **268 → 518 mots** sur 700.
Aucun niveau promu.

### Une affirmation fausse : « exactement celles d'un paramètre de chemin »

La page, dans ses pièges : « Valeurs par défaut et contraintes sont exactement
celles d'un paramètre de chemin ». Les **clés** sont les mêmes ; l'**effet** de la
valeur par défaut, non. `RouteCompiler::compilePattern()` (8.0) ne calcule le
premier paramètre facultatif que pour le chemin (`if (!$isHost)`) : un paramètre
d'hôte est toujours exigé à l'appariement. `host: '{subdomain}.example.com'`
avec `defaults: ['subdomain' => 'm']` ne correspond pas à `example.com`. La
documentation ne dit pas le contraire : elle justifie la valeur par défaut par
la génération d'URL.

**Corrections.** La page distingue les clés, identiques, de l'effet, propre au
chemin. L'explication de la question `LEARNING` `QST-qq1rr2xpmn82` précise que
`requirements` contraint et que `defaults` ne sert qu'à la génération ; sa bonne
réponse ne change pas ; `reviewed_at` passe au 2026-09-24. Aucune question
holdout n'a été lue.

### Compléments, lus dans le code et les tests 8.0

- **Casse ignorée.** Hôte mis en minuscules, expression régulière d'hôte avec
  le drapeau `i` (`testHostIsCaseInsensitive`).
- **Génération.** Un hôte différent de la requête courante fait passer `path()`
  de `ABSOLUTE_PATH` à `NETWORK_PATH` : `//m.example.com/`
  (`testWithHostDifferentFromContext`). La contrainte d'hôte est vérifiée à la
  génération.
- **Ordre.** La route sans `host` doit suivre celle qui en a un.

**Flashcards.** 7 ajoutées ; la carte préexistante `FLC-x5tjtg0h3pm1` reçoit le
niveau RECALL. L'item en porte **8** (3 RECALL, 2 UNDERSTANDING, 1 APPLICATION,
2 TRAP).

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`testHostIsCaseInsensitive`, `compilePattern` et `NETWORK_PATH`, absentes de la version `master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 592 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 7, lu dans le journal d'exécution.** PR #207 fusionnée
en squash (`99ae644`). Run Pages 35999732982 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-05  the special parameters page carries its four
flashcard levels, the stateless default, the locale listener and the prepare
method` est écrite à **12:35:03 UTC** le 2026-09-24.

## Page 9 — Conditional request matching, 2026-09-24

`CRS-f92d09x14ggx` · `OIT-e2zrm9qkpx7j` · STANDARD · **392 → 613 mots** sur 900.
Aucun niveau promu.

### Une affirmation fausse : « ce n'est pas de la configuration compilée »

La page, dans ses pièges : « L'expression est évaluée à l'appariement, à chaque
requête — ce n'est pas de la configuration compilée une fois pour toutes ». La
documentation 8.0 dit l'inverse : « Internally, expressions are compiled down to
raw PHP ». Le code aussi : `CompiledUrlMatcherDumper::compileRoute()` appelle
`ExpressionLanguage::compile()` avec `context`, `request` et `params`, et écrit
le PHP obtenu dans le matcher en cache. Ce PHP s'exécute à chaque requête ;
l'expression n'est pas réinterprétée.

**Correction.** Le piège énonce désormais la compilation. Aucune question de
l'item ne reprenait l'affirmation. Aucune question holdout n'a été lue.

### Compléments, lus dans le code 8.0

- **Ordre des contrôles.** Dans `CompiledUrlMatcherTrait::doMatch()`, la
  condition est testée avant la barre finale, le schéma et la méthode : une
  condition fausse retire la route sans ajouter ses méthodes au 405 ni
  déclencher de redirection.
- **`Request` paresseux.** Le dumper numérote positivement les conditions dont
  le PHP mentionne `$request` ; les autres s'évaluent sans construire d'objet
  `Request`.
- **`env()` et `service()`.** Déclarées dans `services.php` de FrameworkBundle
  comme fonctions `routing.expression_language_function` ; `service()` lit un
  localisateur des seuls services `routing.condition_service`.

**Flashcards.** 11 ajoutées ; la carte préexistante `FLC-hyrr6qtdkfh9` reçoit le
niveau RECALL. L'item en porte **12** (4 RECALL, 4 UNDERSTANDING, 2 APPLICATION,
2 TRAP).

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`CompiledUrlMatcherDumper`, `ExpressionLanguage::compile()` et
`CompiledUrlMatcherTrait::doMatch()`, absentes de la version `master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 603 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 8, lu dans le journal d'exécution.** PR #208 fusionnée
en squash (`7b997b7`). Run Pages 36001250509 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-05  the domain name page carries its four
flashcard levels, the case test, the compiler method and the network path` est
écrite à **12:49:43 UTC** le 2026-09-24.

## Page 10 — HTTP methods matching, 2026-09-24

`CRS-h3q0qxnq8eq0` · `OIT-5g82spham3vm` · MINIMAL · **307 → 474 mots** sur 700.
Aucun niveau promu.

### Une affirmation incomplète : le remplacement de méthode et son option

La page présentait `framework.http_method_override` comme la condition de tout
remplacement de méthode. `Request::getMethod()` (8.0) lit d'abord l'en-tête
`X-HTTP-METHOD-OVERRIDE`, **sans** consulter cette option : elle ne gouverne que
le paramètre `_method`, comme le dit son texte d'aide dans `Configuration`. Seul
`allowed_http_method_override: []` neutralise aussi l'en-tête.

Une seconde imprécision : « Le composant Form pose le champ automatiquement
quand c'est le cas ». Le thème `form_div_layout.html.twig` ajoute le champ caché
dès que la méthode du formulaire n'est ni `GET` ni `POST`, **indépendamment** de
l'option.

**Corrections.** La page décrit l'algorithme de `getMethod()` et le rôle exact
de chaque option. Les questions de l'item restent exactes ; aucune n'est
modifiée. Aucune question holdout n'a été lue.

### Compléments, lus dans le code 8.0

- Remplacement seulement depuis un `POST` ; jamais vers `GET`, `HEAD`, `CONNECT`
  ou `TRACE` ; nom invalide → `SuspiciousOperationException`.
- `_method` lu dans le corps puis dans la chaîne de requête.
- `allowed_http_method_override` : `null` (défaut) tout, liste restreinte, `[]`
  rien ; la configuration refuse les quatre méthodes interdites.
- `methods: ['GET']` accepte `HEAD` (méthode canonique dans le matcher).

**Flashcards.** 7 ajoutées ; la carte préexistante `FLC-v2wg754jbxy7` reçoit le
niveau RECALL. L'item en porte **8** (3 RECALL, 3 UNDERSTANDING, 1 APPLICATION,
1 TRAP). Une affirmation retirée d'une carte avant commit : une note
historique non sourcée sur la valeur par défaut de l'option.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`X-HTTP-METHOD-OVERRIDE`, `SuspiciousOperationException` et `CONNECT`, absentes
de la version `master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 610 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 9, lu dans le journal d'exécution.** PR #209 fusionnée
en squash (`740348d`). Run Pages 36002797063 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-05  the conditional matching page carries its four
flashcard levels, the dumper, the compile call and the doMatch method` est
écrite à **13:03:49 UTC** le 2026-09-24.

## Page 11 — User's locale guessing, 2026-09-24

`CRS-evzcj63fgdb7` · `OIT-xxcpx1qssp93` · STANDARD · **390 → 591 mots** sur 900.
Aucun niveau promu.

### Une lacune, pas une erreur

Les affirmations de la page se vérifient dans le code 8.0 et la documentation.
Mais un item intitulé *locale guessing* laissait de côté la seule
« divination » que fait `LocaleListener` : le choix d'après `Accept-Language`,
renvoyé au lot HTTP. `LocaleListener::setLocale()` (8.0) décide dans cet ordre —
attribut `_locale`, puis `Accept-Language` parmi `enabled_locales` **si**
`framework.set_locale_from_accept_language` vaut `true` (défaut `false`, dans
`Configuration`), sinon la locale par défaut. Le choix d'après l'en-tête pose
aussi `_vary_by_language`, que `ResponseListener` traduit en
`Vary: Accept-Language`.

La page dit « priorité plus élevée » sans chiffre ; `getSubscribedEvents()` donne
**16** pour `onKernelRequest` (après `RouterListener`, 32) et 100 pour
`setDefaultLocale`.

**Corrections.** Section « Quand l'URL ne dit rien » ajoutée, priorité chiffrée.
Les questions de l'item restent exactes ; aucune n'est modifiée. Aucune
question holdout n'a été lue.

### Compléments, lus dans le code 8.0

- `AttributeClassLoader::addRoute()` : une route `nom.locale` par locale, avec
  `_locale`, une contrainte et `_canonical_route` ; l'entrée sans clé devient la
  route `nom`.
- `UrlGenerator::generate()` : `nom.locale`, puis la langue sans région
  (`fr_CA` → `fr`), puis `nom`.
- `LocaleListener` recopie la locale dans le contexte du routeur.

**Flashcards.** 11 ajoutées ; la carte préexistante `FLC-hdwv8cdrrdwb` reçoit le
niveau RECALL. L'item en porte **12** (4 RECALL, 4 UNDERSTANDING, 2 APPLICATION,
2 TRAP). Une affirmation retirée d'une carte avant commit : une valeur de
priorité attribuée à la documentation, qui n'y figure pas.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`set_locale_from_accept_language`, `_canonical_route` et `AttributeClassLoader`,
absentes de la version `master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 621 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 10, lu dans le journal d'exécution.** PR #210 fusionnée
en squash (`9195f95`). Run Pages 36004545160 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-05  the HTTP methods page carries its four
flashcard levels, the override header, the suspicious exception and the CONNECT
method` est écrite à **13:18:45 UTC** le 2026-09-24.

## Page 12 — Router debugging, 2026-09-24

`CRS-8dgxs89hrah0` · `OIT-8sr74a2wnb3r` · MINIMAL · **279 → 527 mots** sur 700.
Aucun niveau promu.

### Une imprécision : `router:match` ne prend pas une URL

La page — et l'explication de la question `LEARNING` `QST-d3z5234k4p9k` — disait
que `router:match` « prend une URL ». `RouterMatchCommand` (8.0) déclare un
argument `path_info`, décrit « A path info » ; l'hôte, le schéma et la méthode
passent par `--host`, `--scheme` et `--method`, qui modifient le contexte du
routeur. Une URL complète en argument ne teste ni l'hôte ni le schéma.

Dans la même question, le distracteur `router:debug` s'expliquait par un
renommage « il y a longtemps » : affirmation historique sans source dans ce
projet. Retirée ; l'explication dit seulement que la commande de liste de 8.0
s'appelle `debug:router`.

**Corrections.** La page et l'explication disent *path info*. La bonne réponse
ne change pas ; `reviewed_at` passe au 2026-09-24. Aucune question holdout n'a
été lue.

### Compléments, lus dans le code 8.0

- `debug:router <nom>` : nom exact, sinon recherche des noms qui **contiennent**
  l'argument, casse ignorée ; choix interactif ou liste ; sinon « The route …
  does not exist ». `--method` garde les routes sans contrainte de méthode ;
  `--format` : `txt` (défaut), `xml`, `json`, `md`.
- `router:match` : lignes « almost matches » toujours affichées, avec la raison
  donnée par `TraceableUrlMatcher` ; routes non appariées seulement en `-v` ;
  succès → détail via `debug:router` ; échec → code de sortie 1.

**Flashcards.** 7 ajoutées ; la carte préexistante `FLC-jjgd2xj2w7y4` reçoit le
niveau RECALL. L'item en porte **8** (3 RECALL, 3 UNDERSTANDING, 1 APPLICATION,
1 TRAP). Deux formulations retirées avant commit : une fréquence non prouvée
(« souvent »), et une description inexacte des lignes « almost matches ».

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`RouterMatchCommand`, `TraceableUrlMatcher` et `path info`, absentes de la
version `master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 628 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 11, lu dans le journal d'exécution.** PR #211 fusionnée
en squash (`07bb9cf`). Run Pages 36006271232 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-05  the locale guessing page carries its four
flashcard levels, the Accept-Language option, the canonical route and the
attribute loader` est écrite à **13:34:41 UTC** le 2026-09-24.

**Déploiement de la page 12, lu dans le journal d'exécution.** PR #212 fusionnée
en squash (`75df3e9`). Run Pages 36008126517 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-05  the router debugging page carries its four
flashcard levels, the match command, the traceable matcher and the path info`
est écrite à **13:50:29 UTC** le 2026-09-24.

# Rapport de fin de lot 05

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire (`CLAUDE.md`, « Reporting a lot »). Base de comparaison :
`3bb0479`, le commit de `master` qui précède la première page refondue (PR #201).

## Périmètre

**12** items officiels atomiques portent `lot: lot-05` dans la matrice :
3 `MINIMAL`, 9 `STANDARD`, aucun `DEEP` — niveaux inchangés pendant la campagne.
L'absence de `DEEP` est une **observation** : aucune cible de répartition
n'existe.

## Couverture — formule unique (§3.5)

```text
EXAM_READY atomiques officiels / total atomiques officiels
= 163 / 163 = 100,0 %
```

Ce chiffre est **cumulatif et porte sur tout le projet**. Le sous-ensemble du
lot 05 est **12 / 12**. Aucun des deux n'a bougé : les douze items étaient déjà
`EXAM_READY`. **Ce lot n'a pas fait progresser la couverture** — il a approfondi
des pages déjà comptées.

## Volume de cours — corps en mots, front matter exclu

Compté avec la tokenisation de `Course::wordCount()`, sur le fichier et sur son
état à la base de comparaison :

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| 12 cours du lot 05 | 4 701 | **7 547** | **+2 846** |

Aucune page ne dépasse son budget `REV-001` :

| Niveau | Budget | Pages | Plus proche du plafond |
|---|---|---|---|
| `MINIMAL` | 700 | 3 | Router debugging, 527 |
| `STANDARD` | 900 | 9 | Trigger redirects, 823 |

## Flashcards

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| Cartes sur les items du lot 05 | 13 | **140** | **+127** |

Répartition par niveau — **observation, jamais une cible** :
`RECALL` 48 · `UNDERSTANDING` 42 · `APPLICATION` 25 · `TRAP` 25.
**Zéro carte du lot sans niveau.** Les treize cartes préexistantes ont toutes
reçu un niveau ; aucune n'a été supprimée. Une explication de carte
préexistante a été corrigée (`FLC-5j4yp6wp9yzk`, page 4).

## Questions et pools

**51** questions portent sur les items du lot 05 — aucune ajoutée, aucune
supprimée :

| Pool | Nombre | Fichier |
|---|---|---|
| `LEARNING` | 36 | 34 dans `lot-05-routing.yml`, 2 dans `golden-slice.yml` |
| `VALIDATION` | 9 | `validation-pool.yml` |
| `HOLDOUT` | 6 | 4 dans `mock-04-holdout.yml`, 2 dans `lot-05-routing.yml` |

**Sept questions `LEARNING` modifiées**, toutes à bonne réponse inchangée,
`reviewed_at` au 2026-09-24 :

| Question | Page | Champs modifiés |
|---|---|---|
| `QST-0y1zbrpb67fw` | 5 | explication |
| `QST-np471a1fkmv6` | 6 | énoncé précisé (`GET`), `version` 1 → 2, explication |
| `QST-m5x0wprr2kvf` | 7 | explication |
| `QST-0negxbfbe972` | 7 | explication |
| `QST-f0k6jqb9twcj` | 7 | explication, explication d'un choix |
| `QST-qq1rr2xpmn82` | 8 | explication |
| `QST-d3z5234k4p9k` | 12 | explication, explication d'un choix |

La comparaison scriptée avec la base ne compte **aucune question `HOLDOUT`
modifiée** ; le script n'en compare que l'égalité, sans en afficher le contenu.

**`POOL-002` : 0 manquant.** Les neuf items `STANDARD` `EXAM_READY` portent
chacun au moins une question `VALIDATION`.

### Holdout — isolation fonctionnelle, pas confidentialité

Les 6 questions `HOLDOUT` du lot sont **absentes de `practice.json` et de
`exam.json`** — `PayloadBuilder::assertNoHoldoutLeak()` l'assure à la
construction, et le smoke test de production le revérifie sur les octets servis
(« 516 questions, all LEARNING, no holdout id or choice »). C'est une
**isolation fonctionnelle**.

Ce n'est **pas** de la confidentialité : `mock-4.json` est publié et porte les
réponses correctes. Aucun contenu holdout n'a été lu pour ce lot ni pour ce
rapport.

## Contrôles — état réel

| Contrôle | Résultat | Preuve |
|---|---|---|
| `php bin/cert validate` | **PASS** | 0 bloquant à chaque page ; 1 avertissement `PED-003` préexistant, hors lot |
| `php bin/cert coverage` | **PASS** | aucun écart, à chaque page |
| `node website/tools/verify-reschedule.mjs` | **PASS** | exit 0 à chaque page |
| `composer gate-full` | **PASS** | 299 tests, 16 628 assertions sur la dernière page, `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **PASS** | exit 0, `FINDINGS: 0`, à chaque page |
| `prove_framework_rules_fail.py` | **PASS** | `PROOF OK`, 11 cas ; empreinte SHA-256 de `content/` et `docs/` identique avant et après |
| `prove_flashcard_coverage_fails.py` | **PASS** | `PROOF OK` |
| `aud10 --prove`, `lot27_practice_audit.py --prove` | **PASS** | exit 0 |
| Accessibilité (§13, §17) | **PASS** | incluse dans `gate-full` ; étape « Accessibility audit » verte en CI à chaque PR |
| Blocs `run:` des workflows | **PASS** | 34 blocs passent `bash -n` |
| Branche + PR par page (§15) | **PASS** | #201 à #212, une par page, CI verte avant chaque fusion en squash |
| Déploiement + smoke test de production | **PASS pour les pages 1 à 11** | lignes `ok lot-05 …` lues dans les journaux d'exécution, page par page |
| Déploiement de la page 12 | **PASS** | PR #212 fusionnée (`75df3e9`) ; run Pages 36008126517 ; smoke test à 13:50:29 UTC |

## Défauts trouvés dans le corpus existant

Onze pages sur douze portaient une affirmation fausse, incomplète ou
imprécise ; la douzième, une lacune. Toutes vérifiées contre le code de la
branche 8.0 :

| Page | Défaut corrigé |
|---|---|
| 01 Routing component | `RouterListener` attribué à FrameworkBundle ; il appartient à HttpKernel. Les routes d'attributs présentées comme lues dans un répertoire |
| 02 Configuration | « le même jeu d'options » : YAML refuse `priority` (`AVAILABLE_KEYS`) |
| 03 Restrict URL parameters | « la contrainte ne vaut qu'à l'appariement » : le générateur la vérifie, `strict_requirements` vaut `true` par défaut |
| 04 Default values | « le `!` est une décision de génération » : il agit aussi à l'appariement (`RouteCompiler`) ; aussi dans une carte |
| 05 URLs generation | un objet en paramètre supplémentaire serait laissé tel quel ; il est converti ; aussi dans une question |
| 06 Trigger redirects | la redirection de schéma présentée sans condition ; elle ne vaut qu'en `GET`/`HEAD` ; aussi dans une question |
| 07 Special attributes | `#[Route(..., query: …)]` : l'argument n'existe pas en 8.0 ; une justification inventée dans une question |
| 08 Domain name matching | un défaut d'hôte rendrait le paramètre facultatif comme sur le chemin ; non |
| 09 Conditions | « pas de configuration compilée » : l'expression est compilée en PHP |
| 10 HTTP methods | `http_method_override` présentée comme condition de tout remplacement ; l'en-tête n'en dépend pas |
| 11 Locale guessing | lacune : le choix d'après `Accept-Language` et l'ordre de `LocaleListener` |
| 12 Router debugging | `router:match` « prend une URL » : il prend un *path info* ; aussi dans une question |

## Écarts entre la documentation 8.0 et le code 8.0

Tous tranchés par la hiérarchie des sources — le code l'emporte — et signalés
sur la page, parce qu'une question d'examen peut reprendre la formulation
documentaire :

| Page | La documentation | Le code |
|---|---|---|
| 05 | un Uuid passé en paramètre supplémentaire n'est pas converti | converti (`Stringable`) |
| 07 | exemple `#[Route(..., query: [...])]` et clé YAML `query:` | aucun argument `query` ; clé refusée par `YamlFileLoader` |
| 07 | `_query` utilisable dans une route ou un import | une valeur par défaut `_query` n'est pas lue par le générateur |
| 06 | une requête HTTP est redirigée vers HTTPS | seulement en `GET` et `HEAD` |

Deux silences documentaires comblés par le code sans contradiction : le `!`
à l'appariement (page 4) et l'exclusion de `_fragment` des imports, règle
documentaire que le code ne fait pas respecter (page 7). Un écart **interne au
framework** : le texte d'aide de `strict_requirements` annonce `null`, le code
retourne `''` (page 3).

## Défauts introduits par moi

**Aucun n'a atteint la production ni la CI.** Tous ont été attrapés avant
commit :

| Défaut | Attrapé par |
|---|---|
| un nom de paquet qui reproduisait la bonne réponse d'une question du lot 03 (page 1) | `CRS-001` — reformulé, pas caché |
| un résultat de gate périmé, lu avant d'être écrasé (page 4) | égalité suspecte des assertions, puis horodatages |
| `composer gate-full` lancé en root sans `COMPOSER_ALLOW_SUPERUSER` (page 6) | exit 1 avant tout test ; relancé et consigné |
| affirmations non prouvées retirées de cartes : une note historique (page 10), une priorité attribuée à la documentation (page 11), une fréquence « souvent » (page 12), un renvoi au profileur (page 6) | relecture |
| une description inexacte des lignes « almost matches » (page 12) | relecture contre `TraceableUrlMatcher` |

## Constat laissé ouvert

`QST-m5x0wprr2kvf` et `QST-f0k6jqb9twcj` testent le même fait avec presque les
mêmes choix. En supprimer une est une décision de banque de questions, hors du
périmètre de ce raffinement : **signalé, non traité**.

## Résumé auditable

> Le lot 05 compte **12** items officiels atomiques, tous `EXAM_READY` avant
> comme après. La couverture du projet — `EXAM_READY / total`, la seule formule
> admise — vaut **163/163 = 100,0 %** et **n'a pas bougé** : cette campagne
> approfondit des pages déjà comptées.
>
> Les douze cours passent de **4 701** à **7 547** mots de corps (+2 846),
> aucun au-dessus de son budget, aucun niveau promu. Les flashcards passent de
> **13** à **140** (+127), toutes nivelées, réparties 48/42/25/25 — une
> **observation**, pas une cible. **51** questions portent sur le lot (36
> `LEARNING`, 9 `VALIDATION`, 6 `HOLDOUT`), aucune ajoutée ni supprimée ; sept
> `LEARNING` ont une explication ou un énoncé corrigé, bonne réponse
> inchangée ; aucune `HOLDOUT` modifiée ; `POOL-002` ne signale aucun manquant.
>
> Le holdout est **fonctionnellement isolé** des payloads d'apprentissage, ce que
> le smoke test de production revérifie ; il n'est **pas confidentiel**,
> `mock-4.json` étant publié avec ses réponses.
>
> Onze pages portaient une affirmation fausse, incomplète ou imprécise, la
> douzième une lacune ; toutes corrigées contre le code 8.0. Quatre écarts entre
> la documentation et le code ont été tranchés par la hiérarchie des sources et
> signalés sur les pages.
>
> **Les douze pages** sont déployées et vérifiées en production. Le même
> smoke test — run 36008126517, sur `75df3e9` — émet les douze lignes
> `ok lot-05 …` entre 13:50:28 et 13:50:29 UTC le 2026-09-24. La dernière :
>
> ```text
> ok  lot-05  the router debugging page carries its four flashcard levels,
>             the match command, the traceable matcher and the path info
> ```

## Addendum — le doublon de questions, traité sur instruction (2026-09-24)

Le rapport laissait ouvert un doublon : `QST-m5x0wprr2kvf` et
`QST-f0k6jqb9twcj` testaient toutes deux l'exclusion de `_fragment` des
imports. Le propriétaire a demandé de supprimer l'une des deux, selon l'option
recommandée.

**Une suppression pure aurait dégradé la preuve.** Chacune était la seule
question `LEARNING` de son objectif : `f0k6` de `OUT-8f5nc3sevst2` (l'exclusion
de `_fragment`), `m5x0` de `OUT-zsdbtcwh25g5` (« Nommer les cinq paramètres
réservés et leur effet »), qu'elle ne testait pas. Et `m5x0` est l'une des deux
questions citées dans les `question_refs` de l'item, pour un minimum de preuve
`STANDARD` de deux questions.

**Retenu : `m5x0` est réécrite, pas supprimée.** Même identifiant, `version` 1 → 2,
quatre nouveaux identifiants de choix : elle demande quel paramètre réservé
ajoute l'identifiant de fragment à l'URL générée, avec pour distracteurs les
effets de `_query`, `_format` et `_controller`. Le fait « `_fragment` hors des
imports » n'est plus testé qu'une fois, par `f0k6` ; chaque objectif garde sa
question. La réponse correcte n'est pas le choix le plus long (`_controller`
l'est). Aucune question holdout n'a été lue.

**Constat traité ensuite, sur instruction.** L'objectif `OUT-4k0zcm86nfkk` de la
matrice s'intitulait « Associer les options locale, format et query à leur
paramètre réservé » ; `#[Route]` n'a pas d'argument `query` en 8.0 (page 7). Le
propriétaire a demandé la correction : le libellé devient « Associer les
arguments locale, format et stateless de #[Route] à leur paramètre réservé »,
ce que le constructeur de `Attribute\Route` (8.0) établit. Identifiant
inchangé ; la question qui l'évalue, `QST-0negxbfbe972`, porte déjà sur
`locale` et ne cite `query` que pour dire qu'il n'existe pas.

## Prochaine étape

Lot 06, page 1, dans l'ordre officiel des items. Reste ouverte, sans lien avec
ce lot : la PR #148 (ordre des événements de formulaires imbriqués).
