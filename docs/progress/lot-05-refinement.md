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
| 3 | Restrict URL parameters | STANDARD | 392 / 900 | 1 | en cours |
| 4 | Set default values to URL parameters | STANDARD | 417 / 900 | 2 | à faire |
| 5 | URLs generation | STANDARD | 422 / 900 | 1 | à faire |
| 6 | Trigger redirects | STANDARD | 432 / 900 | 1 | à faire |
| 7 | Special internal routing attributes | STANDARD | 391 / 900 | 1 | à faire |
| 8 | Domain name matching | MINIMAL | 268 / 700 | 1 | à faire |
| 9 | Conditional request matching | STANDARD | 392 / 900 | 1 | à faire |
| 10 | HTTP methods matching | MINIMAL | 307 / 700 | 1 | à faire |
| 11 | User's locale guessing | STANDARD | 390 / 900 | 1 | à faire |
| 12 | Router debugging | MINIMAL | 279 / 700 | 1 | à faire |

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

## Prochaine étape

Page 4 — *Set default values to URL parameters* (STANDARD, 417 / 900).
