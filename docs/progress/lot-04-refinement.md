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
| 2 | Naming conventions | MINIMAL | 370 / 700 | 1 | à faire |
| 3 | The base AbstractController class | STANDARD | 490 / 900 | 1 | à faire |
| 4 | The request | MINIMAL | 359 / 700 | 1 | à faire |
| 5 | The response | STANDARD | 453 / 900 | 1 | à faire |
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

## Prochaine étape

Page 2 — *Naming conventions* (`CRS-9fz4erg0wmbq`, `OIT-ycc2c8tnv68h`,
MINIMAL, 370 / 700). À ne pas confondre avec la page *Naming conventions* du
lot 03 (`CRS-9g4qrgfs7nm4`, `OIT-c9pjp03cv4bq`), déjà raffinée : celle du lot 03
porte sur les conventions de nommage du framework, celle du lot 04 sur celles
des contrôleurs.
