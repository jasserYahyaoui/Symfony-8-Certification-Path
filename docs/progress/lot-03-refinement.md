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

## Prochaine étape

Page 5 — **Code organization**.
