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
| 1 | Routing component and FrameworkBundle | STANDARD | 460 / 900 | 1 | en cours |
| 2 | Configuration (YAML and PHP attributes) | STANDARD | 551 / 900 | 1 | à faire |
| 3 | Restrict URL parameters | STANDARD | 392 / 900 | 1 | à faire |
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

## Prochaine étape

Page 2 — *Configuration (YAML and PHP attributes)* (STANDARD, 551 / 900).
